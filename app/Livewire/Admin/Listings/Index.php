<?php

namespace App\Livewire\Admin\Listings;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\User;
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

    public string $filter = 'published';

    public ?int $rejecting = null;

    public string $rejection_reason = '';

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function approve(int $listingId): void
    {
        $admin = User::find(auth()->id());
        abort_unless($admin && $admin->isAdmin() && ! $admin->isSuspended() && $admin->hasVerifiedEmail(), 403);
        DB::transaction(function () use ($listingId) {
            $snapshot = Listing::findOrFail($listingId);
            ListingPublication::ensureVerifiedOwner($snapshot->owner_id);
            $listing = Listing::lockForUpdate()->findOrFail($listingId);
            abort_unless($listing->owner_id === $snapshot->owner_id, 409);
            $listing->update(['status' => ListingStatus::Published, 'rejection_reason' => null]);
        });
    }

    public function startRejecting(int $listingId): void
    {
        $this->rejecting = $listingId;
        $this->rejection_reason = '';
    }

    public function confirmReject(): void
    {
        $this->validate(['rejection_reason' => ['required', 'string', 'max:255']]);

        $listing = Listing::findOrFail($this->rejecting);

        $listing->update(['status' => ListingStatus::Rejected, 'rejection_reason' => $this->rejection_reason]);

        $this->rejecting = null;
        $this->rejection_reason = '';
    }

    public function remove(int $listingId): void
    {
        $listing = Listing::findOrFail($listingId);

        $listing->update(['status' => ListingStatus::Inactive]);
    }

    public function render(): View
    {
        $listings = Listing::query()
            ->when($this->filter !== 'all', fn ($query) => $query->where('status', $this->filter))
            ->with(['category', 'owner', 'images'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.admin.listings.index', [
            'listings' => $listings,
            'pendingCount' => Listing::pendingApproval()->count(),
        ]);
    }
}
