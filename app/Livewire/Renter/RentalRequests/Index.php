<?php

namespace App\Livewire\Renter\RentalRequests;

use App\Enums\NotificationType;
use App\Enums\RentalRequestStatus;
use App\Livewire\Concerns\FiltersRecords;
use App\Models\Listing;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Notifications\TalaNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use FiltersRecords;
    use WithPagination;

    public ?int $cancelling = null;

    public string $cancellation_reason = '';

    public function startCancelling(int $rentalRequestId): void
    {
        $rentalRequest = RentalRequest::findOrFail($rentalRequestId);
        $this->authorize('cancel', $rentalRequest);
        abort_if($rentalRequest->rental()->exists(), 403, 'Cancel confirmed bookings from the booking page.');

        $this->resetValidation('cancellation_reason');
        $this->cancelling = $rentalRequestId;
        $this->cancellation_reason = '';
    }

    public function cancel(int $rentalRequestId): void
    {
        DB::transaction(function () use ($rentalRequestId) {
            $snapshot = RentalRequest::findOrFail($rentalRequestId);
            Listing::withTrashed()->whereKey($snapshot->listing_id)->lockForUpdate()->firstOrFail();
            $rental = Rental::where('rental_request_id', $rentalRequestId)->lockForUpdate()->first();
            $rentalRequest = RentalRequest::lockForUpdate()->findOrFail($rentalRequestId);
            $rentalRequest->setRelation('rental', $rental);

            $this->authorize('cancel', $rentalRequest);
            abort_if($rental, 403, 'Cancel confirmed bookings from the booking page.');

            $this->validate(['cancellation_reason' => ['nullable', 'string', 'max:500']]);
            $reason = trim($this->cancellation_reason);

            $rentalRequest->update([
                'status' => RentalRequestStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason !== '' ? $reason : null,
                'cancellation_fee' => 0,
            ]);

            foreach (['owner' => $rentalRequest->listing->owner, 'renter' => $rentalRequest->renter] as $interface => $participant) {
                if ($participant->trashed()) {
                    continue;
                }
                $participant->notify(new TalaNotification(
                    NotificationType::RentalCancelled->value,
                    'Rental request cancelled',
                    "{$rentalRequest->renter->name} cancelled request #{$rentalRequest->id} for \"{$rentalRequest->listing->name}\" ({$rentalRequest->start_date->format('M d, Y')} – {$rentalRequest->end_date->format('M d, Y')}). Item availability: {$rentalRequest->listing->availabilityStatus()->label()}.",
                    route($interface.'.rental-requests.index', ['filter' => RentalRequestStatus::Cancelled->value]),
                ));
            }
        }, 3);

        $this->reset('cancelling', 'cancellation_reason');
    }

    #[Url]
    public string $filter = 'all';

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $rentalRequests = $this->filterRecords(RentalRequest::query()
            ->where('renter_id', auth()->id())
            ->when($this->filter !== 'all', fn ($query) => $query->where('status', $this->filter))
            ->with(['listing.images', 'listing.owner', 'rental'])
            ->orderByDesc('created_at')
            ->orderByDesc('id'), ['id'], ['listing' => ['name'], 'listing.owner' => ['name']])->paginate(10);

        return view('livewire.renter.rental-requests.index', ['rentalRequests' => $rentalRequests]);
    }
}
