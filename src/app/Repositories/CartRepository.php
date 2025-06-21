<?php

namespace App\Repositories;

use App\Models\Cart;
use Illuminate\Database\Eloquent\Collection;

class CartRepository extends BaseRepository
{
    public function __construct(Cart $model)
    {
        parent::__construct($model);
    }

    public function getCartItems(?int $userId, string $sessionId): Collection
    {
        $query = $this->model->newQuery()->with(['product.seller', 'product.category']);
        
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId)->whereNull('user_id');
        }
        
        return $query->get();
    }

    public function findCartItem(int $productId, ?int $userId, string $sessionId): ?Cart
    {
        $query = $this->model->where('product_id', $productId);
        
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId)->whereNull('user_id');
        }
        
        return $query->first();
    }

    public function getCartCount(?int $userId, string $sessionId): int
    {
        $query = $this->model->newQuery();
        
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId)->whereNull('user_id');
        }
        
        return $query->sum('quantity');
    }

    public function clearUserCart(?int $userId, string $sessionId): bool
    {
        $query = $this->model->newQuery();
        
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId)->whereNull('user_id');
        }
        
        return $query->delete() > 0;
    }

    public function getGuestCartItems(string $sessionId): Collection
    {
        return $this->model->where('session_id', $sessionId)
            ->whereNull('user_id')
            ->with(['product'])
            ->get();
    }
}