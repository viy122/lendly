<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route(Auth::user()->dashboardRouteName(), absolute: false), navigate: true);
    }
}; ?>

<div x-data="{ showPassword: false }">
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
                <input wire:model="form.password" id="password" x-bind:type="showPassword ? 'text' : 'password'" name="password" required autocomplete="current-password" placeholder="Enter your password" class="block w-full rounded-2xl border-slate-200 bg-slate-50/80 py-3.5 pl-12 pr-12 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 hover:border-slate-300 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100" />
                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center px-4 text-slate-400 transition hover:text-blue-600" :aria-label="showPassword ? 'Hide password' : 'Show password'">
                    <svg x-show="!showPassword" class="h-5 w-5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M2.5 10s2.75-4.5 7.5-4.5 7.5 4.5 7.5 4.5-2.75 4.5-7.5 4.5S2.5 10 2.5 10Z" stroke="currentColor" stroke-width="1.5"/><circle cx="10" cy="10" r="2" stroke="currentColor" stroke-width="1.5"/></svg>
                    <svg x-show="showPassword" x-cloak class="h-5 w-5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m3 3 14 14M8.7 5.65A8.3 8.3 0 0 1 10 5.5c4.75 0 7.5 4.5 7.5 4.5a12.7 12.7 0 0 1-2.05 2.5M11.4 14.38c-.45.08-.92.12-1.4.12-4.75 0-7.5-4.5-7.5-4.5a12.8 12.8 0 0 1 2.1-2.55" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <label for="remember" class="flex cursor-pointer items-center gap-3 text-sm text-slate-600">
            <input wire:model="form.remember" id="remember" type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500" />
            <span>Keep me logged in on this device</span>
        </label>

        <button type="submit" wire:loading.attr="disabled" class="group inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-[#075cf5] px-5 py-3.5 text-sm font-bold text-white shadow-[0_12px_28px_rgba(7,92,245,0.28)] transition duration-300 hover:-translate-y-0.5 hover:bg-blue-700 hover:shadow-[0_16px_34px_rgba(7,92,245,0.34)] focus:outline-none focus:ring-4 focus:ring-blue-200 disabled:cursor-wait disabled:opacity-70">
            <span wire:loading.remove wire:target="login">Log in to your account</span>
            <span wire:loading wire:target="login">Logging you in…</span>
            <svg wire:loading.remove wire:target="login" class="h-4 w-4 transition-transform group-hover:translate-x-1" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4.5 10h11m-4-4 4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </form>

    <div class="mt-7 border-t border-slate-100 pt-6 text-center">
        <p class="text-sm text-slate-500">New to the community? <a href="{{ route('register') }}" wire:navigate class="font-bold text-blue-600 transition hover:text-blue-800">Create a free account</a></p>
    </div>
</div>
