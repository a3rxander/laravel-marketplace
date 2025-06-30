<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true; // Everyone can view products list
    }

    public function view(?User $user, Product $product): bool
    {
        // Public products can be viewed by anyone
        if ($product->status === 'active') {
            return true;
        }

        // Non-public products require authentication
        if (!$user) {
            return false;
        }

        // Admins can view any product
        if ($user->is_admin) {
            return true;
        }

        // Sellers can view their own products
        if ($user->seller && $user->seller->id === $product->seller_id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Admins can always create products
        if ($user->is_admin) {
            return true;
        } 

        // Sellers can create products if approved
        return $user->seller && $user->seller->status === 'approved';
    }

    public function update(User $user, Product $product): bool
    {
        // Admins can update any product
        if ($user->is_admin) {
            return true;
        }

        // Sellers can only update their own products
        if ($user->seller && $user->seller->id === $product->seller_id) {
            return $user->seller->status === 'approved';
        }

        return false;
    }

    public function delete(User $user, Product $product): bool
    {
        // Admins can delete any product
        if ($user->is_admin) {
            return true;
        }

        // Sellers can delete their own products (with some restrictions)
        if ($user->seller && $user->seller->id === $product->seller_id) {
            // Cannot delete if there are pending/active orders
            return !$this->hasActiveOrders($product);
        }

        return false;
    }

    public function updateStatus(User $user, Product $product): bool
    {
        // Admins can update any product status
        if ($user->is_admin) {
            return true;
        }

        // Sellers can update their own product status (with limitations)
        if ($user->seller && $user->seller->id === $product->seller_id) {
            return $user->seller->status === 'approved';
        }

        return false;
    }

    public function viewOwn(User $user): bool
    {
        return $user->seller && $user->seller->status === 'approved';
    }

    public function bulkUpdate(User $user): bool
    {
        return $user->is_admin;
    }

    public function feature(User $user, Product $product): bool
    {
        // Only admins can feature products
        return $user->is_admin;
    }

    public function approve(User $user, Product $product): bool
    {
        // Only admins can approve products
        return $user->is_admin;
    }

    public function archive(User $user, Product $product): bool
    {
        // Admins can archive any product
        if ($user->is_admin) {
            return true;
        }

        // Sellers can archive their own products
        if ($user->seller && $user->seller->id === $product->seller_id) {
            return !$this->hasActiveOrders($product);
        }

        return false;
    }

    public function duplicate(User $user, Product $product): bool
    {
        // Admins can duplicate any product
        if ($user->is_admin) {
            return true;
        }

        // Sellers can duplicate their own products
        if ($user->seller && $user->seller->id === $product->seller_id) {
            return $user->seller->status === 'approved';
        }

        return false;
    }

    public function manageInventory(User $user, Product $product): bool
    {
        // Admins can manage any product inventory
        if ($user->is_admin) {
            return true;
        }

        // Sellers can manage their own product inventory
        if ($user->seller && $user->seller->id === $product->seller_id) {
            return $user->seller->status === 'approved';
        }

        return false;
    }

    public function viewAnalytics(User $user, Product $product): bool
    {
        // Admins can view analytics for any product
        if ($user->is_admin) {
            return true;
        }

        // Sellers can view analytics for their own products
        if ($user->seller && $user->seller->id === $product->seller_id) {
            return true;
        }

        return false;
    }

    private function hasActiveOrders(Product $product): bool
    {
        // Check if product has any pending or active orders
        return $product->orderItems()
            ->whereHas('order', function ($query) {
                $query->whereIn('status', ['pending', 'confirmed', 'processing', 'shipped']);
            })
            ->exists();
    }
}