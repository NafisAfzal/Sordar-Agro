<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $fishCat   = Category::where('slug', 'fish')->first();
        $plantCat  = Category::where('slug', 'aquatic-plants')->first();
        $foodCat   = Category::where('slug', 'fish-food')->first();
        $equipCat  = Category::where('slug', 'equipment')->first();
        $seller    = User::where('role', 'seller')->first();

        // ---- Fish products: sold in pairs, three size variants -----------
        $fish = [
            [
                'name' => 'Neon Tetra', 'temperament' => 'peaceful', 'tank' => 40,
                'desc' => 'Vibrant schooling fish with an electric blue stripe. Best kept in groups.',
                'featured' => true,
                'sizes' => [
                    ['small', 180, 25, '1.5–2 cm juveniles'],
                    ['medium', 240, 18, '2.5–3 cm sub-adults'],
                    ['large', 320, 10, '3.5 cm+ adults, full colour'],
                ],
            ],
            [
                'name' => 'Betta Splendens', 'temperament' => 'aggressive', 'tank' => 20,
                'desc' => 'The famous Siamese fighting fish — stunning fins, kept singly or in a pair tank.',
                'featured' => true,
                'sizes' => [
                    ['small', 350, 12, 'Young, fins developing'],
                    ['medium', 550, 9, 'Half-moon tail, vivid colour'],
                    ['large', 800, 5, 'Show-grade, full flare'],
                ],
            ],
            [
                'name' => 'Guppy', 'temperament' => 'peaceful', 'tank' => 40,
                'desc' => 'Hardy, colourful livebearers — ideal for beginners.',
                'featured' => false,
                'sizes' => [
                    ['small', 120, 30, '1.5 cm fry-grown'],
                    ['medium', 180, 22, '2.5 cm coloured'],
                    ['large', 260, 14, '3 cm+ fancy tails'],
                ],
            ],
            [
                'name' => 'Goldfish', 'temperament' => 'peaceful', 'tank' => 80,
                'desc' => 'Classic cold-water fish. Needs a roomy tank and good filtration.',
                'featured' => true,
                'sizes' => [
                    ['small', 200, 20, '3 cm young'],
                    ['medium', 350, 12, '5–6 cm'],
                    ['large', 600, 6, '8 cm+ mature'],
                ],
            ],
        ];

        foreach ($fish as $f) {
            $product = Product::updateOrCreate(
                ['slug' => Str::slug($f['name'])],
                [
                    'seller_id'   => null, // marketplace-listed
                    'category_id' => $fishCat->id,
                    'name'        => $f['name'],
                    'description' => $f['desc'],
                    'thumbnail'   => 'products/'.Str::slug($f['name']).'.webp',
                    'is_fish'     => true,
                    'min_tank_size_litres' => $f['tank'],
                    'temperament' => $f['temperament'],
                    'status'      => 'approved',
                    'is_featured' => $f['featured'],
                ]
            );

            foreach ($f['sizes'] as [$size, $price, $stock, $note]) {
                ProductVariant::updateOrCreate(
                    ['product_id' => $product->id, 'size' => $size],
                    ['price' => $price, 'stock' => $stock, 'size_description' => $note, 'sku' => strtoupper(Str::random(8))]
                );
            }
        }

        // ---- Non-fish products: single 'standard' variant ----------------
        $others = [
            ['Java Fern', $plantCat, 150, 40, 'Low-light hardy aquarium plant.', true],
            ['Amazon Sword', $plantCat, 220, 25, 'Lush background plant for planted tanks.', false],
            ['Premium Flake Food 100g', $foodCat, 280, 60, 'Daily staple flakes for tropical fish.', true],
            ['Sinking Pellets 200g', $foodCat, 350, 45, 'High-protein pellets for bottom feeders.', false],
            ['Aquarium Filter 1200L/h', $equipCat, 1850, 15, 'Quiet hang-on-back filter for tanks up to 200L.', true],
            ['LED Aquarium Light 60cm', $equipCat, 2400, 10, 'Full-spectrum LED for plant growth.', false],
            ['Submersible Heater 100W', $equipCat, 950, 20, 'Adjustable heater with thermostat.', false],
        ];

        foreach ($others as [$name, $cat, $price, $stock, $desc, $featured]) {
            $product = Product::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'seller_id'   => $seller?->id,
                    'category_id' => $cat->id,
                    'name'        => $name,
                    'description' => $desc,
                    'thumbnail'   => 'products/'.Str::slug($name).'.webp',
                    'is_fish'     => false,
                    'status'      => 'approved',
                    'is_featured' => $featured,
                ]
            );

            ProductVariant::updateOrCreate(
                ['product_id' => $product->id, 'size' => 'standard'],
                ['price' => $price, 'stock' => $stock, 'sku' => strtoupper(Str::random(8))]
            );
        }

        // One seller product left pending to demonstrate the approval queue.
        $pending = Product::updateOrCreate(
            ['slug' => 'cardinal-tetra'],
            [
                'seller_id'   => $seller?->id,
                'category_id' => $fishCat->id,
                'name'        => 'Cardinal Tetra',
                'description' => 'Deep red-and-blue schooling fish, similar to neons but more vivid.',
                'thumbnail'   => 'products/cardinal-tetra.webp',
                'is_fish'     => true,
                'min_tank_size_litres' => 60,
                'temperament' => 'peaceful',
                'status'      => 'pending',
                'is_featured' => false,
            ]
        );
        foreach ([['small', 220, 15, 'Juveniles'], ['medium', 300, 10, 'Sub-adults'], ['large', 400, 6, 'Adults']] as [$size, $price, $stock, $note]) {
            ProductVariant::updateOrCreate(
                ['product_id' => $pending->id, 'size' => $size],
                ['price' => $price, 'stock' => $stock, 'size_description' => $note, 'sku' => strtoupper(Str::random(8))]
            );
        }
    }
}
