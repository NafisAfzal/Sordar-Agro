<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_page_redirects_to_cart_when_empty(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->get('/checkout');

        $response->assertRedirect(route('cart.index'));
    }

    public function test_checkout_page_shows_cart_items(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 5, price: 200)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 1]);

        $response = $this->actingAs($customer)->get('/checkout');
        $response->assertOk();
        $response->assertSee($product->name);
    }

    public function test_place_order_redirects_to_payment_page(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 5, price: 200)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 1]);

        $response = $this->actingAs($customer)->post('/checkout', [
            'shipping_name' => 'Test Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => '123 Test Road, Dhaka',
            'payment_method' => 'bkash',
        ]);

        $order = Order::where('user_id', $customer->id)->latest()->first();
        $response->assertRedirect(route('payment.show', $order));
    }

    public function test_place_order_creates_order_with_correct_total(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 5, price: 200)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 3]);

        $this->actingAs($customer)->post('/checkout', [
            'shipping_name' => 'Test Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => '123 Test Road, Dhaka',
            'payment_method' => 'bkash',
        ]);

        $order = Order::where('user_id', $customer->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('600.00', (string) $order->total);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame('processing', $order->status);
    }

    public function test_place_order_does_not_clear_cart_until_payment(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 5, price: 200)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 1]);

        $this->actingAs($customer)->post('/checkout', [
            'shipping_name' => 'Test Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => '123 Test Road, Dhaka',
            'payment_method' => 'bkash',
        ]);

        // Cart is NOT cleared until payment is processed.
        $this->assertSame(1, Cart::where('user_id', $customer->id)->count());
    }

    public function test_place_order_stocks_order_items(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 5, price: 200)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 2]);

        $this->actingAs($customer)->post('/checkout', [
            'shipping_name' => 'Test Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => '123 Test Road, Dhaka',
            'payment_method' => 'bkash',
        ]);

        $order = Order::where('user_id', $customer->id)->latest()->first();

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'quantity' => 2,
        ]);
    }

    public function test_place_order_does_not_decrement_stock(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 5, price: 200)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 2]);

        $this->actingAs($customer)->post('/checkout', [
            'shipping_name' => 'Test Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => '123 Test Road, Dhaka',
            'payment_method' => 'bkash',
        ]);

        $variant->refresh();
        $this->assertSame(5, $variant->stock);
    }

    public function test_place_order_rejects_empty_cart(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->post('/checkout', [
            'shipping_name' => 'Test Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => '123 Test Road, Dhaka',
            'payment_method' => 'bkash',
        ]);

        $response->assertRedirect(route('cart.index'));
    }

    public function test_place_order_rejects_missing_required_fields(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 5, price: 200)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 1]);

        $response = $this->actingAs($customer)->post('/checkout', []);
        $response->assertSessionHasErrors(['shipping_name', 'shipping_phone', 'shipping_address', 'payment_method']);
    }

    public function test_place_order_rejects_invalid_payment_method(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 5, price: 200)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 1]);

        $response = $this->actingAs($customer)->post('/checkout', [
            'shipping_name' => 'Test Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => '123 Test Road, Dhaka',
            'payment_method' => 'visa',
        ]);

        $response->assertSessionHasErrors('payment_method');
    }

    public function test_place_order_blocks_when_variant_sold_out(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 2, price: 200)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 2]);

        $variant->update(['stock' => 0]);

        $response = $this->actingAs($customer)->post('/checkout', [
            'shipping_name' => 'Test Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => '123 Test Road, Dhaka',
            'payment_method' => 'bkash',
        ]);

        $response->assertSessionHas('error');
    }

    public function test_guest_redirected_from_checkout_to_login(): void
    {
        $this->get('/checkout')->assertRedirect(route('login'));
    }

    public function test_order_items_snapshot_variant_price_and_profit_share(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create(['profit_share_amount' => 50]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'standard',
            'price' => 250,
            'stock' => 10,
            'sku' => 'SKU-SNAP1',
        ]);

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 1]);

        $this->actingAs($customer)->post('/checkout', [
            'shipping_name' => 'Snapshot Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => '123 Test Road, Dhaka',
            'payment_method' => 'bkash',
        ]);

        $order = Order::where('user_id', $customer->id)->latest()->first();
        $item = OrderItem::where('order_id', $order->id)->first();

        $this->assertEquals('250.00', (string) $item->price);
        $this->assertEquals('50.00', (string) $item->marketplace_share_amount);
    }
}
