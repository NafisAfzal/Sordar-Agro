<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Administrator — seeded only, never creatable through the app.
        // firstOrCreate: only sets these attributes (incl. password) the
        // first time this runs, so re-seeding on a later deploy doesn't
        // reset a password that was changed since.
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'      => 'Site Administrator',
                'phone'     => '01700000000',
                'password'  => 'password',     // hashed via model cast
                'role'      => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // A ready-to-use partner seller (password already changed for demo).
        $admin = User::where('role', 'admin')->first();
        User::updateOrCreate(
            ['email' => 'seller@example.com'],
            [
                'name'                 => 'Demo Aquatics',
                'phone'                => '01800000000',
                'password'             => 'password',
                'role'                 => 'seller',
                'must_change_password' => false,
                'is_active'            => true,
                'created_by'           => $admin?->id,
                'email_verified_at'    => now(),
            ]
        );

        // A sample customer.
        User::updateOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name'      => 'Demo Customer',
                'phone'     => '01900000000',
                'password'  => 'password',
                'role'      => 'customer',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
