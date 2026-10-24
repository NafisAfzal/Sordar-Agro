<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function placeOrder(User $customer, int $quantity = 2, int $stock = 5): Order
    {
        $product = Product::factory()->withVariant(stock: $stock, price: 100)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => $quantity]);
        $this->actingAs($customer)->post('/checkout', [
            'shipping_name'    => 'Buyer',
            'shipping_phone'   => '01700000000',
            'shipping_address' => '123 Test Road, Dhaka',
            'payment_method'   => 'bkash',
        ]);

        return Order::where('user_id', $customer->id)->latest('id')->firstOrFail();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_submitting_a_transaction_id_reserves_stock_and_awaits_verification(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);
        $variant = $order->items()->first()->variant;

        $this->actingAs($customer)
            ->post("/payment/{$order->id}", ['transaction_id' => ' trxabc123 '])
            ->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $variant->refresh();

        $this->assertSame(Order::PAYMENT_UNPAID, $order->payment_status);
        $this->assertSame('TRXABC123', $order->transaction_id);
        $this->assertNotNull($order->payment_submitted_at);
        $this->assertNull($order->payment_verified_at);
        $this->assertNull($order->payment_verified_by);
        $this->assertTrue($order->isAwaitingPaymentVerification());
        $this->assertSame(3, $variant->stock); // 5 - 2 reserved
        $this->assertSame(0, Cart::where('user_id', $customer->id)->count());
    }

    public function test_customer_sees_that_payment_is_awaiting_verification(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);

        $this->actingAs($customer)->post("/payment/{$order->id}", ['transaction_id' => 'TRXWAIT001']);

        $this->actingAs($customer)
            ->get("/orders/{$order->id}")
            ->assertOk()
            ->assertSee('Awaiting verification');

        // The payment page must not let an already-submitted order pay twice.
        $this->actingAs($customer)
            ->get("/payment/{$order->id}")
            ->assertRedirect(route('orders.show', $order));
    }

    public function test_admin_can_verify_a_payment_and_mark_the_order_paid(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);
        $variant = $order->items()->first()->variant;
        $admin = $this->admin();

        $this->actingAs($customer)->post("/payment/{$order->id}", ['transaction_id' => 'TRXVERIFY01']);

        $this->actingAs($admin)
            ->patch("/admin/orders/{$order->id}/payment/verify")
            ->assertRedirect();

        $order->refresh();
        $variant->refresh();

        $this->assertSame(Order::PAYMENT_PAID, $order->payment_status);
        $this->assertNotNull($order->payment_verified_at);
        $this->assertSame($admin->id, (int) $order->payment_verified_by);
        $this->assertSame(3, $variant->stock); // remains reserved, not decremented again
    }

    public function test_admin_can_reject_a_payment_and_release_reserved_stock(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);
        $variant = $order->items()->first()->variant;
        $admin = $this->admin();

        $this->actingAs($customer)->post("/payment/{$order->id}", ['transaction_id' => 'TRXREJECT01']);
        $this->assertSame(3, $variant->fresh()->stock);

        $this->actingAs($admin)
            ->patch("/admin/orders/{$order->id}/payment/reject")
            ->assertRedirect();

        $order->refresh();
        $variant->refresh();

        $this->assertSame(Order::PAYMENT_FAILED, $order->payment_status);
        $this->assertSame($admin->id, (int) $order->payment_verified_by);
        $this->assertNotNull($order->payment_verified_at);
        $this->assertSame(5, $variant->stock); // reserved stock returned
    }

    public function test_a_rejected_payment_can_be_retried_with_a_new_transaction_id(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);
        $variant = $order->items()->first()->variant;
        $admin = $this->admin();

        $this->actingAs($customer)->post("/payment/{$order->id}", ['transaction_id' => 'TRXOLD00001']);
        $this->actingAs($admin)->patch("/admin/orders/{$order->id}/payment/reject");

        $this->actingAs($customer)
            ->post("/payment/{$order->id}", ['transaction_id' => 'TRXNEW00002'])
            ->assertRedirect(route('orders.show', $order));

        $order->refresh();

        $this->assertSame(Order::PAYMENT_UNPAID, $order->payment_status);
        $this->assertSame('TRXNEW00002', $order->transaction_id);
        $this->assertNull($order->payment_verified_at);
        $this->assertTrue($order->isAwaitingPaymentVerification());
        $this->assertSame(3, $variant->fresh()->stock); // reserved again
    }

    public function test_pending_payment_cannot_be_submitted_twice(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);
        $variant = $order->items()->first()->variant;

        $this->actingAs($customer)->post("/payment/{$order->id}", ['transaction_id' => 'TRXFIRST001']);

        $this->actingAs($customer)
            ->post("/payment/{$order->id}", ['transaction_id' => 'TRXSECOND01'])
            ->assertRedirect(route('orders.show', $order));

        $order->refresh();

        $this->assertSame('TRXFIRST001', $order->transaction_id);
        $this->assertSame(3, $variant->fresh()->stock); // not reserved twice
    }

    public function test_transaction_id_must_be_alphanumeric(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);
        $variant = $order->items()->first()->variant;

        $this->actingAs($customer)
            ->post("/payment/{$order->id}", ['transaction_id' => 'TRX 123456'])
            ->assertSessionHasErrors('transaction_id');

        $order->refresh();

        $this->assertNull($order->transaction_id);
        $this->assertSame(5, $variant->fresh()->stock);
    }

    public function test_customers_cannot_verify_or_reject_payments(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);

        $this->actingAs($customer)->post("/payment/{$order->id}", ['transaction_id' => 'TRXGUARD001']);

        $this->actingAs($customer)
            ->patch("/admin/orders/{$order->id}/payment/verify")
            ->assertForbidden();

        $this->actingAs($customer)
            ->patch("/admin/orders/{$order->id}/payment/reject")
            ->assertForbidden();

        $this->assertTrue($order->fresh()->isAwaitingPaymentVerification());
    }

    public function test_only_awaiting_orders_can_be_verified_or_rejected(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer); // unpaid, no TrxID submitted
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch("/admin/orders/{$order->id}/payment/verify")
            ->assertRedirect();

        $this->actingAs($admin)
            ->patch("/admin/orders/{$order->id}/payment/reject")
            ->assertRedirect();

        $order->refresh();

        $this->assertSame(Order::PAYMENT_UNPAID, $order->payment_status);
        $this->assertNull($order->payment_verified_at);
        $this->assertSame(5, $order->items()->first()->variant->fresh()->stock);
    }

    public function test_admin_can_filter_orders_awaiting_payment_verification(): void
    {
        $pendingCustomer = User::factory()->create();
        $pendingOrder = $this->placeOrder($pendingCustomer);
        $this->actingAs($pendingCustomer)
            ->post("/payment/{$pendingOrder->id}", ['transaction_id' => 'TRXPENDING1']);

        $unpaidCustomer = User::factory()->create();
        $unpaidOrder = $this->placeOrder($unpaidCustomer); // no TrxID submitted

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/admin/orders?payment=pending')
            ->assertOk()
            ->assertSee($pendingOrder->order_number)
            ->assertDontSee($unpaidOrder->order_number);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('1 payment to verify');
    }

    public function test_cancelled_orders_cannot_be_paid_and_do_not_reserve_stock(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);
        $variant = $order->items()->first()->variant;
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch("/admin/orders/{$order->id}", ['status' => 'cancelled'])
            ->assertRedirect();

        $this->actingAs($customer)->post("/payment/{$order->id}", ['transaction_id' => 'TRXCANCELLED'])
            ->assertRedirect(route('orders.show', $order));

        $order->refresh();

        $this->assertSame('cancelled', $order->status);
        $this->assertNull($order->transaction_id);
        $this->assertSame(5, $variant->fresh()->stock);
    }

    public function test_cancelling_a_pending_order_releases_reserved_stock_only_once(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);
        $variant = $order->items()->first()->variant;
        $admin = $this->admin();

        $this->actingAs($customer)->post("/payment/{$order->id}", ['transaction_id' => 'TRXCANCEL01']);
        $this->assertSame(3, $variant->fresh()->stock);

        $this->actingAs($admin)
            ->patch("/admin/orders/{$order->id}", ['status' => 'cancelled'])
            ->assertRedirect();

        $order->refresh();

        $this->assertSame('cancelled', $order->status);
        $this->assertSame(Order::PAYMENT_FAILED, $order->payment_status);
        $this->assertSame(5, $variant->fresh()->stock);

        // A second cancellation must not release the same stock again.
        $this->actingAs($admin)
            ->patch("/admin/orders/{$order->id}", ['status' => 'cancelled'])
            ->assertRedirect();

        $this->assertSame(5, $variant->fresh()->stock);
    }
}
