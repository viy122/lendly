<?php

namespace App\Livewire\Rentals;

use App\Models\Rental;
use App\Services\ExchangeArrangements;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ExchangeSchedule extends Component
{
    #[Locked]
    public int $rentalId;

    public string $exchange_time = '';

    public string $address = '';

    public string $notes = '';

    public bool $editing = false;

    #[Locked]
    public ?int $editingScheduleId = null;

    public function mount(Rental $rental): void
    {
        $this->authorize('view', $rental);
        $this->rentalId = $rental->id;
    }

    public function startProposal(): void
    {
        $rental = Rental::findOrFail($this->rentalId);
        $this->authorize('coordinateExchange', $rental);
        $schedule = $rental->exchangeSchedule;
        $this->editingScheduleId = $schedule?->id;
        $this->exchange_time = $schedule?->scheduled_at->setTimezone(ExchangeArrangements::TIMEZONE)->format('Y-m-d\TH:i') ?? '';
        $this->address = $schedule?->address ?? '';
        $this->notes = $schedule?->notes ?? '';
        $this->editing = true;
        $this->resetValidation();
    }

    public function propose(): void
    {
        abort_unless($this->editing, 403);
        ExchangeArrangements::propose(Rental::findOrFail($this->rentalId), auth()->user(), [
            'exchange_time' => $this->exchange_time,
            'address' => $this->address,
            'notes' => $this->notes,
        ], $this->editingScheduleId);
        $this->reset('editing', 'exchange_time', 'address', 'notes', 'editingScheduleId');
        $this->resetValidation();
    }

    public function confirm(int $scheduleId): void
    {
        ExchangeArrangements::confirm(Rental::findOrFail($this->rentalId), auth()->user(), $scheduleId);
        $this->resetValidation();
    }

    public function render(): View
    {
        $rental = Rental::with('exchangeSchedule.proposer')->findOrFail($this->rentalId);
        $this->authorize('view', $rental);

        return view('livewire.rentals.exchange-schedule', [
            'rental' => $rental,
            'schedule' => $rental->exchangeSchedule,
            'canArrange' => auth()->user()->can('coordinateExchange', $rental),
            'isOwner' => auth()->id() === $rental->owner_id,
        ]);
    }
}
