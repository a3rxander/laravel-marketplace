<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            SellerSeeder::class,
            ProductSeeder::class,
            AddressSeeder::class,
            CartSeeder::class,
            OrderSeeder::class,
            ReviewSeeder::class,
        ]);

        // Create admin user
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@marketplace.com',
        ]);

        // Create test user
        User::factory()->active()->create([
            'name' => 'Test User',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@marketplace.com',
        ]);
    }
}