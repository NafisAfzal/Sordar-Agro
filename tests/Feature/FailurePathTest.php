<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FailurePathTest extends TestCase
{
    use RefreshDatabase;

    public function test_nonexistent_product_returns_404(): void
    {
        $response = $this->get('/products/nonexistent-slug-12345');
        $response->assertNotFound();
    }

    public function test_nonexistent_care_guide_returns_404(): void
    {
        $response = $this->get('/care-guides/nonexistent-guide-12345');
        $response->assertNotFound();
    }

    public function test_nonexistent_order_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/orders/999999');
        $response->assertNotFound();
    }

    public function test_existing_order_returns_403_for_unrelated_user(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $order = Order::create([
            'order_number' => 'SA-UNAUTH001',
            'user_id' => $alice->id,
            'total' => 200,
            'status' => 'processing',
            'payment_method' => 'bkash',
            'payment_status' => 'unpaid',
            'shipping_name' => 'Alice',
            'shipping_phone' => '01700000000',
            'shipping_address' => 'Dhaka',
        ]);

        $response = $this->actingAs($bob)->get("/orders/{$order->id}");
        $response->assertForbidden();
    }

    public function test_cart_update_rejects_invalid_quantity(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 5, price: 100)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 1]);
        $cart = \App\Models\Cart::where('user_id', $customer->id)->first();

        $response = $this->actingAs($customer)->patch("/cart/{$cart->id}", ['quantity' => 0]);
        $response->assertSessionHasErrors('quantity');
    }

    public function test_cart_update_rejects_negative_quantity(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 5, price: 100)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 1]);
        $cart = \App\Models\Cart::where('user_id', $customer->id)->first();

        $response = $this->actingAs($customer)->patch("/cart/{$cart->id}", ['quantity' => -1]);
        $response->assertSessionHasErrors('quantity');
    }

    public function test_payment_process_rejects_empty_transaction_id(): void
    {
        $customer = User::factory()->create();
        $order = Order::create([
            'order_number' => 'SA-FAIL001',
            'user_id' => $customer->id,
            'total' => 200,
            'status' => 'processing',
            'payment_method' => 'bkash',
            'payment_status' => 'unpaid',
            'shipping_name' => 'Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => 'Dhaka',
        ]);

        $response = $this->actingAs($customer)->post("/payment/{$order->id}", [
            'transaction_id' => '',
        ]);

        $response->assertSessionHasErrors('transaction_id');
    }

    public function test_payment_process_rejects_short_transaction_id(): void
    {
        $customer = User::factory()->create();
        $order = Order::create([
            'order_number' => 'SA-FAIL002',
            'user_id' => $customer->id,
            'total' => 200,
            'status' => 'processing',
            'payment_method' => 'bkash',
            'payment_status' => 'unpaid',
            'shipping_name' => 'Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => 'Dhaka',
        ]);

        $response = $this->actingAs($customer)->post("/payment/{$order->id}", [
            'transaction_id' => 'SHORT',
        ]);

        $response->assertSessionHasErrors('transaction_id');
    }

    public function test_already_paid_order_redirects_to_order_details(): void
    {
        $customer = User::factory()->create();
        $order = Order::create([
            'order_number' => 'SA-PAID001',
            'user_id' => $customer->id,
            'total' => 200,
            'status' => 'processing',
            'payment_method' => 'bkash',
            'payment_status' => 'paid',
            'transaction_id' => 'TRX123456',
            'shipping_name' => 'Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => 'Dhaka',
        ]);

        $response = $this->actingAs($customer)->get("/payment/{$order->id}");
        $response->assertRedirect(route('orders.show', $order));
    }

    public function test_community_submit_requires_authentication(): void
    {
        $this->post('/community/submit', [
            'title' => 'Test',
            'body' => 'Body',
        ])->assertRedirect(route('login'));
    }

    public function test_community_submit_rejects_missing_title(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->post('/community/submit', [
            'body' => 'Body without title',
        ]);

        $response->assertSessionHasErrors('title');
    }

    public function test_community_submit_rejects_missing_body(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->post('/community/submit', [
            'title' => 'Title without body',
        ]);

        $response->assertSessionHasErrors('body');
    }

    public function test_community_submit_rejects_oversized_body(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->post('/community/submit', [
            'title' => 'Oversized',
            'body' => str_repeat('x', 5001),
        ]);

        $response->assertSessionHasErrors('body');
    }

    public function test_sitemap_returns_xml(): void
    {
        $response = $this->get('/sitemap.xml');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function test_robots_txt_returns_text(): void
    {
        $response = $this->get('/robots.txt');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSee('User-agent');
        $response->assertSee('Sitemap:');
    }

    public function test_robots_txt_disallows_private_routes(): void
    {
        $response = $this->get('/robots.txt');
        $response->assertSee('Disallow: /admin');
        $response->assertSee('Disallow: /cart');
        $response->assertSee('Disallow: /checkout');
        $response->assertSee('Disallow: /orders');
        $response->assertSee('Disallow: /wishlist');
    }

    public function test_care_guides_page_returns_200(): void
    {
        $response = $this->get('/care-guides');
        $response->assertOk();
    }

    public function test_community_page_returns_200(): void
    {
        $response = $this->get('/community');
        $response->assertOk();
    }
}
