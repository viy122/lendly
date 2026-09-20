<?php

namespace App\Livewire\Renter\Rentals;

use App\Models\Rental;
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
        $rentals = Rental::query()
            ->where('renter_id', auth()->id())
            ->with(['listing.images', 'listing.owner'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.renter.rentals.index', ['rentals' => $rentals]);
    }
}
