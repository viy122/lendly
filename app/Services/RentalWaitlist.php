<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\RentalRequest;
use App\Models\RentalWaitlistEntry;
use App\Models\User;
use App\Notifications\TalaNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RentalWaitlist
{
    public static function canJoin(User $renter, int $listingId, array $details): bool
    {
        try {
            $quote = RentalRequestSubmission::preview($renter, $listingId, $details, allowOverlap: true);
            $period = $quote['attributes'];

            return $quote['listing']->hasApprovedOverlap($period['start_date'], $period['end_date'], startsAt: $period['starts_at'], endsAt: $period['ends_at']);
        } catch (ValidationException|HttpException) {
            return false;
        }
    }

    public static function join(User $renter, int $listingId, array $details): RentalWaitlistEntry
    {
        return DB::transaction(function () use ($renter, $listingId, $details) {
            $quote = RentalRequestSubmission::preview($renter, $listingId, $details, allowOverlap: true);
            $period = $quote['attributes'];
            if (! $quote['listing']->hasApprovedOverlap($period['start_date'], $period['end_date'], startsAt: $period['starts_at'], endsAt: $period['ends_at'])) {
                throw ValidationException::withMessages(['waitlist' => 'These dates are available. Review the quote and send a rental request instead.']);
            }
            $data = RentalPeriod::details(new RentalRequest($period)) + ['rental_unit' => 'day'];
            $entry = RentalWaitlistEntry::firstOrNew(['user_id' => $renter->id, 'listing_id' => $listingId]);
            if (! $entry->exists && RentalWaitlistEntry::where('user_id', $renter->id)->count() >= 50) {
                throw ValidationException::withMessages(['waitlist' => 'You can waitlist up to 50 items. Leave an existing waitlist first.']);
            }
            if (! $entry->exists || $entry->details !== $data || $entry->notified_at !== null) {
                $entry->fill(['details' => $data, 'notified_at' => null]);
                $entry->created_at = now();
                $entry->save();
            }

            return $entry;
        }, 3);
    }

    public static function notifyAvailable(): int
    {
        $count = 0;
        RentalWaitlistEntry::whereNull('notified_at')->orderBy('created_at')->orderBy('id')->cursor()->each(function ($candidate) use (&$count) {
            try {
                $count += DB::transaction(function () use ($candidate) {
                    $renter = User::find($candidate->user_id);
                    if (! $renter) {
                        return 0;
                    }
                    RentalRequestSubmission::preview($renter, $candidate->listing_id, $candidate->details);
                    $entry = RentalWaitlistEntry::whereKey($candidate->id)->lockForUpdate()->first();
                    if (! $entry || $entry->notified_at !== null || $entry->details !== $candidate->details) {
                        return 0;
                    }
                    $renter->notify(new TalaNotification(NotificationType::RequestApproved->value,
                        'Your waitlisted dates are available', 'Review the current price and send a request for "'.$candidate->listing->name.'". This alert does not reserve the item; owner approval is required.',
                        route('renter.rental-requests.create', ['listing' => $entry->listing_id, 'waitlist' => $entry->id])));
                    $entry->update(['notified_at' => now()]);

                    return 1;
                }, 3);
            } catch (ValidationException|HttpException) {
                // Dates and eligibility are checked again on the next scheduler run.
            }
        });

        return $count;
    }
}
