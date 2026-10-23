<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AccountLockout;
use App\Support\AuthEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $request->session()->forget(['state', 'google_oauth_interface']);
        $interface = $request->query('interface', 'renter');
        abort_unless(in_array($interface, ['renter', 'owner'], true), 403);
        if (! $this->configured()) {
            return $this->failed();
        }

        try {
            $request->session()->put('google_oauth_interface', $interface);

            return Socialite::driver('google')->scopes(['openid', 'profile', 'email'])
                ->with(['prompt' => 'select_account'])->redirect();
        } catch (Throwable) {
            $request->session()->forget(['state', 'google_oauth_interface']);

            return $this->failed();
        }
    }

    public function callback(Request $request): RedirectResponse
    {
        $interface = $request->session()->pull('google_oauth_interface');
        try {
            if (! $this->configured() || ! in_array($interface, ['renter', 'owner'], true) || $request->has('error')) {
                return $this->failed();
            }

            // Stateful Socialite consumes and validates state before exchanging the authorization code.
            $profile = Socialite::driver('google')->user();
            $id = $profile->getId();
            $email = $profile->getEmail();
            $name = $profile->getName();
            $raw = $profile->getRaw();
            if (! is_string($id) || trim($id) === '' || strlen($id) > 255
                || ! is_string($email) || strlen($email) > 255
                || ! preg_match('/\A[a-z0-9]+(?:\.[a-z0-9]+)*(?:\+[a-z0-9._-]+)?@gmail\.com\z/i', trim($email))
                || ! is_array($raw) || (($raw['email_verified'] ?? null) !== true && ($raw['verified_email'] ?? null) !== true)
                || ($name !== null && (! is_string($name) || ! mb_check_encoding($name, 'UTF-8') || mb_strlen($name) > 255))) {
                return $this->failed();
            }
            $email = AuthEmail::normalize($email);
            AccountLockout::ensureNotLocked($email, 'google');

            $user = DB::transaction(function () use ($id, $email, $name) {
                $linked = User::withTrashed()->where('google_id', $id)->first();
                // shortcut: legacy Gmail aliases require a scan; add an indexed canonical column at larger account volumes.
                $matches = User::withTrashed()->where(fn ($query) => $query
                    ->whereRaw('LOWER(email) LIKE ?', ['%@gmail.com'])
                    ->orWhereRaw('LOWER(email) LIKE ?', ['%@googlemail.com']))
                    ->get()->filter(fn (User $candidate) => AuthEmail::normalize($candidate->email) === $email);
                $ids = $matches->pluck('id');
                if ($linked) {
                    $ids->push($linked->id);
                }
                $accounts = User::withTrashed()->whereIn('id', $ids->unique()->sort()->all())->orderBy('id')->lockForUpdate()->get();
                $matches = $accounts->filter(fn (User $candidate) => AuthEmail::normalize($candidate->email) === $email);
                if ($matches->count() > 1 || ($linked && ($matches->count() !== 1 || $matches->first()->id !== $linked->id))) {
                    throw $this->rejected();
                }
                $user = $matches->first();
                if ($user) {
                    if (! $this->eligible($user) || ($user->google_id !== null && $user->google_id !== $id)) {
                        throw $this->rejected();
                    }
                    if ($user->google_id === null) {
                        $user->forceFill(['google_id' => $id])->save();
                    }
                } else {
                    $user = new User;
                    $user->forceFill([
                        'name' => trim($name ?? '') ?: 'Google member', 'email' => $email,
                        'email_verified_at' => now(), 'google_id' => $id, 'password' => Str::random(64),
                    ])->save();
                }
                $user = User::withTrashed()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                if (! $this->eligible($user) || $user->google_id !== $id || AuthEmail::normalize($user->email) !== $email) {
                    throw $this->rejected();
                }
                AccountLockout::ensureNotLocked($email, 'google');
                Auth::guard('web')->login($user);
                AccountLockout::ensureNotLocked($email, 'google');
                $user->refresh();
                if (! $this->eligible($user)) {
                    throw $this->rejected();
                }

                return $user;
            });

            $request->session()->regenerate();
            $request->session()->forget('url.intended');
            $request->session()->put('active_interface', $interface);
            AccountLockout::clear($email);

            return redirect()->route($user->hasVerifiedIdentity() ? $interface.'.dashboard' : 'identity.verify');
        } catch (Throwable) {
            // Remote exceptions can include tokens and profile data; expose only the generic sign-in error.
            if (Auth::guard('web')->check()) {
                Auth::guard('web')->logout();
            }

            return $this->failed();
        } finally {
            $request->session()->forget(['state', 'google_oauth_interface']);
        }
    }

    private function configured(): bool
    {
        foreach (['client_id', 'client_secret', 'redirect'] as $key) {
            if (! is_string(config('services.google.'.$key)) || trim(config('services.google.'.$key)) === '') {
                return false;
            }
        }

        return true;
    }

    private function eligible(User $user): bool
    {
        return ! $user->trashed() && ! $user->isSuspended() && ! $user->isAdmin() && $user->hasVerifiedEmail();
    }

    private function rejected(): ValidationException
    {
        return ValidationException::withMessages(['google' => 'Google sign-in could not be completed. Please try again or use your password.']);
    }

    private function failed(): RedirectResponse
    {
        return redirect()->route('login')->withErrors($this->rejected()->errors());
    }
}
