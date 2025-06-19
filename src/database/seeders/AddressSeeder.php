<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Seeder;

class AddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('is_admin', false)->get();
        
        foreach ($users as $user) {
            // Create a default shipping address for each user
            Address::factory()->default()->shipping()->create([
                'user_id' => $user->id,
            ]);
            
            // 70% chance to create a billing address
            if (fake()->boolean(70)) {
                Address::factory()->billing()->create([
                    'user_id' => $user->id,
                ]);
            }
            
            // 40% chance to create additional addresses
            if (fake()->boolean(40)) {
                Address::factory(rand(1, 3))->create([
                    'user_id' => $user->id,
                    'is_default' => false,
                ]);
            }
        }

        // Create some additional random addresses
        Address::factory(50)->create();
    }
}