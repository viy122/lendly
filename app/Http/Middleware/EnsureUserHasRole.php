<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /** Members use the same account, with one selected interface per session. */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user, 403);

        $allowed = collect($roles)->contains(fn (string $role) => match ($role) {
            'admin' => $user->isAdmin(),
            'owner' => $user->isOwner(),
            'renter' => $user->isRenter(),
            default => false,
        });

        abort_unless($allowed, 403, 'You are not authorized to access this page.');

        if (! $user->isAdmin() && $request->session()->has('active_interface')) {
            abort_unless(in_array($user->activeInterface(), $roles, true), 403, 'Log in to the matching interface to access this page.');
        }

        return $next($request);
    }
}
