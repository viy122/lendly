<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

new #[Layout('layouts.guest')] class extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        $this->resetErrorBag('verification');
        Session::forget('status');
        $throttleKey = 'verification:'.Auth::id();

        if (RateLimiter::tooManyAttempts($throttleKey, 1)) {
            throw ValidationException::withMessages([
                'verification' => 'Please wait '.RateLimiter::availableIn($throttleKey).' seconds before requesting another verification email.',
            ]);
        }

        RateLimiter::hit($throttleKey, 60);

        try {
            Auth::user()->sendEmailVerificationNotification();
        } catch (TransportExceptionInterface $exception) {
            report($exception);
            $this->addError('verification', 'We could not send your verification email. Please try again shortly.');

            return;
        }

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
    <div class="mb-4 text-sm text-slate-600">
        {{ __('Verify your Gmail address using the link in your email before getting started. If the message has not arrived, check your spam folder or request another verification email below.') }}
    </div>

    @if (session('status') === 'verification-mail-failed')
        <p class="mb-4 text-sm text-red-600" role="alert">
            {{ __('Your account was created, but we could not send the verification email. Please try resending it shortly.') }}
        </p>
    @endif

    <x-input-error :messages="$errors->get('verification')" class="mb-4" />

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <x-primary-button wire:click="sendVerification" wire:loading.attr="disabled">
            {{ __('Resend Verification Email') }}
        </x-primary-button>

        <button wire:click="logout" type="submit" class="underline text-sm text-slate-600 hover:text-slate-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            {{ __('Log Out') }}
        </button>
    </div>
</div>
