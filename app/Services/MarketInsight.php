<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Models\Listing;

/**
 * Daily-rate comparisons and an original-price-based suggestion.
 */
class MarketInsight
{
    /**
     * @return array{count: int, average: ?float, min: ?float, max: ?float, suggested: ?float, basis: string, ratio: ?float, ratio_count: int}
     */
    public static function forListing(?int $categoryId, ?int $subcategoryId, ?int $excludingListingId = null, ?float $originalPrice = null): array
    {
        $listings = Listing::query()
            ->where('status', ListingStatus::Published)
            ->where(function ($query) use ($categoryId, $subcategoryId) {
                $query->where('category_id', $categoryId);

                if ($subcategoryId) {
                    $query->orWhere('subcategory_id', $subcategoryId);
                }
            })
            ->when(! $categoryId, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($excludingListingId, fn ($query) => $query->whereKeyNot($excludingListingId))
            ->get(['price_per_day', 'estimated_original_price']);
        $similar = $listings->pluck('price_per_day')->map(fn ($price) => (float) $price);

        $ratios = $listings->filter(fn ($listing) => $listing->estimated_original_price > 0 && $listing->price_per_day > 0)
            ->map(fn ($listing) => (float) $listing->price_per_day / (float) $listing->estimated_original_price);
        $hasOriginal = $originalPrice !== null && is_finite($originalPrice) && $originalPrice > 0;
        $ratio = $hasOriginal ? ($ratios->isNotEmpty() ? (float) $ratios->median() : 0.01) : null;
        $estimate = $hasOriginal ? round(max(0.01, $originalPrice * $ratio), 2) : null;
        $explanation = [
            'basis' => $hasOriginal ? ($ratios->isNotEmpty() ? 'original-price-ratio' : 'starting-estimate') : ($similar->isNotEmpty() ? 'market-average' : 'none'),
            'ratio' => $ratio,
            'ratio_count' => $hasOriginal ? $ratios->count() : 0,
        ];

        if ($similar->isEmpty()) {
            return ['count' => 0, 'average' => null, 'min' => null, 'max' => null, 'suggested' => $estimate] + $explanation;
        }

        $average = round($similar->avg(), 2);

        return [
            'count' => $similar->count(),
            'average' => $average,
            'min' => round($similar->min(), 2),
            'max' => round($similar->max(), 2),
            'suggested' => $estimate ?? $average,
        ] + $explanation;
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
