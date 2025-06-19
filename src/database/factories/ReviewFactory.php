<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
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
            'product_id' => $product->id,
            'order_id' => fake()->optional(0.8)->randomElement([Order::factory(), null]),
            'seller_id' => $product->seller_id,
            'rating' => fake()->numberBetween(1, 5),
            'title' => fake()->optional(0.7)->sentence(6),
            'comment' => fake()->optional(0.8)->paragraph(3),
            'images' => fake()->optional(0.2)->randomElement([
                json_encode([
                    fake()->imageUrl(400, 400, 'products'),
                    fake()->imageUrl(400, 400, 'products'),
                ]),
                json_encode([fake()->imageUrl(400, 400, 'products')]),
                null
            ]),
            'verified_purchase' => fake()->boolean(70),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'rejection_reason' => null,
            'moderated_by' => null,
            'moderated_at' => null,
            'helpful_count' => fake()->numberBetween(0, 50),
            'not_helpful_count' => fake()->numberBetween(0, 10),
            'seller_response' => fake()->optional(0.3)->paragraph(2),
            'seller_responded_at' => fake()->optional(0.3)->dateTimeThisMonth(),
        ];
    }

    /**
     * Create an approved review.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'moderated_by' => User::factory()->admin(),
            'moderated_at' => fake()->dateTimeThisMonth(),
        ]);
    }

    /**
     * Create a rejected review.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'rejection_reason' => fake()->sentence(),
            'moderated_by' => User::factory()->admin(),
            'moderated_at' => fake()->dateTimeThisMonth(),
        ]);
    }

    /**
     * Create a verified purchase review.
     */
    public function verifiedPurchase(): static
    {
        return $this->state(fn (array $attributes) => [
            'verified_purchase' => true,
            'order_id' => Order::factory(),
        ]);
    }

    /**
     * Create a review with seller response.
     */
    public function withSellerResponse(): static
    {
        return $this->state(fn (array $attributes) => [
            'seller_response' => fake()->paragraph(2),
            'seller_responded_at' => fake()->dateTimeThisMonth(),
        ]);
    }

    /**
     * Create a high-rated review.
     */
    public function highRated(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => fake()->numberBetween(4, 5),
        ]);
    }

    /**
     * Create a low-rated review.
     */
    public function lowRated(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => fake()->numberBetween(1, 2),
        ]);
    }
}