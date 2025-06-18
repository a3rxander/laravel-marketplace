<?php

namespace App\Repositories;

use App\Models\Seller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SellerRepository extends BaseRepository
{
    public function __construct(Seller $model)
    {
        parent::__construct($model);
    }

    public function findByUserId(int $userId): ?Seller
    {
        return $this->model->where('user_id', $userId)->first();
    }

    public function findBySlug(string $slug): ?Seller
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function getPaginated(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with(['user']);

        $this->applyFilters($query, $filters);

        return $query->paginate(
            $filters['per_page'] ?? 15,
            ['*'],
            'page',
            $filters['page'] ?? 1
        );
    }

    public function getTopSellers(int $limit = 10): Collection
    {
        return $this->model->where('status', 'approved')
            ->where('rating', '>', 0)
            ->with(['user'])
            ->orderBy('rating', 'desc')
            ->orderBy('total_sales', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getRecentApplications(int $days = 7): int
    {
        return $this->model->where('created_at', '>=', now()->subDays($days))->count();
    }

    public function slugExists(string $slug, int $excludeId = null): bool
    {
        $query = $this->model->where('slug', $slug);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    public function getTotalCount(): int
    {
        return $this->model->count();
    }

    public function getCountByStatus(string $status): int
    {
        return $this->model->where('status', $status)->count();
    }

    public function getApprovedSellers(): Collection
    {
        return $this->model->where('status', 'approved')
            ->with(['user'])
            ->orderBy('business_name')
            ->get();
    }

    public function getPendingApprovals(): Collection
    {
        return $this->model->where('status', 'pending')
            ->with(['user'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function updateRating(int $sellerId, float $rating, int $totalReviews): bool
    {
        return $this->model->where('id', $sellerId)
            ->update([
                'rating' => $rating,
                'total_reviews' => $totalReviews
            ]) > 0;
    }

    public function updateSalesStats(int $sellerId, int $totalSales, float $totalRevenue): bool
    {
        return $this->model->where('id', $sellerId)
            ->update([
                'total_sales' => $totalSales,
                'total_revenue' => $totalRevenue
            ]) > 0;
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                  ->orWhere('business_email', 'like', "%{$search}%")
                  ->orWhere('business_registration_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if (isset($filters['min_rating'])) {
            $query->where('rating', '>=', $filters['min_rating']);
        }

        if (isset($filters['commission_rate'])) {
            $query->where('commission_rate', $filters['commission_rate']);
        }

        if (isset($filters['approved_from'])) {
            $query->where('approved_at', '>=', $filters['approved_from']);
        }

        if (isset($filters['approved_to'])) {
            $query->where('approved_at', '<=', $filters['approved_to']);
        }

        if (isset($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }

        if (isset($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        
        switch ($sortBy) {
            case 'business_name':
                $query->orderBy('business_name', $sortOrder);
                break;
            case 'rating':
                $query->orderBy('rating', $sortOrder);
                break;
            case 'total_sales':
                $query->orderBy('total_sales', $sortOrder);
                break;
            case 'total_revenue':
                $query->orderBy('total_revenue', $sortOrder);
                break;
            case 'approved_at':
                $query->orderBy('approved_at', $sortOrder);
                break;
            default:
                $query->orderBy('created_at', $sortOrder);
        }
    }
}