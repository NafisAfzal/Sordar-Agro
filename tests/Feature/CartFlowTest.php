<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_index_shows_user_items(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 5, price: 100)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 2]);

        $response = $this->actingAs($customer)->get('/cart');
        $response->assertOk();
        $response->assertSee($product->name);
    }

    public function test_adding_same_variant_twice_merges_quantity(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 10, price: 100)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 2]);
        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 3]);

        $this->assertDatabaseHas('carts', [
            'user_id' => $customer->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
        ]);
    }

    public function test_adding_variant_cannot_exceed_stock(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 3, price: 100)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 5]);

        $this->assertDatabaseHas('carts', [
            'user_id' => $customer->id,
            'quantity' => 3,
        ]);
    }

    public function test_cart_update_cannot_exceed_stock(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 3, price: 100)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 1]);
        $cart = Cart::where('user_id', $customer->id)->first();

        $this->actingAs($customer)->patch("/cart/{$cart->id}", ['quantity' => 10]);

        $this->assertSame(3, $cart->fresh()->quantity);
    }

    public function test_cart_update_returns_json_when_requested(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 10, price: 100)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 1]);
        $cart = Cart::where('user_id', $customer->id)->first();

        $response = $this->actingAs($customer)->patchJson("/cart/{$cart->id}", ['quantity' => 3]);

        $response->assertOk();
        $response->assertJson(['quantity' => 3]);
    }

    public function test_cart_update_redirects_for_non_json_request(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 10, price: 100)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 1]);
        $cart = Cart::where('user_id', $customer->id)->first();

        $response = $this->actingAs($customer)->patch("/cart/{$cart->id}", ['quantity' => 3]);

        $response->assertRedirect();
    }

    public function test_cart_remove_deletes_item(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 5, price: 100)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 1]);
        $cart = Cart::where('user_id', $customer->id)->first();

        $this->actingAs($customer)->delete("/cart/{$cart->id}");

        $this->assertDatabaseMissing('carts', ['id' => $cart->id]);
    }

    public function test_cart_total_reflects_variants_and_quantities(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 10, price: 200)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => 3]);

        $response = $this->actingAs($customer)->get('/cart');
        $response->assertOk();
        $response->assertSee('600');
    }

    public function test_cart_default_quantity_is_one(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 5, price: 100)->create();
        $variant = $product->variants()->first();

        $this->actingAs($customer)->post("/cart/{$variant->id}");

        $this->assertDatabaseHas('carts', [
            'user_id' => $customer->id,
            'quantity' => 1,
        ]);
    }

    public function test_customer_cannot_update_other_customers_cart(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 5, price: 100)->create();
        $variant = $product->variants()->first();

        $this->actingAs($alice)->post("/cart/{$variant->id}", ['quantity' => 2]);
        $cart = Cart::where('user_id', $alice->id)->first();

        $this->actingAs($bob)->patch("/cart/{$cart->id}", ['quantity' => 9])->assertForbidden();
        $this->assertSame(2, $cart->fresh()->quantity);
    }

    public function test_customer_cannot_remove_other_customers_cart(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 5, price: 100)->create();
        $variant = $product->variants()->first();

        $this->actingAs($alice)->post("/cart/{$variant->id}", ['quantity' => 1]);
        $cart = Cart::where('user_id', $alice->id)->first();

        $this->actingAs($bob)->delete("/cart/{$cart->id}")->assertForbidden();
        $this->assertNotNull(Cart::find($cart->id));
    }
}
