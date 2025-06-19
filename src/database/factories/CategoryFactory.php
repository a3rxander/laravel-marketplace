<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(rand(1, 10), true);
        
        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => fake()->optional(0.7)->paragraph(),
            'image' => fake()->optional(0.5)->imageUrl(400, 300, 'abstract'),
            'icon' => fake()->optional(0.6)->randomElement([
                'shopping-bag', 'smartphone', 'laptop', 'headphones', 
                'camera', 'watch', 'gamepad', 'book', 'shirt', 'home'
            ]),
            'parent_id' => null,
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => fake()->boolean(85),
            'is_featured' => fake()->boolean(20),
            'meta_data' => fake()->optional(0.3)->randomElement([
                json_encode([
                    'meta_title' => fake()->sentence(),
                    'meta_description' => fake()->text(160),
                    'keywords' => fake()->words(5, true)
                ]),
                null
            ]),
        ];
    }

    /**
     * Create a subcategory with a parent.
     */
    public function subcategory(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'parent_id' => Category::factory(),
                'is_featured' => false,
            ];
        });
    }

    /**
     * Create a featured category.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Create an inactive category.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}