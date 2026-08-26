<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private function setUpCatalogue(): array
    {
        $fishCategory = Category::factory()->create(['name' => 'Fish', 'slug' => 'fish']);
        $plantCategory = Category::factory()->create(['name' => 'Plants', 'slug' => 'plants']);

        // Product A: Peaceful fish, small tank, cheap
        $productA = Product::factory()->create([
            'name'        => 'Neon Tetra',
            'category_id' => $fishCategory->id,
            'status'      => 'approved',
            'temperament' => 'peaceful',
            'min_tank_size_litres' => 20,
        ]);
        $productA->variants()->create(['size' => 'small', 'sku' => 'NT1', 'price' => 100, 'stock' => 10]);
        $productA->variants()->create(['size' => 'medium', 'sku' => 'NT2', 'price' => 150, 'stock' => 5]);
        $productA->variants()->create(['size' => 'large', 'sku' => 'NT3', 'price' => 200, 'stock' => 2]);

        // Product B: Aggressive fish, larger tank, mid price
        $productB = Product::factory()->create([
            'name'        => 'Oscar Cichlid',
            'category_id' => $fishCategory->id,
            'status'      => 'approved',
            'temperament' => 'aggressive',
            'min_tank_size_litres' => 200,
        ]);
        $productB->variants()->create(['size' => 'small', 'sku' => 'OC1', 'price' => 300, 'stock' => 3]);
        $productB->variants()->create(['size' => 'medium', 'sku' => 'OC2', 'price' => 450, 'stock' => 2]);
        $productB->variants()->create(['size' => 'large', 'sku' => 'OC3', 'price' => 600, 'stock' => 1]);

        // Product C: Plant, no temperament, standard variant
        $productC = Product::factory()->create([
            'name'        => 'Java Fern',
            'category_id' => $plantCategory->id,
            'status'      => 'approved',
            'temperament' => null,
            'min_tank_size_litres' => null,
        ]);
        $productC->variants()->create(['size' => 'standard', 'sku' => 'JF1', 'price' => 180, 'stock' => 40]);

        // Product D: Pending (not approved) - should not appear
        $productD = Product::factory()->create([
            'name'        => 'Pending Product',
            'category_id' => $fishCategory->id,
            'status'      => 'pending',
            'temperament' => 'peaceful',
            'min_tank_size_litres' => 20,
        ]);
        $productD->variants()->create(['size' => 'standard', 'sku' => 'PP1', 'price' => 100, 'stock' => 5]);

        // Product E: Out of stock
        $productE = Product::factory()->create([
            'name'        => 'Out of Stock Fish',
            'category_id' => $fishCategory->id,
            'status'      => 'approved',
            'temperament' => 'peaceful',
            'min_tank_size_litres' => 20,
        ]);
        $productE->variants()->create(['size' => 'standard', 'sku' => 'OS1', 'price' => 250, 'stock' => 0]);

        return [$productA, $productB, $productC, $productD, $productE];
    }

    public function test_catalogue_shows_only_approved_products(): void
    {
        [$productA, , , $productD] = $this->setUpCatalogue();

        $response = $this->get('/products');

        $response->assertOk();
        $response->assertSee($productA->name);
        $response->assertDontSee($productD->name);
    }

    public function test_search_by_keyword_matches_name_and_description(): void
    {
        [$productA, $productB] = $this->setUpCatalogue();

        $response = $this->get('/products?q=Neon');

        $response->assertOk();
        $response->assertSee($productA->name);
        $response->assertDontSee($productB->name);
    }

    public function test_search_is_case_insensitive(): void
    {
        [$productA] = $this->setUpCatalogue();

        $response = $this->get('/products?q=neon');

        $response->assertOk();
        $response->assertSee($productA->name);
    }

    public function test_category_filter_by_slug(): void
    {
        [$productA, , $productC] = $this->setUpCatalogue();

        $response = $this->get('/products?category=fish');

        $response->assertOk();
        $response->assertSee($productA->name);
        $response->assertDontSee($productC->name);
    }

    public function test_temperament_filter_peaceful(): void
    {
        [$productA, $productB] = $this->setUpCatalogue();

        $response = $this->get('/products?temperament=peaceful');

        $response->assertOk();
        $response->assertSee($productA->name);
        $response->assertDontSee($productB->name);
    }

    public function test_temperament_filter_aggressive(): void
    {
        [$productA, $productB] = $this->setUpCatalogue();

        $response = $this->get('/products?temperament=aggressive');

        $response->assertOk();
        $response->assertSee($productB->name);
        $response->assertDontSee($productA->name);
    }

    public function test_tank_size_filter_matches_products_requiring_less_or_equal_litres(): void
    {
        [$productA, $productB] = $this->setUpCatalogue();

        // Tank size 50L should show Neon Tetra (needs 20L) but not Oscar (needs 200L)
        $response = $this->get('/products?tank_size=50');

        $response->assertOk();
        $response->assertSee($productA->name);
        $response->assertDontSee($productB->name);
    }

    public function test_tank_size_filter_large_enough_shows_all_fish(): void
    {
        [$productA, $productB] = $this->setUpCatalogue();

        $response = $this->get('/products?tank_size=300');

        $response->assertOk();
        $response->assertSee($productA->name);
        $response->assertSee($productB->name);
    }

    public function test_min_price_filter_uses_starting_price(): void
    {
        // Product A: variants 100, 150, 200 -> starting_price = 100
        // Product B: variants 300, 450, 600 -> starting_price = 300
        // Product C: standard 180 -> starting_price = 180
        [$productA, $productB, $productC] = $this->setUpCatalogue();

        // min_price=200 should show B (300) and C (180 >= 200? No, 180 < 200)
        // Wait: min_price filters products where NO variant is below min_price
        // Product A has variant at 100 (< 200) -> excluded
        // Product B has all variants >= 300 -> included
        // Product C has variant at 180 (< 200) -> excluded
        $response = $this->get('/products?min_price=200');

        $response->assertOk();
        $response->assertSee($productB->name);
        $response->assertDontSee($productA->name);
        $response->assertDontSee($productC->name);
    }

    public function test_max_price_filter_shows_products_with_at_least_one_variant_at_or_below_max(): void
    {
        [$productA, $productB, $productC] = $this->setUpCatalogue();

        // max_price=200 should show A (has 100, 150, 200) and C (180) but not B (min 300)
        $response = $this->get('/products?max_price=200');

        $response->assertOk();
        $response->assertSee($productA->name);
        $response->assertSee($productC->name);
        $response->assertDontSee($productB->name);
    }

    public function test_price_range_filter_combined(): void
    {
        [$productA, $productB, $productC] = $this->setUpCatalogue();

        // 150-400: A has 150,200; C has 180; B min is 300
        // But min_price=150 excludes A (has variant at 100)
        // max_price=400 includes B (300, 450 - has 300 <= 400)
        // So B and C should appear
        $response = $this->get('/products?min_price=150&max_price=400');

        $response->assertOk();
        $response->assertSee($productB->name);
        $response->assertSee($productC->name);
        $response->assertDontSee($productA->name);
    }

    public function test_availability_filter_in_stock_excludes_out_of_stock_products(): void
    {
        [$productA, , , , $productE] = $this->setUpCatalogue();

        $response = $this->get('/products?availability=in_stock');

        $response->assertOk();
        $response->assertSee($productA->name);
        $response->assertDontSee($productE->name);
    }

    public function test_sort_by_name(): void
    {
        [$productA, $productB, $productC] = $this->setUpCatalogue();

        $response = $this->get('/products?sort=name');

        $response->assertOk();
        $content = $response->getContent();

        // All three products should appear
        $this->assertStringContainsString($productA->name, $content);
        $this->assertStringContainsString($productB->name, $content);
        $this->assertStringContainsString($productC->name, $content);
    }

    public function test_sort_by_newest_default(): void
    {
        [$productA, $productB, $productC] = $this->setUpCatalogue();

        $response = $this->get('/products');

        $response->assertOk();
        $content = $response->getContent();

        // All three products should appear
        $this->assertStringContainsString($productA->name, $content);
        $this->assertStringContainsString($productB->name, $content);
        $this->assertStringContainsString($productC->name, $content);
    }

    public function test_combined_filters(): void
    {
        [$productA, $productB, $productC] = $this->setUpCatalogue();

        // peaceful + tank_size=50 + max_price=200
        $response = $this->get('/products?temperament=peaceful&tank_size=50&max_price=200');

        $response->assertOk();
        $response->assertSee($productA->name);
        $response->assertDontSee($productB->name);
        $response->assertDontSee($productC->name); // not a fish, no temperament
    }

    public function test_suggestions_endpoint_returns_json_for_valid_term(): void
    {
        [$productA] = $this->setUpCatalogue();

        $response = $this->getJson('/search/suggestions?q=Neon');

        $response->assertOk();
        $response->assertJsonStructure([
            '*' => ['name', 'url', 'price', 'category']
        ]);
        $this->assertStringContainsString($productA->name, $response->getContent());
    }

    public function test_suggestions_endpoint_returns_empty_for_short_term(): void
    {
        $response = $this->getJson('/search/suggestions?q=a');

        $response->assertOk();
        $this->assertSame('[]', $response->getContent());
    }

    public function test_suggestions_endpoint_returns_empty_for_empty_term(): void
    {
        $response = $this->getJson('/search/suggestions?q=');

        $response->assertOk();
        $this->assertSame('[]', $response->getContent());
    }

    public function test_product_show_page_loads_for_approved_product(): void
    {
        [$productA] = $this->setUpCatalogue();

        $response = $this->get("/products/{$productA->slug}");

        $response->assertOk();
        $response->assertSee($productA->name);
    }

    public function test_product_show_page_404s_for_pending_product(): void
    {
        [, , , $productD] = $this->setUpCatalogue();

        $response = $this->get("/products/{$productD->slug}");

        $response->assertNotFound();
    }

    public function test_product_show_page_404s_for_rejected_product(): void
    {
        $fishCategory = Category::factory()->create(['name' => 'Fish', 'slug' => 'fish']);
        $product = Product::factory()->create([
            'category_id' => $fishCategory->id,
            'status'      => 'rejected',
        ]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'RJ1', 'price' => 100, 'stock' => 5]);

        $response = $this->get("/products/{$product->slug}");

        $response->assertNotFound();
    }
}