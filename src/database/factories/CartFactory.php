<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cart>
 */
class CartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $product = Product::factory()->create();
        
        return [
            'user_id' => User::factory(),
            'session_id' => null,
            'product_id' => $product->id,
            'quantity' => fake()->numberBetween(1, 5),
            'price' => $product->price,
            'product_options' => fake()->optional(0.4)->randomElement([
                json_encode([
                    'color' => fake()->colorName(),
                    'size' => fake()->randomElement(['S', 'M', 'L', 'XL'])
                ]),
                json_encode([
                    'variant' => fake()->randomElement(['Standard', 'Premium'])
                ]),
                null
            ]),
        ];
    }

    /**
     * Create a cart for guest user (session-based).
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'session_id' => Str::random(40),
        ]);
    }

    /**
     * Create a cart with specific product options.
     */
    public function withOptions(array $options): static
    {
        return $this->state(fn (array $attributes) => [
            'product_options' => json_encode($options),
        ]);
    }
}