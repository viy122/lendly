<?php

namespace App\Services;

use App\Models\Listing;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ListingAvailability
{
    /** Owner-only unbooked gaps; readiness is checked separately before reopening. */
    public static function freePeriodsToday(Listing $listing): array
    {
        if ($listing->trashed() || ! $listing->isPublished()) {
            return [];
        }
        $today = now('Asia/Manila')->toDateString();
        if (! $listing->includesAvailableDates($today, $today)) {
            return [];
        }
        $cursor = Carbon::parse($today.' '.$listing->available_time_from, 'Asia/Manila')->max(now('Asia/Manila'));
        $closing = Carbon::parse($today.' '.$listing->available_time_until, 'Asia/Manila');
        $bookings = $listing->reservingRequests()->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->get();
        $intervals = $bookings->map(fn ($booking) => [
            $booking->starts_at?->copy()->setTimezone('Asia/Manila') ?? $cursor->copy()->startOfDay(),
            $booking->ends_at?->copy()->setTimezone('Asia/Manila') ?? $closing->copy()->endOfDay(),
        ])->sortBy(fn ($interval) => $interval[0]->timestamp);
        $free = [];
        foreach ($intervals as [$start, $end]) {
            if ($start->gt($cursor) && $cursor->lt($closing)) {
                $free[] = $cursor->format('H:i').'–'.$start->copy()->min($closing)->format('H:i');
            }
            $cursor = $cursor->max($end);
        }
        if ($cursor->lt($closing)) {
            $free[] = $cursor->format('H:i').'–'.$closing->format('H:i');
        }

        return $free;
    }

    public static function set(Listing $listing, bool $available): void
    {
        DB::transaction(function () use ($listing, $available) {
            $listing = Listing::whereKey($listing->id)->lockForUpdate()->firstOrFail();
            if ($available && $listing->hasPhysicalBlocker()) {
                throw ValidationException::withMessages([
                    'availability' => 'Confirm the item has returned and complete its inspection before reopening requests.',
                ]);
            }
            $listing->update(['is_available' => $available] + ($available ? ['reopen_required' => false] : []));
        }, 3);
    }
}
