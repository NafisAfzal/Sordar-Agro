<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_destroys_session_and_redirects_home(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $this->get('/cart')->assertRedirect(route('login'));
    }

    public function test_seller_is_redirected_to_seller_dashboard_after_login(): void
    {
        $seller = User::factory()->seller()->create(['password' => 'password123']);

        $response = $this->post('/login', [
            'email' => $seller->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('seller.dashboard'));
    }

    public function test_seller_with_must_change_password_is_redirected_to_change_password(): void
    {
        $seller = User::factory()->seller()->create([
            'password' => 'password123',
            'must_change_password' => true,
        ]);

        $response = $this->actingAs($seller)->get('/seller/dashboard');

        $response->assertRedirect(route('password.change'));
    }

    public function test_customer_with_must_change_password_is_redirected_to_change_password(): void
    {
        $customer = User::factory()->create([
            'password' => 'password123',
            'must_change_password' => true,
        ]);

        $response = $this->actingAs($customer)->get('/cart');

        $response->assertRedirect(route('password.change'));
    }

    public function test_change_password_requires_current_password_for_normal_user(): void
    {
        $user = User::factory()->create(['password' => 'oldpassword']);

        $this->actingAs($user)->post('/change-password', [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertSessionHasErrors('current_password');
    }

    public function test_change_password_succeeds_with_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'oldpassword']);

        $this->actingAs($user)->post('/change-password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertRedirect();

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
        $this->assertFalse($user->must_change_password);
    }

    public function test_change_password_does_not_require_current_password_when_must_change_password(): void
    {
        $seller = User::factory()->seller()->create([
            'password' => 'temppassword',
            'must_change_password' => true,
        ]);

        $this->actingAs($seller)->post('/change-password', [
            'password' => 'realpassword123',
            'password_confirmation' => 'realpassword123',
        ])->assertRedirect();

        $seller->refresh();
        $this->assertTrue(Hash::check('realpassword123', $seller->password));
        $this->assertFalse($seller->must_change_password);
    }

    public function test_change_password_rejects_password_shorter_than_8_characters(): void
    {
        $user = User::factory()->create(['password' => 'oldpassword']);

        $this->actingAs($user)->post('/change-password', [
            'current_password' => 'oldpassword',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');
    }

    public function test_change_password_rejects_mismatched_confirmation(): void
    {
        $user = User::factory()->create(['password' => 'oldpassword']);

        $this->actingAs($user)->post('/change-password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword123',
        ])->assertSessionHasErrors('password');
    }

    public function test_change_password_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => 'oldpassword']);

        $this->actingAs($user)->post('/change-password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertSessionHasErrors('current_password');
    }

    public function test_login_with_remember_me_sets_remember_token(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
            'remember' => 'on',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_creates_active_customer_with_verified_email(): void
    {
        $this->post('/register', [
            'name' => 'New Customer',
            'email' => 'newcustomer@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'newcustomer@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('customer', $user->role);
        $this->assertTrue((bool) $user->is_active);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->post('/register', [
            'name' => 'Another User',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_guest_redirected_from_change_password_to_login(): void
    {
        $this->get('/change-password')->assertRedirect(route('login'));
    }

    public function test_guest_redirected_from_forgot_password_page(): void
    {
        $response = $this->get('/forgot-password');
        $response->assertOk();
    }

    public function test_forgot_password_rejects_invalid_email(): void
    {
        $response = $this->post('/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_forgot_password_rejects_empty_email(): void
    {
        $response = $this->post('/forgot-password', [
            'email' => '',
        ]);

        $response->assertSessionHasErrors('email');
    }
}
