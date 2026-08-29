<?php

namespace Tests\Feature;

use App\Models\CareGuide;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_includes_home_page(): void
    {
        $response = $this->get('/sitemap.xml');
        $response->assertOk();
        $response->assertSee(route('home'));
    }

    public function test_sitemap_includes_products_index(): void
    {
        $response = $this->get('/sitemap.xml');
        $response->assertSee(route('products.index'));
    }

    public function test_sitemap_includes_care_guides_index(): void
    {
        $response = $this->get('/sitemap.xml');
        $response->assertSee(route('care.index'));
    }

    public function test_sitemap_includes_community_index(): void
    {
        $response = $this->get('/sitemap.xml');
        $response->assertSee(route('community.index'));
    }

    public function test_sitemap_includes_approved_products(): void
    {
        $product = Product::factory()->create(['status' => 'approved']);
        $product->variants()->create(['size' => 'standard', 'sku' => 'SKU1', 'price' => 100, 'stock' => 5]);

        $response = $this->get('/sitemap.xml');
        $response->assertSee(route('products.show', $product));
    }

    public function test_sitemap_excludes_pending_products(): void
    {
        $product = Product::factory()->create(['status' => 'pending']);
        $product->variants()->create(['size' => 'standard', 'sku' => 'SKU1', 'price' => 100, 'stock' => 5]);

        $response = $this->get('/sitemap.xml');
        $response->assertDontSee(route('products.show', $product));
    }

    public function test_sitemap_includes_published_care_guides(): void
    {
        $guide = CareGuide::create([
            'title' => 'Sitemap Guide',
            'slug' => 'sitemap-guide',
            'content' => 'Guide content.',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/sitemap.xml');
        $response->assertSee(route('care.show', $guide));
    }

    public function test_sitemap_excludes_unpublished_care_guides(): void
    {
        $guide = CareGuide::create([
            'title' => 'Draft Sitemap Guide',
            'slug' => 'draft-sitemap-guide',
            'content' => 'Guide content.',
            'published_at' => null,
        ]);

        $response = $this->get('/sitemap.xml');
        $response->assertDontSee(route('care.show', $guide));
    }

    public function test_sitemap_returns_xml_content_type(): void
    {
        $response = $this->get('/sitemap.xml');
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function test_robots_txt_contains_sitemap_directive(): void
    {
        $response = $this->get('/robots.txt');
        $response->assertSee('Sitemap: ' . route('sitemap.index'));
    }

    public function test_robots_txt_disallows_all_private_paths(): void
    {
        $response = $this->get('/robots.txt');
        $content = $response->getContent();

        $disallowedPaths = ['/admin', '/seller', '/cart', '/checkout', '/payment', '/orders', '/wishlist', '/login', '/register'];
        foreach ($disallowedPaths as $path) {
            $this->assertStringContainsString("Disallow: {$path}", $content);
        }
    }

    public function test_robots_txt_allows_public_paths(): void
    {
        $response = $this->get('/robots.txt');
        $content = $response->getContent();

        // Verify no "Disallow: /" at end of line (which would block everything)
        $lines = explode("\n", $content);
        $blockAllLine = collect($lines)->contains(fn ($line) => trim($line) === 'Disallow: /');
        $this->assertFalse($blockAllLine, 'robots.txt must not contain a blanket Disallow: /');
    }
}
