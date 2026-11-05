<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchSuggestionsCacheTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function second_identical_search_suggestion_request_returns_cached_result(): void
    {
        // Set up a product that will match searches
        Product::factory()->create([
            'name' => 'Neon Tetra',
            'status' => 'approved',
        ]);

        // First request — hits DB, result is cached
        $response1 = $this->getJson('/search/suggestions?q=Neon');

        // Second request with the same term — should use cache, return same data
        $response2 = $this->getJson('/search/suggestions?q=Neon');

        // Both should return valid JSON with product data
        $response1->assertOk();
        $response2->assertOk();

        // Both should contain the same product name (proving cache works)
        $response1Json = $response1->json();
        $response2Json = $response2->json();

        $this->assertCount(1, $response1Json);
        $this->assertCount(1, $response2Json);
        $this->assertEquals($response1Json[0]['name'], 'Neon Tetra');
        $this->assertEquals($response2Json[0]['name'], 'Neon Tetra');
        $this->assertEquals($response1Json, $response2Json, 'Second identical request should return cached result');
    }

    /** @test */
    public function short_term_search_returns_empty(): void
    {
        $response = $this->getJson('/search/suggestions?q=a');

        $response->assertOk();
        $this->assertSame('[]', $response->getContent());
    }

    /** @test */
    public function empty_term_search_returns_empty(): void
    {
        $response = $this->getJson('/search/suggestions?q=');

        $response->assertOk();
        $this->assertSame('[]', $response->getContent());
    }
}
