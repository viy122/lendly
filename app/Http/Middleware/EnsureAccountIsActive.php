<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($user->trashed() || $user->isSuspended())) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => $user->trashed()
                    ? 'This account has been closed.'
                    : 'Your account has been suspended. Please contact support for assistance.',
            ]);
        }

        if ($user && $user->google_id !== null) {
            $guard = Auth::guard('web');
            $credentialSnapshot = $request->session()->get('password_hash_web');

            if (! is_string($credentialSnapshot) || ! hash_equals($guard->hashPasswordForCookie($user->getAuthPassword()), $credentialSnapshot)) {
                $guard->logoutCurrentDevice();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Your sign-in has changed. Please sign in again.',
                ]);
            }
        }

        return $next($request);
    }
}
