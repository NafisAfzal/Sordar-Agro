<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'category_id' => Category::inRandomOrder()->value('id') ?? Category::factory(),
            'name'        => Str::title($name),
            'slug'        => Str::slug($name).'-'.Str::random(4),
            'description' => fake()->paragraph(),
            'is_fish'     => false,
            'status'      => 'approved',
            'is_featured' => fake()->boolean(30),
        ];
    }

    public function fish(): static
    {
        return $this->state(fn () => [
            'is_fish'              => true,
            'min_tank_size_litres'=> fake()->randomElement([40, 60, 80, 120]),
            'temperament'         => fake()->randomElement(['peaceful', 'semi-aggressive', 'aggressive']),
        ]);
    }

    /**
     * Attach a single in-stock 'standard' variant after creation, so tests can
     * build a directly-purchasable product with one call:
     *   Product::factory()->withVariant(stock: 5, price: 250)->create();
     */
    public function withVariant(int $stock = 10, float $price = 500): static
    {
        return $this->afterCreating(function ($product) use ($stock, $price) {
            $product->variants()->create([
                'size'  => 'standard',
                'sku'   => strtoupper(Str::random(8)),
                'price' => $price,
                'stock' => $stock,
            ]);
        });
    }
}
