<?php

namespace App\Livewire\Renter\Rentals;

use App\Livewire\Concerns\FiltersRecords;
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
    use FiltersRecords;
    use WithPagination;

    #[Url]
    public string $filter = 'all';

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Rental::query()->where('renter_id', auth()->id());
        RentalLifecycle::markOverdueRentals(clone $query);

        $rentals = $this->filterRecords($query
            ->when($this->filter !== 'all', fn ($query) => $query->withCurrentStatus($this->filter))
            ->with(['listing.images', 'owner', 'paymentSubmissions'])
            ->orderByDesc('created_at')
            ->orderByDesc('id'), ['id'], ['listing' => ['name'], 'owner' => ['name']])->paginate(10);

        return view('livewire.renter.rentals.index', ['rentals' => $rentals]);
    }
}
