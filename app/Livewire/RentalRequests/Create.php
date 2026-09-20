<?php

namespace App\Livewire\RentalRequests;

use App\Enums\FulfillmentMethod;
use App\Enums\NotificationType;
use App\Enums\RentalRequestStatus;
use App\Models\CommissionSetting;
use App\Models\Listing;
use App\Models\RentalRequest;
use App\Notifications\TalaNotification;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.marketplace')]
class Create extends Component
{
    public Listing $listing;

    public string $start_date = '';

    public string $end_date = '';

    public string $fulfillment_method = '';

    public bool $accept_terms = false;

    public function mount(Listing $listing): void
    {
        abort_unless($listing->isPublished() && $listing->is_available, 404);
        abort_if($listing->owner_id === auth()->id(), 403, 'You cannot rent your own listing.');

        $this->listing = $listing;
        $this->start_date = now()->addDay()->toDateString();
        $this->end_date = now()->addDay()->toDateString();

        $this->fulfillment_method = $listing->pickup_available
            ? FulfillmentMethod::Pickup->value
            : FulfillmentMethod::Delivery->value;
    }

    public function rentalDays(): int
    {
        if (! $this->start_date || ! $this->end_date) {
            return 0;
        }

        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);

        return $end->greaterThanOrEqualTo($start) ? $start->diffInDays($end) + 1 : 0;
    }

    public function rentalFee(): float
    {
        return round((float) $this->listing->price_per_day * $this->rentalDays(), 2);
    }

    public function commissionRate(): float
    {
        return (float) CommissionSetting::current()->commission_rate;
    }

    public function commissionAmount(): float
    {
        return round($this->rentalFee() * ($this->commissionRate() / 100), 2);
    }

    public function totalAmount(): float
    {
        return round($this->rentalFee() + $this->commissionAmount() + (float) $this->listing->security_deposit, 2);
    }

    public function submit(): void
    {
        $this->validate([
            'start_date' => ['required', 'date', 'after_or_equal:tomorrow'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'fulfillment_method' => ['required', Rule::enum(FulfillmentMethod::class)],
            'accept_terms' => ['accepted'],
        ], [
            'accept_terms.accepted' => 'You must accept the rental terms and agreement to continue.',
        ]);

        if ($this->fulfillment_method === FulfillmentMethod::Pickup->value && ! $this->listing->pickup_available) {
            $this->addError('fulfillment_method', 'Pickup is not available for this item.');

            return;
        }

        if ($this->fulfillment_method === FulfillmentMethod::Delivery->value && ! $this->listing->delivery_available) {
            $this->addError('fulfillment_method', 'Delivery is not available for this item.');

            return;
        }

        $days = $this->rentalDays();

        if ($days > $this->listing->max_rental_duration_days) {
            $this->addError('end_date', "This item can be rented for at most {$this->listing->max_rental_duration_days} days.");

            return;
        }

        if ($this->listing->hasApprovedOverlap($this->start_date, $this->end_date)) {
            $this->addError('start_date', 'These dates are no longer available for this item. Please choose different dates.');

            return;
        }

        $rentalRequest = RentalRequest::create([
            'listing_id' => $this->listing->id,
            'renter_id' => auth()->id(),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'rental_days' => $days,
            'fulfillment_method' => $this->fulfillment_method,
            'rental_fee' => $this->rentalFee(),
            'commission_rate' => $this->commissionRate(),
            'commission_amount' => $this->commissionAmount(),
            'security_deposit' => $this->listing->security_deposit,
            'total_amount' => $this->totalAmount(),
            'status' => RentalRequestStatus::Requested,
            'renter_terms_accepted_at' => now(),
        ]);

        $this->listing->owner->notify(new TalaNotification(
            NotificationType::RentalRequestSubmitted->value,
            'New rental request',
            auth()->user()->name." wants to rent \"{$this->listing->name}\".",
            route('owner.rental-requests.index'),
        ));

        session()->flash('status', 'Rental request sent! The owner will review it shortly.');

        $this->redirect(route('renter.rental-requests.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.rental-requests.create');
    }
}
