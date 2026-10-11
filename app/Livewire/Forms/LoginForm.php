<?php

namespace App\Livewire\Forms;

use App\Models\User;
use App\Support\AccountLockout;
use App\Support\AuthEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * Check credentials without starting an authenticated session.
     *
     * @throws ValidationException
     */
    public function validateCredentials(): User
    {
        AccountLockout::ensureNotLocked($this->email, 'form.email');
        $email = AuthEmail::findUser($this->email)?->email ?? AuthEmail::normalize($this->email);

        $guard = Auth::guard('web');

        if (! $guard->validate(['email' => $email, 'password' => $this->password])) {
            AccountLockout::recordFailure($this->email, 'form.email');

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        AccountLockout::ensureNotLocked($this->email, 'form.email');
        $user = $guard->getLastAttempted();

        if ($user->isSuspended()) {
            throw ValidationException::withMessages([
                'form.email' => 'Your account has been suspended. Please contact support for assistance.',
            ]);
        }

        return $user;
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        AccountLockout::ensureNotLocked($this->email, 'form.email');
        $email = AuthEmail::findUser($this->email)?->email ?? AuthEmail::normalize($this->email);

        if (! Auth::attemptWhen(['email' => $email, 'password' => $this->password], function () {
            AccountLockout::ensureNotLocked($this->email, 'form.email');

            return true;
        }, $this->remember)) {
            AccountLockout::recordFailure($this->email, 'form.email');

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        if (Auth::user()->isSuspended()) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'form.email' => 'Your account has been suspended. Please contact support for assistance.',
            ]);
        }

        AccountLockout::clear($this->email);
    }
}
