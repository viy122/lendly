<?php

namespace App\Livewire\Identity;

use App\Models\IdentityVerificationAttempt;
use App\Models\User;
use App\Services\IdentityVerification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app')]
class Verify extends Component
{
    public string $first_name = '';

    public string $middle_name = '';

    public string $last_name = '';

    public string $suffix = '';

    public string $birth_date = '';

    public string $qr_value = '';

    public bool $consent = false;

    #[Locked]
    public ?string $attemptId = null;

    public function mount(): void
    {
        $this->member();
    }

    public function startVerification(): ?string
    {
        $member = $this->member();
        $this->validate(['consent' => ['accepted']], [
            'consent.accepted' => 'Please consent to the identity and live selfie check before continuing.',
        ]);
        $this->resetErrorBag('identity');
        $this->attemptId = app(IdentityVerification::class)->begin($member, $this->details());

        return $this->attemptId;
    }

    public function completeVerification(string $attemptId, string $sessionId): void
    {
        $member = $this->member();
        $this->validate(['consent' => ['accepted']]);
        abort_unless($this->attemptId !== null && hash_equals($this->attemptId, $attemptId), 403);
        app(IdentityVerification::class)->finish($member, $attemptId, $this->details(), $sessionId);
        $this->attemptId = null;
        $this->reset('first_name', 'middle_name', 'last_name', 'suffix', 'birth_date', 'qr_value', 'consent');
        $this->dispatch('identity-verified');
    }

    public function cancelVerification(string $attemptId): void
    {
        $member = $this->member();
        IdentityVerificationAttempt::whereKey($attemptId)->where('user_id', $member->id)
            ->whereIn('status', ['pending', 'processing'])->update(['status' => 'cancelled']);
        if ($this->attemptId === $attemptId) {
            $this->attemptId = null;
        }
    }

    private function details(): array
    {
        return $this->only(['first_name', 'middle_name', 'last_name', 'suffix', 'birth_date', 'qr_value']);
    }

    private function member(): User
    {
        $member = User::find(auth()->id());
        abort_unless($member && ! $member->isAdmin() && ! $member->isSuspended() && $member->hasVerifiedEmail(), 403);

        return $member;
    }

    public function render(): View
    {
        $member = $this->member();
        $available = app(IdentityVerification::class)->available();

        return view('livewire.identity.verify', [
            'available' => $available,
            'sdkUrl' => $available ? config('services.identity.sdk_url') : '',
            'publicKey' => $available ? config('services.identity.public_key') : '',
            'environment' => config('services.identity.environment', 'sandbox'),
            'verified' => $member->hasVerifiedIdentity(),
            'testVerified' => $member->hasTestedIdentity(),
        ]);
    }
}
