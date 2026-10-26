<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo users share a known password. Never create them on a real
        // production deployment unless the operator explicitly opts in
        // (e.g. a throwaway public demo environment).
        if (app()->environment('production') && ! config('app.allow_demo_seed')) {
            $message = 'Demo seeding skipped: APP_ENV=production and ALLOW_DEMO_SEED is not true.';

            if ($this->command) {
                $this->command->warn($message);
            } else {
                logger()->warning($message);
            }

            return;
        }

        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            CareGuideSeeder::class,
        ]);
    }
}
