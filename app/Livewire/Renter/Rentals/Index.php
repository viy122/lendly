<?php

namespace App\Livewire\Renter\Rentals;

use App\Models\Rental;
use App\Services\RentalLifecycle;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public function render(): View
    {
        $query = Rental::query()->where('renter_id', auth()->id());
        RentalLifecycle::markOverdueRentals(clone $query);

        $rentals = $query
            ->with(['listing.images', 'owner'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.renter.rentals.index', ['rentals' => $rentals]);
    }
}
