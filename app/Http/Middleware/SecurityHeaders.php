<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds transport and content-security hardening headers to every response.
 *
 * CSP mode — controlled by the CSP_STRICT env flag (default false):
 *
 *  • CSP_STRICT=false (default): the storefront CSP permits 'unsafe-inline'
 *    scripts/styles because the Blade views still rely on inline <script>
 *    blocks and inline style attributes. Nonce is not needed.
 *
 *  • CSP_STRICT=true: a per-request nonce is generated and injected into the
 *    response as 'Content-Security-Policy-Report-Only' is NOT used; instead
 *    the main CSP drops 'unsafe-inline' from script-src/style-src and allows
 *    only the nonce + trusted CDN origins. The nonce is shared with views via
 *    a view variable `csp_nonce` so inline scripts can carry nonce="..." .
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
     * Generate (or reuse) a per-request nonce for strict CSP mode.
     *
     * The nonce is shared with views through a view variable so that inline
     * <script> tags can carry nonce="...".
     */
    private function nonce(): string
    {
        static $nonce = null;

        if ($nonce === null) {
            $nonce = Str::random(32);
        }

        return $nonce;
    }

    /**
     * Build a single CSP header string.
     *
     * When CSP_STRICT is enabled, 'unsafe-inline' is removed from script-src
     * and style-src and replaced with the per-request nonce.
     */
    private function csp(Request $request): string
    {
        $directives = array_keys(self::CSP_DIRECTIVES);

        $strict = filter_var(env('CSP_STRICT', false), FILTER_VALIDATE_BOOLEAN);
        $nonce = $strict ? $this->nonce() : '';

        $parts = array_map(
            function (string $directive) use ($strict, $nonce): string {
                $sources = self::CSP_DIRECTIVES[$directive];

                if ($strict && in_array($directive, ['script-src', 'style-src'], true)) {
                    // Drop 'unsafe-inline' and use the nonce instead
                    $sources = array_filter($sources, fn ($s) => $s !== "'unsafe-inline'");
                    $sources = array_merge(["'nonce-{$nonce}'"], $sources);
                }

                return sprintf('%s %s', $directive, implode(' ', $sources));
            },
            $directives
        );

        $policy = implode('; ', $parts);

        // Upgrade any remaining HTTP subresource references on HTTPS pages
        if ($request->isSecure()) {
            $policy .= '; upgrade-insecure-requests';
        }

        return $policy;
    }
}
