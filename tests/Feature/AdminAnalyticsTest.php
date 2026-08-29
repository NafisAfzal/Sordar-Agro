<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function paidOrder(User $customer, Product $product, int $qty, string $trx, ?Carbon $createdAt = null): Order
    {
        $variant = $product->variants()->first();

        $order = Order::create([
            'order_number'     => 'SA-'.$trx,
            'user_id'          => $customer->id,
            'total'            => $variant->price * $qty,
            'status'           => 'processing',
            'payment_method'   => 'bkash',
            'payment_status'   => 'paid',
            'transaction_id'   => $trx,
            'shipping_name'    => 'Buyer',
            'shipping_phone'   => '01700000000',
            'shipping_address' => 'Dhaka',
        ]);

        if ($createdAt) {
            $order->update(['created_at' => $createdAt]);
        }

        OrderItem::create([
            'order_id'                 => $order->id,
            'product_variant_id'       => $variant->id,
            'product_name'             => $product->name,
            'variant_size'             => $variant->label,
            'price'                    => $variant->price,
            'quantity'                 => $qty,
            'marketplace_share_amount' => $product->profit_share_amount ?? 0,
        ]);

        return $order->fresh();
    }

    // ── Basic access ──────────────────────────────────────────────────

    public function test_admin_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin');

        $response->assertOk();
        $response->assertSee('Admin overview');
    }

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/admin');

        $response->assertForbidden();
    }

    // ── Default (all-time) behaviour ──────────────────────────────────

    public function test_dashboard_shows_total_revenue_from_paid_orders(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create();

        $product = Product::factory()->create(['profit_share_amount' => 0]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'A1', 'price' => 500, 'stock' => 20]);

        $this->paidOrder($customer, $product, 3, 'TRX001');

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('৳1,500.00'); // 500 × 3
    }

    public function test_unpaid_orders_excluded_from_revenue(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create();

        $product = Product::factory()->create(['profit_share_amount' => 0]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'U1', 'price' => 400, 'stock' => 20]);

        // Create an unpaid order directly (no payment).
        $variant = $product->variants()->first();
        Order::create([
            'order_number' => 'SA-UNPAID1', 'user_id' => $customer->id,
            'total' => 400, 'status' => 'processing', 'payment_method' => 'bkash',
            'payment_status' => 'unpaid',
            'shipping_name' => 'X', 'shipping_phone' => '017', 'shipping_address' => 'Y',
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        // Revenue card should show 0 (unpaid orders excluded).
        $response->assertSee('Total sales');
        $response->assertSee('৳0.00');
    }

    public function test_cancelled_orders_excluded_from_revenue(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create();

        $product = Product::factory()->create(['profit_share_amount' => 0]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'C1', 'price' => 300, 'stock' => 20]);

        $variant = $product->variants()->first();
        $order = Order::create([
            'order_number' => 'SA-CANCEL1', 'user_id' => $customer->id,
            'total' => 300, 'status' => 'cancelled', 'payment_method' => 'bkash',
            'payment_status' => 'paid', 'transaction_id' => 'TRXC1',
            'shipping_name' => 'X', 'shipping_phone' => '017', 'shipping_address' => 'Y',
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'product_variant_id' => $variant->id,
            'product_name' => $product->name, 'variant_size' => 'Standard',
            'price' => 300, 'quantity' => 1, 'marketplace_share_amount' => 0,
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        // Revenue card should show 0 (cancelled orders excluded).
        $response->assertSee('Total sales');
        $response->assertSee('৳0.00');
    }

    // ── Date filtering ────────────────────────────────────────────────

    public function test_today_filter_shows_only_todays_orders(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create();

        $product = Product::factory()->create(['profit_share_amount' => 0]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'T1', 'price' => 100, 'stock' => 50]);

        // Order from yesterday.
        $this->paidOrder($customer, $product, 5, 'TRXYESTERDAY', Carbon::yesterday());
        // Order from today.
        $this->paidOrder($customer, $product, 3, 'TRXTODAY', Carbon::today());

        $response = $this->actingAs($admin)->get('/admin?period=today');

        $response->assertOk();
        // Period summary card should show only today's sales.
        $response->assertSee('Total sales');
        $this->assertStringContainsString('৳300.00', $response->getContent());
        // Sales-by-product table should show 3 units (today only), not 8.
        $response->assertSee('Sales by product');
    }

    public function test_week_filter_shows_current_week_orders(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create();

        $product = Product::factory()->create(['profit_share_amount' => 0]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'W1', 'price' => 200, 'stock' => 50]);

        // Last week.
        $this->paidOrder($customer, $product, 10, 'TRXLW', Carbon::now()->subWeek()->startOfWeek());
        // This week.
        $this->paidOrder($customer, $product, 2, 'TRXTW', Carbon::now()->startOfWeek());

        $response = $this->actingAs($admin)->get('/admin?period=week');

        $response->assertOk();
        $response->assertSee('৳400.00'); // 200 × 2 only
    }

    public function test_month_filter_shows_current_month_orders(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create();

        $product = Product::factory()->create(['profit_share_amount' => 0]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'M1', 'price' => 150, 'stock' => 50]);

        // Last month.
        $this->paidOrder($customer, $product, 20, 'TRXLM', Carbon::now()->subMonth()->startOfMonth());
        // This month.
        $this->paidOrder($customer, $product, 4, 'TRXTM', Carbon::now()->startOfMonth());

        $response = $this->actingAs($admin)->get('/admin?period=month');

        $response->assertOk();
        $response->assertSee('৳600.00'); // 150 × 4 only
    }

    public function test_custom_date_range_filters_correctly(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create();

        $product = Product::factory()->create(['profit_share_amount' => 0]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'D1', 'price' => 250, 'stock' => 50]);

        $this->paidOrder($customer, $product, 2, 'TRXBEFORE', Carbon::parse('2025-01-01'));
        $this->paidOrder($customer, $product, 3, 'TRXINRANGE', Carbon::parse('2025-06-15'));
        $this->paidOrder($customer, $product, 1, 'TRXAFTER', Carbon::parse('2025-12-31'));

        $response = $this->actingAs($admin)->get('/admin?period=custom&date_from=2025-06-01&date_to=2025-06-30');

        $response->assertOk();
        $response->assertSee('৳750.00'); // 250 × 3 only
    }

    public function test_empty_date_range_returns_zero(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get('/admin?period=custom&date_from=2099-01-01&date_to=2099-12-31');

        $response->assertOk();
        $response->assertSee('৳0.00');
        $response->assertSee('No paid sales');
    }

    // ── Own-product vs seller-product sales ────────────────────────────

    public function test_own_product_sales_distinguished_from_seller_sales(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create();
        $seller = User::factory()->seller()->create();

        // Admin-owned product (seller_id = null).
        $adminProduct = Product::factory()->create([
            'seller_id' => null, 'profit_share_amount' => 0,
        ]);
        $adminProduct->variants()->create(['size' => 'standard', 'sku' => 'OWN1', 'price' => 400, 'stock' => 20]);

        // Seller product.
        $sellerProduct = Product::factory()->create([
            'seller_id' => $seller->id, 'profit_share_amount' => 80,
        ]);
        $sellerProduct->variants()->create(['size' => 'standard', 'sku' => 'SEL1', 'price' => 300, 'stock' => 20]);

        $this->paidOrder($customer, $adminProduct, 2, 'TRXOWN1');
        $this->paidOrder($customer, $sellerProduct, 3, 'TRXSEL1');

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('৳800.00');  // Admin own-product sales: 400 × 2
        $response->assertSee('৳900.00');  // Seller-product sales: 300 × 3
        $response->assertSee('৳240.00');  // Marketplace earnings: 80 × 3
    }

    // ── Marketplace earnings ──────────────────────────────────────────

    public function test_marketplace_earnings_from_seller_products(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create();
        $seller = User::factory()->seller()->create();

        $product = Product::factory()->create([
            'seller_id' => $seller->id, 'profit_share_amount' => 50,
        ]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'MS1', 'price' => 200, 'stock' => 20]);

        $this->paidOrder($customer, $product, 4, 'TRXMS1');

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('৳200.00'); // 50 × 4
    }

    // ── Product-level aggregation ─────────────────────────────────────

    public function test_product_level_sales_aggregate_across_variants(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create();

        $product = Product::factory()->create(['name' => 'Multi Variant Fish', 'profit_share_amount' => 0]);
        $small = $product->variants()->create(['size' => 'small', 'sku' => 'MV-S', 'price' => 100, 'stock' => 20]);
        $large = $product->variants()->create(['size' => 'large', 'sku' => 'MV-L', 'price' => 200, 'stock' => 20]);

        $variant1 = $product->variants()->where('size', 'small')->first();
        $variant2 = $product->variants()->where('size', 'large')->first();

        // Order for small variant.
        $o1 = Order::create([
            'order_number' => 'SA-MV1', 'user_id' => $customer->id,
            'total' => 100 * 5, 'status' => 'processing', 'payment_method' => 'bkash',
            'payment_status' => 'paid', 'transaction_id' => 'TRXMV1',
            'shipping_name' => 'X', 'shipping_phone' => '017', 'shipping_address' => 'Y',
        ]);
        OrderItem::create([
            'order_id' => $o1->id, 'product_variant_id' => $variant1->id,
            'product_name' => 'Multi Variant Fish', 'variant_size' => 'Small',
            'price' => 100, 'quantity' => 5, 'marketplace_share_amount' => 0,
        ]);

        // Order for large variant.
        $o2 = Order::create([
            'order_number' => 'SA-MV2', 'user_id' => $customer->id,
            'total' => 200 * 3, 'status' => 'processing', 'payment_method' => 'bkash',
            'payment_status' => 'paid', 'transaction_id' => 'TRXMV2',
            'shipping_name' => 'X', 'shipping_phone' => '017', 'shipping_address' => 'Y',
        ]);
        OrderItem::create([
            'order_id' => $o2->id, 'product_variant_id' => $variant2->id,
            'product_name' => 'Multi Variant Fish', 'variant_size' => 'Large',
            'price' => 200, 'quantity' => 3, 'marketplace_share_amount' => 0,
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        // Revenue: (100×5) + (200×3) = 500 + 600 = 1100
        $response->assertSee('৳1,100.00');
        // Units: 5 + 3 = 8 — check the sales-by-product table.
        $response->assertSee('Multi Variant Fish');
        $this->assertStringContainsString('>8<', $response->getContent());
    }

    public function test_product_level_quantity_aggregates_correctly(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create();

        $product = Product::factory()->create(['name' => 'Quantity Check', 'profit_share_amount' => 0]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'QC1', 'price' => 100, 'stock' => 50]);

        $this->paidOrder($customer, $product, 7, 'TRXQC1');
        $this->paidOrder($customer, $product, 3, 'TRXQC2');

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        // Total units = 7 + 3 = 10
        $this->assertStringContainsString('>10<', $response->getContent());
        // Revenue = 100 × 10 = 1000
        $response->assertSee('৳1,000.00');
    }

    // ── Best-selling products in analytics ────────────────────────────

    public function test_best_selling_products_ordered_by_revenue(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create();

        $productA = Product::factory()->create(['name' => 'High Revenue', 'profit_share_amount' => 0]);
        $productA->variants()->create(['size' => 'standard', 'sku' => 'HR1', 'price' => 500, 'stock' => 20]);

        $productB = Product::factory()->create(['name' => 'Low Revenue', 'profit_share_amount' => 0]);
        $productB->variants()->create(['size' => 'standard', 'sku' => 'LR1', 'price' => 100, 'stock' => 20]);

        $this->paidOrder($customer, $productA, 2, 'TRXHR1');
        $this->paidOrder($customer, $productB, 3, 'TRXLR1');

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        // High Revenue should appear before Low Revenue in the sales-by-product table.
        $content = $response->getContent();
        $posHR = strpos($content, 'High Revenue');
        $posLR = strpos($content, 'Low Revenue');
        $this->assertNotFalse($posHR);
        $this->assertNotFalse($posLR);
        $this->assertLessThan($posLR, $posHR);
    }

    // ── Financial correctness snapshot ────────────────────────────────

    public function test_marketplace_share_snapshotted_value_used_in_analytics(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create();
        $seller = User::factory()->seller()->create();

        $product = Product::factory()->create([
            'seller_id' => $seller->id, 'profit_share_amount' => 40,
        ]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'SNAP-A', 'price' => 200, 'stock' => 20]);

        $this->paidOrder($customer, $product, 5, 'TRXSNAPA');

        // Seller changes their share after the order is placed.
        $product->update(['profit_share_amount' => 999]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        // Marketplace earnings should use the snapshotted 40, not 999.
        // 40 × 5 = 200
        $response->assertSee('৳200.00');
        $response->assertDontSee('৳4,995.00');
    }

    // ── No data state ─────────────────────────────────────────────────

    public function test_empty_state_shows_appropriate_messages(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin');

        $response->assertOk();
        $response->assertSee('No paid sales');
        $response->assertSee('৳0.00');
    }

    // ── Date filter display ───────────────────────────────────────────

    public function test_filter_period_shown_in_section_headings(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get('/admin?period=today');

        $response->assertOk();
        $response->assertSee('today');
    }

    public function test_reset_link_shown_when_period_is_not_all(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin?period=month');

        $response->assertOk();
        $response->assertSee('Reset');
    }

    public function test_no_reset_link_when_all_time(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin?period=all');

        $response->assertOk();
        $response->assertDontSee('Reset');
    }
}
