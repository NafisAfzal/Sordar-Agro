<?php

namespace Database\Seeders;

use App\Models\CareGuide;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CareGuideSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        $guides = [
            [
                'Setting Up Your First Aquarium',
                'A beginner-friendly walkthrough of cycling, equipment and first fish.',
                "Starting an aquarium is exciting, but patience is key.\n\n1. Choose a tank of at least 40 litres — bigger is more stable.\n2. Install a filter and heater, then fill with dechlorinated water.\n3. Cycle the tank for 2–4 weeks before adding fish so beneficial bacteria establish.\n4. Add hardy fish first and test water weekly.\n\nRemember: at Sordar Agro, fish are sold in pairs, so plan your stocking accordingly.",
            ],
            [
                'How to Care for Betta Fish',
                'Tank size, water temperature and feeding tips for healthy bettas.',
                "Betta fish are beautiful but have specific needs.\n\n- Keep water between 24–28°C with a gentle filter.\n- Feed small amounts of quality pellets once or twice daily.\n- Avoid housing two males together.\n- Perform weekly partial water changes.\n\nA well-kept betta can live 3–5 years.",
            ],
            [
                'Choosing the Right Aquatic Plants',
                'Low-light vs high-light plants and how they keep your water healthy.',
                "Live plants improve water quality and give fish shelter.\n\nLow-light, beginner-friendly options include Java Fern and Anubias.\nFor a lush planted tank, add CO2 and stronger lighting for stem plants.\nPlants absorb nitrates, helping keep your water parameters stable.",
            ],
        ];

        foreach ($guides as [$title, $excerpt, $content]) {
            CareGuide::updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'title' => $title,
                    'excerpt' => $excerpt,
                    'content' => $content,
                    'author_id' => $admin?->id,
                    'published_at' => now(),
                ]
            );
        }
    }
}
