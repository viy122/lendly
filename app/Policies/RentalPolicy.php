<?php

namespace App\Policies;

use App\Enums\DamageReportStatus;
use App\Enums\DisputeStatus;
use App\Enums\ReviewType;
use App\Enums\SecurityDepositStatus;
use App\Models\Rental;
use App\Models\User;

class RentalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() && $user->hasVerifiedEmail() && ! $user->isSuspended() && ! $user->trashed();
    }

    public function manage(User $user, Rental $rental): bool
    {
        return $this->viewAny($user);
    }

    public function adminCancel(User $user, Rental $rental): bool
    {
        return $this->manage($user, $rental)
            && $rental->isCancellableByRenter()
            && $rental->pickup_confirmed_by_owner_at === null
            && $rental->pickup_confirmed_by_renter_at === null
            && (! $rental->securityDeposit || $rental->securityDeposit->status === SecurityDepositStatus::Held);
    }

    public function adminCompleteInspection(User $user, Rental $rental): bool
    {
        return $this->manage($user, $rental)
            && $rental->isReturned()
            && $rental->bothConfirmedReturn()
            && $rental->afterConditionRecord()->exists();
    }

    public function adminReleaseDeposit(User $user, Rental $rental): bool
    {
        return $this->manage($user, $rental)
            && $rental->isCompleted()
            && $rental->securityDeposit?->status === SecurityDepositStatus::ReturnEligible
            && ! $rental->disputes()->where('status', DisputeStatus::Open)->exists()
            && ! $rental->damageReport()->where('status', '!=', DamageReportStatus::Rejected)->exists();
    }

    public function view(User $user, Rental $rental): bool
    {
        return $user->id === $rental->renter_id
            || $user->id === $rental->owner_id
            || $user->isAdmin();
    }

    public function pay(User $user, Rental $rental): bool
    {
        return $user->id === $rental->renter_id && ! $user->trashed() && ! $user->isSuspended()
            && $rental->isPaymentPending() && $rental->paid_at === null
            && $rental->hasAcceptedAgreement() && ! $rental->payment()->exists()
            && ! $rental->securityDeposit()->exists()
            && ! $rental->paymentSubmissions()->where('status', 'pending')->exists();
    }

    public function adminVerifyPayment(User $user, Rental $rental): bool
    {
        return $this->manage($user, $rental) && $rental->isPaymentPending()
            && $rental->paid_at === null && $rental->hasAcceptedAgreement()
            && ! $rental->payment()->exists() && ! $rental->securityDeposit()->exists()
            && $rental->paymentSubmissions()->where('status', 'pending')->exists();
    }

    public function adminRejectPayment(User $user, Rental $rental): bool
    {
        return $this->manage($user, $rental)
            && $rental->paymentSubmissions()->where('status', 'pending')->exists();
    }

    public function review(User $user, Rental $rental, ReviewType $type): bool
    {
        $reviewerId = $type === ReviewType::OwnerToRenter ? $rental->owner_id : $rental->renter_id;

        return ! $user->trashed()
            && $rental->owner_id !== $rental->renter_id
            && $user->id === $reviewerId
            && $rental->isCompleted()
            && ! $rental->reviews()->where('type', $type)->exists();
    }

    public function coordinateExchange(User $user, Rental $rental): bool
    {
        return ($user->id === $rental->renter_id || $user->id === $rental->owner_id)
            && $rental->canArrangeExchange();
    }

    public function cancel(User $user, Rental $rental): bool
    {
        return $user->id === $rental->renter_id && $rental->isCancellableByRenter();
    }

    public function confirmPickup(User $user, Rental $rental): bool
    {
        if (! $rental->awaitingPickupConfirmation()) {
            return false;
        }

        if ($user->id === $rental->owner_id) {
            return $rental->pickup_confirmed_by_owner_at === null;
        }

        if ($user->id === $rental->renter_id) {
            return $rental->pickup_confirmed_by_renter_at === null;
        }

        return false;
    }

    public function confirmReturn(User $user, Rental $rental): bool
    {
        if (! $rental->awaitingReturnConfirmation()) {
            return false;
        }

        if ($user->id === $rental->owner_id) {
            return $rental->return_confirmed_by_owner_at === null;
        }

        if ($user->id === $rental->renter_id) {
            return $rental->return_confirmed_by_renter_at === null;
        }

        return false;
    }

    public function completeInspection(User $user, Rental $rental): bool
    {
        return $user->id === $rental->owner_id
            && $rental->isReturned()
            && $rental->bothConfirmedReturn()
            && $rental->afterConditionRecord()->exists();
    }

    public function recordBeforeCondition(User $user, Rental $rental): bool
    {
        return $user->id === $rental->owner_id
            && ! $rental->isPaymentPending()
            && ! $rental->beforeConditionRecord()->exists();
    }

    public function recordAfterCondition(User $user, Rental $rental): bool
    {
        return $user->id === $rental->owner_id
            && $rental->isReturned()
            && ! $rental->afterConditionRecord()->exists();
    }

    public function releaseDeposit(User $user, Rental $rental): bool
    {
        return $user->id === $rental->owner_id
            && $rental->isCompleted()
            && $rental->securityDeposit?->status === SecurityDepositStatus::ReturnEligible;
    }
}
