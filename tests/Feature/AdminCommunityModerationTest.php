<?php

namespace Tests\Feature;

use App\Models\CommunitySubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCommunityModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_a_pending_community_post(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $author = User::factory()->create(['role' => 'customer']);
        $submission = CommunitySubmission::create([
            'user_id' => $author->id,
            'title' => 'My Tank Setup',
            'body' => 'Some content.',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->patch("/admin/community/{$submission->id}/approve");

        $this->assertSame('approved', $submission->fresh()->status);
    }

    public function test_admin_can_reject_a_post_with_feedback(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $author = User::factory()->create(['role' => 'customer']);
        $submission = CommunitySubmission::create([
            'user_id' => $author->id,
            'title' => 'Off-Topic Post',
            'body' => 'Some content.',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->patch("/admin/community/{$submission->id}/reject", [
            'admin_feedback' => 'This is not related to fishkeeping.',
        ]);

        $submission->refresh();
        $this->assertSame('rejected', $submission->status);
        $this->assertSame('This is not related to fishkeeping.', $submission->admin_feedback);
    }
}
