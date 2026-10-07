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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.marketplace')]
class Create extends Component
{
    public Listing $listing;

    #[Locked]
    public bool $modal = false;

    public string $start_date = '';

    public string $end_date = '';

    public string $fulfillment_method = '';

    public function mount(Listing $listing, bool $modal = false): void
    {
        $this->authorizeRenter();
        abort_unless(! $listing->trashed() && $listing->isPublished() && $listing->is_available && $listing->hasRequestableDates(), 404);
        abort_if($listing->owner_id === auth()->id(), 403, 'You cannot rent your own listing.');

        $this->listing = $listing;
        $this->modal = $modal;
        $this->start_date = max(now()->addDay()->toDateString(), $listing->available_from?->toDateString() ?? '');
        $this->end_date = $this->start_date;

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
        $this->authorizeRenter();

        // Share the listing lock used by approval, so its date hold cannot
        // change between the availability check and saving this request.
        $submitted = DB::transaction(fn () => $this->persistRequest());

        if (! $submitted) {
            return;
        }

        session()->flash('status', 'Rental request sent! The owner will review it shortly.');
        $this->redirect(route('renter.rental-requests.index'), navigate: true);
    }

    private function persistRequest(): bool
    {
        $listing = Listing::withTrashed()->whereKey($this->listing->id)->lockForUpdate()->first();

        if (! $listing || $listing->trashed() || ! $listing->isPublished() || ! $listing->is_available || ! $listing->hasRequestableDates()) {
            $this->addError('listing', 'This item is no longer available for rent. Please choose another item.');

            return false;
        }

        $this->listing = $listing;
        abort_if($listing->owner_id === auth()->id(), 403, 'You cannot rent your own listing.');

        $this->validate([
            'start_date' => ['required', 'date', 'after_or_equal:tomorrow'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'fulfillment_method' => ['required', Rule::enum(FulfillmentMethod::class)],
        ]);

        if (! $listing->includesAvailableDates($this->start_date, $this->end_date)) {
            $this->addError('start_date', 'Choose rental dates within the owner’s availability window.');
            $this->addError('end_date', 'The entire rental period must fit within the available dates.');

            return false;
        }

        if ($this->fulfillment_method === FulfillmentMethod::Pickup->value && ! $this->listing->pickup_available) {
            $this->addError('fulfillment_method', 'Pickup is not available for this item.');

            return false;
        }

        if ($this->fulfillment_method === FulfillmentMethod::Delivery->value && ! $this->listing->delivery_available) {
            $this->addError('fulfillment_method', 'Delivery is not available for this item.');

            return false;
        }

        $days = $this->rentalDays();

        if ($days > $this->listing->max_rental_duration_days) {
            $this->addError('end_date', "This item can be rented for at most {$this->listing->max_rental_duration_days} days.");

            return false;
        }

        if ($this->listing->hasApprovedOverlap($this->start_date, $this->end_date)) {
            $this->addError('start_date', 'These dates are no longer available for this item. Please choose different dates.');

            return false;
        }

        // Read the rate once so every saved charge uses the same snapshot.
        $rentalFee = round((float) $listing->price_per_day * $days, 2);
        $commissionRate = $this->commissionRate();
        $commissionAmount = round($rentalFee * ($commissionRate / 100), 2);
        $totalAmount = round($rentalFee + $commissionAmount + (float) $listing->security_deposit, 2);

        $rentalRequest = RentalRequest::create([
            'listing_id' => $this->listing->id,
            'renter_id' => auth()->id(),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'rental_days' => $days,
            'fulfillment_method' => $this->fulfillment_method,
            'rental_fee' => $rentalFee,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'security_deposit' => $this->listing->security_deposit,
            'total_amount' => $totalAmount,
            'status' => RentalRequestStatus::Requested,
        ]);

        $this->listing->owner->notify(new TalaNotification(
            NotificationType::RentalRequestSubmitted->value,
            'New rental request',
            auth()->user()->name." wants to rent \"{$this->listing->name}\". The request is pending your review.",
            route('owner.rental-requests.index'),
        ));

        $rentalRequest->renter->notify(new TalaNotification(
            NotificationType::RentalRequestSubmitted->value,
            'Request pending',
            "Your request for \"{$this->listing->name}\" is pending the owner's review.",
            route('renter.rental-requests.index'),
        ));

        return true;
    }

    private function authorizeRenter(): void
    {
        $user = auth()->user();

        abort_unless($user && $user->isRenter() && $user->activeInterface() === 'renter', 403);
    }

    public function render(): View
    {
        return view('livewire.rental-requests.create');
    }
}
