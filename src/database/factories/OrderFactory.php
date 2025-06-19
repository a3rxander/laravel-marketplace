<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50, 500);
        $taxAmount = $subtotal * 0.1;
        $shippingAmount = fake()->randomFloat(2, 5, 25);
        $discountAmount = fake()->optional(0.3)->randomFloat(2, 0, $subtotal * 0.2) ?? 0;
        $totalAmount = $subtotal + $taxAmount + $shippingAmount - $discountAmount;
        
        return [
            'order_number' => 'ORD-' . fake()->unique()->numerify('########'),
            'user_id' => User::factory(),
            'status' => fake()->randomElement([
                'pending', 'confirmed', 'processing', 
                'shipped', 'delivered', 'cancelled', 'refunded'
            ]),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'shipping_amount' => $shippingAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP']),
            'shipping_address' => json_encode([
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'company' => fake()->optional()->company(),
                'address_line_1' => fake()->streetAddress(),
                'address_line_2' => fake()->optional()->secondaryAddress(),
                'city' => fake()->city(),
                'state' => fake()->state(),
                'postal_code' => fake()->postcode(),
                'country' => fake()->countryCode(),
                'phone' => fake()->phoneNumber()
            ]),
            'billing_address' => json_encode([
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'company' => fake()->optional()->company(),
                'address_line_1' => fake()->streetAddress(),
                'address_line_2' => fake()->optional()->secondaryAddress(),
                'city' => fake()->city(),
                'state' => fake()->state(),
                'postal_code' => fake()->postcode(),
                'country' => fake()->countryCode(),
                'phone' => fake()->phoneNumber()
            ]),
            'shipping_method' => fake()->optional(0.8)->randomElement([
                'standard', 'express', 'overnight', 'pickup'
            ]),
            'shipping_cost' => $shippingAmount,
            'tracking_number' => fake()->optional(0.6)->numerify('TRK########'),
            'payment_status' => fake()->randomElement([
                'pending', 'paid', 'failed', 'refunded', 'partially_refunded'
            ]),
            'payment_method' => fake()->optional(0.8)->randomElement([
                'credit_card', 'paypal', 'bank_transfer', 'cash_on_delivery'
            ]),
            'payment_reference' => fake()->optional(0.7)->uuid(),
            'paid_at' => fake()->optional(0.7)->dateTimeThisMonth(),
            'confirmed_at' => fake()->optional(0.8)->dateTimeThisMonth(),
            'shipped_at' => fake()->optional(0.6)->dateTimeThisMonth(),
            'delivered_at' => fake()->optional(0.4)->dateTimeThisMonth(),
            'cancelled_at' => fake()->optional(0.1)->dateTimeThisMonth(),
            'notes' => fake()->optional(0.3)->sentence(),
            'admin_notes' => fake()->optional(0.2)->sentence(),
        ];
    }

    /**
     * Create a pending order.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }

    /**
     * Create a completed order.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'payment_status' => 'paid',
            'confirmed_at' => fake()->dateTimeThisMonth(),
            'shipped_at' => fake()->dateTimeThisMonth(),
            'delivered_at' => fake()->dateTimeThisMonth(),
            'paid_at' => fake()->dateTimeThisMonth(),
        ]);
    }

    /**
     * Create a cancelled order.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => fake()->dateTimeThisMonth(),
        ]);
    }
}