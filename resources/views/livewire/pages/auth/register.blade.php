<?php

use App\Models\User;
use App\Rules\GmailAddress;
use App\Support\AuthEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request. Every account can both rent
     * and list items — there's no role to choose anymore.
     */
    public function register(): void
    {
        $throttleKey = 'registration:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many signup attempts. Please try again in '.RateLimiter::availableIn($throttleKey).' seconds.',
            ]);
        }

        RateLimiter::hit($throttleKey, 60);
        $this->email = trim($this->email);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['bail', 'required', 'string', 'email', 'max:255', new GmailAddress],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['email'] = AuthEmail::normalize($validated['email']);
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        Auth::login($user);
        Session::regenerate();

        try {
            event(new Registered($user));
        } catch (TransportExceptionInterface $exception) {
            report($exception);
            Session::flash('status', 'verification-mail-failed');
        }

        $this->redirect(route('verification.notice', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-6 text-center">
        <h1 class="text-xl font-semibold text-slate-900">Create your account</h1>
        <p class="mt-1 text-sm text-slate-500">Join Lendly to rent items and start earning from what you own — one account, both.</p>
    </div>

    <form wire:submit="register" class="space-y-5">
        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Full name')" />
            <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" name="name" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Gmail address')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required autocomplete="username" />
            <p class="mt-1 text-xs text-slate-500">Use your own @gmail.com address. You will need to verify it before renting or listing items.</p>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Phone -->
        <div>
            <x-input-label for="phone" :value="__('Phone number (optional)')" />
            <x-text-input wire:model="phone" id="phone" class="block mt-1 w-full" type="text" name="phone" autocomplete="tel" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />

            <x-password-input wire:model="password" id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" strength />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm password')" />

            <x-password-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between pt-2">
            <a class="text-sm text-slate-500 underline hover:text-slate-800" href="{{ route('login') }}" wire:navigate>
                {{ __('Already registered?') }}
            </a>

            <x-primary-button>
                {{ __('Create account') }}
            </x-primary-button>
        </div>
    </form>
</div>
