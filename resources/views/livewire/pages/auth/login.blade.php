<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    #[Locked]
    public bool $showInterfacePicker = false;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->form->email = trim($this->form->email);
        $this->showInterfacePicker = false;
        $this->validate();

        $user = $this->form->validateCredentials();

        if (! $user->isAdmin()) {
            $this->showInterfacePicker = true;
            $this->dispatch('hide-login-password');

            return;
        }

        $this->form->authenticate();
        Session::regenerate();
        $this->form->reset('password');

        $this->redirectIntended(default: route(Auth::user()->dashboardRouteName(), absolute: false), navigate: true);
    }

    public function chooseInterface(string $interface): void
    {
        abort_unless($this->showInterfacePicker && in_array($interface, ['renter', 'owner'], true), 403);

        $this->showInterfacePicker = false;
        $this->validate();
        $this->form->authenticate();
        Session::regenerate();
        Session::forget('url.intended');
        $this->form->reset('password');

        if (! Auth::user()->isAdmin()) {
            Session::put('active_interface', $interface);
        }

        $destination = Auth::user()->isAdmin()
            ? Auth::user()->dashboardRouteName()
            : $interface.'.dashboard';

        $this->redirect(route($destination, absolute: false));
    }

    public function cancelInterfaceSelection(): void
    {
        $this->showInterfacePicker = false;
    }
}; ?>

<div x-data="{ showPassword: false }" @hide-login-password.window="showPassword = false">
    <div class="mb-8">
        <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.15em] text-blue-700 ring-1 ring-blue-100">
            <span class="h-1.5 w-1.5 rounded-full bg-blue-600"></span>
            Welcome back
        </span>
        <h1 class="mt-4 text-3xl font-extrabold tracking-[-0.04em] text-[#071a3d] sm:text-4xl">Good to see you again.</h1>
        <p class="mt-3 text-sm leading-6 text-slate-500">Log in to continue browsing nearby items, managing rentals, and earning from what you own.</p>
    </div>

    <x-auth-session-status class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" :status="session('status')" />

    <form wire:submit="login" class="space-y-5">
        <div>
            <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email address</label>
            <div class="group relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 transition group-focus-within:text-blue-600">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3 5.75A1.75 1.75 0 0 1 4.75 4h10.5A1.75 1.75 0 0 1 17 5.75v8.5A1.75 1.75 0 0 1 15.25 16H4.75A1.75 1.75 0 0 1 3 14.25v-8.5Z" stroke="currentColor" stroke-width="1.5"/><path d="m4 6 5.06 3.65a1.6 1.6 0 0 0 1.88 0L16 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </span>
                <input wire:model="form.email" id="email" type="email" name="email" required autofocus autocomplete="username" placeholder="you@example.com" class="block w-full rounded-2xl border-slate-200 bg-slate-50/80 py-3.5 pl-12 pr-4 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 hover:border-slate-300 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100" />
            </div>
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <div>
            <div class="mb-2 flex items-center justify-between gap-4">
                <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" wire:navigate class="text-xs font-semibold text-blue-600 transition hover:text-blue-800">Forgot password?</a>
                @endif
            </div>
            <div class="group relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 transition group-focus-within:text-blue-600">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><rect x="4" y="8" width="12" height="9" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M6.75 8V6.25a3.25 3.25 0 0 1 6.5 0V8M10 11.5v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </span>
                <input wire:model="form.password" id="password" type="password" x-bind:type="showPassword ? 'text' : 'password'" x-on:copy.prevent x-on:cut.prevent x-on:paste.prevent x-on:drop.prevent name="password" required autocomplete="current-password" placeholder="Enter your password" class="block w-full rounded-2xl border-slate-200 bg-slate-50/80 py-3.5 pl-12 pr-14 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 hover:border-slate-300 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100" />
                <button type="button" @click="showPassword = !showPassword" aria-label="Show password" :aria-label="showPassword ? 'Hide password' : 'Show password'" :title="showPassword ? 'Hide password' : 'Show password'" :aria-pressed="showPassword" aria-controls="password" class="absolute right-2 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-xl text-slate-500 transition hover:bg-blue-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <svg x-show="!showPassword" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.25 12s3.25-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.25 7.5-9.75 7.5S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg x-show="showPassword" x-cloak class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18M10.6 4.6A10 10 0 0 1 12 4.5c6.5 0 9.75 7.5 9.75 7.5a18 18 0 0 1-3.3 4.7M6.2 6.2A19 19 0 0 0 2.25 12s3.25 7.5 9.75 7.5a10 10 0 0 0 5.8-1.7M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <label for="remember" class="flex cursor-pointer items-center gap-3 text-sm text-slate-600">
            <input wire:model="form.remember" id="remember" type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500" />
            <span>Keep me logged in on this device</span>
        </label>

        <button type="submit" wire:loading.attr="disabled" class="group inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-[#075cf5] px-5 py-3.5 text-sm font-bold text-white shadow-[0_12px_28px_rgba(7,92,245,0.28)] transition duration-300 hover:-translate-y-0.5 hover:bg-blue-700 hover:shadow-[0_16px_34px_rgba(7,92,245,0.34)] focus:outline-none focus:ring-4 focus:ring-blue-200 disabled:cursor-wait disabled:opacity-70">
            <span wire:loading.remove wire:target="login">Continue</span>
            <span wire:loading wire:target="login">Checking your account…</span>
            <svg wire:loading.remove wire:target="login" class="h-4 w-4 transition-transform group-hover:translate-x-1" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4.5 10h11m-4-4 4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </form>

    <div class="mt-7 border-t border-slate-100 pt-6 text-center">
        <p class="text-sm text-slate-500">New to the community? <a href="{{ route('register') }}" wire:navigate class="font-bold text-blue-600 transition hover:text-blue-800">Create a free account</a></p>
    </div>

    <x-interface-picker :show="$showInterfacePicker" />
</div>
