<?php

use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\IdleSession;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Route-level aliases used across the app.
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'password.changed' => EnsurePasswordChanged::class,
        ]);

        // Conservative hardening headers on every response.
        $middleware->append(SecurityHeaders::class);

        // Trust the reverse proxy only when explicitly configured. Railway
        // terminates TLS and forwards X-Forwarded-* headers; without this the
        // app sees the proxy IP and never considers requests secure.
        if ($trustedProxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: $trustedProxies);
        }

        // Invalidate stale sessions after a password change.
        $middleware->web(append: [
            AuthenticateSession::class,
            IdleSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
