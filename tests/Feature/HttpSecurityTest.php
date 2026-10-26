<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class HttpSecurityTest extends TestCase
{
    public function test_csp_allows_storefront_assets_but_blocks_arbitrary_origins(): void
    {
        $response = $this->get('/');

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net", $csp);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com", $csp);
        $this->assertStringContainsString('font-src \'self\' https://cdn.jsdelivr.net https://fonts.gstatic.com data:', $csp);
        $this->assertStringContainsString("img-src 'self' data: https:", $csp);
        $this->assertStringContainsString("connect-src 'self'", $csp);
        $this->assertStringContainsString('object-src none', $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);

        // No origin may host scripts for this page except self plus the CDN.
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringContainsString('https://cdn.jsdelivr.net', $csp);
    }

    public function test_standard_hardening_headers_are_present(): void
    {
        $response = $this->get('/');

        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertSame('camera=(), microphone=(), geolocation=()', $response->headers->get('Permissions-Policy'));
        $this->assertSame('0', $response->headers->get('X-XSS-Protection'));
        $this->assertSame('same-origin', $response->headers->get('Cross-Origin-Opener-Policy'));
        $this->assertSame('same-origin', $response->headers->get('Cross-Origin-Resource-Policy'));
        $this->assertSame('none', $response->headers->get('X-Permitted-Cross-Domain-Policies'));
    }

    public function test_hsts_is_only_emitted_for_secure_production_requests(): void
    {
        $middleware = new SecurityHeaders;
        $next = fn (Request $request): Response => new Response;

        // Non-secure production request — no HSTS.
        $response = $middleware->handle(Request::create('http://example.com/'), $next);
        $this->assertNull($response->headers->get('Strict-Transport-Security'));

        // Secure but non-production (testing) request — no HSTS.
        $response = $middleware->handle(Request::create('https://example.com/'), $next);
        $this->assertNull($response->headers->get('Strict-Transport-Security'));

        // Secure production request — HSTS with a one-year max age.
        $this->app->instance('env', 'production');
        config(['app.env' => 'production']);
        $response = $middleware->handle(Request::create('https://example.com/'), $next);
        $this->assertSame('max-age=31536000; includeSubDomains', $response->headers->get('Strict-Transport-Security'));
    }
}
