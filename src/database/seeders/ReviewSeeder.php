<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $completedOrders = Order::where('status', 'delivered')
            ->whereHas('orderItems')
            ->with(['orderItems.product', 'user'])
            ->get();
        
        if ($completedOrders->isEmpty()) {
            $this->command->warn('No completed orders found for review seeding.');
            
            // Create some reviews without orders
            $products = Product::where('status', 'active')->take(20)->get();
            $users = User::where('is_admin', false)->take(15)->get();
            
            foreach ($products as $product) {
                if (fake()->boolean(60)) { // 60% chance product has reviews
                    $numberOfReviews = fake()->numberBetween(1, 8);
                    $selectedUsers = $users->random(min($numberOfReviews, $users->count()));
                    
                    foreach ($selectedUsers as $user) {
                        Review::factory()->approved()->create([
                            'user_id' => $user->id,
                            'product_id' => $product->id,
                            'seller_id' => $product->seller_id,
                            'verified_purchase' => false,
                        ]);
                    }
                }
            }
            return;
        }

        foreach ($completedOrders as $order) {
            foreach ($order->orderItems as $orderItem) {
                // 70% chance customer leaves a review
                if (fake()->boolean(70)) {
                    $review = Review::factory()->approved()->verifiedPurchase()->create([
                        'user_id' => $order->user_id,
                        'product_id' => $orderItem->product_id,
                        'order_id' => $order->id,
                        'seller_id' => $orderItem->seller_id,
                    ]);

                    // 30% chance seller responds to review
                    if (fake()->boolean(30)) {
                        $review->update([
                            'seller_response' => fake()->paragraph(2),
                            'seller_responded_at' => fake()->dateTimeThisMonth(),
                        ]);
                    }
                }
            }
        }

        // Create additional reviews for products
        $products = Product::where('status', 'active')->take(50)->get();
        $users = User::where('is_admin', false)->get();
        
        foreach ($products as $product) {
            $numberOfAdditionalReviews = fake()->numberBetween(0, 5);
            
            for ($i = 0; $i < $numberOfAdditionalReviews; $i++) {
                $user = $users->random();
                
                // Check if user already reviewed this product
                $existingReview = Review::where('user_id', $user->id)
                    ->where('product_id', $product->id)
                    ->first();
                
                if (!$existingReview) {
                    Review::factory()->approved()->create([
                        'user_id' => $user->id,
                        'product_id' => $product->id,
                        'seller_id' => $product->seller_id,
                        'verified_purchase' => fake()->boolean(40),
                    ]);
                }
            }
        }

        // Create some pending and rejected reviews
        Review::factory(20)->create([
            'status' => 'pending',
        ]);

        Review::factory(10)->rejected()->create();

        // Create high-rated reviews for featured products
        $featuredProducts = Product::where('is_featured', true)->get();
        foreach ($featuredProducts as $product) {
            Review::factory(rand(3, 8))->highRated()->approved()->create([
                'product_id' => $product->id,
                'seller_id' => $product->seller_id,
            ]);
        }

        // Create some reviews with seller responses
        Review::factory(15)->approved()->withSellerResponse()->create();
    }
}