<?php

namespace App\Livewire\Owner\RentalRequests;

use App\Enums\FulfillmentMethod;
use App\Enums\RentalRequestStatus;
use App\Models\Listing;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
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

        $snapshot = RentalRequest::with('listing')->findOrFail($rentalRequestId);
        $this->authorize('moderate', $snapshot);

        $approved = DB::transaction(function () use ($snapshot, $rentalRequestId) {
            $participants = User::whereIn('id', [$snapshot->listing->owner_id, $snapshot->renter_id])->orderBy('id')->lockForUpdate()->get();
            abort_unless($participants->count() === 2 && $participants->every(fn (User $user) => ! $user->isSuspended()), 403);
            $listing = Listing::whereKey($snapshot->listing_id)->lockForUpdate()->first();
            if (! $listing || ! $listing->isPublished() || ! $listing->is_available) {
                $this->addError('approve', 'This listing is no longer available.');

                return false;
            }
            $rentalRequest = RentalRequest::whereKey($rentalRequestId)->lockForUpdate()->firstOrFail();
            $this->authorize('moderate', $rentalRequest);
            abort_unless($rentalRequest->isPending(), 403);
            abort_unless($rentalRequest->renter_terms_accepted_at !== null, 403, 'The renter must accept the terms first.');

            if (($rentalRequest->fulfillment_method === FulfillmentMethod::Pickup && ! $listing->pickup_available)
                || ($rentalRequest->fulfillment_method === FulfillmentMethod::Delivery && ! $listing->delivery_available)
                || $rentalRequest->rental_days > $listing->max_rental_duration_days) {
                $this->addError('approve', 'The requested fulfillment option or duration is no longer available. Ask the renter to submit an updated request.');

                return false;
            }

            if ($rentalRequest->start_date->lt(today()) || $listing->hasApprovedOverlap($rentalRequest->start_date, $rentalRequest->end_date, excludingRequestId: $rentalRequest->id)) {
                $this->addError('approve', 'These dates are no longer available for approval.');

                return false;
            }

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
            $rentalRequest->update([
                'status' => RentalRequestStatus::Approved,
                'owner_terms_accepted_at' => now(),
            ]);
            $rentalRequest->notifyStatus($rental);

            $overlapping = RentalRequest::overlapping($rentalRequest->listing_id, $rentalRequest->start_date, $rentalRequest->end_date)
                ->where('status', RentalRequestStatus::Requested)
                ->whereKeyNot($rentalRequest->id)
                ->lockForUpdate()->get();

            foreach ($overlapping as $other) {
                $other->update([
                    'status' => RentalRequestStatus::Rejected,
                    'rejection_reason' => 'These dates were booked by another renter.',
                ]);

                $other->notifyStatus();
            }

            return true;
        }, 3);

        if ($approved) {
            $this->reset('approving', 'accept_terms');
        }
    }

    public function startRejecting(int $rentalRequestId): void
    {
        $this->rejecting = $rentalRequestId;
        $this->rejection_reason = '';
    }

    public function confirmReject(): void
    {
        $this->validate(['rejection_reason' => ['required', 'string', 'max:255']]);

        DB::transaction(function () {
            $rentalRequest = RentalRequest::with('listing')->lockForUpdate()->findOrFail($this->rejecting);
            $this->authorize('moderate', $rentalRequest);
            abort_unless($rentalRequest->isPending(), 403);
            $rentalRequest->update([
                'status' => RentalRequestStatus::Rejected,
                'rejection_reason' => $this->rejection_reason,
            ]);
            $rentalRequest->notifyStatus();
        });

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
