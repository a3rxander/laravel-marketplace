<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class CartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('is_admin', false)->take(30)->get();
        $activeProducts = Product::where('status', 'active')
            ->where('stock_status', 'in_stock')
            ->get();
        
        if ($activeProducts->isEmpty()) {
            $this->command->warn('No active products found for cart seeding.');
            return;
        }

        foreach ($users as $user) {
            // 60% chance user has items in cart
            if (fake()->boolean(60)) {
                $numberOfItems = fake()->numberBetween(1, 5);
                $selectedProducts = $activeProducts->random($numberOfItems);
                
                foreach ($selectedProducts as $product) {
                    Cart::factory()->create([
                        'user_id' => $user->id,
                        'product_id' => $product->id,
                        'price' => $product->price,
                    ]);
                }
            }
        }

        // Create some guest cart items (session-based)
        Cart::factory(25)->guest()->create([
            'product_id' => function () use ($activeProducts) {
                return $activeProducts->random()->id;
            },
        ]);

        // Create cart items with specific options
        Cart::factory(15)->withOptions([
            'color' => 'Red',
            'size' => 'L'
        ])->create([
            'product_id' => function () use ($activeProducts) {
                return $activeProducts->random()->id;
            },
        ]);
    }
}