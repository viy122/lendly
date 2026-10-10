<?php

use App\Services\PasswordResetCodes;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    public function sendPasswordResetCode(PasswordResetCodes $codes): void
    {
        $this->email = trim($this->email);
        $this->validate(['email' => ['required', 'string', 'email', 'max:255']]);
        $codes->send($this->email);

        session()->flash('status', 'If an account exists for this email, a reset code has been sent. The code expires in 10 minutes.');
        $this->redirect(route('password.reset', ['email' => $this->email], absolute: false), navigate: true);
    }
}; ?>

<div>
    <h1 class="text-xl font-semibold text-slate-900">Forgot password</h1>
    <p class="mt-2 mb-4 text-sm text-slate-600">
        Enter your account email. We will send a 6-digit code to help you choose a new password.
    </p>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="sendPasswordResetCode">
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-4">
            <a href="{{ route('login') }}" wire:navigate class="text-sm underline text-slate-600 hover:text-slate-900">Back to login</a>
            <x-primary-button wire:loading.attr="disabled">{{ __('Send reset code') }}</x-primary-button>
        </div>
    </form>
</div>
