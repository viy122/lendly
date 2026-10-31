<?php

namespace App\Livewire\RentalRequests;

use App\Enums\FulfillmentMethod;
use App\Models\Listing;
use App\Models\RentalWaitlistEntry;
use App\Services\RentalCart;
use App\Services\RentalPeriod;
use App\Services\RentalRequestSubmission;
use App\Services\RentalWaitlist;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.marketplace')]
class Create extends Component
{
    public Listing $listing;

    #[Locked]
    public bool $modal = false;

    #[Locked]
    public array $presentedTerms = [];

    public bool $accept_terms = false;

    public string $start_date = '';

    public string $end_date = '';

    public string $rental_unit = 'day';

    public string $start_time = '09:00';

    public string $end_time = '10:00';

    public string $fulfillment_method = '';

    public function mount(Listing $listing, bool $modal = false): void
    {
        $this->authorizeRenter();
        abort_unless(! $listing->trashed() && $listing->isPublished() && $listing->is_available && $listing->hasRequestableDates(), 404);
        abort_if($listing->owner_id === auth()->id(), 403, 'You cannot rent your own listing.');

        $this->listing = $listing;
        $this->presentedTerms = RentalRequestSubmission::termsFor($listing);
        $this->modal = $modal;
        $this->start_date = max(now()->addDay()->toDateString(), $listing->available_from?->toDateString() ?? '');
        $this->end_date = $this->start_date;
        $this->rental_unit = $listing->allows_daily_rentals ? 'day' : 'hour';

        $this->fulfillment_method = $listing->pickup_available
            ? FulfillmentMethod::Pickup->value
            : FulfillmentMethod::Delivery->value;
        if ($entryId = request()->integer('waitlist')) {
            $entry = RentalWaitlistEntry::where('user_id', auth()->id())->where('listing_id', $listing->id)->find($entryId);
            if ($entry) {
                $this->fill($entry->details);
            }
        }
    }

    public function rentalHours(): int
    {
        try {
            return RentalPeriod::attributes($this->listing, $this->details())['rental_hours'] ?? 0;
        } catch (ValidationException) {
            return 0;
        }
    }

    public function joinWaitlist(): void
    {
        $this->authorizeRenter();
        RentalWaitlist::join(auth()->user(), $this->listing->id, $this->details());
        session()->flash('status', 'Joined the waitlist for your exact dates. We will notify you when they are available. No payment or reservation has been made.');
        $this->redirect(route('renter.cart.index'), navigate: true);
    }

    public function rentalDays(): int
    {
        try {
            return RentalPeriod::attributes($this->listing, $this->details())['rental_days'];
        } catch (ValidationException) {
            return 0;
        }
    }

    public function rentalFee(): float
    {
        return $this->rental_unit === 'hour'
            ? round((float) $this->listing->price_per_hour * $this->rentalHours(), 2)
            : round((float) $this->listing->price_per_day * $this->rentalDays(), 2);
    }

    public function commissionRate(): float
    {
        return (float) $this->presentedTerms['commission_rate'];
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
        $this->resetValidation();

        try {
            RentalRequestSubmission::submit(auth()->user(), $this->listing->id, $this->details(), $this->presentedTerms, $this->accept_terms);
        } catch (ValidationException $exception) {
            $this->refreshPresentedTerms();
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }

            return;
        }

        session()->flash('status', 'Rental request sent! The owner will review it shortly.');
        $this->redirect(route('renter.rental-requests.index'), navigate: true);
    }

    public function addToCart(): void
    {
        $this->authorizeRenter();
        $this->resetValidation();
        RentalCart::save(auth()->user(), $this->listing->id, $this->details());
        session()->flash('status', 'Rental draft added to your cart. No dates are reserved.');
        $this->redirect(route('renter.cart.index'), navigate: true);
    }

    private function details(): array
    {
        return $this->only(['start_date', 'end_date', 'fulfillment_method', 'rental_unit', 'start_time', 'end_time']);
    }

    private function refreshPresentedTerms(): void
    {
        if ($listing = Listing::withTrashed()->find($this->listing->id)) {
            $terms = RentalRequestSubmission::termsFor($listing);
            if (! RentalRequestSubmission::termsMatch($terms, $this->presentedTerms)) {
                $this->accept_terms = false;
            }
            $this->listing = $listing;
            $this->presentedTerms = $terms;
        }
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
