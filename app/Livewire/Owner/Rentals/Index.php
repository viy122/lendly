<?php

namespace App\Livewire\Owner\Rentals;

use App\Models\Rental;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $filter = 'all';

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $rentals = Rental::query()
            ->where('owner_id', auth()->id())
            ->when($this->filter !== 'all', fn ($query) => $query->where('status', $this->filter))
            ->with(['listing.images', 'renter'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.owner.rentals.index', ['rentals' => $rentals]);
    }
}
