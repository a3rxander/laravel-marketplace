<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository extends BaseRepository
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function getPaginated(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with(['seller', 'category']);

        $this->applyFilters($query, $filters);

        return $query->paginate(
            $filters['per_page'] ?? 15,
            ['*'],
            'page',
            $filters['page'] ?? 1
        );
    }

    public function search(array $searchParams): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with(['seller', 'category']);

        // Full text search
        if (isset($searchParams['q'])) {
            $searchTerm = $searchParams['q'];
            $query->whereFullText(['name', 'description', 'short_description'], $searchTerm)
                  ->orWhere('name', 'like', "%{$searchTerm}%")
                  ->orWhere('sku', 'like', "%{$searchTerm}%");
        }

        // Category filter
        if (isset($searchParams['category_id'])) {
            $query->where('category_id', $searchParams['category_id']);
        }

        // Price range
        if (isset($searchParams['min_price'])) {
            $query->where('price', '>=', $searchParams['min_price']);
        }

        if (isset($searchParams['max_price'])) {
            $query->where('price', '<=', $searchParams['max_price']);
        }

        // Only active products for public search
        $query->where('status', 'active')
              ->where('stock_status', '!=', 'out_of_stock');

        // Sorting
        $sortBy = $searchParams['sort_by'] ?? 'created_at';
        $sortOrder = $searchParams['sort_order'] ?? 'desc';
        
        switch ($sortBy) {
            case 'price':
                $query->orderBy('price', $sortOrder);
                break;
            case 'name':
                $query->orderBy('name', $sortOrder);
                break;
            case 'rating':
                $query->orderBy('rating', $sortOrder);
                break;
            default:
                $query->orderBy('created_at', $sortOrder);
        }

        return $query->paginate(
            $searchParams['per_page'] ?? 20,
            ['*'],
            'page',
            $searchParams['page'] ?? 1
        );
    }

    public function getFeatured(int $limit = 12): Collection
    {
        return $this->model->where('is_featured', true)
            ->where('status', 'active')
            ->with(['seller', 'category'])
            ->limit($limit)
            ->get();
    }

    public function getRecent(int $limit = 12): Collection
    {
        return $this->model->where('status', 'active')
            ->with(['seller', 'category'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getTopRated(int $limit = 12): Collection
    {
        return $this->model->where('status', 'active')
            ->where('rating', '>', 0)
            ->with(['seller', 'category'])
            ->orderBy('rating', 'desc')
            ->orderBy('total_reviews', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getBestSelling(int $limit = 12): Collection
    {
        return $this->model->where('status', 'active')
            ->where('total_sales', '>', 0)
            ->with(['seller', 'category'])
            ->orderBy('total_sales', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getRelated(Product $product, int $limit = 6): Collection
    {
        return $this->model->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'active')
            ->with(['seller', 'category'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function getSellerTopProducts(int $sellerId, int $limit = 5): Collection
    {
        return $this->model->where('seller_id', $sellerId)
            ->where('status', 'active')
            ->orderBy('total_sales', 'desc')
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->get();
    }

    public function incrementViewCount(int $id): void
    {
        $this->model->where('id', $id)->increment('view_count');
    }

    public function slugExists(string $slug, int $excludeId = null): bool
    {
        $query = $this->model->where('slug', $slug);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    public function skuExists(string $sku): bool
    {
        return $this->model->where('sku', $sku)->exists();
    }

    public function getTotalCount(int $sellerId = null): int
    {
        $query = $this->model->newQuery();
        
        if ($sellerId) {
            $query->where('seller_id', $sellerId);
        }
        
        return $query->count();
    }

    public function getCountByStatus(string $status, int $sellerId = null): int
    {
        $query = $this->model->where('status', $status);
        
        if ($sellerId) {
            $query->where('seller_id', $sellerId);
        }
        
        return $query->count();
    }

    public function getOutOfStockCount(int $sellerId = null): int
    {
        $query = $this->model->where('stock_status', 'out_of_stock');
        
        if ($sellerId) {
            $query->where('seller_id', $sellerId);
        }
        
        return $query->count();
    }

    public function getLowStockCount(int $sellerId = null): int
    {
        $query = $this->model->whereColumn('stock_quantity', '<=', 'min_stock_level')
            ->where('track_stock', true);
        
        if ($sellerId) {
            $query->where('seller_id', $sellerId);
        }
        
        return $query->count();
    }

    public function getFeaturedCount(int $sellerId = null): int
    {
        $query = $this->model->where('is_featured', true);
        
        if ($sellerId) {
            $query->where('seller_id', $sellerId);
        }
        
        return $query->count();
    }

    public function getTotalViews(int $sellerId = null): int
    {
        $query = $this->model->newQuery();
        
        if ($sellerId) {
            $query->where('seller_id', $sellerId);
        }
        
        return $query->sum('view_count');
    }

    public function bulkUpdateStatus(array $productIds, string $status): int
    {
        $updateData = ['status' => $status];
        
        if ($status === 'active') {
            $updateData['published_at'] = now();
        }
        
        return $this->model->whereIn('id', $productIds)->update($updateData);
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['stock_status'])) {
            $query->where('stock_status', $filters['stock_status']);
        }

        if (isset($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured']);
        }

        if (isset($filters['is_digital'])) {
            $query->where('is_digital', $filters['is_digital']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        if (isset($filters['low_stock']) && $filters['low_stock']) {
            $query->whereColumn('stock_quantity', '<=', 'min_stock_level');
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);
    }
}