<?php

namespace App\Services;

use App\Enums\DamageReportStatus;
use App\Enums\DisputeReason;
use App\Enums\DisputeResolution;
use App\Enums\DisputeStatus;
use App\Enums\NotificationType;
use App\Enums\RentalRequestStatus;
use App\Enums\RentalStatus;
use App\Enums\SecurityDepositStatus;
use App\Models\DamageReport;
use App\Models\Dispute;
use App\Models\Listing;
use App\Models\Rental;
use App\Models\User;
use App\Notifications\TalaNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RentalLifecycle
{
    /**
     * Renter-initiated cancellation, prior to hand-over (see
     * Rental::isCancellableByRenter()). Also flips the underlying
     * RentalRequest away from Approved so Listing::hasApprovedOverlap()
     * frees the dates back up for other renters, and refunds the held
     * security deposit since the rental never happened.
     */
    public static function cancel(Rental $rental, string $reason): array
    {
        return DB::transaction(function () use ($rental, $reason) {
            Listing::withTrashed()->whereKey($rental->listing_id)->lockForUpdate()->firstOrFail();
            $rental = Rental::whereKey($rental->id)->lockForUpdate()->firstOrFail();
            abort_unless($rental->isCancellableByRenter(), 403, 'This booking can no longer be cancelled after handover.');
            $evaluation = CancellationPolicy::evaluate($rental);

            $rental->update([
                'status' => RentalStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'cancellation_fee' => $evaluation['fee'],
            ]);

            $rental->rentalRequest?->update(['status' => RentalRequestStatus::Cancelled]);

            $rental->securityDeposit?->update(['status' => SecurityDepositStatus::Refunded]);

            foreach ([$rental->owner, $rental->renter] as $participant) {
                self::notify(
                    $participant,
                    NotificationType::RentalCancelled,
                    'Rental cancelled',
                    "The renter cancelled the rental for \"{$rental->listing->name}\".".($evaluation['fee'] > 0 ? " A ₱{$evaluation['fee']} cancellation fee applies." : ''),
                    self::urlFor($participant, $rental),
                );
            }

            return $evaluation;
        }, 3);
    }

    public static function confirmPickup(Rental $rental, User $confirmingUser): void
    {
        DB::transaction(function () use ($rental, $confirmingUser) {
            $rental = Rental::whereKey($rental->id)->lockForUpdate()->firstOrFail();
            abort_unless($rental->awaitingPickupConfirmation(), 403);
            abort_unless(in_array($confirmingUser->id, [$rental->owner_id, $rental->renter_id], true), 403);
            $field = $confirmingUser->id === $rental->owner_id
                ? 'pickup_confirmed_by_owner_at'
                : 'pickup_confirmed_by_renter_at';
            abort_unless($rental->$field === null, 403);

            $rental->update([$field => now()]);

            $otherParty = $confirmingUser->id === $rental->owner_id ? $rental->renter : $rental->owner;
            self::notify($otherParty, NotificationType::PickupConfirmed, 'Pickup confirmed', "{$confirmingUser->name} confirmed pickup for \"{$rental->listing->name}\".", self::urlFor($otherParty, $rental));

            if ($rental->fresh()->bothConfirmedPickup()) {
                $rental->update(['status' => RentalStatus::Active]);

                self::notify($rental->renter, NotificationType::PickupConfirmed, 'Rental is now active', "Your rental of \"{$rental->listing->name}\" has started.", self::urlFor($rental->renter, $rental));
                self::notify($rental->owner, NotificationType::PickupConfirmed, 'Rental is now active', "The rental of \"{$rental->listing->name}\" has started.", self::urlFor($rental->owner, $rental));
            }
        }, 3);
    }

    public static function confirmReturn(Rental $rental, User $confirmingUser): void
    {
        DB::transaction(function () use ($rental, $confirmingUser) {
            $rental = Rental::whereKey($rental->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($confirmingUser)->authorize('confirmReturn', $rental);
            $isOwner = $confirmingUser->id === $rental->owner_id;
            $field = $isOwner ? 'return_confirmed_by_owner_at' : 'return_confirmed_by_renter_at';

            $confirmation = [$field => now()];
            if ($isOwner) {
                $confirmation += [
                    'status' => RentalStatus::Returned,
                    'days_overdue' => $rental->currentOverdueDays(),
                    'late_fee' => $rental->currentLateFee(),
                ];
            }
            $rental->update($confirmation);

            $otherParty = $isOwner ? $rental->renter : $rental->owner;
            self::notify($otherParty, NotificationType::ReturnConfirmed, 'Return confirmed', "{$confirmingUser->name} confirmed the return of \"{$rental->listing->name}\".", self::urlFor($otherParty, $rental));

            if ($isOwner && $rental->afterConditionRecord()->exists()) {
                self::completeInspection($rental);
            }
        }, 3);
    }

    public static function completeInspection(Rental $rental): void
    {
        DB::transaction(function () use ($rental) {
            $rental = Rental::whereKey($rental->id)->lockForUpdate()->firstOrFail();
            abort_unless($rental->isReturned(), 403);
            $rental->update([
                'status' => RentalStatus::Completed,
                'completed_at' => now(),
            ]);

            $damageReport = $rental->damageReport()->first();
            $hasDamageClaim = $damageReport && in_array($damageReport->status, [DamageReportStatus::Pending, DamageReportStatus::Disputed], true);
            $deposit = $rental->securityDeposit()->lockForUpdate()->first();
            if ($deposit && ! in_array($deposit->status, [SecurityDepositStatus::Released, SecurityDepositStatus::Refunded, SecurityDepositStatus::Deducted], true)) {
                $deposit->update(['status' => $hasDamageClaim ? SecurityDepositStatus::DamageClaim : SecurityDepositStatus::ReturnEligible]);
            }

            self::notify($rental->renter, NotificationType::ReviewRequest, 'Rental completed', "Your rental of \"{$rental->listing->name}\" is complete. You can leave a review.", self::urlFor($rental->renter, $rental));
            self::notify($rental->owner, NotificationType::ReviewRequest, 'Rental completed', "The rental of \"{$rental->listing->name}\" is complete. You can rate the renter.", self::urlFor($rental->owner, $rental));

            if ($hasDamageClaim) {
                self::notify($rental->renter, NotificationType::DamageClaimFiled, 'Damage claim filed', "The owner filed a damage claim for \"{$rental->listing->name}\". Please respond.", self::urlFor($rental->renter, $rental));
            }
        }, 3);
    }

    public static function releaseDeposit(Rental $rental): void
    {
        $rental->securityDeposit?->update(['status' => SecurityDepositStatus::Released]);

        self::notify($rental->renter, NotificationType::DepositUpdate, 'Deposit released', "Your security deposit for \"{$rental->listing->name}\" has been fully released.", self::urlFor($rental->renter, $rental));
    }

    public static function acceptDamageClaim(DamageReport $damageReport): void
    {
        $damageReport->update(['status' => DamageReportStatus::Accepted]);

        $rental = $damageReport->rental;
        $deposit = $rental->securityDeposit;

        $deposit?->update([
            'status' => SecurityDepositStatus::Deducted,
            'deducted_amount' => $damageReport->proposed_deduction,
        ]);

        self::notify($rental->owner, NotificationType::DepositUpdate, 'Damage claim accepted', "The renter accepted your damage claim for \"{$rental->listing->name}\".", self::urlFor($rental->owner, $rental));
    }

    public static function disputeDamageClaim(DamageReport $damageReport, ?string $notes): void
    {
        $damageReport->update([
            'status' => DamageReportStatus::Disputed,
            'renter_response_notes' => $notes,
        ]);

        Dispute::create([
            'rental_id' => $damageReport->rental_id,
            'damage_report_id' => $damageReport->id,
            'raised_by' => $damageReport->rental->renter_id,
            'reason' => DisputeReason::ItemDamaged,
            'description' => $notes ?? 'Renter disputed the damage claim.',
        ]);

        $rental = $damageReport->rental;
        self::notify($rental->owner, NotificationType::DisputeUpdate, 'Damage claim disputed', "The renter disputed your damage claim for \"{$rental->listing->name}\". An admin will review it.", self::urlFor($rental->owner, $rental));
    }

    /**
     * Admin resolution of a dispute. For a damage-claim dispute this also
     * settles the underlying claim and deposit; for a general (non-damage)
     * dispute it just records the decision, since there's no automatic
     * financial remedy for reasons like "item not as described".
     */
    public static function resolveDispute(Dispute $dispute, DisputeResolution $resolution, ?string $notes, User $admin, ?float $partialAmount = null): void
    {
        $dispute->update([
            'status' => DisputeStatus::Resolved,
            'resolution' => $resolution,
            'resolution_notes' => $notes,
            'resolved_by' => $admin->id,
            'resolved_at' => now(),
        ]);

        if ($dispute->damage_report_id) {
            $damageReport = $dispute->damageReport;
            $deposit = $dispute->rental->securityDeposit;

            switch ($resolution) {
                case DisputeResolution::ApprovedClaim:
                    $damageReport->update(['status' => DamageReportStatus::Accepted]);
                    $deposit?->update([
                        'status' => SecurityDepositStatus::Deducted,
                        'deducted_amount' => $damageReport->proposed_deduction,
                    ]);
                    break;

                case DisputeResolution::RejectedClaim:
                case DisputeResolution::FullRefund:
                    $damageReport->update(['status' => DamageReportStatus::Rejected]);
                    $deposit?->update(['status' => SecurityDepositStatus::ReturnEligible, 'deducted_amount' => 0]);
                    break;

                case DisputeResolution::PartialCompensation:
                    $damageReport->update(['status' => DamageReportStatus::Accepted]);
                    $deposit?->update([
                        'status' => SecurityDepositStatus::Deducted,
                        'deducted_amount' => min($partialAmount ?? 0, (float) $deposit->amount),
                    ]);
                    break;

                case DisputeResolution::AccountWarning:
                    break;
            }
        }

        $rental = $dispute->rental;
        $message = "Your dispute for \"{$rental->listing->name}\" was resolved: {$resolution->label()}.";
        self::notify($rental->renter, NotificationType::DisputeUpdate, 'Dispute resolved', $message, self::urlFor($rental->renter, $rental));
        self::notify($rental->owner, NotificationType::DisputeUpdate, 'Dispute resolved', $message, self::urlFor($rental->owner, $rental));
    }

    /**
     * Flags active rentals whose end date has passed as overdue (and keeps
     * recalculating days_overdue/late_fee for ones already flagged, since
     * both grow the longer the item isn't returned). Returns how many
     * rentals were newly flagged as overdue in this run.
     */
    public static function markOverdueRentals(): int
    {
        $newlyOverdue = 0;

        Rental::query()
            ->whereIn('status', [RentalStatus::Active, RentalStatus::Overdue])
            ->where('end_date', '<', now()->startOfDay())
            ->each(function (Rental $rental) use (&$newlyOverdue) {
                $newlyOverdue += (int) DB::transaction(function () use ($rental) {
                    $rental = Rental::whereKey($rental->id)->lockForUpdate()->first();
                    if (! $rental || ! $rental->awaitingReturnConfirmation() || ! $rental->end_date->lt(today())) {
                        return false;
                    }
                    $wasAlreadyOverdue = $rental->status === RentalStatus::Overdue;
                    $daysOverdue = (int) $rental->end_date->diffInDays(now()->startOfDay());

                    $rental->update([
                        'status' => RentalStatus::Overdue,
                        'days_overdue' => $daysOverdue,
                        'late_fee' => round($rental->agreedDailyRate() * $daysOverdue, 2),
                    ]);

                    if (! $wasAlreadyOverdue) {
                        $message = "\"{$rental->listing->name}\" is now {$daysOverdue} day(s) overdue. Late fee: ₱{$rental->late_fee}.";
                        self::notify($rental->renter, NotificationType::RentalOverdue, 'Rental overdue', $message, self::urlFor($rental->renter, $rental));
                        self::notify($rental->owner, NotificationType::RentalOverdue, 'Rental overdue', $message, self::urlFor($rental->owner, $rental));
                    }

                    return ! $wasAlreadyOverdue;
                }, 3);
            });

        return $newlyOverdue;
    }

    /**
     * Notifies renters whose active rental's return date is tomorrow —
     * the "Return reminder" from the spec's notification list. Meant to be
     * run daily alongside markOverdueRentals().
     */
    public static function sendReturnReminders(): int
    {
        $count = 0;

        Rental::query()
            ->where('status', RentalStatus::Active)
            ->whereDate('end_date', now()->addDay()->toDateString())
            ->each(function (Rental $rental) use (&$count) {
                self::notify(
                    $rental->renter,
                    NotificationType::ReturnReminder,
                    'Return due tomorrow',
                    "\"{$rental->listing->name}\" is due back tomorrow ({$rental->end_date->format('M d, Y')}).",
                    self::urlFor($rental->renter, $rental),
                );

                $count++;
            });

        return $count;
    }

    private static function notify(User $user, NotificationType $type, string $title, string $message, string $url): void
    {
        $user->notify(new TalaNotification($type->value, $title, $message, $url));
    }

    private static function urlFor(User $user, Rental $rental): string
    {
        return $user->id === $rental->owner_id
            ? route('owner.rentals.show', $rental)
            : route('renter.rentals.show', $rental);
    }
}
