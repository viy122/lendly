<?php

namespace App\Services;

use App\Models\Rental;

/**
 * Rule-based, explainable cancellation-fee calculator — no ML, every peso
 * is tied to a concrete rule. The BRD doesn't specify exact tiers, so this
 * uses a single simple threshold: cancelling well ahead of the rental start
 * date is free, cancelling close to it costs a percentage of the rental fee.
 */
class CancellationPolicy
{
    public const CHARGEABLE_WINDOW_HOURS = 48;

    public const FEE_PERCENTAGE = 0.20;

    public static function evaluate(Rental $rental): array
    {
        $hoursUntilStart = round(max(0, now()->diffInHours($rental->start_date, false)), 1);
        $withinChargeableWindow = now()->diffInHours($rental->start_date, false) < self::CHARGEABLE_WINDOW_HOURS;

        $fee = $withinChargeableWindow
            ? round((float) $rental->rental_fee * self::FEE_PERCENTAGE, 2)
            : 0.0;

        return [
            'fee' => $fee,
            'hours_until_start' => $hoursUntilStart,
            'within_chargeable_window' => $withinChargeableWindow,
            'reason' => $withinChargeableWindow
                ? 'Cancelled within '.self::CHARGEABLE_WINDOW_HOURS.' hours of the rental start date — a '.(self::FEE_PERCENTAGE * 100).'% cancellation fee applies per the rental terms and agreement.'
                : 'Cancelled more than '.self::CHARGEABLE_WINDOW_HOURS.' hours before the rental start date — no cancellation fee applies.',
        ];
    }
}
