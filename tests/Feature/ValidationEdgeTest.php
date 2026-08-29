<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationEdgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_rejects_missing_name(): void
    {
        $response = $this->post('/register', [
            'email' => 'noname@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_registration_rejects_invalid_email(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_registration_rejects_unconfirmed_password(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'unconfirmed@example.com',
            'password' => 'password123',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'unconfirmed@example.com']);
    }

    public function test_login_rejects_empty_email(): void
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_login_rejects_empty_password(): void
    {
        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_cart_add_rejects_quantity_above_99(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 100, price: 100)->create();
        $variant = $product->variants()->first();

        $response = $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 100]);
        $response->assertSessionHasErrors('quantity');
    }

    public function test_cart_add_rejects_zero_quantity(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 10, price: 100)->create();
        $variant = $product->variants()->first();

        $response = $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 0]);
        $response->assertSessionHasErrors('quantity');
    }

    public function test_cart_add_rejects_negative_quantity(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 10, price: 100)->create();
        $variant = $product->variants()->first();

        $response = $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => -5]);
        $response->assertSessionHasErrors('quantity');
    }

    public function test_checkout_rejects_empty_shipping_name(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 5, price: 200)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 1]);

        $response = $this->actingAs($customer)->post('/checkout', [
            'shipping_phone' => '01700000000',
            'shipping_address' => '123 Test Road',
            'payment_method' => 'bkash',
        ]);

        $response->assertSessionHasErrors('shipping_name');
    }

    public function test_checkout_rejects_empty_shipping_phone(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 5, price: 200)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 1]);

        $response = $this->actingAs($customer)->post('/checkout', [
            'shipping_name' => 'Test Buyer',
            'shipping_address' => '123 Test Road',
            'payment_method' => 'bkash',
        ]);

        $response->assertSessionHasErrors('shipping_phone');
    }

    public function test_checkout_rejects_empty_shipping_address(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 5, price: 200)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 1]);

        $response = $this->actingAs($customer)->post('/checkout', [
            'shipping_name' => 'Test Buyer',
            'shipping_phone' => '01700000000',
            'payment_method' => 'bkash',
        ]);

        $response->assertSessionHasErrors('shipping_address');
    }

    public function test_community_submit_rejects_empty_title(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->post('/community/submit', [
            'title' => '',
            'body' => 'Some body content',
        ]);

        $response->assertSessionHasErrors('title');
    }

    public function test_community_submit_rejects_empty_body(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->post('/community/submit', [
            'title' => 'Valid title',
            'body' => '',
        ]);

        $response->assertSessionHasErrors('body');
    }

    public function test_community_submit_rejects_oversized_title(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->post('/community/submit', [
            'title' => str_repeat('x', 256),
            'body' => 'Valid body content',
        ]);

        $response->assertSessionHasErrors('title');
    }

    public function test_payment_rejects_empty_transaction_id(): void
    {
        $customer = User::factory()->create();
        $order = Order::create([
            'order_number' => 'SA-VAL001',
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

    public function test_payment_rejects_short_transaction_id(): void
    {
        $customer = User::factory()->create();
        $order = Order::create([
            'order_number' => 'SA-VAL002',
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
            'transaction_id' => 'ABCD',
        ]);

        $response->assertSessionHasErrors('transaction_id');
    }
}
