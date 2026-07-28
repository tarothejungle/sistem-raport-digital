<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    private const DEMO_USERNAME = 'demo.admin';

    private const DEMO_PASSWORD = 'DemoRaport2026';

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['username' => self::DEMO_USERNAME],
            [
                'name' => 'Administrator Demo',
                'email' => 'demo.admin@raport.test',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ],
        );
    }
}
