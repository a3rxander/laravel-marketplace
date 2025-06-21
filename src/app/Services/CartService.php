<?php

// src/app/Services/CartService.php
namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Collection;

class CartService
{
    public function getCart(?int $userId, string $sessionId): array
    {
        $query = Cart::with(['product.seller', 'product.category']);
        
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId)->whereNull('user_id');
        }
        
        $items = $query->get();
        
        return [
            'items' => $items,
            'total' => $items->sum(fn($item) => $item->quantity * $item->price),
            'count' => $items->sum('quantity')
        ];
    }

    public function addToCart(array $data): Cart
    {
        $product = Product::findOrFail($data['product_id']);
        
        // Check if item already exists
        $existingItem = Cart::where('product_id', $data['product_id'])
            ->where(function($query) use ($data) {
                if ($data['user_id']) {
                    $query->where('user_id', $data['user_id']);
                } else {
                    $query->where('session_id', $data['session_id'])->whereNull('user_id');
                }
            })
            ->first();

        if ($existingItem) {
            $existingItem->quantity += $data['quantity'];
            $existingItem->save();
            return $existingItem;
        }

        return Cart::create([
            'user_id' => $data['user_id'],
            'session_id' => $data['session_id'],
            'product_id' => $data['product_id'],
            'quantity' => $data['quantity'],
            'price' => $product->price,
            'product_options' => $data['product_options'] ?? []
        ]);
    }

    public function updateCartItem(int $cartId, array $data, ?int $userId, string $sessionId): Cart
    {
        $query = Cart::where('id', $cartId);
        
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId)->whereNull('user_id');
        }
        
        $cartItem = $query->firstOrFail();
        $cartItem->update($data);
        
        return $cartItem;
    }

    public function removeFromCart(int $cartId, ?int $userId, string $sessionId): bool
    {
        $query = Cart::where('id', $cartId);
        
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId)->whereNull('user_id');
        }
        
        return $query->delete() > 0;
    }

    public function clearCart(?int $userId, string $sessionId): bool
    {
        $query = Cart::query();
        
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId)->whereNull('user_id');
        }
        
        return $query->delete() > 0;
    }

    public function getCartCount(?int $userId, string $sessionId): int
    {
        $query = Cart::query();
        
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId)->whereNull('user_id');
        }
        
        return $query->sum('quantity');
    }

    public function getCartTotal(?int $userId, string $sessionId): array
    {
        $query = Cart::query();
        
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId)->whereNull('user_id');
        }
        
        $items = $query->get();
        $subtotal = $items->sum(fn($item) => $item->quantity * $item->price);
        $tax = $subtotal * 0.15; // 15% tax
        $total = $subtotal + $tax;
        
        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'items_count' => $items->count()
        ];
    }

    public function prepareCheckout(?int $userId, string $sessionId): array
    {
        $cart = $this->getCart($userId, $sessionId);
        $totals = $this->getCartTotal($userId, $sessionId);
        
        return [
            'items' => $cart['items'],
            'totals' => $totals,
            'shipping_options' => $this->getShippingOptions(),
            'payment_methods' => $this->getPaymentMethods()
        ];
    }

    public function mergeGuestCart(int $userId, string $sessionId): array
    {
        // Get guest cart items
        $guestItems = Cart::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->get();

        foreach ($guestItems as $guestItem) {
            // Check if user already has this product in cart
            $existingItem = Cart::where('user_id', $userId)
                ->where('product_id', $guestItem->product_id)
                ->first();

            if ($existingItem) {
                $existingItem->quantity += $guestItem->quantity;
                $existingItem->save();
                $guestItem->delete();
            } else {
                $guestItem->update(['user_id' => $userId]);
            }
        }

        return $this->getCart($userId, $sessionId);
    }

    public function validateCart(?int $userId, string $sessionId): array
    {
        $cart = $this->getCart($userId, $sessionId);
        $issues = [];

        foreach ($cart['items'] as $item) {
            $product = $item->product;
            
            if (!$product->is_available) {
                $issues[] = "Product '{$product->name}' is no longer available";
            }
            
            if ($product->track_stock && $product->stock_quantity < $item->quantity) {
                $issues[] = "Only {$product->stock_quantity} items available for '{$product->name}'";
            }
            
            if ($product->price != $item->price) {
                $issues[] = "Price changed for '{$product->name}'";
            }
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'cart' => $cart
        ];
    }

    private function getShippingOptions(): array
    {
        return [
            ['id' => 'standard', 'name' => 'Standard Shipping', 'price' => 5.99, 'days' => '5-7'],
            ['id' => 'express', 'name' => 'Express Shipping', 'price' => 12.99, 'days' => '2-3'],
            ['id' => 'overnight', 'name' => 'Overnight', 'price' => 24.99, 'days' => '1']
        ];
    }

    private function getPaymentMethods(): array
    {
        return [
            ['id' => 'credit_card', 'name' => 'Credit Card'],
            ['id' => 'paypal', 'name' => 'PayPal'],
            ['id' => 'bank_transfer', 'name' => 'Bank Transfer']
        ];
    }
}