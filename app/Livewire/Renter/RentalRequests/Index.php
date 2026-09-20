<?php

namespace App\Livewire\Renter\RentalRequests;

use App\Enums\RentalRequestStatus;
use App\Models\RentalRequest;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public function cancel(int $rentalRequestId): void
    {
        $rentalRequest = RentalRequest::findOrFail($rentalRequestId);

        $this->authorize('cancel', $rentalRequest);

        $rentalRequest->update(['status' => RentalRequestStatus::Cancelled]);
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
