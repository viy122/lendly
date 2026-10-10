<?php

namespace App\Livewire\Renter\RentalRequests;

use App\Enums\RentalRequestStatus;
use App\Models\Listing;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Services\RentalLifecycle;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public function cancel(int $rentalRequestId): void
    {
        $snapshot = RentalRequest::findOrFail($rentalRequestId);
        DB::transaction(function () use ($snapshot, $rentalRequestId) {
            Listing::withTrashed()->whereKey($snapshot->listing_id)->lockForUpdate()->firstOrFail();
            $rental = Rental::where('rental_request_id', $rentalRequestId)->lockForUpdate()->first();
            $rentalRequest = RentalRequest::lockForUpdate()->findOrFail($rentalRequestId);
            $rentalRequest->setRelation('rental', $rental);
            $this->authorize('cancel', $rentalRequest);
            if ($rentalRequest->rental) {
                RentalLifecycle::cancel($rentalRequest->rental, 'Cancelled from rental requests.');
            } else {
                $rentalRequest->update(['status' => RentalRequestStatus::Cancelled]);
                $rentalRequest->notifyStatus();
            }
        }, 3);
    }

    public function render(): View
    {
        $rentalRequests = RentalRequest::query()
            ->where('renter_id', auth()->id())
            ->with(['listing.images', 'listing.owner'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.renter.rental-requests.index', ['rentalRequests' => $rentalRequests]);
    }
}
