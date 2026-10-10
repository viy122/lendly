<?php

namespace App\Livewire\Admin\Rentals;

use App\Models\Rental;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public Rental $rental;

    public function mount(Rental $rental): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->rental = $rental->load([
            'rentalRequest', 'listing', 'owner', 'renter', 'payment', 'securityDeposit',
            'conditionRecords.photos', 'conditionRecords.recordedBy', 'damageReport.photos',
            'disputes.raisedBy', 'disputes.resolvedBy',
        ]);
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $timeline = collect([
            'Request submitted' => $this->rental->rentalRequest->created_at,
            'Booking approved' => $this->rental->created_at,
            'Payment confirmed' => $this->rental->paid_at,
            'Owner confirmed pickup' => $this->rental->pickup_confirmed_by_owner_at,
            'Renter confirmed pickup' => $this->rental->pickup_confirmed_by_renter_at,
            'Owner confirmed return' => $this->rental->return_confirmed_by_owner_at,
            'Renter confirmed return' => $this->rental->return_confirmed_by_renter_at,
            'Inspection completed' => $this->rental->completed_at,
            'Rental cancelled' => $this->rental->cancelled_at,
        ])->filter()->sortBy(fn ($date) => $date->timestamp);

        return view('livewire.admin.rentals.show', ['timeline' => $timeline]);
    }
}
