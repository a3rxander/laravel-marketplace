<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Seller>
 */
class SellerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $businessName = fake()->company();
        
        return [
            'user_id' => User::factory(),
            'business_name' => $businessName,
            'slug' => Str::slug($businessName) . '-' . fake()->randomNumber(4),
            'description' => fake()->optional(0.8)->paragraph(3),
            'business_email' => fake()->optional(0.7)->companyEmail(),
            'business_phone' => fake()->optional(0.8)->phoneNumber(),
            'business_registration_number' => fake()->optional(0.6)->numerify('REG-########'),
            'tax_id' => fake()->optional(0.7)->numerify('TAX-########'),
            'logo' => fake()->optional(0.4)->imageUrl(200, 200, 'business'),
            'banner' => fake()->optional(0.3)->imageUrl(800, 300, 'business'),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected', 'suspended']),
            'commission_rate' => fake()->randomFloat(2, 5.00, 15.00),
            'business_hours' => fake()->optional(0.6)->randomElement([
                json_encode([
                    'monday' => ['open' => '09:00', 'close' => '18:00'],
                    'tuesday' => ['open' => '09:00', 'close' => '18:00'],
                    'wednesday' => ['open' => '09:00', 'close' => '18:00'],
                    'thursday' => ['open' => '09:00', 'close' => '18:00'],
                    'friday' => ['open' => '09:00', 'close' => '18:00'],
                    'saturday' => ['open' => '10:00', 'close' => '16:00'],
                    'sunday' => ['closed' => true]
                ]),
                null
            ]),
            'rating' => fake()->randomFloat(2, 1.00, 5.00),
            'total_reviews' => fake()->numberBetween(0, 500),
            'total_sales' => fake()->numberBetween(0, 1000),
            'total_revenue' => fake()->randomFloat(2, 0, 50000),
            'approved_at' => fake()->optional(0.7)->dateTimeThisYear(),
            'approved_by' => null,
            'rejection_reason' => null,
        ];
    }

    /**
     * Create an approved seller.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => fake()->dateTimeThisYear(),
            'approved_by' => User::factory()->admin(),
        ]);
    }

    /**
     * Create a pending seller.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    /**
     * Create a rejected seller.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'approved_at' => null,
            'approved_by' => User::factory()->admin(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    /**
     * Create a suspended seller.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}