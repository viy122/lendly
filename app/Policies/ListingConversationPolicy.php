<?php

namespace App\Policies;

use App\Models\ListingConversation;
use App\Models\User;

class ListingConversationPolicy
{
    public function converse(User $user, ListingConversation $conversation): bool
    {
        if ($user->isAdmin() || $user->isSuspended()) {
            return false;
        }

        return match (session('active_interface')) {
            'owner' => $user->id === $conversation->owner_id,
            'renter' => $user->id === $conversation->renter_id,
            default => $user->id === $conversation->owner_id || $user->id === $conversation->renter_id,
        };
    }
}
