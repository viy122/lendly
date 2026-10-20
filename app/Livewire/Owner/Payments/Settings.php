<?php

namespace App\Livewire\Owner\Payments;

use App\Models\OwnerPaymentSetting;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Settings extends Component
{
    use WithFileUploads;

    public string $provider = 'maya';

    public string $account_name = '';

    public string $instructions = '';

    public bool $enabled = false;

    public $qr_image = null;

    public function mount(): void
    {
        $member = $this->member();
        if ($settings = OwnerPaymentSetting::currentFor($member->id)) {
            $this->provider = $settings->provider;
            $this->account_name = $settings->account_name;
            $this->instructions = $settings->instructions ?? '';
            $this->enabled = $settings->enabled;
        }
    }

    public function save(): void
    {
        $this->member();
        $this->account_name = trim($this->account_name);
        $this->instructions = trim($this->instructions);
        $validated = $this->validate([
            'provider' => ['required', 'in:maya,gcash'],
            'account_name' => ['required', 'string', 'max:120'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'enabled' => ['boolean'],
            'qr_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'mimetypes:image/jpeg,image/png', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
        ]);
        unset($validated['qr_image']);
        $validated['instructions'] = $validated['instructions'] ?: null;
        $storedPath = null;
        $oldPath = null;

        try {
            DB::transaction(function () use ($validated, &$storedPath, &$oldPath) {
                $member = $this->member(lock: true);
                $settings = OwnerPaymentSetting::where('user_id', $member->id)->lockForUpdate()->first()
                    ?? new OwnerPaymentSetting(['user_id' => $member->id]);
                $oldPath = $settings->qr_path;

                if ($this->qr_image) {
                    $storedPath = $this->qr_image->store('owner-payment-qr', 'local');
                    if (! $storedPath) {
                        throw ValidationException::withMessages(['qr_image' => 'Your QR image could not be saved. Please try again.']);
                    }
                    $settings->qr_path = $storedPath;
                }

                if ($validated['enabled'] && (! $settings->qr_path || ! Storage::disk('local')->exists($settings->qr_path))) {
                    throw ValidationException::withMessages(['qr_image' => 'Upload your payment QR image before enabling e-wallet payments.']);
                }

                $settings->fill($validated)->save();
            });
        } catch (\Throwable $exception) {
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }
            throw $exception;
        }

        if ($storedPath && $oldPath && $oldPath !== $storedPath) {
            OwnerPaymentSetting::deleteUnreferencedQr($oldPath);
        }
        $this->reset('qr_image');
        session()->flash('status', 'Payment QR settings saved.');
    }

    public function removeQr(): void
    {
        $oldPath = DB::transaction(function () {
            $member = $this->member(lock: true);
            $settings = OwnerPaymentSetting::where('user_id', $member->id)->lockForUpdate()->first();
            if (! $settings) {
                return null;
            }
            $oldPath = $settings->qr_path;
            $settings->update(['qr_path' => null, 'enabled' => false]);

            return $oldPath;
        });

        if ($oldPath) {
            OwnerPaymentSetting::deleteUnreferencedQr($oldPath);
        }
        $this->reset('qr_image');
        $this->enabled = false;
        session()->flash('status', 'Payment QR removed. E-wallet payments are disabled for new payment instructions.');
    }

    private function member(bool $lock = false): User
    {
        $query = User::query();
        if ($lock) {
            $query->lockForUpdate();
        }
        $member = $query->find(auth()->id());
        abort_unless($member && ! $member->isAdmin() && ! $member->isSuspended() && $member->hasVerifiedEmail(), 403);

        return $member;
    }

    public function render(): View
    {
        $member = $this->member();

        return view('livewire.owner.payments.settings', ['settings' => OwnerPaymentSetting::currentFor($member->id)]);
    }
}
