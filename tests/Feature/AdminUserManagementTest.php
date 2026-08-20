<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_deactivate_a_customer_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);

        $this->actingAs($admin)->patch("/admin/users/{$customer->id}/toggle");

        $this->assertFalse($customer->fresh()->is_active);
    }

    public function test_a_deactivated_user_cannot_log_in(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => false,
            'password' => 'password',
        ]);

        $response = $this->post('/login', [
            'email' => $customer->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_can_reactivate_a_suspended_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => false]);

        $this->actingAs($admin)->patch("/admin/users/{$customer->id}/toggle");

        $this->assertTrue($customer->fresh()->is_active);
    }
}
