<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CspStrictTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Smoke test: with CSP_STRICT=true enabled, visiting ~10 key routes
     * should all return HTTP 200 (strict nonce-based CSP has no effect on
     * the status code — the pages still render).
     */
    public function test_csp_strict_mode_routes_return_200(): void
    {
        // Seed a minimal catalogue so dynamic routes return 200
        $category = Category::factory()->create(['slug' => 'fish']);
        $product = Product::factory()->create([
            'name' => 'Neon Tetra',
            'slug' => 'neon-tetra',
            'category_id' => $category->id,
            'status' => 'approved',
        ]);
        $product->variants()->create(['size' => 'small', 'sku' => 'NT-S', 'price' => 100, 'stock' => 5]);

        // Key routes to smoke-test under CSP_STRICT=true
        $routes = [
            '/',
            '/login',
            '/register',
            '/products',
            '/products/'.$product->slug,
            '/care-guides',
            '/community',
            '/sitemap.xml',
            '/robots.txt',
        ];

        putenv('CSP_STRICT=true');

        foreach ($routes as $uri) {
            $response = $this->get($uri);
            $this->assertTrue(
                $response->isSuccessful(),
                "Route {$uri} (HTTP {$response->getStatusCode()}) did not return 200 under CSP_STRICT"
            );
        }

        putenv('CSP_STRICT=false');
    }

    public function test_csp_strict_uses_nonce_and_drops_unsafe_inline(): void
    {
        putenv('CSP_STRICT=true');

        $response = $this->get('/');

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp);

        // In strict mode, 'unsafe-inline' should be absent for scripts
        $this->assertStringNotContainsString("script-src 'unsafe-inline'", $csp);
        $this->assertStringNotContainsString("'unsafe-inline'", $csp);

        // The nonce directive should be present
        $this->assertStringContainsString('nonce-', $csp);

        putenv('CSP_STRICT=false');
    }

    public function test_csp_default_mode_allows_unsafe_inline(): void
    {
        putenv('CSP_STRICT=false');

        $response = $this->get('/');

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp);
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net", $csp);
    }
}
