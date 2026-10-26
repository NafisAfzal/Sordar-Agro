<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_change_rotates_remember_token_and_updates_session_hash(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $oldToken = $user->remember_token;
        $oldHash = $user->password;

        $this->actingAs($user);

        // Prime AuthenticateSession so the session carries the old hash.
        $this->get('/change-password')->assertOk();

        $this->post('/change-password', [
            'current_password' => 'old-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect();

        $user->refresh();

        $this->assertNotSame($oldToken, $user->remember_token);
        $this->assertTrue(Hash::check('new-password-123', $user->password));
        $this->assertAuthenticatedAs($user);

        // The current session carries a rotated password digest, not the old one.
        $this->assertNotNull(session('password_hash_web'));
        $this->assertNotSame($oldHash, session('password_hash_web'));
    }

    public function test_sessions_holding_the_old_password_hash_are_logged_out(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $oldHash = $user->password;

        $this->actingAs($user);

        // Simulate a session that authenticated before the password change.
        $this->withSession(['password_hash_web' => $oldHash]);

        $this->post('/change-password', [
            'current_password' => 'old-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect();

        // Another device still holds the old hash and must be signed out.
        $this->withSession(['password_hash_web' => $oldHash]);
        $this->get('/')->assertRedirect();
        $this->assertGuest();
    }

    public function test_forced_password_change_does_not_require_the_temporary_password(): void
    {
        $user = User::factory()->create([
            'password' => 'temp-password',
            'must_change_password' => true,
        ]);

        $this->actingAs($user)->post('/change-password', [
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertRedirect();

        $user->refresh();

        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('brand-new-password', $user->password));
    }
}
