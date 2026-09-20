<?php

namespace App\Livewire\Owner\RentalRequests;

use App\Enums\NotificationType;
use App\Enums\RentalRequestStatus;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Notifications\TalaNotification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $filter = 'requested';

    public ?int $rejecting = null;

    public string $rejection_reason = '';

    public ?int $approving = null;

    public bool $accept_terms = false;

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function startApproving(int $rentalRequestId): void
    {
        $this->approving = $rentalRequestId;
        $this->accept_terms = false;
    }

    public function approve(int $rentalRequestId): void
    {
        $this->validate([
            'accept_terms' => ['accepted'],
        ], [
            'accept_terms.accepted' => 'You must accept the rental terms and agreement to approve this request.',
        ]);

        $rentalRequest = RentalRequest::with('listing')->findOrFail($rentalRequestId);

        $this->authorize('moderate', $rentalRequest);

        abort_unless($rentalRequest->isPending(), 403);

        if ($rentalRequest->listing->hasApprovedOverlap($rentalRequest->start_date, $rentalRequest->end_date, excludingRequestId: $rentalRequest->id)) {
            $this->addError('approve', 'These dates were already approved for another renter.');

            return;
        }

        $rentalRequest->update([
            'status' => RentalRequestStatus::Approved,
            'owner_terms_accepted_at' => now(),
        ]);

        $rental = Rental::create([
            'rental_request_id' => $rentalRequest->id,
            'listing_id' => $rentalRequest->listing_id,
            'owner_id' => $rentalRequest->listing->owner_id,
            'renter_id' => $rentalRequest->renter_id,
            'start_date' => $rentalRequest->start_date,
            'end_date' => $rentalRequest->end_date,
            'rental_days' => $rentalRequest->rental_days,
            'fulfillment_method' => $rentalRequest->fulfillment_method,
            'rental_fee' => $rentalRequest->rental_fee,
            'commission_rate' => $rentalRequest->commission_rate,
            'commission_amount' => $rentalRequest->commission_amount,
            'security_deposit' => $rentalRequest->security_deposit,
            'total_amount' => $rentalRequest->total_amount,
        ]);

        $rentalRequest->renter->notify(new TalaNotification(
            NotificationType::RequestApproved->value,
            'Request approved',
            "Your request for \"{$rentalRequest->listing->name}\" was approved. Please complete payment.",
            route('renter.rentals.show', $rental),
        ));

        // Approving one request confirms the dates, so any other pending
        // request for the same listing that overlaps is no longer viable.
        $overlapping = RentalRequest::overlapping($rentalRequest->listing_id, $rentalRequest->start_date, $rentalRequest->end_date)
            ->where('status', RentalRequestStatus::Requested)
            ->whereKeyNot($rentalRequest->id)
            ->get();

        foreach ($overlapping as $other) {
            $other->update([
                'status' => RentalRequestStatus::Rejected,
                'rejection_reason' => 'These dates were booked by another renter.',
            ]);

            $other->renter->notify(new TalaNotification(
                NotificationType::RequestRejected->value,
                'Request no longer available',
                "Your request for \"{$rentalRequest->listing->name}\" was rejected — those dates were booked by another renter.",
                route('renter.rental-requests.index'),
            ));
        }

        $this->reset('approving', 'accept_terms');
    }

    public function startRejecting(int $rentalRequestId): void
    {
        $this->rejecting = $rentalRequestId;
        $this->rejection_reason = '';
    }

    public function confirmReject(): void
    {
        $this->validate(['rejection_reason' => ['required', 'string', 'max:255']]);

        $rentalRequest = RentalRequest::with('listing')->findOrFail($this->rejecting);

        $this->authorize('moderate', $rentalRequest);

        abort_unless($rentalRequest->isPending(), 403);

        $rentalRequest->update([
            'status' => RentalRequestStatus::Rejected,
            'rejection_reason' => $this->rejection_reason,
        ]);

        $rentalRequest->renter->notify(new TalaNotification(
            NotificationType::RequestRejected->value,
            'Request rejected',
            "Your request for \"{$rentalRequest->listing->name}\" was rejected: {$this->rejection_reason}",
            route('renter.rental-requests.index'),
        ));

        $this->rejecting = null;
        $this->rejection_reason = '';
    }

    public function render(): View
    {
        $rentalRequests = RentalRequest::query()
            ->whereHas('listing', fn ($query) => $query->where('owner_id', auth()->id()))
            ->when($this->filter !== 'all', fn ($query) => $query->where('status', $this->filter))
            ->with(['listing.images', 'renter'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.owner.rental-requests.index', ['rentalRequests' => $rentalRequests]);
    }
}
