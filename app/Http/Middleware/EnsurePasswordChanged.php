<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sellers provisioned by an admin receive a temporary password and must
 * change it before using the rest of the app. This middleware funnels them
 * to the change-password screen until they do.
 */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password
            && ! $request->routeIs('password.change', 'password.change.update', 'logout')) {
            return redirect()->route('password.change')
                ->with('warning', 'Please set a new password before continuing.');
        }

        return $next($request);
    }
}
