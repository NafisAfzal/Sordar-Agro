<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds transport and content-security hardening headers to every response.
 *
 * CSP deliberately permits:
 * - Bootstrap 5 + Icons via jsDelivr (the project's CDN assets)
 * - Google Fonts (style/font origins)
 * - 'unsafe-inline' scripts/styles because the Blade storefront still relies
 *   on inline `<script>` blocks and inline style attributes.
 *
 * HSTS is only emitted for secure requests in production; on plain HTTP or
 * local development it would break assets served over HTTP.
 */
class SecurityHeaders
{
    /** @var array<string, list<string>> */
    private const CSP_DIRECTIVES = [
        'default-src' => ["'self'"],
        'script-src' => ["'self'", "'unsafe-inline'", 'https://cdn.jsdelivr.net'],
        'style-src' => ["'self'", "'unsafe-inline'", 'https://cdn.jsdelivr.net', 'https://fonts.googleapis.com'],
        'font-src' => ["'self'", 'https://cdn.jsdelivr.net', 'https://fonts.gstatic.com', 'data:'],
        'img-src' => ["'self'", 'data:', 'https:'],
        'connect-src' => ["'self'"],
        'frame-ancestors' => ["'self'"],
        'base-uri' => ["'self'"],
        'form-action' => ["'self'"],
        'object-src' => ['none'],
        'manifest-src' => ["'self'"],
        'worker-src' => ['none'],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('X-XSS-Protection', '0');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        $response->headers->set('Content-Security-Policy', $this->csp($request));

        if ($request->isSecure() && app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    /**
     * Build a single CSP header string.
     *
     * @return string
     */
    private function csp(Request $request): string
    {
        $directives = array_keys(self::CSP_DIRECTIVES);

        $parts = array_map(
            fn (string $directive): string => sprintf('%s %s', $directive, implode(' ', self::CSP_DIRECTIVES[$directive])),
            $directives
        );

        $policy = implode('; ', $parts);

        // Upgrade any remaining HTTP subresource references on HTTPS pages.
        if ($request->isSecure()) {
            $policy .= '; upgrade-insecure-requests';
        }

        return $policy;
    }
}
