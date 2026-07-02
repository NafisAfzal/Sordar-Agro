<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to one or more roles.
 *
 * Usage in routes:  ->middleware('role:admin')
 *                   ->middleware('role:seller,admin')
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            abort(403, 'Your account is inactive or you are not signed in.');
        }

        if (! in_array($user->role, $roles, true)) {
            abort(403, 'You are not authorised to access this area.');
        }

        return $next($request);
    }
}
