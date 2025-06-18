<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByIdWithRelations(int $id, array $relations = []): ?User
    {
        return $this->model->with($relations)->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function getPaginated(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        $this->applyFilters($query, $filters);

        return $query->paginate(
            $filters['per_page'] ?? 15,
            ['*'],
            'page',
            $filters['page'] ?? 1
        );
    }

    public function getTotalCount(): int
    {
        return $this->model->count();
    }

    public function getCountByStatus(string $status): int
    {
        return $this->model->where('status', $status)->count();
    }

    public function getAdminCount(): int
    {
        return $this->model->where('is_admin', true)->count();
    }

    public function getSellerCount(): int
    {
        return $this->model->whereHas('seller')->count();
    }

    public function getCustomerCount(): int
    {
        return $this->model->where('is_admin', false)
            ->whereDoesntHave('seller')
            ->count();
    }

    public function getRecentRegistrations(int $days = 7): int
    {
        return $this->model->where('created_at', '>=', now()->subDays($days))->count();
    }

    public function bulkUpdateStatus(array $userIds, string $status): int
    {
        return $this->model->whereIn('id', $userIds)
            ->update(['status' => $status]);
    }

    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->newQuery();
        $this->applyFilters($query, $filters);
        
        return $query->select([
            'id', 'name', 'email', 'first_name', 'last_name', 
            'phone', 'status', 'is_admin', 'created_at'
        ])->get();
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_admin'])) {
            $query->where('is_admin', $filters['is_admin']);
        }

        if (isset($filters['role'])) {
            switch ($filters['role']) {
                case 'admin':
                    $query->where('is_admin', true);
                    break;
                case 'seller':
                    $query->whereHas('seller');
                    break;
                case 'customer':
                    $query->where('is_admin', false)->whereDoesntHave('seller');
                    break;
            }
        }

        if (isset($filters['email_verified'])) {
            if ($filters['email_verified']) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        if (isset($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }

        if (isset($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }

        if (isset($filters['last_login_from'])) {
            $query->where('last_login_at', '>=', $filters['last_login_from']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);
    }
}