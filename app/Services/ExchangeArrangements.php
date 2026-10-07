<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Rental;
use App\Models\RentalExchangeSchedule;
use App\Models\User;
use App\Notifications\TalaNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ExchangeArrangements
{
    public const TIMEZONE = 'Asia/Manila';

    public static function propose(Rental $rental, User $user, array $data, ?int $expectedScheduleId): RentalExchangeSchedule
    {
        return DB::transaction(function () use ($rental, $user, $data, $expectedScheduleId) {
            $rental = Rental::lockForUpdate()->findOrFail($rental->id);
            Gate::forUser($user)->authorize('coordinateExchange', $rental);
            self::checkVersion($rental, $expectedScheduleId);

            if (isset($data['address']) && is_string($data['address'])) {
                $data['address'] = trim($data['address']);
            }
            $data = Validator::make($data, [
                'exchange_time' => ['required', 'date_format:Y-m-d\TH:i'],
                'address' => ['required', 'string', 'max:500'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ])->validate();
            $time = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $data['exchange_time'], self::TIMEZONE);

            if ($time->isPast() || $time->equalTo(now())) {
                throw ValidationException::withMessages(['exchange_time' => 'Choose a future pickup or delivery time.']);
            }
            if ($time->toDateString() < $rental->start_date->toDateString() || $time->toDateString() > $rental->end_date->toDateString()) {
                throw ValidationException::withMessages(['exchange_time' => 'Choose a pickup or delivery time within the rental period.']);
            }

            // Each revision is a new proposal. Earlier confirmations apply only
            // to their original time and address, never to a revised schedule.
            $schedule = $rental->exchangeSchedules()->create([
                'proposed_by' => $user->id,
                'fulfillment_method' => $rental->fulfillment_method,
                'scheduled_at' => $time->utc(),
                'address' => $data['address'],
                'notes' => $data['notes'] ?? null,
            ]);
            $other = $user->id === $rental->owner_id ? $rental->renter : $rental->owner;
            $other->notify(new TalaNotification(
                NotificationType::ExchangeScheduleProposed->value,
                $schedule->fulfillment_method->label().' schedule proposed',
                "{$user->name} proposed {$schedule->fulfillment_method->label()} for \"{$rental->listing->name}\" on ".self::displayTime($schedule)." at {$schedule->address}. Both parties must confirm this schedule.",
                self::urlFor($rental, $other),
            ));

            return $schedule;
        });
    }

    public static function confirm(Rental $rental, User $user, int $scheduleId): RentalExchangeSchedule
    {
        return DB::transaction(function () use ($rental, $user, $scheduleId) {
            $rental = Rental::lockForUpdate()->findOrFail($rental->id);
            Gate::forUser($user)->authorize('coordinateExchange', $rental);
            self::checkVersion($rental, $scheduleId);
            $schedule = $rental->exchangeSchedules()->lockForUpdate()->findOrFail($scheduleId);

            if ($schedule->confirmed_at) {
                return $schedule;
            }
            if ($schedule->scheduled_at->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages(['schedule' => 'This proposed time has passed. Propose a new future pickup or delivery time.']);
            }

            $field = $user->id === $rental->owner_id ? 'owner_confirmed_at' : 'renter_confirmed_at';
            if ($schedule->$field === null) {
                $schedule->update([$field => now()]);
            }
            if ($schedule->owner_confirmed_at && $schedule->renter_confirmed_at) {
                $schedule->update(['confirmed_at' => now()]);
                foreach ([$rental->owner, $rental->renter] as $party) {
                    $party->notify(new TalaNotification(
                        NotificationType::ExchangeScheduleConfirmed->value,
                        $schedule->fulfillment_method->label().' schedule confirmed',
                        "Both parties confirmed {$schedule->fulfillment_method->label()} for \"{$rental->listing->name}\" on ".self::displayTime($schedule)." at {$schedule->address}.",
                        self::urlFor($rental, $party),
                    ));
                }
            }

            return $schedule;
        });
    }

    private static function checkVersion(Rental $rental, ?int $expectedId): void
    {
        $currentId = $rental->exchangeSchedules()->max('id');
        if (($currentId === null ? null : (int) $currentId) !== $expectedId) {
            throw ValidationException::withMessages(['schedule' => 'The schedule has changed. Review the latest time and address before confirming or proposing changes.']);
        }
    }

    private static function displayTime(RentalExchangeSchedule $schedule): string
    {
        return $schedule->scheduled_at->setTimezone(self::TIMEZONE)->format('M d, Y \a\t g:i A').' (Asia/Manila)';
    }

    private static function urlFor(Rental $rental, User $user): string
    {
        return route(($user->id === $rental->owner_id ? 'owner' : 'renter').'.rentals.show', $rental).'#exchange-arrangements';
    }
}
