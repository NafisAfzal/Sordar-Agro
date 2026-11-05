<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdleSessionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_idle_session_is_dropped_on_next_request(): void
    {
        // Admin user with must_change_password = false (active session)
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        // Simulate activity: set last_activity to 35 minutes ago
        $this->actingAs($admin);
        $this->withSession([
            'last_activity' => now()->subMinutes(35)->timestamp,
            'session_start' => now()->subHours(1)->timestamp,
        ]);

        // Next request should log the admin out due to idle timeout
        $response = $this->get('/');

        // Should be redirected to login (not authenticated)
        $response->assertRedirect('/login');

        // User should no longer be authenticated
        $this->assertGuest();
    }

    /** @test */
    public function customer_session_unaffected_by_idle_timeout(): void
    {
        // Customer user
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        // Simulate activity: set last_activity to 35 minutes ago
        $this->actingAs($customer);
        $this->withSession([
            'last_activity' => now()->subMinutes(35)->timestamp,
            'session_start' => now()->subHours(1)->timestamp,
        ]);

        // Next request should NOT log the customer out
        $response = $this->get('/');

        // Should be authenticated as the customer
        $response->assertSuccessful();
        $this->assertAuthenticatedAs($customer);
    }

    /** @test */
    public function absolute_12h_cap_hits_regardless_of_activity(): void
    {
        // Admin user
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        // Force absolute session age > 12 hours by setting session_start very old
        $this->actingAs($admin);
        $this->withSession([
            'last_activity' => now()->subMinute(1)->timestamp, // Recent activity
            'session_start' => now()->subDays(1)->timestamp, // 1-day-old session
        ]);

        // Request should be rejected due to absolute cap
        $response = $this->get('/');

        // Should be redirected to login
        $response->assertRedirect('/login');

        // User should no longer be authenticated
        $this->assertGuest();
    }
}
