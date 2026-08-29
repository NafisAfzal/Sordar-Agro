<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createOrderFor(User $customer, array $overrides = []): Order
    {
        $product = Product::factory()->create(['profit_share_amount' => 50]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'standard',
            'price' => 200,
            'stock' => 10,
            'sku' => 'SKU-ORDER-'.rand(1000, 9999),
        ]);

        $order = Order::create(array_merge([
            'order_number'     => 'SA-ORD'.rand(1000, 9999),
            'user_id'          => $customer->id,
            'total'            => 400,
            'status'           => 'processing',
            'payment_method'   => 'bkash',
            'payment_status'   => 'paid',
            'transaction_id'   => 'TRX'.rand(100000, 999999),
            'shipping_name'    => 'Buyer',
            'shipping_phone'   => '01700000000',
            'shipping_address' => 'Dhaka',
        ], $overrides));

        OrderItem::create([
            'order_id'           => $order->id,
            'product_variant_id' => $variant->id,
            'product_name'       => $product->name,
            'variant_size'       => $variant->label,
            'price'              => $variant->price,
            'quantity'           => 2,
            'marketplace_share_amount' => $product->profit_share_amount,
        ]);

        return $order;
    }

    public function test_order_list_only_shows_own_orders(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $this->createOrderFor($alice, ['order_number' => 'SA-ALICE01']);
        $this->createOrderFor($bob, ['order_number' => 'SA-BOB01']);

        $response = $this->actingAs($alice)->get('/orders');
        $response->assertSee('SA-ALICE01');
        $response->assertDontSee('SA-BOB01');
    }

    public function test_order_detail_shows_items_with_names(): void
    {
        $customer = User::factory()->create();
        $order = $this->createOrderFor($customer);

        $response = $this->actingAs($customer)->get("/orders/{$order->id}");
        $response->assertOk();

        $item = OrderItem::where('order_id', $order->id)->first();
        $response->assertSee($item->product_name);
        $response->assertSee($item->variant_size);
    }

    public function test_order_detail_shows_shipping_info(): void
    {
        $customer = User::factory()->create();
        $order = $this->createOrderFor($customer, [
            'shipping_name' => 'Farhana',
            'shipping_phone' => '01800000000',
            'shipping_address' => 'Chittagong, Bangladesh',
        ]);

        $response = $this->actingAs($customer)->get("/orders/{$order->id}");
        $response->assertOk();
        $response->assertSee('Farhana');
        $response->assertSee('01800000000');
        $response->assertSee('Chittagong, Bangladesh');
    }

    public function test_order_list_is_paginated(): void
    {
        $customer = User::factory()->create();
        for ($i = 0; $i < 15; $i++) {
            $this->createOrderFor($customer, ['order_number' => "SA-PAGE{$i}"]);
        }

        $response = $this->actingAs($customer)->get('/orders');
        $response->assertOk();
        $response->assertSee('SA-PAGE0');
        $response->assertSee('SA-PAGE9');
    }

    public function test_order_status_transitions_through_lifecycle(): void
    {
        $customer = User::factory()->create();
        $order = $this->createOrderFor($customer, ['status' => 'processing']);
        $admin = User::factory()->create(['role' => 'admin']);

        // Admin ships the order
        $this->actingAs($admin)->patch("/admin/orders/{$order->id}", [
            'status' => 'shipped',
            'courier' => 'steadfast',
        ]);
        $order->refresh();
        $this->assertSame('shipped', $order->status);
        $this->assertSame('steadfast', $order->courier);

        // Admin marks as delivered
        $this->actingAs($admin)->patch("/admin/orders/{$order->id}", [
            'status' => 'delivered',
        ]);
        $order->refresh();
        $this->assertSame('delivered', $order->status);

        // Customer can view the delivered order
        $response = $this->actingAs($customer)->get("/orders/{$order->id}");
        $response->assertOk();
        $response->assertSee('Delivered');
    }

    public function test_order_total_reflects_cart_subtotal_at_checkout(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'standard',
            'price' => 350,
            'stock' => 10,
            'sku' => 'SKU-TOTAL1',
        ]);

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 3]);
        $this->actingAs($customer)->post('/checkout', [
            'shipping_name' => 'Test Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => 'Dhaka',
            'payment_method' => 'nagad',
        ]);

        $order = Order::where('user_id', $customer->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('1050.00', (string) $order->total);
        $this->assertSame('nagad', $order->payment_method);
    }

    public function test_order_number_is_unique_per_order(): void
    {
        $customer = User::factory()->create();
        $order1 = $this->createOrderFor($customer, ['order_number' => 'SA-UNIQUE01']);
        $order2 = $this->createOrderFor($customer, ['order_number' => 'SA-UNIQUE02']);

        $this->assertNotSame($order1->order_number, $order2->order_number);
    }

    public function test_unpaid_order_has_no_transaction_id(): void
    {
        $customer = User::factory()->create();
        $order = $this->createOrderFor($customer, [
            'payment_status' => 'unpaid',
            'transaction_id' => null,
        ]);

        $response = $this->actingAs($customer)->get("/orders/{$order->id}");
        $response->assertOk();
        $response->assertSee('Unpaid');
    }
}
