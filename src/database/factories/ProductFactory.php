<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Seller;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(rand(2, 4), true);
        $price = fake()->randomFloat(2, 10, 1000);
        $comparePrice = fake()->optional(0.3)->randomFloat(2, $price + 10, $price * 1.5);
        $costPrice = fake()->randomFloat(2, $price * 0.3, $price * 0.7);
        
        return [
            'seller_id' => Seller::factory()->approved(),
            'category_id' => Category::factory(),
            'name' => ucwords($name),
            'slug' => Str::slug($name) . '-' . fake()->randomNumber(4),
            'description' => fake()->paragraphs(3, true),
            'short_description' => fake()->optional(0.8)->sentence(20),
            'sku' => 'SKU-' . fake()->unique()->numerify('########'),
            'price' => $price,
            'compare_price' => $comparePrice,
            'cost_price' => $costPrice,
            'stock_quantity' => fake()->numberBetween(0, 100),
            'min_stock_level' => fake()->numberBetween(1, 10),
            'track_stock' => fake()->boolean(90),
            'stock_status' => fake()->randomElement(['in_stock', 'out_of_stock', 'on_backorder']),
            'weight' => fake()->optional(0.7)->randomFloat(2, 0.1, 10),
            'dimensions' => fake()->optional(0.6)->randomElement([
                json_encode([
                    'length' => fake()->randomFloat(2, 1, 50),
                    'width' => fake()->randomFloat(2, 1, 50),
                    'height' => fake()->randomFloat(2, 1, 50),
                    'unit' => 'cm'
                ]),
                null
            ]),
            'status' => fake()->randomElement(['draft', 'active', 'inactive', 'archived']),
            'is_featured' => fake()->boolean(15),
            'is_digital' => fake()->boolean(10),
            'images' => fake()->optional(0.8)->randomElement([
                json_encode([
                    fake()->imageUrl(600, 600, 'products'),
                    fake()->imageUrl(600, 600, 'products'),
                    fake()->imageUrl(600, 600, 'products'),
                ]),
                json_encode([fake()->imageUrl(600, 600, 'products')]),
                null
            ]),
            'gallery' => fake()->optional(0.4)->randomElement([
                json_encode([
                    fake()->imageUrl(800, 600, 'products'),
                    fake()->imageUrl(800, 600, 'products'),
                ]),
                null
            ]),
            'attributes' => fake()->optional(0.5)->randomElement([
                json_encode([
                    'color' => fake()->colorName(),
                    'size' => fake()->randomElement(['S', 'M', 'L', 'XL']),
                    'material' => fake()->randomElement(['Cotton', 'Polyester', 'Wool', 'Silk'])
                ]),
                json_encode([
                    'brand' => fake()->company(),
                    'model' => fake()->bothify('Model-####'),
                ]),
                null
            ]),
            'variants' => fake()->optional(0.3)->randomElement([
                json_encode([
                    [
                        'name' => 'Red-Small',
                        'sku' => 'SKU-' . fake()->numerify('########'),
                        'price' => $price,
                        'stock' => fake()->numberBetween(0, 20)
                    ],
                    [
                        'name' => 'Blue-Medium',
                        'sku' => 'SKU-' . fake()->numerify('########'),
                        'price' => $price + 5,
                        'stock' => fake()->numberBetween(0, 20)
                    ]
                ]),
                null
            ]),
            'meta_data' => fake()->optional(0.4)->randomElement([
                json_encode([
                    'meta_title' => fake()->sentence(),
                    'meta_description' => fake()->text(160),
                    'keywords' => fake()->words(5, true)
                ]),
                null
            ]),
            'rating' => fake()->randomFloat(2, 0, 5),
            'total_reviews' => fake()->numberBetween(0, 100),
            'total_sales' => fake()->numberBetween(0, 500),
            'view_count' => fake()->numberBetween(0, 1000),
            'published_at' => fake()->optional(0.7)->dateTimeThisYear(),
        ];
    }

    /**
     * Create an active product.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'published_at' => fake()->dateTimeThisYear(),
        ]);
    }

    /**
     * Create a featured product.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'status' => 'active',
        ]);
    }

    /**
     * Create a digital product.
     */
    public function digital(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_digital' => true,
            'weight' => null,
            'dimensions' => null,
            'track_stock' => false,
        ]);
    }

    /**
     * Create an out of stock product.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
            'stock_status' => 'out_of_stock',
        ]);
    }
}