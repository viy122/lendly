<?php

namespace App\Policies;

use App\Enums\SecurityDepositStatus;
use App\Models\Rental;
use App\Models\User;

class RentalPolicy
{
    public function view(User $user, Rental $rental): bool
    {
        return $user->id === $rental->renter_id
            || $user->id === $rental->owner_id
            || $user->isAdmin();
    }

    public function pay(User $user, Rental $rental): bool
    {
        return $user->id === $rental->renter_id && $rental->isPaymentPending();
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
