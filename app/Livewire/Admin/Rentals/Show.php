<?php

namespace App\Livewire\Admin\Rentals;

use App\Enums\RentalAdminActionType;
use App\Models\Rental;
use App\Services\CancellationPolicy;
use App\Services\RentalAdministration;
use App\Services\RentalLifecycle;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Show extends Component
{
    use WithPagination;

    #[Locked]
    public int $rentalId;

    public string $reason = '';

    public string $note = '';

    public bool $funds_received = false;

    public function mount(Rental $rental): void
    {
        $this->authorize('manage', $rental);
        $this->rentalId = $rental->id;
    }

    public function cancelBooking(): void
    {
        $this->perform(RentalAdminActionType::BookingCancelled);
    }

    public function completeInspection(): void
    {
        $this->perform(RentalAdminActionType::InspectionCompleted);
    }

    public function releaseDeposit(): void
    {
        $this->perform(RentalAdminActionType::DepositReleased);
    }

    public function addNote(): void
    {
        $this->perform(RentalAdminActionType::NoteAdded);
    }

    public function verifyPayment(int $submissionId): void
    {
        $this->perform(RentalAdminActionType::PaymentVerified, $submissionId);
    }

    public function rejectPayment(int $submissionId): void
    {
        $this->perform(RentalAdminActionType::PaymentRejected, $submissionId);
    }

    private function perform(RentalAdminActionType $action, ?int $submissionId = null): void
    {
        $rental = Rental::findOrFail($this->rentalId);
        $this->authorize($action->ability(), $rental);
        $field = $action === RentalAdminActionType::NoteAdded ? 'note' : 'reason';
        $this->$field = trim($this->$field);
        $this->validate([$field => ['required', 'string', 'max:1000']]);
        if ($action === RentalAdminActionType::PaymentVerified) {
            $this->validate(['funds_received' => ['accepted']]);
        }

        RentalAdministration::perform($rental, auth()->user(), $action, $this->$field, $submissionId, $this->funds_received);

        $this->reset($field);
        $this->reset('funds_received');
        $this->resetPage();
        session()->flash('status', $action->label().'.');
    }

    public function render(): View
    {
        $rental = Rental::findOrFail($this->rentalId);
        $this->authorize('manage', $rental);
        RentalLifecycle::synchronizeRentalStatus($rental);
        $rental->refresh()->load([
            'listing.images', 'owner', 'renter', 'rentalRequest', 'payment', 'securityDeposit',
            'exchangeSchedule.proposer', 'conditionRecords.photos', 'conditionRecords.recordedBy',
            'damageReport.photos', 'disputes.raisedBy', 'disputes.resolvedBy', 'reviews',
            'paymentSubmissions.reviewer',
        ]);

        return view('livewire.admin.rentals.show', [
            'rental' => $rental,
            'cancellation' => $rental->isCancellableByRenter() ? CancellationPolicy::evaluate($rental) : null,
            'actions' => $rental->adminActions()->with('administrator')->orderByDesc('id')->paginate(10),
        ]);
    }
}
