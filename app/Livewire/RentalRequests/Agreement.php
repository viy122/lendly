<?php

namespace App\Livewire\RentalRequests;

use App\Models\RentalRequest;
use App\Services\RentalAgreement;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Agreement extends Component
{
    public RentalRequest $rentalRequest;

    public bool $accept_terms = false;

    public function mount(RentalRequest $rentalRequest): void
    {
        $this->authorize('acceptAgreement', $rentalRequest);
        abort_unless($rentalRequest->isApproved(), 403);
        $this->rentalRequest = $rentalRequest;
    }

    public function acceptTerms(): void
    {
        $this->validate(['accept_terms' => ['accepted']], [
            'accept_terms.accepted' => 'You must read and accept the rental terms and agreement to continue.',
        ]);

        RentalAgreement::accept($this->rentalRequest, auth()->user());
        $this->accept_terms = false;
        $this->rentalRequest->refresh();
    }

    public function render(): View
    {
        $request = $this->rentalRequest->fresh(['listing.owner', 'renter', 'rental']);
        abort_unless($request, 404);
        $this->authorize('acceptAgreement', $request);

        return view('livewire.rental-requests.agreement', [
            'request' => $request,
            'isOwner' => auth()->id() === $request->listing->owner_id,
        ]);
    }
}
