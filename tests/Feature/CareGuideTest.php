<?php

namespace Tests\Feature;

use App\Models\CareGuide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CareGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_published_care_guide_is_visible_on_the_public_index(): void
    {
        CareGuide::create([
            'title' => 'Cycling a New Tank',
            'slug' => 'cycling-a-new-tank',
            'content' => 'Full guide content here.',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/care-guides');

        $response->assertOk();
        $response->assertSee('Cycling a New Tank');
    }

    public function test_an_unpublished_care_guide_is_not_visible(): void
    {
        $guide = CareGuide::create([
            'title' => 'Draft Guide Not Ready',
            'slug' => 'draft-guide-not-ready',
            'content' => 'Full guide content here.',
            'published_at' => null,
        ]);

        $indexResponse = $this->get('/care-guides');
        $indexResponse->assertDontSee('Draft Guide Not Ready');

        $showResponse = $this->get("/care-guides/{$guide->slug}");
        $showResponse->assertNotFound();
    }

    public function test_viewing_a_single_published_guide_shows_its_content(): void
    {
        $guide = CareGuide::create([
            'title' => 'Betta Tank Setup',
            'slug' => 'betta-tank-setup',
            'content' => 'Bettas need a heated, filtered tank of at least 20 litres.',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get("/care-guides/{$guide->slug}");

        $response->assertOk();
        $response->assertSee('Betta Tank Setup');
        $response->assertSee('Bettas need a heated, filtered tank of at least 20 litres.');
    }
}
