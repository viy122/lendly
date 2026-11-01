<?php

namespace App\Services;

use App\Models\Listing;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RentalPeriod
{
    public static function attributes(Listing $listing, array $details, bool $newRequest = true): array
    {
        $data = Validator::make($details + ['rental_unit' => 'day'], [
            'rental_unit' => ['required', 'in:day,hour'],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.now('Asia/Manila')->toDateString()],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'start_time' => ['required_if:rental_unit,hour', 'nullable', 'date_format:H:i'],
            'end_time' => ['required_if:rental_unit,hour', 'nullable', 'date_format:H:i'],
        ])->validate();
        if (! $listing->includesAvailableDates($data['start_date'], $data['end_date'])) {
            throw ValidationException::withMessages(['start_date' => 'Choose dates within the owner’s availability window.', 'end_date' => 'The entire rental period must fit within the available dates.']);
        }
        $days = (int) CarbonImmutable::parse($data['start_date'])->diffInDays(CarbonImmutable::parse($data['end_date'])) + 1;
        if ($data['rental_unit'] === 'day' && $days > $listing->max_rental_duration_days) {
            throw ValidationException::withMessages(['end_date' => "This item can be rented for at most {$listing->max_rental_duration_days} days."]);
        }
        $attributes = ['start_date' => $data['start_date'], 'end_date' => $data['end_date'], 'rental_days' => $days,
            'rental_unit' => $data['rental_unit'], 'rental_hours' => null, 'starts_at' => null, 'ends_at' => null];
        if ($data['rental_unit'] === 'day') {
            if (! $listing->allows_daily_rentals) {
                throw ValidationException::withMessages(['rental_unit' => 'This owner offers hourly rentals only.']);
            }

            // Old saved daily agreements retain their date-only terms.
            if (! $newRequest && empty($data['start_time']) && empty($data['end_time'])) {
                return $attributes;
            }
        }
        if ($data['rental_unit'] === 'hour' && (! $listing->price_per_hour || $listing->price_per_hour <= 0)) {
            throw ValidationException::withMessages(['rental_unit' => 'Hourly rental is not available for this item.']);
        }
        $startTime = $data['start_time'] ?? $listing->available_time_from;
        $endTime = $data['end_time'] ?? $listing->available_time_until;
        $start = CarbonImmutable::parse($data['start_date'].' '.$startTime, 'Asia/Manila');
        $end = CarbonImmutable::parse($data['end_date'].' '.$endTime, 'Asia/Manila');
        $minutes = $start->diffInMinutes($end, false);
        if ($start->lte(now())) {
            throw ValidationException::withMessages(['start_time' => 'Choose a start time in the future (Philippine time).']);
        }
        if ($data['rental_unit'] === 'hour' && $minutes > $listing->max_rental_duration_days * 24 * 60) {
            throw ValidationException::withMessages(['end_date' => "This item can be rented for at most {$listing->max_rental_duration_days} days."]);
        }
        if ($minutes <= 0 || ($data['rental_unit'] === 'hour' && ($minutes < 60 || fmod($minutes, 60) !== 0.0))) {
            throw ValidationException::withMessages(['end_time' => 'Return time must follow the start time. Hourly rentals must use whole hours.']);
        }
        if ($startTime < $listing->available_time_from || $startTime > $listing->available_time_until
            || $endTime < $listing->available_time_from || $endTime > $listing->available_time_until
            || ($data['rental_unit'] === 'hour' && ! $start->isSameDay($end) && ($listing->available_time_from !== '00:00' || $listing->available_time_until !== '23:59'))) {
            throw ValidationException::withMessages(['start_time' => 'The rental must fit within the owner’s available hours (Philippine time).']);
        }

        return array_replace($attributes, ['rental_days' => $data['rental_unit'] === 'hour' ? (int) ceil($minutes / 1440) : $days, 'rental_hours' => $data['rental_unit'] === 'hour' ? (int) ($minutes / 60) : null, 'starts_at' => $start->utc()->toDateTimeString(), 'ends_at' => $end->utc()->toDateTimeString()]);
    }

    public static function details($booking): array
    {
        $details = ['start_date' => $booking->start_date->toDateString(), 'end_date' => $booking->end_date->toDateString(),
            'fulfillment_method' => $booking->fulfillment_method instanceof \BackedEnum ? $booking->fulfillment_method->value : $booking->fulfillment_method];
        if ($booking->starts_at && $booking->ends_at) {
            $details += ['rental_unit' => $booking->rental_unit ?? 'day', 'start_time' => $booking->starts_at->copy()->setTimezone('Asia/Manila')->format('H:i'),
                'end_time' => $booking->ends_at->copy()->setTimezone('Asia/Manila')->format('H:i')];
        }

        return $details;
    }

    public static function applyOverlap($query, $startDate, $endDate, $startsAt = null, $endsAt = null): void
    {
        $start = $startsAt ?? CarbonImmutable::parse($startDate, 'Asia/Manila')->startOfDay()->utc()->toDateTimeString();
        $end = $endsAt ?? CarbonImmutable::parse($endDate, 'Asia/Manila')->addDay()->startOfDay()->utc()->toDateTimeString();
        $lastIncludedDate = $endsAt ? CarbonImmutable::parse($endsAt)->setTimezone('Asia/Manila')->subMicrosecond()->toDateString() : $endDate;
        $query->where(function ($query) use ($startDate, $lastIncludedDate, $start, $end) {
            $query->where(fn ($daily) => $daily->whereNull('starts_at')->whereDate('start_date', '<=', $lastIncludedDate)->whereDate('end_date', '>=', $startDate))
                ->orWhere(fn ($hourly) => $hourly->whereNotNull('starts_at')->where('starts_at', '<', $end)->where('ends_at', '>', $start));
        });
    }
}
