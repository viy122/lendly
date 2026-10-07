<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route(Auth::user()->dashboardRouteName(), absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <h1 class="mb-3 text-2xl font-bold text-slate-900">{{ __('Verify your email') }}</h1>
    <div class="mb-4 text-sm text-slate-600">
        @if (auth()->user()->isAdmin())
            {{ __('Verify your email using the link we emailed you to access your admin account. If you did not receive the email, you can request another below.') }}
        @else
            {{ __('Your account has been created and your details are saved. Verify your email using the link we emailed you, or choose Verify later to start using your account now. You can verify anytime from your profile.') }}
        @endif
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    @if (! auth()->user()->isAdmin())
        <a href="{{ route(auth()->user()->dashboardRouteName()) }}" wire:navigate class="mb-4 inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            {{ __('Verify later') }}
        </a>
    @endif

    <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
        <x-primary-button wire:click="sendVerification" wire:loading.attr="disabled" wire:target="sendVerification">
            {{ __('Resend Verification Email') }}
        </x-primary-button>

        <button wire:click="logout" type="button" class="underline text-sm text-slate-600 hover:text-slate-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            {{ __('Log Out') }}
        </button>
    </div>
</div>
