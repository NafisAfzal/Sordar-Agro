<?php

namespace Tests\Feature;

use App\Models\CommunitySubmission;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    private function loginError(): string
    {
        $errors = session('errors');

        return $errors ? (string) $errors->first('email') : '';
    }

    public function test_sixth_failed_login_for_same_email_and_ip_is_throttled(): void
    {
        $email = 'victim@example.com';

        User::factory()->create(['email' => $email, 'password' => 'password']);

        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', ['email' => $email, 'password' => 'wrong-password']);
            $response->assertSessionHasErrors('email');
            $this->assertStringNotContainsString('Too many login attempts', $this->loginError());
        }

        // The 6th attempt for the same email+IP pair is refused.
        $response = $this->post('/login', ['email' => $email, 'password' => 'wrong-password']);
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many login attempts', $this->loginError());
    }

    public function test_successful_login_clears_the_login_limiter(): void
    {
        $email = 'customer@example.com';

        User::factory()->create(['email' => $email, 'password' => 'password']);

        // Four failures consume most of the per-email+IP allowance.
        for ($i = 0; $i < 4; $i++) {
            $this->post('/login', ['email' => $email, 'password' => 'wrong-password'])
                ->assertSessionHasErrors('email');
        }

        // A successful login (the 5th attempt) still fits inside the limit and
        // clears the throttle so a legitimate user is not locked out.
        $this->post('/login', ['email' => $email, 'password' => 'password'])->assertRedirect();
        $this->assertAuthenticatedAs(User::where('email', $email)->first());

        // Log out so the guest middleware lets the next attempts reach the route.
        $this->post('/logout');

        // Five more failures are allowed again because the limiter was cleared.
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $email, 'password' => 'wrong-password'])
                ->assertSessionHasErrors('email');
            $this->assertStringNotContainsString('Too many login attempts', $this->loginError());
        }

        // The 6th failure after the clear is throttled once more.
        $this->post('/login', ['email' => $email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many login attempts', $this->loginError());
    }

    public function test_registration_is_rate_limited(): void
    {
        $register = fn (int $i) => $this->post('/register', [
            'name' => 'User '.$i,
            'email' => 'reg'.$i.'@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $register($i)->assertSessionHasNoErrors();
            // Registration logs the user in, which the guest middleware would
            // otherwise redirect; log out so the next attempt reaches the route.
            $this->post('/logout');
        }

        // The 6th registration from the same IP is refused.
        $register(5)->assertStatus(429);
    }

    public function test_password_reset_requests_are_rate_limited(): void
    {
        $email = 'somebody@example.com';

        for ($i = 0; $i < 3; $i++) {
            $this->post('/forgot-password', ['email' => $email])->assertStatus(302);
        }

        // The 4th request for the same address+IP is refused.
        $this->post('/forgot-password', ['email' => $email])->assertStatus(429);
    }

    public function test_password_reset_does_not_reveal_unknown_email_addresses(): void
    {
        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();
    }

    public function test_community_submission_is_rate_limited(): void
    {
        $customer = User::factory()->create();

        $submit = fn (int $i) => $this->actingAs($customer)->post('/community/submit', [
            'title' => 'Submission '.$i,
            'body' => str_repeat('This is a community submission body. ', $i + 1),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $submit($i)->assertSessionHas('success');
        }

        // The 6th submission from the same user is refused.
        $submit(5)->assertStatus(429);

        $this->assertCount(5, CommunitySubmission::query()->get());
    }

    public function test_payment_submission_is_rate_limited(): void
    {
        $customer = User::factory()->create(['email_verified_at' => now()]);
        $product = Product::factory()->withVariant(stock: 10, price: 100)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 1]);

        $this->actingAs($customer)->post('/checkout', [
            'shipping_name' => 'Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => 'House 1, Road 1, Dhaka',
            'payment_method' => 'bkash',
        ]);

        $order = Order::where('user_id', $customer->id)->latest()->first();

        for ($i = 0; $i < 6; $i++) {
            $this->post("/payment/{$order->id}", [
                'transaction_id' => 'TRX'.str_pad($i, 6, '0', STR_PAD_LEFT),
            ]);
        }

        // The 7th payment submission from the same user is refused.
        $this->post("/payment/{$order->id}", [
            'transaction_id' => 'TRXFINAL',
        ])->assertStatus(429);
    }

    public function test_suggestions_endpoint_is_not_throttled_for_normal_use(): void
    {
        $this->get('/search/suggestions?q=widget')->assertSuccessful();
    }
}
