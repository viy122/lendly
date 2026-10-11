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
        $terms = $rental->rentalRequest?->agreement_terms;
        $windowHours = $terms['cancellation_window_hours'] ?? self::CHARGEABLE_WINDOW_HOURS;
        $feePercentage = ($terms['cancellation_fee_percentage'] ?? self::FEE_PERCENTAGE * 100) / 100;
        $hoursUntilStart = round(max(0, now()->diffInHours($rental->start_date, false)), 1);
        $withinChargeableWindow = now()->diffInHours($rental->start_date, false) < $windowHours;

        $fee = $withinChargeableWindow
            ? round((float) $rental->rental_fee * $feePercentage, 2)
            : 0.0;

        return [
            'fee' => $fee,
            'hours_until_start' => $hoursUntilStart,
            'within_chargeable_window' => $withinChargeableWindow,
            'reason' => $withinChargeableWindow
                ? 'Cancelled less than '.$windowHours.' hours before the rental start date — a '.($feePercentage * 100).'% cancellation fee applies per the rental terms and agreement.'
                : 'Cancelled at least '.$windowHours.' hours before the rental start date — no cancellation fee applies.',
        ];
    }
}
