<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_increase_a_variants_stock_level(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->withVariant(stock: 5, price: 100)->create();
        $variant = $product->variants()->first();

        $this->actingAs($admin)->patch("/admin/variants/{$variant->id}/adjust", ['delta' => 10]);

        $this->assertSame(15, $variant->fresh()->stock);
    }

    public function test_admin_can_decrease_a_variants_stock_level(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->withVariant(stock: 5, price: 100)->create();
        $variant = $product->variants()->first();

        $this->actingAs($admin)->patch("/admin/variants/{$variant->id}/adjust", ['delta' => -3]);

        $this->assertSame(2, $variant->fresh()->stock);
    }

    public function test_stock_cannot_be_pushed_below_zero(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->withVariant(stock: 5, price: 100)->create();
        $variant = $product->variants()->first();

        $this->actingAs($admin)->patch("/admin/variants/{$variant->id}/adjust", ['delta' => -50]);

        $this->assertSame(0, $variant->fresh()->stock);
    }
}
