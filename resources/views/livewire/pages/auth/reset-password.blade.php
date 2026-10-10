<?php

use App\Services\PasswordResetCodes;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';
    public string $code = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->email = request()->string('email')->toString();
    }

    public function resetPassword(PasswordResetCodes $codes): void
    {
        $this->email = trim($this->email);
        $this->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'code' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            'password' => ['required', 'string', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $codes->reset($this->email, $this->code, $this->password);
        $this->reset('code', 'password', 'password_confirmation');
        session()->flash('status', 'Your password has been reset. Please log in with your new password.');
        $this->redirectRoute('login', navigate: true);
    }
}; ?>

<div>
    <h1 class="text-xl font-semibold text-slate-900">Reset password</h1>
    <p class="mt-2 mb-4 text-sm text-slate-600">Enter the code from your email within 10 minutes. After 3 incorrect passwords or codes, your account is locked for 15 minutes.</p>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="resetPassword" class="space-y-4">
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="code" :value="__('6-digit reset code')" />
            <x-text-input wire:model="code" id="code" class="block mt-1 w-full" type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="one-time-code" required autofocus aria-describedby="code-help" />
            <p id="code-help" class="mt-1 text-xs text-slate-500">Use the most recent code sent to your email.</p>
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('New password')" />
            <x-password-input wire:model="password" id="password" class="block mt-1 w-full" name="password" required autocomplete="new-password" strength />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm password')" />
            <x-password-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('password.request') }}" wire:navigate class="text-sm underline text-slate-600 hover:text-slate-900">Request a new code</a>
            <x-primary-button wire:loading.attr="disabled">{{ __('Reset password') }}</x-primary-button>
        </div>
    </form>
</div>
