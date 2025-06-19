<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('is_admin', false)->take(40)->get();
        $products = Product::where('status', 'active')->get();
        
        if ($products->isEmpty()) {
            $this->command->warn('No active products found for order seeding.');
            return;
        }

        foreach ($users as $user) {
            // 70% chance user has made orders
            if (fake()->boolean(70)) {
                $numberOfOrders = fake()->numberBetween(1, 5);
                
                for ($i = 0; $i < $numberOfOrders; $i++) {
                    $order = Order::factory()->create([
                        'user_id' => $user->id,
                    ]);
                    
                    // Create order items for this order
                    $numberOfItems = fake()->numberBetween(1, 4);
                    $selectedProducts = $products->random($numberOfItems);
                    
                    $orderSubtotal = 0;
                    
                    foreach ($selectedProducts as $product) {
                        $orderItem = OrderItem::factory()->create([
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'seller_id' => $product->seller_id,
                            'product_name' => $product->name,
                            'product_sku' => $product->sku,
                            'status' => $order->status,
                        ]);
                        
                        $orderSubtotal += $orderItem->total_price;
                    }
                    
                    // Update order totals
                    $taxAmount = $orderSubtotal * 0.1;
                    $shippingAmount = fake()->randomFloat(2, 5, 25);
                    $discountAmount = fake()->optional(0.3)->randomFloat(2, 0, $orderSubtotal * 0.2) ?? 0;
                    $totalAmount = $orderSubtotal + $taxAmount + $shippingAmount - $discountAmount;
                    
                    $order->update([
                        'subtotal' => $orderSubtotal,
                        'tax_amount' => $taxAmount,
                        'shipping_amount' => $shippingAmount,
                        'discount_amount' => $discountAmount,
                        'total_amount' => $totalAmount,
                    ]);
                }
            }
        }

        // Create some specific order states
        Order::factory(10)->completed()->create()->each(function ($order) {
            $products = Product::where('status', 'active')->take(rand(1, 3))->get();
            foreach ($products as $product) {
                OrderItem::factory()->delivered()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'seller_id' => $product->seller_id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                ]);
            }
        });

        Order::factory(5)->cancelled()->create()->each(function ($order) {
            $products = Product::where('status', 'active')->take(rand(1, 2))->get();
            foreach ($products as $product) {
                OrderItem::factory()->cancelled()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'seller_id' => $product->seller_id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                ]);
            }
        });

        Order::factory(15)->pending()->create()->each(function ($order) {
            $products = Product::where('status', 'active')->take(rand(1, 3))->get();
            foreach ($products as $product) {
                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'seller_id' => $product->seller_id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'status' => 'pending',
                ]);
            }
        });
    }
}