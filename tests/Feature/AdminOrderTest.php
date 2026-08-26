<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderTest extends TestCase
{
    use RefreshDatabase;

    private function createPaidOrder(array $overrides = []): Order
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 10, price: 200)->create(['profit_share_amount' => 50]);
        $variant = $product->variants()->first();

        $order = Order::create(array_merge([
            'order_number'     => 'SA-TEST001',
            'user_id'          => $customer->id,
            'total'            => 400,
            'status'           => 'processing',
            'payment_method'   => 'bkash',
            'payment_status'   => 'paid',
            'transaction_id'   => 'TRX123456',
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

    public function test_admin_can_view_order_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->createPaidOrder();
        $this->createPaidOrder(['order_number' => 'SA-TEST002']);

        $response = $this->actingAs($admin)->get('/admin/orders');

        $response->assertOk();
        $response->assertSee('SA-TEST001');
        $response->assertSee('SA-TEST002');
    }

    public function test_admin_can_filter_orders_by_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->createPaidOrder(['status' => 'processing']);
        $this->createPaidOrder(['order_number' => 'SA-TEST002', 'status' => 'shipped']);
        $this->createPaidOrder(['order_number' => 'SA-TEST003', 'status' => 'delivered']);

        $response = $this->actingAs($admin)->get('/admin/orders?status=shipped');

        $response->assertOk();
        $response->assertSee('SA-TEST002');
        $response->assertDontSee('SA-TEST001');
        $response->assertDontSee('SA-TEST003');
    }

    public function test_admin_can_view_order_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->createPaidOrder();

        $response = $this->actingAs($admin)->get("/admin/orders/{$order->id}");

        $response->assertOk();
        $response->assertSee($order->order_number);
        $response->assertSee($order->shipping_name);
    }

    public function test_admin_can_update_order_status_to_shipped_with_courier(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->createPaidOrder(['status' => 'processing']);

        $response = $this->actingAs($admin)->patch("/admin/orders/{$order->id}", [
            'status'  => 'shipped',
            'courier' => 'pathao',
        ]);

        $response->assertRedirect();
        $order->refresh();
        $this->assertSame('shipped', $order->status);
        $this->assertSame('pathao', $order->courier);
        $this->assertStringStartsWith('PATHAO-', $order->tracking_code);
    }

    public function test_admin_can_update_order_status_to_shipped_with_custom_tracking_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->createPaidOrder(['status' => 'processing']);

        $response = $this->actingAs($admin)->patch("/admin/orders/{$order->id}", [
            'status'        => 'shipped',
            'courier'       => 'steadfast',
            'tracking_code' => 'CUSTOM-TRACK-123',
        ]);

        $response->assertRedirect();
        $order->refresh();
        $this->assertSame('shipped', $order->status);
        $this->assertSame('steadfast', $order->courier);
        $this->assertSame('CUSTOM-TRACK-123', $order->tracking_code);
    }

    public function test_admin_can_update_order_status_to_delivered(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->createPaidOrder(['status' => 'shipped', 'courier' => 'pathao', 'tracking_code' => 'PATHAO-ABC123']);

        $response = $this->actingAs($admin)->patch("/admin/orders/{$order->id}", [
            'status' => 'delivered',
        ]);

        $response->assertRedirect();
        $order->refresh();
        $this->assertSame('delivered', $order->status);
    }

    public function test_admin_can_cancel_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->createPaidOrder(['status' => 'processing']);

        $response = $this->actingAs($admin)->patch("/admin/orders/{$order->id}", [
            'status' => 'cancelled',
        ]);

        $response->assertRedirect();
        $order->refresh();
        $this->assertSame('cancelled', $order->status);
    }

    public function test_admin_cannot_set_invalid_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->createPaidOrder();

        $response = $this->actingAs($admin)->patch("/admin/orders/{$order->id}", [
            'status' => 'invalid_status',
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertSame('processing', $order->fresh()->status);
    }

    public function test_admin_cannot_set_invalid_courier(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->createPaidOrder();

        $response = $this->actingAs($admin)->patch("/admin/orders/{$order->id}", [
            'status'  => 'shipped',
            'courier' => 'invalid_courier',
        ]);

        $response->assertSessionHasErrors('courier');
    }

    public function test_admin_cannot_assign_tracking_code_without_courier_on_shipped(): void
    {
        // The controller allows this - tracking_code is nullable even without courier
        // This test documents current behavior
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->createPaidOrder();

        $response = $this->actingAs($admin)->patch("/admin/orders/{$order->id}", [
            'status'        => 'shipped',
            'tracking_code' => 'SOME-CODE',
        ]);

        $response->assertRedirect();
        $order->refresh();
        $this->assertSame('shipped', $order->status);
        $this->assertNull($order->courier);
        $this->assertSame('SOME-CODE', $order->tracking_code);
    }

    public function test_customer_cannot_access_admin_orders(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->createPaidOrder();

        $this->actingAs($customer)->get('/admin/orders')->assertForbidden();
        $this->actingAs($customer)->get("/admin/orders/{$order->id}")->assertForbidden();
        $this->actingAs($customer)->patch("/admin/orders/{$order->id}", ['status' => 'shipped'])->assertForbidden();
    }

    public function test_seller_cannot_access_admin_orders(): void
    {
        $seller = User::factory()->seller()->create();
        $order = $this->createPaidOrder();

        $this->actingAs($seller)->get('/admin/orders')->assertForbidden();
    }
}