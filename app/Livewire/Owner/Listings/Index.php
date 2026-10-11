<?php

namespace App\Livewire\Owner\Listings;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Services\ListingPublication;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public function setAvailability(int $listingId, bool $available): void
    {
        $listing = Listing::findOrFail($listingId);

        $this->authorize('update', $listing);

        $listing->update(['is_available' => $available]);
    }

    public function deactivate(int $listingId): void
    {
        $listing = Listing::findOrFail($listingId);

        abort_unless($listing->isOwnedBy(auth()->user()), 403);
        abort_if($listing->status !== ListingStatus::Published, 403);

        $listing->update(['status' => ListingStatus::Inactive]);
    }

    public function reactivate(int $listingId): void
    {
        DB::transaction(function () use ($listingId) {
            $listing = Listing::lockForUpdate()->findOrFail($listingId);

            abort_unless($listing->isOwnedBy(auth()->user()), 403);
            abort_if($listing->status !== ListingStatus::Inactive, 403);

            ListingPublication::validate($listing);
            $listing->update(['status' => ListingStatus::Published]);
        });
    }

    public function delete(int $listingId): void
    {
        $listing = Listing::findOrFail($listingId);

        abort_unless($listing->isOwnedBy(auth()->user()), 403);

        $listing->delete();
        session()->flash('status', 'Listing removed. Existing bookings and transaction history are retained.');
    }

    public function render(): View
    {
        $listings = Listing::query()
            ->where('owner_id', auth()->id())
            ->withAvailability()
            ->with(['category', 'images', 'paidReservations'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.owner.listings.index', ['listings' => $listings]);
    }
}
