<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_out_of_stock_variant_cannot_be_added_to_cart(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 0)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 1]);

        $this->assertSame(0, Cart::where('user_id', $customer->id)->count());
    }

    public function test_in_stock_variant_can_be_added_to_cart(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 5, price: 250)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 2]);

        $this->assertDatabaseHas('carts', [
            'user_id' => $customer->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
    }

    public function test_successful_payment_decrements_stock_and_clears_cart(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 5, price: 100)->create();
        $variant = $product->variants()->first();

        // Add 2 to cart, then place the order.
        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 2]);
        $this->actingAs($customer)->post('/checkout', [
            'shipping_name' => 'Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => '123 Test Road, Dhaka',
            'payment_method' => 'bkash',
        ]);

        $order = Order::where('user_id', $customer->id)->latest()->firstOrFail();

        // Simulate a successful payment.
        $this->actingAs($customer)->post("/payment/{$order->id}", ['outcome' => 'success']);

        $order->refresh();
        $variant->refresh();

        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(3, $variant->stock);              // 5 - 2
        $this->assertSame(0, Cart::where('user_id', $customer->id)->count());
    }

    public function test_payment_is_blocked_when_stock_dropped_below_order_quantity(): void
    {
        // This guards the bug fixed in review: stock must never be oversold or
        // driven negative if it falls between checkout and payment.
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 5, price: 100)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 3]);
        $this->actingAs($customer)->post('/checkout', [
            'shipping_name' => 'Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => '123 Test Road, Dhaka',
            'payment_method' => 'bkash',
        ]);

        $order = Order::where('user_id', $customer->id)->latest()->firstOrFail();

        // Simulate stock being depleted by someone else before payment clears.
        $variant->update(['stock' => 1]);

        $this->actingAs($customer)->post("/payment/{$order->id}", ['outcome' => 'success']);

        $order->refresh();
        $variant->refresh();

        $this->assertNotSame('paid', $order->payment_status); // payment refused
        $this->assertSame(1, $variant->stock);                // stock untouched, not negative
    }
}
