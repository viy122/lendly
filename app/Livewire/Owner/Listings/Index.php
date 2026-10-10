<?php

namespace App\Livewire\Owner\Listings;

use App\Enums\ListingStatus;
use App\Models\Listing;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public function deactivate(int $listingId): void
    {
        $listing = Listing::findOrFail($listingId);

        abort_unless($listing->isOwnedBy(auth()->user()), 403);
        abort_if($listing->status !== ListingStatus::Published, 403);

        $listing->update(['status' => ListingStatus::Inactive]);
    }

    public function reactivate(int $listingId): void
    {
        $listing = Listing::findOrFail($listingId);

        abort_unless($listing->isOwnedBy(auth()->user()), 403);
        abort_if($listing->status !== ListingStatus::Inactive, 403);

        $listing->update(['status' => ListingStatus::PendingApproval]);
    }

    public function delete(int $listingId): void
    {
        $listing = Listing::findOrFail($listingId);

        abort_unless($listing->isOwnedBy(auth()->user()), 403);

        $listing->delete();
    }

    public function render(): View
    {
        $listings = Listing::query()
            ->where('owner_id', auth()->id())
            ->with(['category', 'images', 'rentals' => fn ($query) => $query->whereIn('status', ['paid', 'active', 'overdue'])])
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.owner.listings.index', ['listings' => $listings]);
    }
}
