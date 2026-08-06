<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id'       => Product::factory(),
            'size'             => 'standard',
            'sku'              => strtoupper(Str::random(8)),
            'price'            => fake()->randomFloat(2, 100, 2000),
            'stock'            => fake()->numberBetween(1, 50),
            'size_description' => null,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }
}
