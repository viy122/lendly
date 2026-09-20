<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * 'renter' and 'owner' both just mean "any non-admin member" now (see
     * User::isRenter()/isOwner()) — routed through those methods rather than
     * a strict enum comparison so this middleware stays in sync with them
     * automatically.
     */
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

        return $next($request);
    }
}
