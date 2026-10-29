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
            'start_date' => ['required', 'date_format:Y-m-d', $newRequest ? 'after_or_equal:tomorrow' : 'after_or_equal:today'],
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

            return $attributes;
        }
        if (! $listing->price_per_hour || $listing->price_per_hour <= 0) {
            throw ValidationException::withMessages(['rental_unit' => 'Hourly rental is not available for this item.']);
        }
        $start = CarbonImmutable::parse($data['start_date'].' '.$data['start_time'], 'Asia/Manila');
        $end = CarbonImmutable::parse($data['end_date'].' '.$data['end_time'], 'Asia/Manila');
        $minutes = $start->diffInMinutes($end, false);
        if ($minutes > $listing->max_rental_duration_days * 24 * 60) {
            throw ValidationException::withMessages(['end_date' => "This item can be rented for at most {$listing->max_rental_duration_days} days."]);
        }
        if ($minutes <= 0 || fmod($minutes, 60) !== 0.0 || $minutes / 60 < $listing->min_rental_duration_hours || $minutes / 60 > $listing->max_rental_duration_hours) {
            throw ValidationException::withMessages(['end_time' => "Choose whole hours between {$listing->min_rental_duration_hours} and {$listing->max_rental_duration_hours} hours."]);
        }
        if ($data['start_time'] < $listing->available_time_from || $data['end_time'] > $listing->available_time_until
            || (! $start->isSameDay($end) && ($listing->available_time_from !== '00:00' || $listing->available_time_until !== '23:59'))) {
            throw ValidationException::withMessages(['start_time' => 'The rental must fit within the owner’s available hours (Philippine time).']);
        }

        return array_replace($attributes, ['rental_days' => (int) ceil($minutes / 1440), 'rental_hours' => (int) ($minutes / 60), 'starts_at' => $start->utc()->toDateTimeString(), 'ends_at' => $end->utc()->toDateTimeString()]);
    }

    public static function details($booking): array
    {
        $details = ['start_date' => $booking->start_date->toDateString(), 'end_date' => $booking->end_date->toDateString(),
            'fulfillment_method' => $booking->fulfillment_method instanceof \BackedEnum ? $booking->fulfillment_method->value : $booking->fulfillment_method];
        if ($booking->rental_unit === 'hour') {
            $details += ['rental_unit' => 'hour', 'start_time' => $booking->starts_at->copy()->setTimezone('Asia/Manila')->format('H:i'),
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
