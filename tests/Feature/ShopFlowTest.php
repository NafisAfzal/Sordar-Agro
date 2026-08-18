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

        // Simulate the customer submitting their bKash/Nagad Transaction ID.
        $this->actingAs($customer)->post("/payment/{$order->id}", ['transaction_id' => 'TRX123456789']);

        $order->refresh();
        $variant->refresh();

        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('TRX123456789', $order->transaction_id);
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

        $this->actingAs($customer)->post("/payment/{$order->id}", ['transaction_id' => 'TRX987654321']);

        $order->refresh();
        $variant->refresh();

        $this->assertNotSame('paid', $order->payment_status); // payment refused
        $this->assertSame(1, $variant->stock);                // stock untouched, not negative
    }

    public function test_duplicate_transaction_id_is_rejected(): void
    {
        // Two different customers must not be able to submit the same TrxID.
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 10, price: 100)->create();
        $variant = $product->variants()->first();

        // Alice pays successfully first.
        $this->actingAs($alice)->post("/cart/{$variant->id}", ['quantity' => 1]);
        $this->actingAs($alice)->post('/checkout', [
            'shipping_name' => 'Alice',
            'shipping_phone' => '01700000000',
            'shipping_address' => 'Dhaka',
            'payment_method' => 'bkash',
        ]);
        $aliceOrder = Order::where('user_id', $alice->id)->latest()->firstOrFail();
        $this->actingAs($alice)->post("/payment/{$aliceOrder->id}", ['transaction_id' => 'TRXSHARED001']);

        // Bob tries to reuse the same Transaction ID.
        $this->actingAs($bob)->post("/cart/{$variant->id}", ['quantity' => 1]);
        $this->actingAs($bob)->post('/checkout', [
            'shipping_name' => 'Bob',
            'shipping_phone' => '01700000001',
            'shipping_address' => 'Dhaka',
            'payment_method' => 'nagad',
        ]);
        $bobOrder = Order::where('user_id', $bob->id)->latest()->firstOrFail();
        $this->actingAs($bob)->post("/payment/{$bobOrder->id}", ['transaction_id' => 'TRXSHARED001']);

        $bobOrder->refresh();
        $this->assertNotSame('paid', $bobOrder->payment_status);
    }

    public function test_min_price_filter_matches_starting_price_not_any_variant(): void
    {
        // Regression test: the shop's min_price filter must match a product's
        // starting (cheapest-variant) price — the figure shown on its card —
        // not just "any variant happens to be in range". A product with a
        // cheap variant below the filter, even if it also has a pricier
        // variant that would satisfy the bound, must not appear in the
        // filtered results.
        $cheapStart = Product::factory()->create();
        $cheapStart->variants()->create(['size' => 'small', 'sku' => 'A1', 'price' => 180, 'stock' => 5]);
        $cheapStart->variants()->create(['size' => 'large', 'sku' => 'A2', 'price' => 350, 'stock' => 5]);

        $allAboveMin = Product::factory()->create();
        $allAboveMin->variants()->create(['size' => 'small', 'sku' => 'B1', 'price' => 320, 'stock' => 5]);
        $allAboveMin->variants()->create(['size' => 'large', 'sku' => 'B2', 'price' => 550, 'stock' => 5]);

        $response = $this->get('/products?min_price=300');

        $response->assertSee($allAboveMin->name);
        $response->assertDontSee($cheapStart->name);
    }
}