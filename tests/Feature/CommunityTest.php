<?php

namespace Tests\Feature;

use App\Models\CommunitySubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_can_submit_a_community_post_starting_as_pending(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->post('/community/submit', [
            'title' => 'My Planted Tank Journey',
            'body' => 'Started six months ago, here is what worked for me.',
        ]);

        $this->assertDatabaseHas('community_submissions', [
            'user_id' => $customer->id,
            'title' => 'My Planted Tank Journey',
            'status' => 'pending',
        ]);
    }

    public function test_a_pending_post_is_not_visible_publicly(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        CommunitySubmission::create([
            'user_id' => $customer->id,
            'title' => 'Not Yet Reviewed Post',
            'body' => 'Some content.',
            'status' => 'pending',
        ]);

        $response = $this->get('/community');

        $response->assertOk();
        $response->assertDontSee('Not Yet Reviewed Post');
    }

    public function test_an_approved_post_is_visible_publicly(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        CommunitySubmission::create([
            'user_id' => $customer->id,
            'title' => 'Approved Community Post',
            'body' => 'Some content.',
            'status' => 'approved',
        ]);

        $response = $this->get('/community');

        $response->assertOk();
        $response->assertSee('Approved Community Post');
    }
}
