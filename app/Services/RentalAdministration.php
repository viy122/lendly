<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\RentalAdminActionType;
use App\Models\Rental;
use App\Models\RentalAdminAction;
use App\Models\User;
use App\Notifications\TalaNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RentalAdministration
{
    public static function perform(Rental $rental, User $admin, RentalAdminActionType $action, string $notes, ?int $submissionId = null, bool $fundsReceived = false): RentalAdminAction
    {
        return DB::transaction(function () use ($rental, $admin, $action, $notes, $submissionId, $fundsReceived) {
            $rental = Rental::lockForUpdate()->findOrFail($rental->id);
            Gate::forUser($admin)->authorize($action->ability(), $rental);
            $notes = trim($notes);
            validator(['notes' => $notes], ['notes' => ['required', 'string', 'max:1000']])->validate();
            $before = self::state($rental);

            match ($action) {
                RentalAdminActionType::NoteAdded => null,
                RentalAdminActionType::BookingCancelled => RentalLifecycle::cancelByAdmin($rental, $admin, $notes),
                RentalAdminActionType::InspectionCompleted => RentalLifecycle::completeInspectionByAdmin($rental, $admin),
                RentalAdminActionType::DepositReleased => RentalLifecycle::releaseDepositByAdmin($rental, $admin),
                RentalAdminActionType::PaymentVerified, RentalAdminActionType::PaymentRejected => RentalPayments::review($rental, $admin, $submissionId, $action === RentalAdminActionType::PaymentVerified, $notes, $fundsReceived),
            };

            $rental->refresh();
            $record = $rental->adminActions()->create([
                'performed_by' => $admin->id,
                'action' => $action,
                'notes' => $notes,
                'before_state' => $before,
                'after_state' => self::state($rental),
            ]);

            if ($action !== RentalAdminActionType::NoteAdded) {
                $message = 'An administrator updated the rental of "'.$rental->listing->name.'". '.$notes;
                if ($action === RentalAdminActionType::BookingCancelled) {
                    $message .= ' Cancellation fee: ₱'.number_format($rental->cancellation_fee, 2).'.';
                } elseif ($action === RentalAdminActionType::InspectionCompleted) {
                    $message .= ' The completed transaction is archived. You may now leave a review.';
                    if ($rental->damageReport()->exists()) {
                        $message .= ' The damage claim remains available for review on the rental page.';
                    }
                }
                foreach (['owner' => $rental->owner, 'renter' => $rental->renter] as $interface => $party) {
                    $party->notify(new TalaNotification(
                        match ($action) {
                            RentalAdminActionType::PaymentVerified => NotificationType::PaymentConfirmed->value,
                            RentalAdminActionType::PaymentRejected => NotificationType::PaymentRejected->value,
                            default => NotificationType::AdminTransactionUpdate->value,
                        },
                        $action->label(),
                        $message,
                        route($interface.'.rentals.show', $rental),
                    ));
                }
            }

            return $record;
        });
    }

    private static function state(Rental $rental): array
    {
        $rental->load(['rentalRequest', 'securityDeposit', 'payment']);
        $submission = $rental->paymentSubmissions()->first();

        return [
            'status' => $rental->status->value,
            'request_status' => $rental->rentalRequest?->status->value,
            'deposit_status' => $rental->securityDeposit?->status->value,
            'cancellation_fee' => $rental->cancellation_fee,
            'cancellation_reason' => $rental->cancellation_reason,
            'cancelled_at' => $rental->cancelled_at?->toIso8601String(),
            'completed_at' => $rental->completed_at?->toIso8601String(),
            'archived_at' => $rental->archived_at?->toIso8601String(),
            'payment_reference' => $rental->payment?->transaction_reference,
            'paid_at' => $rental->paid_at?->toIso8601String(),
            'submission_id' => $submission?->id,
            'submission_status' => $submission?->status,
        ];
    }
}
