<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Default Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@scanyuk.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // 2. Create Dummy SaaS Client
        Client::firstOrCreate(
            ['email' => 'client@example.com'],
            [
                'name' => 'Demo Client Website',
                'license_key' => 'DEMO-LICENSE-12345',
                'status' => 'active',
                'subscription_expires_at' => now()->addYear()
            ]
        );
    }
}
