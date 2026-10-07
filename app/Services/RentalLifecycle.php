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
use App\Models\Rental;
use App\Models\User;
use App\Notifications\TalaNotification;
use Illuminate\Database\Eloquent\Builder;
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
    public static function cancel(Rental $rental, ?string $reason = null): array
    {
        return self::cancelAuthorized($rental, $reason);
    }

    public static function cancelByAdmin(Rental $rental, User $admin, string $reason): array
    {
        return self::cancelAuthorized($rental, $reason, $admin);
    }

    private static function cancelAuthorized(Rental $rental, ?string $reason, ?User $admin = null): array
    {
        return DB::transaction(function () use ($rental, $reason, $admin) {
            $rental = Rental::lockForUpdate()->findOrFail($rental->id);
            $admin ? Gate::forUser($admin)->authorize('adminCancel', $rental) : Gate::authorize('cancel', $rental);

            $evaluation = CancellationPolicy::evaluate($rental);
            $reason = $reason !== null && trim($reason) !== '' ? trim($reason) : null;
            validator(['cancellation_reason' => $reason], ['cancellation_reason' => ['nullable', 'string', 'max:1000']])->validate();
            $cancellation = [
                'status' => RentalStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'cancellation_fee' => $evaluation['fee'],
            ];

            $rental->update($cancellation);

            $rental->rentalRequest?->update([
                ...$cancellation,
                'status' => RentalRequestStatus::Cancelled,
            ]);

            $rental->securityDeposit?->update(['status' => SecurityDepositStatus::Refunded]);

            if (! $admin) {
                self::notify(
                    $rental->owner,
                    NotificationType::RentalCancelled,
                    'Rental cancelled',
                    "The renter cancelled the rental for \"{$rental->listing->name}\".".($evaluation['fee'] > 0 ? " A ₱{$evaluation['fee']} cancellation fee applies." : '')." Item availability: {$rental->listing->availabilityStatus()->label()}.",
                    self::urlFor($rental->owner, $rental),
                );
            }

            return $evaluation;
        });
    }

    public static function confirmPickup(Rental $rental, User $confirmingUser): void
    {
        DB::transaction(function () use ($rental, $confirmingUser) {
            $rental = Rental::lockForUpdate()->findOrFail($rental->id);
            Gate::forUser($confirmingUser)->authorize('confirmPickup', $rental);

            $field = $confirmingUser->id === $rental->owner_id
                ? 'pickup_confirmed_by_owner_at'
                : 'pickup_confirmed_by_renter_at';

            $rental->update([$field => now()]);

            $otherParty = $confirmingUser->id === $rental->owner_id ? $rental->renter : $rental->owner;
            $method = $rental->fulfillment_method->label();
            self::notify($otherParty, NotificationType::PickupConfirmed, $method.' confirmed', "{$confirmingUser->name} confirmed ".strtolower($method)." hand-over for \"{$rental->listing->name}\".", self::urlFor($otherParty, $rental));

            if ($rental->fresh()->bothConfirmedPickup()) {
                $rental->update(['status' => RentalStatus::Active]);

                self::notify($rental->renter, NotificationType::PickupConfirmed, 'Rental is now active', "Your rental of \"{$rental->listing->name}\" has started.", self::urlFor($rental->renter, $rental));
                self::notify($rental->owner, NotificationType::PickupConfirmed, 'Rental is now active', "The rental of \"{$rental->listing->name}\" has started.", self::urlFor($rental->owner, $rental));
            }
        });
    }

    public static function confirmReturn(Rental $rental, User $confirmingUser): void
    {
        DB::transaction(function () use ($rental, $confirmingUser) {
            $rental = Rental::lockForUpdate()->findOrFail($rental->id);
            Gate::forUser($confirmingUser)->authorize('confirmReturn', $rental);
            $field = $confirmingUser->id === $rental->owner_id
                ? 'return_confirmed_by_owner_at'
                : 'return_confirmed_by_renter_at';

            $rental->fill([$field => now()]);
            if ($rental->bothConfirmedReturn()) {
                $rental->status = RentalStatus::Returned;
            }
            $rental->save();

            $otherParty = $confirmingUser->id === $rental->owner_id ? $rental->renter : $rental->owner;
            self::notify($otherParty, NotificationType::ReturnConfirmed, 'Return confirmed', "{$confirmingUser->name} confirmed the return of \"{$rental->listing->name}\".", self::urlFor($otherParty, $rental));
        });
    }

    public static function completeInspection(Rental $rental): void
    {
        self::completeInspectionAuthorized($rental);
    }

    public static function completeInspectionByAdmin(Rental $rental, User $admin): void
    {
        self::completeInspectionAuthorized($rental, $admin);
    }

    private static function completeInspectionAuthorized(Rental $rental, ?User $admin = null): void
    {
        DB::transaction(function () use ($rental, $admin) {
            $rental = Rental::lockForUpdate()->findOrFail($rental->id);
            $admin ? Gate::forUser($admin)->authorize('adminCompleteInspection', $rental) : Gate::authorize('completeInspection', $rental);
            abort_unless($rental->bothConfirmedReturn(), 403, 'Both parties must confirm the return.');

            $completedAt = now();
            $rental->update([
                'status' => RentalStatus::Completed,
                'completed_at' => $completedAt,
                'archived_at' => $completedAt,
            ]);

            $hasDamageClaim = $rental->damageReport()->exists();

            $rental->securityDeposit?->update([
                'status' => $hasDamageClaim ? SecurityDepositStatus::DamageClaim : SecurityDepositStatus::ReturnEligible,
            ]);

            if ($rental->owner_id !== $rental->renter_id) {
                foreach ([$rental->owner, $rental->renter] as $party) {
                    $reviewedRole = $party->id === $rental->owner_id ? 'renter' : 'owner';
                    self::notify($party, NotificationType::ReviewRequest, 'Rental completed — optional review', "The rental of \"{$rental->listing->name}\" is complete. You may rate and review the {$reviewedRole}. Your review will appear on their public profile.", self::urlFor($party, $rental).'#reviews');
                }
            }

            if (! $admin) {
                self::notify($rental->owner, NotificationType::ReturnConfirmed, 'Rental completed and archived', "The completed rental of \"{$rental->listing->name}\" has been saved in your rental history.", self::urlFor($rental->owner, $rental));

                if ($hasDamageClaim) {
                    self::notify($rental->renter, NotificationType::DamageClaimFiled, 'Damage claim filed', "The owner filed a damage claim for \"{$rental->listing->name}\". Please respond.", self::urlFor($rental->renter, $rental));
                }
            }
        });
    }

    public static function releaseDeposit(Rental $rental): void
    {
        self::releaseDepositAuthorized($rental);
    }

    public static function releaseDepositByAdmin(Rental $rental, User $admin): void
    {
        self::releaseDepositAuthorized($rental, $admin);
    }

    private static function releaseDepositAuthorized(Rental $rental, ?User $admin = null): void
    {
        DB::transaction(function () use ($rental, $admin) {
            $rental = Rental::lockForUpdate()->findOrFail($rental->id);
            $admin ? Gate::forUser($admin)->authorize('adminReleaseDeposit', $rental) : Gate::authorize('releaseDeposit', $rental);
            $rental->securityDeposit->update(['status' => SecurityDepositStatus::Released]);

            if (! $admin) {
                self::notify($rental->renter, NotificationType::DepositUpdate, 'Deposit released', "Your security deposit for \"{$rental->listing->name}\" has been fully released.", self::urlFor($rental->renter, $rental));
            }
        });
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
    public static function markOverdueRentals(?Builder $query = null): int
    {
        $newlyOverdue = 0;

        ($query ?? Rental::query())
            ->whereIn('status', [RentalStatus::Active, RentalStatus::Overdue])
            ->where('end_date', '<', now()->startOfDay())
            ->each(function (Rental $rental) use (&$newlyOverdue) {
                if (self::synchronizeRentalStatus($rental)) {
                    $newlyOverdue++;
                }
            });

        return $newlyOverdue;
    }

    /**
     * Recheck persisted status under a lock so scheduler and browser refreshes
     * cannot resurrect returned rentals or send duplicate overdue notices.
     */
    public static function synchronizeRentalStatus(Rental $rental): bool
    {
        return DB::transaction(function () use ($rental) {
            $rental = Rental::lockForUpdate()->findOrFail($rental->id);

            if (! in_array($rental->status, [RentalStatus::Active, RentalStatus::Overdue], true)
                || $rental->end_date->greaterThanOrEqualTo(now()->startOfDay())) {
                return false;
            }

            $newlyOverdue = ! $rental->isOverdue();
            $daysOverdue = (int) $rental->end_date->diffInDays(now()->startOfDay());
            $rental->fill([
                'status' => RentalStatus::Overdue,
                'days_overdue' => $daysOverdue,
                'late_fee' => round($rental->agreedDailyRate() * $daysOverdue, 2),
            ]);

            if ($rental->isDirty()) {
                $rental->save();
            }

            if ($newlyOverdue) {
                $message = "\"{$rental->listing->name}\" is now {$daysOverdue} day(s) overdue. Late fee: ₱{$rental->late_fee}.";
                self::notify($rental->renter, NotificationType::RentalOverdue, 'Rental overdue', $message, self::urlFor($rental->renter, $rental));
                self::notify($rental->owner, NotificationType::RentalOverdue, 'Rental overdue', $message, self::urlFor($rental->owner, $rental));
            }

            return $newlyOverdue;
        });
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
