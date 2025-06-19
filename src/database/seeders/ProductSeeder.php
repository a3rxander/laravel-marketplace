<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $approvedSellers = Seller::where('status', 'approved')->get();
        $categories = Category::all();
        
        if ($approvedSellers->isEmpty() || $categories->isEmpty()) {
            $this->command->warn('No approved sellers or categories found. Creating some first...');
            return;
        }

        // Create active products
        foreach ($approvedSellers->take(10) as $seller) {
            Product::factory(rand(5, 15))->active()->create([
                'seller_id' => $seller->id,
                'category_id' => $categories->random()->id,
            ]);
        }

        // Create featured products
        Product::factory(20)->featured()->create([
            'seller_id' => $approvedSellers->random()->id,
            'category_id' => $categories->random()->id,
        ]);

        // Create digital products
        Product::factory(15)->digital()->create([
            'seller_id' => $approvedSellers->random()->id,
            'category_id' => $categories->random()->id,
        ]);

        // Create out of stock products
        Product::factory(10)->outOfStock()->create([
            'seller_id' => $approvedSellers->random()->id,
            'category_id' => $categories->random()->id,
        ]);

        // Create draft products
        Product::factory(25)->create([
            'seller_id' => $approvedSellers->random()->id,
            'category_id' => $categories->random()->id,
            'status' => 'draft',
        ]);

        // Create inactive products
        Product::factory(15)->create([
            'seller_id' => $approvedSellers->random()->id,
            'category_id' => $categories->random()->id,
            'status' => 'inactive',
        ]);

        // Create archived products
        Product::factory(10)->create([
            'seller_id' => $approvedSellers->random()->id,
            'category_id' => $categories->random()->id,
            'status' => 'archived',
        ]);
    }
}