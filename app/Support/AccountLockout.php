<?php

namespace App\Support;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AccountLockout
{
    public static function ensureNotLocked(string $email, string $field): void
    {
        $key = self::key($email).':locked';

        if (! RateLimiter::tooManyAttempts($key, 1)) {
            return;
        }

        event(new Lockout(request()));
        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            $field => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public static function recordFailure(string $email, string $field): void
    {
        self::ensureNotLocked($email, $field);
        $key = self::key($email);

        if (RateLimiter::hit($key, 900) >= 3) {
            RateLimiter::hit($key.':locked', 900);
            RateLimiter::clear($key);
            self::ensureNotLocked($email, $field);
        }
    }

    public static function clear(string $email): void
    {
        $key = self::key($email);
        RateLimiter::clear($key);
    }

    private static function key(string $email): string
    {
        return 'account-auth:'.hash('sha256', AuthEmail::normalize($email));
    }
}
