<?php

namespace App\Services;

use App\Models\Rental;
use Carbon\Carbon;

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
        $newPolicy = $terms['cancellation_requires_review'] ?? false;
        $reference = $newPolicy ? ($rental->exchangeSchedule?->confirmed_at ? $rental->exchangeSchedule->scheduled_at : Carbon::parse($terms['cancellation_reference_at'])) : $rental->start_date;
        $hoursUntilStart = round(max(0, now()->diffInHours($reference, false)), 1);
        $withinChargeableWindow = now()->diffInHours($reference, false) < $windowHours;
        $packed = $newPolicy && $rental->fulfillment_stage === 'packed';

        $fee = ($withinChargeableWindow || $packed)
            ? round((float) $rental->rental_fee * $feePercentage, 2)
            : 0.0;

        return [
            'fee' => $fee,
            'requires_review' => $newPolicy && ($withinChargeableWindow || $packed),
            'hours_until_start' => $hoursUntilStart,
            'within_chargeable_window' => $withinChargeableWindow,
            'reason' => $packed ? 'The owner has packed this item. A '.($feePercentage * 100).'% rental-only cancellation fee applies if your cancellation request is approved.' : ($withinChargeableWindow
                ? 'Cancelled less than '.$windowHours.' hours before the rental start date — a '.($feePercentage * 100).'% cancellation fee applies per the rental terms and agreement.'
                : 'Cancelled at least '.$windowHours.' hours before the rental start date — no cancellation fee applies.'),
        ];
    }
}
