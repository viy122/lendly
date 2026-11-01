<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Listing;
use App\Notifications\TalaNotification;

class ListingProgress
{
    public static function notify(Listing $listing, string $action): void
    {
        $owner = $listing->owner;
        if (! $owner || $owner->trashed()) {
            return;
        }
        $message = match ($action) {
            'removed' => 'The listing was removed. Existing transaction history is retained.',
            'reopened' => 'New rental requests are open. Existing reserved dates remain protected.',
            'paused' => 'New rental requests are paused.',
            default => 'Current status: '.$listing->status->label().'.'.($listing->rejection_reason ? ' '.$listing->rejection_reason : ''),
        };
        $owner->notify(new TalaNotification(NotificationType::ListingUpdate->value,
            'Listing '.($action === 'status' ? strtolower($listing->status->label()) : $action),
            '"'.$listing->name.'": '.$message, route('owner.listings.index')));
    }
}
