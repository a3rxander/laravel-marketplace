<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create main categories
        $mainCategories = [
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'Electronic devices and accessories',
                'icon' => 'smartphone',
                'is_featured' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Fashion',
                'slug' => 'fashion',
                'description' => 'Clothing, shoes, and accessories',
                'icon' => 'shirt',
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Home & Garden',
                'slug' => 'home-garden',
                'description' => 'Home improvement and garden supplies',
                'icon' => 'home',
                'is_featured' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Books',
                'slug' => 'books',
                'description' => 'Books and educational materials',
                'icon' => 'book',
                'is_featured' => false,
                'sort_order' => 4,
            ],
            [
                'name' => 'Sports & Outdoors',
                'slug' => 'sports-outdoors',
                'description' => 'Sports equipment and outdoor gear',
                'icon' => 'gamepad',
                'is_featured' => false,
                'sort_order' => 5,
            ],
        ];

        $createdCategories = [];
        
        foreach ($mainCategories as $categoryData) {
            $createdCategories[] = Category::create($categoryData);
        }

        // Create subcategories for Electronics
        $electronicsSubcategories = [
            'Smartphones',
            'Laptops',
            'Headphones',
            'Cameras',
            'Gaming',
            'Accessories',
        ];

        foreach ($electronicsSubcategories as $subcategory) {
            Category::create([
                'name' => $subcategory,
                'slug' => Str::slug($subcategory),
                'parent_id' => $createdCategories[0]->id,
                'is_active' => true,
                'sort_order' => rand(1, 10),
            ]);
        }

        // Create subcategories for Fashion
        $fashionSubcategories = [
            'Men\'s Clothing',
            'Women\'s Clothing',
            'Shoes',
            'Bags',
            'Jewelry',
            'Watches',
        ];

        foreach ($fashionSubcategories as $subcategory) {
            Category::create([
                'name' => $subcategory,
                'slug' => Str::slug($subcategory),
                'parent_id' => $createdCategories[1]->id,
                'is_active' => true,
                'sort_order' => rand(1, 10),
            ]);
        }

        // Create subcategories for Home & Garden
        $homeSubcategories = [
            'Furniture',
            'Kitchen & Dining',
            'Bedding',
            'Garden Tools',
            'Home Decor',
            'Appliances',
        ];

        foreach ($homeSubcategories as $subcategory) {
            Category::create([
                'name' => $subcategory,
                'slug' => Str::slug($subcategory),
                'parent_id' => $createdCategories[2]->id,
                'is_active' => true,
                'sort_order' => rand(1, 10),
            ]);
        }

        // Create additional random categories
        Category::factory(15)->create();
        
        // Create some subcategories using factory
        Category::factory(20)->subcategory()->create();
    }
}