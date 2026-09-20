<?php

namespace App\Livewire\Listings;

use App\Enums\RentalRequestStatus;
use App\Enums\ReviewType;
use App\Models\Listing;
use App\Models\Review;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.marketplace')]
class Show extends Component
{
    public Listing $listing;

    public function mount(Listing $listing): void
    {
        $this->authorize('view', $listing);

        $this->listing = $listing->load(['category', 'subcategory', 'images', 'owner']);

        if (! auth()->check() || auth()->id() !== $listing->owner_id) {
            $listing->increment('views_count');
        }
    }

    public function render(): View
    {
        $reviews = Review::whereHas('rental', fn ($query) => $query->where('listing_id', $this->listing->id))
            ->where('type', ReviewType::RenterToListing)
            ->with('rental.renter')
            ->latest()
            ->get();

        // Approved requests are the only thing that actually blocks dates
        // (see Listing::hasApprovedOverlap()) — only future/ongoing ones are
        // relevant to show on a "what's available" calendar.
        $bookedRanges = $this->listing->rentalRequests()
            ->where('status', RentalRequestStatus::Approved)
            ->where('end_date', '>=', now()->startOfDay())
            ->get(['start_date', 'end_date'])
            ->map(fn ($request) => [
                'start' => $request->start_date->toDateString(),
                'end' => $request->end_date->toDateString(),
            ])
            ->values();

        return view('livewire.listings.show', [
            'reviews' => $reviews,
            'averageRating' => $this->listing->averageRating(),
            'ownerAverageRating' => $this->listing->owner->averageRatingAsOwner(),
            'bookedRanges' => $bookedRanges,
        ]);
    }
}
