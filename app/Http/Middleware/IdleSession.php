<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idle session middleware.
 *
 * - Admin and seller roles: 30-minute idle timeout + 12-hour absolute cap.
 * - Customer role: idle timeout is NOT enforced (session remains active).
 * - On idle expiry: logout and redirect to login.
 * - On absolute cap expiry: logout and redirect to login.
 *
 * The middleware stamps a per-request `last_activity` timestamp in the session
 * on every request. On subsequent requests, if the elapsed time since
 * `last_activity` exceeds 30 minutes for admin/seller roles, the user is
 * logged out. An absolute 12-hour cap applies regardless of role activity.
 *
 * Only triggers when the relevant session key IS present: a fresh login (no
 * prior `last_activity` or `session_start`) is never treated as expired.
 */
class IdleSession
{
    /** @var int|float Idle timeout in minutes (30) */
    protected $idleMinutes = 30;

    /** @var int|float Absolute session cap in minutes (720 = 12h) */
    protected $capMinutes = 720;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();
        $now = time();

        // Check absolute 12-h cap (all roles). Only enforce if a session_start
        // was recorded (i.e. this is not the first request after login).
        $sessionStart = $session->get('session_start');
        if ($sessionStart !== null && ($now - $sessionStart) / 60 >= $this->capMinutes) {
            Auth::logout();
            $session->flush();

            return redirect()->route('login')->with('error', 'Your session has expired due to inactivity duration limit.');
        }

        // Idle timeout only for admin/seller roles, BEFORE stamping.
        if (Auth::check() && in_array(Auth::user()->role, ['admin', 'seller'], true)) {
            $lastActivity = $session->get('last_activity');

            // Only enforce idle timeout if a prior activity stamp exists.
            if ($lastActivity !== null && ($now - $lastActivity) / 60 >= $this->idleMinutes) {
                Auth::logout();
                $session->flush();

                return redirect()->route('login')->with('error', 'Your session has expired due to inactivity.');
            }
        }

        // Stamp last-activity timestamp on every request (after checks).
        // This captures the request timestamp for the NEXT request's idle calc.
        $session->put('last_activity', $now);

        // Record session start time on first request (defensive; login flow
        // ideally sets this, but we keep the middleware self-contained).
        if ($session->get('session_start') === null) {
            $session->put('session_start', $now);
        }

        return $next($request);
    }
}
