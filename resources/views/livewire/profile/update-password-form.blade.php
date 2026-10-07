<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<section id="profile-security" class="account-profile-section overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="profile-security-title">
    <header class="flex items-start gap-3 border-b border-slate-100 p-6 sm:p-7">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-600">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2" /><path stroke-linecap="round" d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3" /></svg>
        </span>
        <div>
            <h2 id="profile-security-title" class="text-lg font-bold text-slate-900 sm:text-xl">{{ __('Password & security') }}</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('Choose a strong password that you do not use for other accounts.') }}</p>
        </div>
    </header>

    <form wire:submit="updatePassword" class="space-y-6 p-6 sm:p-7">
        <div class="account-profile-fields account-profile-password-fields">
            <div class="account-profile-current-password">
                <x-input-label for="update_password_current_password" :value="__('Current Password')" />
                <x-text-input wire:model="current_password" id="update_password_current_password" name="current_password" type="password" class="mt-2 block min-h-11 w-full bg-slate-50/50" autocomplete="current-password" required />
                <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="update_password_password" :value="__('New Password')" />
                <x-text-input wire:model="password" id="update_password_password" name="password" type="password" class="mt-2 block min-h-11 w-full bg-slate-50/50" autocomplete="new-password" required />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
                <x-text-input wire:model="password_confirmation" id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-2 block min-h-11 w-full bg-slate-50/50" autocomplete="new-password" required />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-4 border-t border-slate-100 pt-5">
            <x-action-message on="password-updated" class="text-emerald-700" role="status">{{ __('Password updated.') }}</x-action-message>
            <x-primary-button class="min-h-11" wire:loading.attr="disabled" wire:target="updatePassword">{{ __('Update password') }}</x-primary-button>
        </div>
    </form>
</section>
