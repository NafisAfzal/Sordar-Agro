<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionSeedingTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_accounts_are_not_seeded_in_production_by_default(): void
    {
        $this->app['env'] = 'production';
        config(['app.allow_demo_seed' => false]);

        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'seller@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'customer@example.com']);
    }

    public function test_demo_seeding_can_be_explicitly_enabled_for_a_demo_deployment(): void
    {
        $this->app['env'] = 'production';
        config(['app.allow_demo_seed' => true]);

        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'admin@example.com', 'role' => 'admin']);
    }

    public function test_local_seeding_still_creates_demo_accounts(): void
    {
        $this->app['env'] = 'local';
        config(['app.allow_demo_seed' => false]);

        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);
    }
}
