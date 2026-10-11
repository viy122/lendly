<?php

namespace App\Livewire\Owner\Rentals;

use App\Models\Rental;
use App\Services\RentalLifecycle;
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
    public string $filter = 'all';

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Rental::query()->where('owner_id', auth()->id());
        RentalLifecycle::markOverdueRentals(clone $query);

        $rentals = $query
            ->when($this->filter !== 'all', fn ($query) => $query->where('status', $this->filter))
            ->with(['listing.images', 'renter'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.owner.rentals.index', ['rentals' => $rentals]);
    }
}
