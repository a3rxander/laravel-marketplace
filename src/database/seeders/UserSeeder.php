<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 50 regular users
        User::factory(50)->active()->create();
        
        // Create 10 inactive users
        User::factory(10)->inactive()->create();
        
        // Create 5 admin users
        User::factory(5)->admin()->create();
        
        // Create specific users for testing
        User::factory()->create([
            'name' => 'John Doe',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'is_admin' => false,
        ]);

        User::factory()->create([
            'name' => 'Jane Smith',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'status' => 'active',
            'is_admin' => false,
        ]);
    }
}