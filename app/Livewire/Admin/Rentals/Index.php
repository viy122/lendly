<?php

namespace App\Livewire\Admin\Rentals;

use App\Enums\RentalStatus;
use App\Models\Rental;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    #[Url]
    public string $search = '';

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    private function baseQuery()
    {
        return Rental::query()
            ->when($this->status, fn ($query) => $query->withCurrentStatus($this->status))
            ->when($this->search, fn ($query) => $query->where(fn ($q) => $q
                ->whereHas('listing', fn ($lq) => $lq->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('owner', fn ($oq) => $oq->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('renter', fn ($rq) => $rq->where('name', 'like', "%{$this->search}%"))
            ));
    }

    public function render(): View
    {
        $rentals = $this->baseQuery()
            ->with(['listing', 'owner', 'renter'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('livewire.admin.rentals.index', [
            'rentals' => $rentals,
            'statuses' => RentalStatus::cases(),
            'totalTransactionValue' => $this->baseQuery()->whereNotNull('paid_at')->sum('total_amount'),
        ]);
    }
}
