<?php

namespace Database\Seeders;

use App\Models\Seller;
use App\Models\User;
use Illuminate\Database\Seeder;

class SellerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some users to become sellers
        $users = User::where('is_admin', false)->take(20)->get();
        
        // Create approved sellers
        foreach ($users->take(15) as $user) {
            Seller::factory()->approved()->create([
                'user_id' => $user->id,
            ]);
        }

        // Create pending sellers
        foreach ($users->skip(15)->take(3) as $user) {
            Seller::factory()->pending()->create([
                'user_id' => $user->id,
            ]);
        }

        // Create rejected sellers
        foreach ($users->skip(18)->take(2) as $user) {
            Seller::factory()->rejected()->create([
                'user_id' => $user->id,
            ]);
        }

        // Create additional sellers with new users
        Seller::factory(10)->approved()->create();
        Seller::factory(5)->pending()->create();
        Seller::factory(3)->rejected()->create();
        Seller::factory(2)->suspended()->create();
    }
}