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

    public function converse(User $user, RentalRequest $rentalRequest): bool
    {
        return $user->id === $rentalRequest->renter_id || $user->id === $rentalRequest->listing->owner_id;
    }
}
