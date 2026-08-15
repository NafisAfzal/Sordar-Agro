<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoundaryValueTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_exactly_8_characters_is_accepted(): void
    {
        $response = $this->post('/register', [
            'name' => 'Boundary Test',
            'email' => 'boundary8@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'boundary8@example.com']);
    }

    public function test_password_7_characters_is_rejected(): void
    {
        $response = $this->post('/register', [
            'name' => 'Boundary Test',
            'email' => 'boundary7@example.com',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'boundary7@example.com']);
    }

    public function test_cart_quantity_at_exact_stock_limit_is_allowed(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 5, price: 100)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 5]);

        $this->assertDatabaseHas('carts', [
            'user_id' => $customer->id,
            'quantity' => 5,
        ]);
    }

    public function test_cart_quantity_exceeding_stock_is_capped(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 5, price: 100)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 10]);

        $this->assertDatabaseHas('carts', [
            'user_id' => $customer->id,
            'quantity' => 5,
        ]);
    }
}