<?php

namespace App\Livewire\Renter\RentalRequests;

use App\Enums\NotificationType;
use App\Enums\RentalRequestStatus;
use App\Models\RentalRequest;
use App\Notifications\TalaNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
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
            $rentalRequest = RentalRequest::lockForUpdate()->findOrFail($rentalRequestId);

            $this->authorize('cancel', $rentalRequest);
            abort_if($rentalRequest->rental()->exists(), 403, 'Cancel confirmed bookings from the booking page.');

            $this->validate(['cancellation_reason' => ['nullable', 'string', 'max:500']]);
            $reason = trim($this->cancellation_reason);

            $rentalRequest->update([
                'status' => RentalRequestStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason !== '' ? $reason : null,
                'cancellation_fee' => 0,
            ]);

            $rentalRequest->listing->owner->notify(new TalaNotification(
                NotificationType::RentalCancelled->value,
                'Rental request cancelled',
                "{$rentalRequest->renter->name} cancelled request #{$rentalRequest->id} for \"{$rentalRequest->listing->name}\" ({$rentalRequest->start_date->format('M d, Y')} – {$rentalRequest->end_date->format('M d, Y')}). Item availability: {$rentalRequest->listing->availabilityStatus()->label()}.",
                route('owner.rental-requests.index', ['filter' => RentalRequestStatus::Cancelled->value]),
            ));
        });

        $this->reset('cancelling', 'cancellation_reason');
    }

    public function render(): View
    {
        $rentalRequests = RentalRequest::query()
            ->where('renter_id', auth()->id())
            ->with(['listing.images', 'listing.owner', 'rental'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.renter.rental-requests.index', ['rentalRequests' => $rentalRequests]);
    }
}
