<?php

namespace App\Livewire\Admin\Payments;

use App\Models\CommissionSetting;
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

    public float $commission_rate = 10;

    public float $cancellation_fee_percentage = 20;

    public int $cancellation_window_hours = 24;

    public int $payment_window_hours = 24;

    public function mount(bool $modal = false): void
    {
        $this->authorize('viewAny', Rental::class);
        $this->modal = $modal;
        $fees = CommissionSetting::current()->refresh();
        $this->commission_rate = (float) $fees->commission_rate;
        $this->cancellation_fee_percentage = (float) $fees->cancellation_fee_percentage;
        $this->cancellation_window_hours = $fees->cancellation_window_hours;
        $this->payment_window_hours = $fees->payment_window_hours;
        if ($settings = OfflinePaymentSetting::current()) {
            $this->method = $settings->method;
            $this->instructions = $settings->instructions ?? '';
            $this->enabled = $settings->enabled;
        }
    }

    public function saveFees(): void
    {
        $this->authorize('viewAny', Rental::class);
        $data = $this->validate([
            'commission_rate' => ['required', 'numeric', 'between:0,100'],
            'cancellation_fee_percentage' => ['required', 'numeric', 'between:0,100'],
            'cancellation_window_hours' => ['required', 'integer', 'between:1,168'],
            'payment_window_hours' => ['required', 'integer', 'between:1,168'],
        ]);
        CommissionSetting::current()->update($data);
        session()->flash('status', 'Fee rules saved for future agreements. Accepted agreements keep their saved terms.');
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
