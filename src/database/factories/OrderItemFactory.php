<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $product = Product::factory()->create();
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->randomFloat(2, 10, 200);
        $totalPrice = $quantity * $unitPrice;
        $commissionRate = fake()->randomFloat(2, 5, 15);
        $commissionAmount = $totalPrice * ($commissionRate / 100);
        $sellerEarnings = $totalPrice - $commissionAmount;
        
        return [
            'order_id' => Order::factory(),
            'product_id' => $product->id,
            'seller_id' => $product->seller_id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
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
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'seller_earnings' => $sellerEarnings,
            'status' => fake()->randomElement([
                'pending', 'confirmed', 'processing', 
                'shipped', 'delivered', 'cancelled', 'refunded'
            ]),
            'tracking_number' => fake()->optional(0.6)->numerify('TRK########'),
            'shipped_at' => fake()->optional(0.6)->dateTimeThisMonth(),
            'delivered_at' => fake()->optional(0.4)->dateTimeThisMonth(),
        ];
    }

    /**
     * Create a delivered order item.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'shipped_at' => fake()->dateTimeThisMonth(),
            'delivered_at' => fake()->dateTimeThisMonth(),
            'tracking_number' => fake()->numerify('TRK########'),
        ]);
    }

    /**
     * Create a shipped order item.
     */
    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'shipped',
            'shipped_at' => fake()->dateTimeThisMonth(),
            'tracking_number' => fake()->numerify('TRK########'),
        ]);
    }

    /**
     * Create a cancelled order item.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}