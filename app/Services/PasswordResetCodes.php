<?php

namespace App\Services;

use App\Notifications\PasswordResetCode;
use App\Support\AccountLockout;
use App\Support\AuthEmail;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetCodes
{
    public function send(string $email): void
    {
        $suppliedEmail = $email;
        $email = AuthEmail::normalize($email);
        AccountLockout::ensureNotLocked($email, 'email');

        $emailKey = 'reset-send:'.hash('sha256', $email);
        $ipKey = 'reset-send-ip:'.request()->ip();
        if (RateLimiter::tooManyAttempts($emailKey, 1) || RateLimiter::tooManyAttempts($ipKey, 5)) {
            throw ValidationException::withMessages(['email' => 'Please wait a minute before requesting another code.']);
        }

        RateLimiter::hit($emailKey, 60);
        RateLimiter::hit($ipKey, 60);

        $user = AuthEmail::findUser($suppliedEmail);
        if (! $user) {
            return;
        }

        try {
            DB::transaction(function () use ($user) {
                $user->newQuery()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                $tokens = DB::table(config('auth.passwords.users.table'));
                $previous = (clone $tokens)->where('email', $user->email)->first();
                do {
                    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                } while ($previous && Hash::check($code, $previous->token));

                $tokens->updateOrInsert(['email' => $user->email], [
                    'token' => Hash::make($code),
                    'created_at' => now(),
                ]);
                Notification::send($user, new PasswordResetCode($code));
            });
        } catch (\Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages([
                'email' => 'We could not send the email. Please wait a minute and try again, or contact support.',
            ]);
        }
    }

    public function reset(string $email, string $code, string $password): void
    {
        $suppliedEmail = $email;
        $email = AuthEmail::normalize($email);
        AccountLockout::ensureNotLocked($email, 'code');
        $user = AuthEmail::findUser($suppliedEmail);

        $error = DB::transaction(function () use ($user, $email, $code, $password) {
            if ($user) {
                $user->newQuery()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            }
            AccountLockout::ensureNotLocked($email, 'code');
            $token = $user ? DB::table(config('auth.passwords.users.table'))
                ->where('email', $user->email)->lockForUpdate()->first() : null;

            $status = $token && $token->created_at && Carbon::parse($token->created_at)->addMinutes(10)->isFuture()
                ? Password::reset([
                    'email' => $user->email,
                    'token' => $code,
                    'password' => $password,
                ], function ($user, $password) use ($email) {
                    AccountLockout::ensureNotLocked($email, 'code');
                    $user->forceFill([
                        'password' => Hash::make($password),
                        'remember_token' => Str::random(60),
                    ])->save();

                    if (config('session.driver') === 'database') {
                        DB::connection(config('session.connection'))->table(config('session.table'))
                            ->where('user_id', $user->id)->delete();
                    }

                    event(new PasswordReset($user));
                }) : Password::INVALID_TOKEN;
            if ($status !== Password::PASSWORD_RESET) {
                try {
                    AccountLockout::recordFailure($email, 'code');
                } catch (ValidationException $exception) {
                    return $exception;
                }

                // Database-backed failure counts must commit before reporting validation errors.
                return ValidationException::withMessages(['code' => 'The code is invalid or expired. Request a new code if needed.']);
            }

            AccountLockout::clear($email);

            return null;
        });

        if ($error) {
            throw $error;
        }
    }
}
