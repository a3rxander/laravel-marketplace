<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Scout\Builder as ScoutBuilder;

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

    /**
     * Search products using Laravel Scout (Elasticsearch)
     */
    public function search(array $searchParams): LengthAwarePaginator
    {
        // Si no hay término de búsqueda, usar consulta SQL normal
        if (empty($searchParams['q'])) {
            return $this->searchWithoutScout($searchParams);
        }

        // Usar Laravel Scout para búsqueda con Elasticsearch
        $scoutQuery = $this->model->search($searchParams['q']);

        // Aplicar filtros adicionales usando Scout
        $scoutQuery = $this->applyScoutFilters($scoutQuery, $searchParams);

        // Obtener resultados paginados
        $perPage = $searchParams['per_page'] ?? 20;
        $page = $searchParams['page'] ?? 1;

        try {
            // Intentar obtener resultados con Scout
            $results = $scoutQuery->paginate($perPage, 'page', $page);
            
            // Cargar relaciones
            $results->load(['seller', 'category']);
            
            return $results;
        } catch (\Exception $e) {
            // Si Scout falla, usar búsqueda SQL como fallback
            \Log::warning('Scout search failed, falling back to SQL search', [
                'error' => $e->getMessage(),
                'search_params' => $searchParams
            ]);
            
            return $this->searchWithoutScout($searchParams);
        }
    }

    /**
     * Search usando consultas SQL tradicionales (fallback)
     */
    public function searchWithoutScout(array $searchParams): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with(['seller', 'category']);

        // Búsqueda por texto usando LIKE
        if (isset($searchParams['q'])) {
            $searchTerm = $searchParams['q'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%")
                  ->orWhere('short_description', 'like', "%{$searchTerm}%")
                  ->orWhere('sku', 'like', "%{$searchTerm}%");
            });
        }

        // Aplicar otros filtros
        $this->applySqlFilters($query, $searchParams);

        return $query->paginate(
            $searchParams['per_page'] ?? 20,
            ['*'],
            'page',
            $searchParams['page'] ?? 1
        );
    }

    /**
     * Aplicar filtros a Scout query
     */
    protected function applyScoutFilters(ScoutBuilder $query, array $filters): ScoutBuilder
    {
        // Category filter
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Price range filters
        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        // Status filters para búsqueda pública
        $query->where('status', 'active');

        return $query;
    }

    /**
     * Aplicar filtros a consulta SQL
     */
    protected function applySqlFilters(Builder $query, array $filters): void
    {
        // Category filter
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Price range
        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        // Solo productos activos para búsqueda pública
        $query->where('status', 'active')
              ->where('stock_status', '!=', 'out_of_stock');

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        
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
    }

    /**
     * Búsqueda avanzada con Scout incluyendo facetas
     */
    public function advancedSearch(array $searchParams): array
    {
        $searchTerm = $searchParams['q'] ?? '';
        
        try {
            // Búsqueda principal
            $scoutQuery = $this->model->search($searchTerm);
            $scoutQuery = $this->applyScoutFilters($scoutQuery, $searchParams);
            
            $results = $scoutQuery->paginate($searchParams['per_page'] ?? 20);
            $results->load(['seller', 'category']);

            // Obtener facetas/agregaciones para filtros
            $facets = $this->getSearchFacets($searchTerm, $searchParams);

            return [
                'results' => $results,
                'facets' => $facets,
                'total' => $results->total(),
                'search_term' => $searchTerm
            ];
        } catch (\Exception $e) {
            \Log::warning('Advanced Scout search failed', [
                'error' => $e->getMessage(),
                'search_params' => $searchParams
            ]);
            
            // Fallback a búsqueda SQL simple
            return [
                'results' => $this->searchWithoutScout($searchParams),
                'facets' => [],
                'total' => 0,
                'search_term' => $searchTerm
            ];
        }
    }

    /**
     * Obtener facetas para filtros de búsqueda
     */
    protected function getSearchFacets(string $searchTerm, array $filters = []): array
    {
        try {
            // Obtener categorías disponibles en los resultados
            $categoryFacets = $this->model->search($searchTerm)
                ->get()
                ->groupBy('category_id')
                ->map(function ($products, $categoryId) {
                    return [
                        'count' => $products->count(),
                        'category' => $products->first()->category
                    ];
                });

            // Rangos de precio
            $priceRanges = $this->getPriceRangesFromSearch($searchTerm);

            return [
                'categories' => $categoryFacets,
                'price_ranges' => $priceRanges
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtener rangos de precio de los resultados de búsqueda
     */
    protected function getPriceRangesFromSearch(string $searchTerm): array
    {
        try {
            $products = $this->model->search($searchTerm)->get();
            
            if ($products->isEmpty()) {
                return [];
            }

            $minPrice = $products->min('price');
            $maxPrice = $products->max('price');
            
            // Crear rangos automáticos
            $range = $maxPrice - $minPrice;
            $step = $range / 4; // 4 rangos
            
            return [
                ['min' => $minPrice, 'max' => $minPrice + $step, 'label' => '$' . number_format($minPrice, 0) . ' - $' . number_format($minPrice + $step, 0)],
                ['min' => $minPrice + $step, 'max' => $minPrice + ($step * 2), 'label' => '$' . number_format($minPrice + $step, 0) . ' - $' . number_format($minPrice + ($step * 2), 0)],
                ['min' => $minPrice + ($step * 2), 'max' => $minPrice + ($step * 3), 'label' => '$' . number_format($minPrice + ($step * 2), 0) . ' - $' . number_format($minPrice + ($step * 3), 0)],
                ['min' => $minPrice + ($step * 3), 'max' => $maxPrice, 'label' => '$' . number_format($minPrice + ($step * 3), 0) . ' - $' . number_format($maxPrice, 0)],
            ];
        } catch (\Exception $e) {
            return [];
        }
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