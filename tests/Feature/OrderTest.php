<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private function createPaidOrder(User $customer, array $overrides = []): Order
    {
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

    public function test_customer_can_view_their_order_list(): void
    {
        $customer = User::factory()->create();
        $this->createPaidOrder($customer);
        $this->createPaidOrder($customer, ['order_number' => 'SA-TEST002', 'total' => 300]);

        $response = $this->actingAs($customer)->get('/orders');

        $response->assertOk();
        $response->assertSee('SA-TEST001');
        $response->assertSee('SA-TEST002');
        $response->assertSee('৳400.00');
        $response->assertSee('৳300.00');
    }

    public function test_customer_sees_only_their_own_orders(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();

        $this->createPaidOrder($customer, ['order_number' => 'SA-CUSTOMER001']);
        $this->createPaidOrder($otherCustomer, ['order_number' => 'SA-OTHER001']);

        $response = $this->actingAs($customer)->get('/orders');

        $response->assertOk();
        $response->assertSee('SA-CUSTOMER001');
        $response->assertDontSee('SA-OTHER001');
    }

    public function test_customer_can_view_order_details(): void
    {
        $customer = User::factory()->create();
        $order = $this->createPaidOrder($customer);

        $response = $this->actingAs($customer)->get("/orders/{$order->id}");

        $response->assertOk();
        $response->assertSee($order->order_number);
        $response->assertSee($order->shipping_name);
        $response->assertSee($order->shipping_address);
        $response->assertSee('Bkash');
        $response->assertSee('Processing');
    }

    public function test_customer_cannot_view_another_customers_order_details(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $order = $this->createPaidOrder($otherCustomer);

        $response = $this->actingAs($customer)->get("/orders/{$order->id}");

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_from_order_list_to_login(): void
    {
        $this->get('/orders')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_order_details_to_login(): void
    {
        $customer = User::factory()->create();
        $order = $this->createPaidOrder($customer);

        $this->get("/orders/{$order->id}")->assertRedirect(route('login'));
    }

    public function test_order_shows_correct_status_badge(): void
    {
        $customer = User::factory()->create();
        $order = $this->createPaidOrder($customer, ['status' => 'shipped']);

        $response = $this->actingAs($customer)->get("/orders/{$order->id}");

        $response->assertOk();
        $response->assertSee('Shipped');
    }

    public function test_order_shows_tracking_code_when_shipped(): void
    {
        $customer = User::factory()->create();
        $order = $this->createPaidOrder($customer, [
            'status'        => 'shipped',
            'courier'       => 'pathao',
            'tracking_code' => 'PATHAO-ABC123',
        ]);

        $response = $this->actingAs($customer)->get("/orders/{$order->id}");

        $response->assertOk();
        $response->assertSee('PATHAO-ABC123');
    }

    public function test_order_shows_delivered_status(): void
    {
        $customer = User::factory()->create();
        $order = $this->createPaidOrder($customer, ['status' => 'delivered']);

        $response = $this->actingAs($customer)->get("/orders/{$order->id}");

        $response->assertOk();
        $response->assertSee('Delivered');
    }

    public function test_order_shows_cancelled_status(): void
    {
        $customer = User::factory()->create();
        $order = $this->createPaidOrder($customer, ['status' => 'cancelled']);

        $response = $this->actingAs($customer)->get("/orders/{$order->id}");

        $response->assertOk();
        $response->assertSee('Cancelled');
    }

    public function test_unpaid_order_is_visible_in_list(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 10, price: 200)->create(['profit_share_amount' => 50]);
        $variant = $product->variants()->first();

        $order = Order::create([
            'order_number'     => 'SA-UNPAID001',
            'user_id'          => $customer->id,
            'total'            => 200,
            'status'           => 'processing',
            'payment_method'   => 'bkash',
            'payment_status'   => 'unpaid',
            'shipping_name'    => 'Buyer',
            'shipping_phone'   => '01700000000',
            'shipping_address' => 'Dhaka',
        ]);

        OrderItem::create([
            'order_id'           => $order->id,
            'product_variant_id' => $variant->id,
            'product_name'       => $product->name,
            'variant_size'       => $variant->label,
            'price'              => $variant->price,
            'quantity'           => 1,
            'marketplace_share_amount' => $product->profit_share_amount,
        ]);

        $response = $this->actingAs($customer)->get('/orders');

        $response->assertOk();
        $response->assertSee('SA-UNPAID001');
        $response->assertSee('Unpaid');
    }
}