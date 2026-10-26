<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationExpiryTest extends TestCase
{
    use RefreshDatabase;

    private function placeOrder(User $customer, int $quantity = 2, int $stock = 5): Order
    {
        $product = Product::factory()->withVariant(stock: $stock, price: 100)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => $quantity]);
        $this->actingAs($customer)->post('/checkout', [
            'shipping_name' => 'Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => '123 Test Road, Dhaka',
            'payment_method' => 'bkash',
        ]);

        return Order::where('user_id', $customer->id)->latest('id')->firstOrFail();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_stale_order_releases_stock_once(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);
        $variant = $order->items()->first()->variant;

        $this->actingAs($customer)->post("/payment/{$order->id}", ['transaction_id' => 'TRXSTALE001']);
        $this->assertSame(3, $variant->fresh()->stock);

        // Make the order stale (submitted 25 hours ago, TTL is 24).
        $order->forceFill(['payment_submitted_at' => now()->subHours(25)])->save();

        $this->artisan('orders:release-stale-reservations')->assertExitCode(0);

        $order->refresh();
        $variant->refresh();

        $this->assertSame(Order::PAYMENT_FAILED, $order->payment_status);
        $this->assertSame('cancelled', $order->status);
        $this->assertSame(5, $variant->stock); // stock fully restored
    }

    public function test_re_running_the_command_releases_nothing_further(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);
        $variant = $order->items()->first()->variant;

        $this->actingAs($customer)->post("/payment/{$order->id}", ['transaction_id' => 'TRXRERUN001']);
        $order->forceFill(['payment_submitted_at' => now()->subHours(25)])->save();

        // First run releases stock.
        $this->artisan('orders:release-stale-reservations')->assertExitCode(0);
        $this->assertSame(5, $variant->fresh()->stock);

        // Second run must not release anything further.
        $this->artisan('orders:release-stale-reservations')->assertExitCode(0);
        $this->assertSame(5, $variant->fresh()->stock);
    }

    public function test_fresh_orders_are_untouched(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);
        $variant = $order->items()->first()->variant;

        $this->actingAs($customer)->post("/payment/{$order->id}", ['transaction_id' => 'TRXFRESH001']);
        $this->assertSame(3, $variant->fresh()->stock);

        // Order was just submitted — within TTL.
        $this->artisan('orders:release-stale-reservations')->assertExitCode(0);

        $order->refresh();
        $variant->refresh();

        $this->assertSame(Order::PAYMENT_UNPAID, $order->payment_status);
        $this->assertSame('processing', $order->status);
        $this->assertSame(3, $variant->stock); // still reserved
    }

    public function test_admin_verified_orders_are_untouched(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);
        $variant = $order->items()->first()->variant;
        $admin = $this->admin();

        $this->actingAs($customer)->post("/payment/{$order->id}", ['transaction_id' => 'TRXVERIFIED1']);

        // Admin verifies the payment.
        $this->actingAs($admin)->patch("/admin/orders/{$order->id}/payment/verify");

        $order->refresh();
        $this->assertSame(Order::PAYMENT_PAID, $order->payment_status);

        // Make it look old.
        $order->forceFill(['payment_submitted_at' => now()->subHours(25)])->save();

        $this->artisan('orders:release-stale-reservations')->assertExitCode(0);

        $order->refresh();
        $variant->refresh();

        $this->assertSame(Order::PAYMENT_PAID, $order->payment_status);
        $this->assertSame('processing', $order->status);
        $this->assertSame(3, $variant->stock); // stock stays reserved for paid order
    }

    public function test_already_cancelled_orders_are_untouched(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);
        $variant = $order->items()->first()->variant;
        $admin = $this->admin();

        $this->actingAs($customer)->post("/payment/{$order->id}", ['transaction_id' => 'TRXCANCEL01']);

        // Admin cancels the order (which already releases stock).
        $this->actingAs($admin)->patch("/admin/orders/{$order->id}", ['status' => 'cancelled']);

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame(5, $variant->fresh()->stock);

        // Make it look old.
        $order->forceFill(['payment_submitted_at' => now()->subHours(25)])->save();

        // Command must not touch already-cancelled orders.
        $this->artisan('orders:release-stale-reservations')->assertExitCode(0);

        $variant->refresh();
        $this->assertSame(5, $variant->stock); // no double-release
    }
}
