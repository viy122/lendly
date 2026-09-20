<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Models\Listing;

/**
 * Basic, database-driven market price insight (spec section 8) — no price
 * prediction model, just descriptive stats over existing similar listings.
 */
class MarketInsight
{
    /**
     * @return array{count: int, average: ?float, min: ?float, max: ?float, suggested: ?float}
     */
    public static function forListing(int $categoryId, ?int $subcategoryId, ?int $excludingListingId = null): array
    {
        $similar = Listing::query()
            ->where('status', ListingStatus::Published)
            ->where(function ($query) use ($categoryId, $subcategoryId) {
                $query->where('category_id', $categoryId);

                if ($subcategoryId) {
                    $query->orWhere('subcategory_id', $subcategoryId);
                }
            })
            ->when($excludingListingId, fn ($query) => $query->whereKeyNot($excludingListingId))
            ->pluck('price_per_day')
            ->map(fn ($price) => (float) $price);

        if ($similar->isEmpty()) {
            return ['count' => 0, 'average' => null, 'min' => null, 'max' => null, 'suggested' => null];
        }

        $average = round($similar->avg(), 2);

        return [
            'count' => $similar->count(),
            'average' => $average,
            'min' => round($similar->min(), 2),
            'max' => round($similar->max(), 2),
            'suggested' => $average,
        ];
    }

    /**
     * Whether a proposed price is "significantly higher" than the market
     * average — the spec's threshold isn't numeric, so this uses a plain,
     * explainable rule: more than 20% above the average of similar listings.
     */
    public static function isSignificantlyHigher(float $proposedPrice, float $average): bool
    {
        return $average > 0 && $proposedPrice > $average * 1.2;
    }
}
