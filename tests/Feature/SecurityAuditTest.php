<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\CommunitySubmission;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Security regression suite added during the Step 8 security audit.
 * Locks in authentication boundaries, ownership (IDOR) checks, role
 * separation, CSRF middleware wiring and XSS escaping behaviour.
 */
class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    // ---- Authentication -------------------------------------------------

    public function test_login_rejects_wrong_password(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);

        $response = $this->post('/login', [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_rejects_nonexistent_account(): void
    {
        $response = $this->post('/login', [
            'email'    => 'nobody@example.com',
            'password' => 'whatever123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_rejects_empty_and_malformed_credentials(): void
    {
        $this->post('/login', [])->assertSessionHasErrors(['email', 'password']);
        $this->post('/login', ['email' => 'not-an-email', 'password' => 'x'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_user_cannot_reach_guest_only_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/login')->assertRedirect(route('home'));
        $this->actingAs($user)->get('/register')->assertRedirect(route('home'));
    }

    public function test_registration_payload_cannot_assign_privileged_role_or_flags(): void
    {
        $response = $this->post('/register', [
            'name'     => 'Escalator',
            'email'    => 'escalator@example.com',
            'phone'    => '01700000001',
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
            'role'     => 'admin',
            'is_active' => false,
            'must_change_password' => false,
        ]);

        $response->assertRedirect(route('home'));

        $user = User::where('email', 'escalator@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('customer', $user->role);
        $this->assertTrue((bool) $user->is_active);
    }

    // ---- Ownership / IDOR -----------------------------------------------

    private function makeOrder(User $user): Order
    {
        return Order::create([
            'order_number'     => 'SA-IDOR001',
            'user_id'          => $user->id,
            'total'            => 250,
            'status'           => 'processing',
            'payment_method'   => 'bkash',
            'payment_status'   => 'unpaid',
            'shipping_name'    => 'Alice',
            'shipping_phone'   => '01700000000',
            'shipping_address' => 'Dhaka',
        ]);
    }

    public function test_user_cannot_open_another_users_payment_page(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $order = $this->makeOrder($alice);

        $this->actingAs($bob)->get("/payment/{$order->id}")->assertForbidden();
    }

    public function test_user_cannot_pay_another_users_order(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $order = $this->makeOrder($alice);

        $this->actingAs($bob)->post("/payment/{$order->id}", [
            'transaction_id' => 'E2ETRX-SHOULD-NOT-EXIST-001',
        ])->assertForbidden();

        $this->assertDatabaseMissing('orders', [
            'id'             => $order->id,
            'transaction_id' => 'E2ETRX-SHOULD-NOT-EXIST-001',
        ]);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_user_cannot_update_another_users_cart_row(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $product = Product::factory()->create();
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size'       => 'standard',
            'price'      => 100,
            'stock'      => 50,
            'sku'        => 'SKU-IDOR-1',
        ]);

        $cart = Cart::create([
            'user_id'            => $alice->id,
            'product_variant_id' => $variant->id,
            'quantity'           => 2,
        ]);

        $this->actingAs($bob)->patch("/cart/{$cart->id}", ['quantity' => 9])->assertForbidden();
        $this->actingAs($bob)->delete("/cart/{$cart->id}")->assertForbidden();

        $this->assertSame(2, $cart->fresh()->quantity);
        $this->assertNotNull(Cart::find($cart->id));
    }

    public function test_user_cannot_remove_another_users_wishlist_row(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $product = Product::factory()->create();
        $wishlist = Wishlist::create([
            'user_id'    => $alice->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($bob)->delete("/wishlist/{$wishlist->id}")->assertForbidden();

        $this->assertNotNull(Wishlist::find($wishlist->id));
    }

    // ---- Role separation --------------------------------------------------

    public function test_customer_cannot_adjust_inventory(): void
    {
        $customer = User::factory()->create();

        $product = Product::factory()->create();
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size'       => 'standard',
            'price'      => 100,
            'stock'      => 40,
            'sku'        => 'SKU-IDOR-2',
        ]);

        $this->actingAs($customer)->patch("/admin/variants/{$variant->id}/adjust", [
            'delta' => -30,
        ])->assertForbidden();

        $this->assertSame(40, $variant->fresh()->stock);
    }

    public function test_seller_cannot_approve_products(): void
    {
        $seller = User::factory()->seller()->create();

        $product = Product::factory()->create(['status' => 'pending']);

        $this->actingAs($seller)->patch("/admin/products/{$product->slug}/approve")->assertForbidden();

        $this->assertSame('pending', $product->fresh()->status);
    }

    public function test_suspended_account_is_blocked_by_role_middleware(): void
    {
        $customer = User::factory()->create(['is_active' => false]);

        $this->actingAs($customer)->get('/cart')->assertForbidden();
    }

    // ---- Checkout gating ---------------------------------------------------

    public function test_unverified_user_is_blocked_from_checkout_and_payment(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $order = $this->makeOrder($user);

        $this->actingAs($user)->get('/checkout')->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get("/payment/{$order->id}")->assertRedirect(route('verification.notice'));
    }

    // ---- CSRF wiring -------------------------------------------------------

    public function test_state_changing_routes_run_inside_the_web_group_with_csrf(): void
    {
        $groups = $this->app->make(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewareGroups();
        $this->assertArrayHasKey('web', $groups);

        $csrfPresent = collect($groups['web'])->contains(
            fn ($m) => str_contains(is_string($m) ? $m : '', \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
                || str_contains(is_string($m) ? $m : '', 'VerifyCsrfToken')
        );
        $this->assertTrue($csrfPresent, 'The web middleware group must enforce CSRF.');

        foreach (['logout', 'cart.add', 'cart.update', 'cart.remove', 'checkout.place',
                  'payment.process', 'wishlist.add', 'wishlist.remove',
                  'community.store', 'seller.products.store'] as $name) {
            $route = app('router')->getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route {$name} must exist.");
            $this->assertContains('web', $route->gatherMiddleware(), "Route {$name} must be in the web group.");
        }
    }

    // ---- Security headers -------------------------------------------------

    public function test_hardening_headers_are_present_on_responses(): void
    {
        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    // ---- Input validation / XSS -------------------------------------------

    public function test_stored_community_content_is_rendered_escaped(): void
    {
        $author = User::factory()->create();
        $payload = '<script>alert(1)</script>';

        CommunitySubmission::create([
            'user_id' => $author->id,
            'title'   => $payload.' title',
            'body'    => $payload.' body <img src=x onerror=alert(1)>',
            'status'  => 'approved',
        ]);

        $response = $this->get('/community');

        $response->assertOk();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $response->getContent());
        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $response->getContent());
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $response->getContent());
    }

    public function test_search_terms_are_reflected_escaped(): void
    {
        Product::factory()->create(['name' => 'Betta Splendens']);

        $response = $this->get('/products?q=<script>alert(1)</script>');
        $response->assertOk();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $response->getContent());

        $injection = $this->get("/products?q=".urlencode("' OR '1'='1"));
        $injection->assertOk();
    }

    public function test_product_suggestions_endpoint_escapes_and_limits_output(): void
    {
        Product::factory()->create(['name' => 'Angel Fish']);

        $response = $this->getJson('/search/suggestions?q='.urlencode("'<>&\""));

        $response->assertOk();
        $this->assertIsArray($response->json());
    }

    // ---- Payment integrity --------------------------------------------------

    public function test_client_cannot_influence_order_total_or_payment_status(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create();
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size'       => 'standard',
            'price'      => 500,
            'stock'      => 10,
            'sku'        => 'SKU-PAY-1',
        ]);
        Cart::create([
            'user_id'            => $user->id,
            'product_variant_id' => $variant->id,
            'quantity'           => 1,
        ]);

        $this->actingAs($user)->post('/checkout', [
            'shipping_name'    => 'Tester',
            'shipping_phone'   => '01700000000',
            'shipping_address' => 'E2E TEST ROAD',
            'payment_method'   => 'bkash',
            'total'            => 1,          // spoofed field — must be ignored
            'payment_status'   => 'paid',     // spoofed field — must be ignored
            'status'           => 'delivered',// spoofed field — must be ignored
        ]);

        $order = Order::where('user_id', $user->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals('500.00', (string) $order->total);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame('processing', $order->status);
    }
}
