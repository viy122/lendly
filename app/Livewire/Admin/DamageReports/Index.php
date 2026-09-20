<?php

namespace App\Livewire\Admin\DamageReports;

use App\Models\DamageReport;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $filter = 'disputed';

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $damageReports = DamageReport::query()
            ->when($this->filter !== 'all', fn ($query) => $query->where('status', $this->filter))
            ->with(['rental.listing', 'rental.owner', 'rental.renter', 'photos'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.admin.damage-reports.index', ['damageReports' => $damageReports]);
    }
}
