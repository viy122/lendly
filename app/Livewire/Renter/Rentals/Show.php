<?php

namespace App\Livewire\Renter\Rentals;

use App\Enums\DisputeReason;
use App\Enums\NotificationType;
use App\Enums\RentalStatus;
use App\Enums\ReviewType;
use App\Models\Dispute;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\Review;
use App\Models\SecurityDeposit;
use App\Notifications\TalaNotification;
use App\Services\CancellationPolicy;
use App\Services\RentalLifecycle;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public Rental $rental;

    public string $damage_response_notes = '';

    public int $owner_rating = 5;

    public string $owner_comment = '';

    public int $listing_rating = 5;

    public string $listing_comment = '';

    public string $dispute_reason = '';

    public string $dispute_description = '';

    public bool $showDisputeForm = false;

    public bool $showCancelForm = false;

    public string $cancellation_reason = '';

    public function mount(Rental $rental): void
    {
        $this->authorize('view', $rental);

        $this->rental = $rental->load([
            'listing', 'owner', 'renter', 'payment', 'securityDeposit',
            'beforeConditionRecord.photos', 'afterConditionRecord.photos', 'damageReport.photos',
            'reviewFromRenterToOwner', 'reviewFromRenterToListing', 'disputes',
        ]);
    }

    public function confirmPayment(): void
    {
        abort_unless(auth()->id() === $this->rental->renter_id, 403);
        DB::transaction(function () {
            $rental = Rental::whereKey($this->rental->id)->lockForUpdate()->firstOrFail();
            abort_unless(auth()->id() === $rental->renter_id, 403);
            if ($rental->payment()->exists() && ! $rental->isCancelled() && ! $rental->isPaymentPending()) {
                return;
            }
            $this->authorize('pay', $rental);

            Payment::create([
                'rental_id' => $rental->id,
                'transaction_reference' => Payment::generateReference(),
                'amount' => $rental->total_amount,
                'paid_at' => now(),
            ]);

            SecurityDeposit::create([
                'rental_id' => $rental->id,
                'amount' => $rental->security_deposit,
            ]);

            $rental->update([
                'status' => RentalStatus::Paid,
                'paid_at' => now(),
            ]);

            foreach ([$rental->owner, $rental->renter] as $participant) {
                $participant->notify(new TalaNotification(
                    NotificationType::PaymentConfirmed->value,
                    'Payment received',
                    "Payment for \"{$rental->listing->name}\" has been confirmed. Your receipt is available.",
                    route($participant->id === $rental->owner_id ? 'owner.rentals.show' : 'renter.rentals.show', $rental),
                ));
            }
        }, 3);

        $this->rental->refresh()->load(['payment', 'securityDeposit']);
    }

    public function cancellationPreview(): ?array
    {
        return $this->rental->isCancellableByRenter()
            ? CancellationPolicy::evaluate($this->rental)
            : null;
    }

    public function cancelRental(): void
    {
        $this->authorize('cancel', $this->rental);

        $this->validate(['cancellation_reason' => ['required', 'string', 'max:500']]);

        RentalLifecycle::cancel($this->rental, $this->cancellation_reason);

        $this->reset('cancellation_reason', 'showCancelForm');
        $this->rental->refresh()->load(['rentalRequest', 'securityDeposit']);
    }

    public function confirmPickup(): void
    {
        $this->authorize('confirmPickup', $this->rental);

        RentalLifecycle::confirmPickup($this->rental, auth()->user());

        $this->rental->refresh();
    }

    public function confirmReturn(): void
    {
        $this->authorize('confirmReturn', $this->rental);

        RentalLifecycle::confirmReturn($this->rental, auth()->user());

        $this->rental->refresh();
    }

    public function acceptDamageClaim(): void
    {
        $damageReport = $this->rental->damageReport;

        $this->authorize('respond', $damageReport);

        RentalLifecycle::acceptDamageClaim($damageReport);

        $this->rental->refresh()->load(['damageReport', 'securityDeposit']);
    }

    public function disputeDamageClaim(): void
    {
        $damageReport = $this->rental->damageReport;

        $this->authorize('respond', $damageReport);

        $this->validate(['damage_response_notes' => ['required', 'string', 'max:1000']]);

        RentalLifecycle::disputeDamageClaim($damageReport, $this->damage_response_notes);

        $this->rental->refresh()->load('damageReport');
        $this->reset('damage_response_notes');
    }

    public function submitOwnerReview(): void
    {
        abort_unless(auth()->id() === $this->rental->renter_id, 403);
        abort_unless($this->rental->isCompleted(), 403);
        abort_if($this->rental->reviewFromRenterToOwner, 403, 'You already reviewed the owner.');

        $this->validate([
            'owner_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'owner_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        Review::create([
            'rental_id' => $this->rental->id,
            'type' => ReviewType::RenterToOwner,
            'rating' => $this->owner_rating,
            'comment' => $this->owner_comment,
        ]);

        $this->rental->refresh()->load('reviewFromRenterToOwner');
    }

    public function submitListingReview(): void
    {
        abort_unless(auth()->id() === $this->rental->renter_id, 403);
        abort_unless($this->rental->isCompleted(), 403);
        abort_if($this->rental->reviewFromRenterToListing, 403, 'You already reviewed this item.');

        $this->validate([
            'listing_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'listing_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        Review::create([
            'rental_id' => $this->rental->id,
            'type' => ReviewType::RenterToListing,
            'rating' => $this->listing_rating,
            'comment' => $this->listing_comment,
        ]);

        $this->rental->refresh()->load('reviewFromRenterToListing');
    }

    public function createDispute(): void
    {
        $this->validate([
            'dispute_reason' => ['required', Rule::enum(DisputeReason::class)],
            'dispute_description' => ['required', 'string', 'max:1000'],
        ]);

        Dispute::create([
            'rental_id' => $this->rental->id,
            'raised_by' => auth()->id(),
            'reason' => $this->dispute_reason,
            'description' => $this->dispute_description,
        ]);

        $this->rental->owner->notify(new TalaNotification(
            NotificationType::DisputeUpdate->value,
            'New dispute raised',
            "The renter raised a dispute for \"{$this->rental->listing->name}\".",
            route('owner.rentals.show', $this->rental),
        ));

        session()->flash('status', 'Your dispute has been submitted. An admin will review it.');

        $this->reset('dispute_reason', 'dispute_description', 'showDisputeForm');
        $this->rental->refresh()->load('disputes');
    }

    public function render(): View
    {
        return view('livewire.renter.rentals.show');
    }

    public function refreshRental(): void
    {
        $this->rental->refresh();
        $this->authorize('view', $this->rental);
        $this->rental->load(['payment', 'securityDeposit', 'damageReport.photos', 'afterConditionRecord.photos', 'disputes']);
    }
}
