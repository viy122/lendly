<?php

namespace App\Livewire\Admin\Payments;

use App\Models\OfflinePaymentSetting;
use App\Models\Rental;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app')]
class Settings extends Component
{
    #[Locked]
    public bool $modal = false;

    public string $method = 'bank_transfer';

    public string $instructions = '';

    public bool $enabled = false;

    public function mount(bool $modal = false): void
    {
        $this->authorize('viewAny', Rental::class);
        $this->modal = $modal;
        if ($settings = OfflinePaymentSetting::current()) {
            $this->method = $settings->method;
            $this->instructions = $settings->instructions ?? '';
            $this->enabled = $settings->enabled;
        }
    }

    public function save(): void
    {
        $this->authorize('viewAny', Rental::class);
        $this->instructions = trim($this->instructions);
        $this->validate([
            'method' => ['required', 'in:bank_transfer,cash'],
            'instructions' => ['required', 'string', 'max:3000'],
            'enabled' => ['boolean'],
        ]);
        OfflinePaymentSetting::updateOrCreate(['id' => 1], [
            'method' => $this->method, 'instructions' => $this->instructions,
            'enabled' => $this->enabled, 'updated_by' => auth()->id(),
        ]);
        session()->flash('status', 'Payment instructions saved.');
    }

    public function render()
    {
        $this->authorize('viewAny', Rental::class);

        return view('livewire.admin.payments.settings');
    }
}
