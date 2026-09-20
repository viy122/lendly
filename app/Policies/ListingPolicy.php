<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;

class ListingPolicy
{
    public function view(?User $user, Listing $listing): bool
    {
        if ($listing->isPublished()) {
            return true;
        }

        return $user && ($user->isAdmin() || $listing->isOwnedBy($user));
    }

    public function update(User $user, Listing $listing): bool
    {
        return $listing->isOwnedBy($user);
    }

    public function delete(User $user, Listing $listing): bool
    {
        return $listing->isOwnedBy($user) || $user->isAdmin();
    }

    public function moderate(User $user, Listing $listing): bool
    {
        return $user->isAdmin();
    }
}
