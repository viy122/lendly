<?php

namespace App\Policies;

use App\Models\RentalRequest;
use App\Models\User;

class RentalRequestPolicy
{
    public function view(User $user, RentalRequest $rentalRequest): bool
    {
        return $user->id === $rentalRequest->renter_id
            || $user->id === $rentalRequest->listing->owner_id
            || $user->isAdmin();
    }

    public function cancel(User $user, RentalRequest $rentalRequest): bool
    {
        return $user->id === $rentalRequest->renter_id && $rentalRequest->isCancellableByRenter();
    }

    public function moderate(User $user, RentalRequest $rentalRequest): bool
    {
        return $user->id === $rentalRequest->listing->owner_id;
    }

    public function acceptAgreement(User $user, RentalRequest $rentalRequest): bool
    {
        return $user->id === $rentalRequest->renter_id
            || $user->id === $rentalRequest->listing->owner_id;
    }

    public function converse(User $user, RentalRequest $rentalRequest): bool
    {
        if (session('active_interface') === 'owner') {
            return $user->id === $rentalRequest->listing->owner_id;
        }

        if (session('active_interface') === 'renter') {
            return $user->id === $rentalRequest->renter_id;
        }

        return $user->id === $rentalRequest->renter_id || $user->id === $rentalRequest->listing->owner_id;
    }
}
