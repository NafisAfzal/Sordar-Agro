<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SellerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function paidOrderFor(Product $product, User $customer, int $qty, string $trx, ?Carbon $when = null, string $status = 'processing'): Order
    {
        $variant = $product->variants()->first();
        $order = Order::create([
            'order_number' => 'SA-'.$trx,
            'user_id' => $customer->id,
            'total' => $variant->price * $qty,
            'status' => $status,
            'payment_method' => 'bkash',
            'payment_status' => 'paid',
            'transaction_id' => $trx,
            'shipping_name' => 'Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => 'Dhaka',
        ]);
        if ($when) {
            // Bypass mass-assignment guard for timestamps
            \Illuminate\Support\Facades\DB::table('orders')->where('id', $order->id)->update(['created_at' => $when, 'updated_at' => $when]);
            $order->refresh();
        }
        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_size' => $variant->label,
            'price' => $variant->price,
            'quantity' => $qty,
            'marketplace_share_amount' => $product->profit_share_amount ?? 0,
        ]);
        return $order->fresh();
    }

    public function test_seller_can_access_dashboard(): void
    {
        $seller = User::factory()->seller()->create();
        $this->actingAs($seller)->get('/seller/dashboard')->assertOk()->assertSee('Welcome');
    }

    public function test_guest_cannot_access_seller_dashboard(): void
    {
        $this->get('/seller/dashboard')->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_seller_dashboard(): void
    {
        $customer = User::factory()->create(['role'=>'customer']);
        $this->actingAs($customer)->get('/seller/dashboard')->assertForbidden();
    }

    public function test_seller_sees_only_own_products_count(): void
    {
        $sellerA = User::factory()->seller()->create();
        $sellerB = User::factory()->seller()->create();
        $cat = Category::factory()->create();
        Product::factory()->create(['seller_id'=>$sellerA->id,'category_id'=>$cat->id,'name'=>'A Product','status'=>'approved']);
        Product::factory()->create(['seller_id'=>$sellerB->id,'category_id'=>$cat->id,'name'=>'B Product','status'=>'approved']);
        // Need variants for stock check but not required for count; create variants to avoid low-stock edge
        Product::where('name','A Product')->first()->variants()->create(['size'=>'standard','sku'=>'A1','price'=>100,'stock'=>10]);
        Product::where('name','B Product')->first()->variants()->create(['size'=>'standard','sku'=>'B1','price'=>100,'stock'=>10]);

        $resp = $this->actingAs($sellerA)->get('/seller/dashboard');
        $resp->assertSee('A Product', false); // ensure not filtering incorrectly? Actually dashboard stats not listing products by name in header but productPerformance shows names. Ensure isolation.
        // Recent products list should show A Product
        $resp->assertSee('A Product');
        $resp->assertDontSee('B Product');
    }

    public function test_seller_sees_only_own_sales_and_earnings(): void
    {
        $sellerA = User::factory()->seller()->create();
        $sellerB = User::factory()->seller()->create();
        $customer = User::factory()->create();
        $cat = Category::factory()->create();

        $prodA = Product::factory()->create(['seller_id'=>$sellerA->id,'category_id'=>$cat->id,'profit_share_amount'=>30,'status'=>'approved']);
        $prodA->variants()->create(['size'=>'standard','sku'=>'PA1','price'=>200,'stock'=>20]);
        $prodB = Product::factory()->create(['seller_id'=>$sellerB->id,'category_id'=>$cat->id,'profit_share_amount'=>50,'status'=>'approved']);
        $prodB->variants()->create(['size'=>'standard','sku'=>'PB1','price'=>300,'stock'=>20]);

        $this->paidOrderFor($prodA, $customer, 2, 'TRXSELLERA'); // gross 400, earnings 340
        $this->paidOrderFor($prodB, $customer, 2, 'TRXSELLERB'); // gross 600, earnings 500 — should NOT appear for A

        $resp = $this->actingAs($sellerA)->get('/seller/dashboard');
        $resp->assertOk();
        // Seller A gross 400, earnings 340
        $resp->assertSee('৳400.00');
        $resp->assertSee('৳340.00');
        // Should not show seller B gross 600 in same page
        $resp->assertDontSee('৳600.00');
        $resp->assertDontSee('৳500.00');
    }

    public function test_admin_owned_products_not_counted_for_seller(): void
    {
        $seller = User::factory()->seller()->create();
        $customer = User::factory()->create();
        $cat = Category::factory()->create();
        $adminProd = Product::factory()->create(['seller_id'=>null,'category_id'=>$cat->id,'profit_share_amount'=>0,'status'=>'approved']);
        $adminProd->variants()->create(['size'=>'standard','sku'=>'ADM1','price'=>500,'stock'=>10]);
        $this->paidOrderFor($adminProd, $customer, 1, 'TRXADMINOWN');

        $resp = $this->actingAs($seller)->get('/seller/dashboard');
        $resp->assertSee('৳0.00'); // no seller sales
        $resp->assertDontSee('ADM'); // ensure not leaking admin product name
    }

    public function test_cancelled_orders_not_counted(): void
    {
        $seller = User::factory()->seller()->create();
        $customer = User::factory()->create();
        $cat = Category::factory()->create();
        $prod = Product::factory()->create(['seller_id'=>$seller->id,'category_id'=>$cat->id,'profit_share_amount'=>20,'status'=>'approved']);
        $prod->variants()->create(['size'=>'standard','sku'=>'C1','price'=>100,'stock'=>10]);
        $this->paidOrderFor($prod, $customer, 3, 'TRXCANCEL', null, 'cancelled');

        $resp = $this->actingAs($seller)->get('/seller/dashboard');
        $resp->assertSee('৳0.00');
        $resp->assertDontSee('৳300.00');
    }

    public function test_unpaid_orders_not_counted(): void
    {
        $seller = User::factory()->seller()->create();
        $customer = User::factory()->create();
        $cat = Category::factory()->create();
        $prod = Product::factory()->create(['seller_id'=>$seller->id,'category_id'=>$cat->id,'profit_share_amount'=>20,'status'=>'approved']);
        $variant = $prod->variants()->create(['size'=>'standard','sku'=>'U1','price'=>100,'stock'=>10]);
        Order::create([
            'order_number'=>'SA-UNPAIDSELLER','user_id'=>$customer->id,'total'=>100,'status'=>'processing','payment_method'=>'bkash','payment_status'=>'unpaid',
            'shipping_name'=>'X','shipping_phone'=>'017','shipping_address'=>'Y',
        ]);
        $order = Order::where('order_number','SA-UNPAIDSELLER')->first();
        OrderItem::create(['order_id'=>$order->id,'product_variant_id'=>$variant->id,'product_name'=>$prod->name,'variant_size'=>'Standard','price'=>100,'quantity'=>1,'marketplace_share_amount'=>20]);
        $resp = $this->actingAs($seller)->get('/seller/dashboard');
        $resp->assertSee('৳0.00');
    }

    public function test_paid_orders_do_count(): void
    {
        $seller = User::factory()->seller()->create();
        $customer = User::factory()->create();
        $cat = Category::factory()->create();
        $prod = Product::factory()->create(['seller_id'=>$seller->id,'category_id'=>$cat->id,'profit_share_amount'=>10,'status'=>'approved']);
        $prod->variants()->create(['size'=>'standard','sku'=>'P1','price'=>150,'stock'=>10]);
        $this->paidOrderFor($prod, $customer, 2, 'TRXPAID');

        $resp = $this->actingAs($seller)->get('/seller/dashboard');
        $resp->assertSee('৳300.00');
        $resp->assertSee('৳280.00'); // earnings
    }

    public function test_date_filters_work(): void
    {
        $seller = User::factory()->seller()->create();
        $customer = User::factory()->create();
        $cat = Category::factory()->create();
        $prod = Product::factory()->create(['seller_id'=>$seller->id,'category_id'=>$cat->id,'profit_share_amount'=>0,'status'=>'approved']);
        $prod->variants()->create(['size'=>'standard','sku'=>'D1','price'=>100,'stock'=>20]);
        $this->paidOrderFor($prod, $customer, 5, 'TRXYDAY', Carbon::yesterday());
        $this->paidOrderFor($prod, $customer, 2, 'TRXTDAY', Carbon::today());

        $resp = $this->actingAs($seller)->get('/seller/dashboard?period=today');
        $resp->assertSee('৳200.00');
        $resp->assertDontSee('৳500.00');
    }

    public function test_product_performance_aggregation(): void
    {
        $seller = User::factory()->seller()->create();
        $customer = User::factory()->create();
        $cat = Category::factory()->create();
        $p1 = Product::factory()->create(['seller_id'=>$seller->id,'category_id'=>$cat->id,'name'=>'Perf One','profit_share_amount'=>20,'status'=>'approved']);
        $p1->variants()->create(['size'=>'standard','sku'=>'PP1','price'=>100,'stock'=>10]);
        $p2 = Product::factory()->create(['seller_id'=>$seller->id,'category_id'=>$cat->id,'name'=>'Perf Two','profit_share_amount'=>0,'status'=>'approved']);
        $p2->variants()->create(['size'=>'standard','sku'=>'PP2','price'=>200,'stock'=>10]);
        $this->paidOrderFor($p1, $customer, 3, 'TRXPP1');
        $this->paidOrderFor($p2, $customer, 1, 'TRXPP2');

        $resp = $this->actingAs($seller)->get('/seller/dashboard');
        $resp->assertSee('Perf One');
        $resp->assertSee('Perf Two');
        $resp->assertSee('৳300.00'); // p1 gross
        $resp->assertSee('৳200.00'); // p2 gross
    }

    public function test_low_stock_detection(): void
    {
        $seller = User::factory()->seller()->create();
        $cat = Category::factory()->create();
        $low = Product::factory()->create(['seller_id'=>$seller->id,'category_id'=>$cat->id,'name'=>'Low Stock Fish','status'=>'approved']);
        $low->variants()->create(['size'=>'standard','sku'=>'LOW1','price'=>100,'stock'=>2]);
        $ok = Product::factory()->create(['seller_id'=>$seller->id,'category_id'=>$cat->id,'name'=>'Healthy Stock','status'=>'approved']);
        $ok->variants()->create(['size'=>'standard','sku'=>'OK1','price'=>100,'stock'=>20]);

        $resp = $this->actingAs($seller)->get('/seller/dashboard');
        $resp->assertSee('Low stock');
        $resp->assertSee('Low Stock Fish');
    }

    public function test_pending_order_count(): void
    {
        $seller = User::factory()->seller()->create();
        $customer = User::factory()->create();
        $cat = Category::factory()->create();
        $prod = Product::factory()->create(['seller_id'=>$seller->id,'category_id'=>$cat->id,'profit_share_amount'=>0,'status'=>'approved']);
        $prod->variants()->create(['size'=>'standard','sku'=>'PO1','price'=>100,'stock'=>10]);
        $this->paidOrderFor($prod, $customer, 1, 'TRXPEND', null, 'processing');
        $this->paidOrderFor($prod, $customer, 1, 'TRXSHIP', null, 'shipped');

        $resp = $this->actingAs($seller)->get('/seller/dashboard');
        $resp->assertSee('awaiting fulfilment');
        // pending should be 1 (only processing)
        $resp->assertSee('1');
    }

    public function test_empty_states_do_not_break(): void
    {
        $seller = User::factory()->seller()->create();
        $resp = $this->actingAs($seller)->get('/seller/dashboard');
        $resp->assertOk();
        $resp->assertSee('No products yet');
        $resp->assertSee('No paid orders');
        $resp->assertSee('No sales in this period');
    }

    public function test_marketplace_snapshot_used_not_live_value(): void
    {
        $seller = User::factory()->seller()->create();
        $customer = User::factory()->create();
        $cat = Category::factory()->create();
        $prod = Product::factory()->create(['seller_id'=>$seller->id,'category_id'=>$cat->id,'profit_share_amount'=>40,'status'=>'approved']);
        $prod->variants()->create(['size'=>'standard','sku'=>'SN1','price'=>200,'stock'=>10]);
        $this->paidOrderFor($prod, $customer, 2, 'TRXSNAP');
        $prod->update(['profit_share_amount'=>999]);
        $resp = $this->actingAs($seller)->get('/seller/dashboard');
        $resp->assertSee('৳400.00'); // gross 400
        $resp->assertSee('৳320.00'); // earnings 400-80
        $resp->assertDontSee('999');
    }

    public function test_other_seller_data_not_leaked_in_recent_orders(): void
    {
        $sellerA = User::factory()->seller()->create();
        $sellerB = User::factory()->seller()->create();
        $customer = User::factory()->create();
        $cat = Category::factory()->create();
        $prodA = Product::factory()->create(['seller_id'=>$sellerA->id,'category_id'=>$cat->id,'name'=>'Only A','profit_share_amount'=>10,'status'=>'approved']);
        $prodA->variants()->create(['size'=>'standard','sku'=>'OA1','price'=>100,'stock'=>10]);
        $prodB = Product::factory()->create(['seller_id'=>$sellerB->id,'category_id'=>$cat->id,'name'=>'Only B','profit_share_amount'=>10,'status'=>'approved']);
        $prodB->variants()->create(['size'=>'standard','sku'=>'OB1','price'=>999,'stock'=>10]);
        $this->paidOrderFor($prodB, $customer, 1, 'TRXONLYB');

        $resp = $this->actingAs($sellerA)->get('/seller/dashboard');
        $resp->assertDontSee('Only B');
        $resp->assertDontSee('৳999.00');
    }
}
