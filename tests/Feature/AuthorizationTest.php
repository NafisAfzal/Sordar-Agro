<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_cart_to_login(): void
    {
        $this->get('/cart')->assertRedirect(route('login'));
    }

    public function test_user_cannot_view_another_users_order(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $order = Order::create([
            'order_number' => 'SA-TEST0001',
            'user_id' => $alice->id,
            'total' => 100,
            'status' => 'processing',
            'payment_method' => 'bkash',
            'payment_status' => 'unpaid',
            'shipping_name' => 'Alice',
            'shipping_phone' => '01700000000',
            'shipping_address' => 'Dhaka',
        ]);

        // Bob must not be able to open Alice's order.
        $this->actingAs($bob)->get("/orders/{$order->id}")->assertForbidden();
    }

    public function test_customer_cannot_reach_admin_dashboard(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/admin')->assertForbidden();
    }

    public function test_customer_cannot_reach_seller_workspace(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/seller/dashboard')->assertForbidden();
    }
}
