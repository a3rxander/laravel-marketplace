<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;

class ProductService
{
    public function __construct(
        private ProductRepository $productRepository
    ) {}

    public function getProducts(array $filters = []): LengthAwarePaginator
    {
        return $this->productRepository->getPaginated($filters);
    }

    public function getProductById(int $id): Product
    {
        $product = $this->productRepository->findById($id);
        
        if (!$product) {
            throw new ModelNotFoundException('Product not found');
        }
        
        return $product;
    }

    public function createProduct(array $data): Product
    {
        // Generate slug if not provided
        if (!isset($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        // Generate SKU if not provided
        if (!isset($data['sku'])) {
            $data['sku'] = $this->generateUniqueSku();
        }

        // Set default values
        $data['status'] = $data['status'] ?? 'draft';
        $data['stock_status'] = $this->determineStockStatus($data['stock_quantity'] ?? 0);
        $data['is_featured'] = $data['is_featured'] ?? false;
        $data['is_digital'] = $data['is_digital'] ?? false;
        $data['track_stock'] = $data['track_stock'] ?? true;
        $data['min_stock_level'] = $data['min_stock_level'] ?? 5;
        $data['published_at'] = $data['status'] === 'active' ? now() : null;

        $product = $this->productRepository->create($data); 
        
        return $product;
    }

    public function updateProduct(int $id, array $data): Product
    {
        $product = $this->getProductById($id);

        // Update slug if name changed
        if (isset($data['name']) && $data['name'] !== $product->name) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $id);
        }

        // Update stock status based on quantity
        if (isset($data['stock_quantity'])) {
            $data['stock_status'] = $this->determineStockStatus($data['stock_quantity']);
        }

        // Set published_at when activating
        if (isset($data['status']) && $data['status'] === 'active' && $product->status !== 'active') {
            $data['published_at'] = now();
        }

        $updatedProduct = $this->productRepository->update($product, $data); 
        return $updatedProduct;
    }

    public function deleteProduct(int $id): bool
    {
        $product = $this->getProductById($id); 
        
        return $this->productRepository->delete($product);
    }

    public function getSellerProducts(int $sellerId, array $filters = []): LengthAwarePaginator
    {
        $filters['seller_id'] = $sellerId;
        return $this->productRepository->getPaginated($filters);
    }

    public function updateProductStatus(int $id, string $status): Product
    {
        $product = $this->getProductById($id);
        
        $updateData = ['status' => $status];
        
        if ($status === 'active' && $product->status !== 'active') {
            $updateData['published_at'] = now();
        }
        
        $updatedProduct = $this->productRepository->update($product, $updateData); 
        
        return $updatedProduct;
    }

    public function bulkUpdateStatus(array $productIds, string $status): int
    {
        $updatedCount = $this->productRepository->bulkUpdateStatus($productIds, $status); 
        
        return $updatedCount;
    }

    public function incrementViewCount(int $id): void
    {
        $this->productRepository->incrementViewCount($id);
    }

    /**
     * Search products using Scout/Elasticsearch
     */
    public function searchProducts(array $searchParams): LengthAwarePaginator
    {
        return $this->productRepository->search($searchParams);
    }

    /**
     * Advanced search with facets
     */
    public function advancedSearchProducts(array $searchParams): array
    {
        return $this->productRepository->advancedSearch($searchParams);
    }

    /**
     * Get search suggestions for autocomplete
     */
    public function getSearchSuggestions(string $query, int $limit = 10): array
    {
        try {
            // Use Scout to get suggestions
            $products = Product::search($query)
                ->where('status', 'active')
                ->take($limit)
                ->get(['name', 'sku']);

            $suggestions = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku
                ];
            })->toArray();

            // Add popular search terms if available
            $popularTerms = $this->getPopularSearchTerms($query, 5);
            
            return [
                'products' => $suggestions,
                'popular_terms' => $popularTerms
            ];
        } catch (\Exception $e) {
            \Log::warning('Search suggestions failed', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            
            return [
                'products' => [],
                'popular_terms' => []
            ];
        }
    }

    /**
     * Get similar products using Scout
     */
    public function getSimilarProducts(int $productId, int $limit = 6): array
    {
        $product = $this->getProductById($productId);
        
        try {
            // Search by product name and category
            $searchTerms = explode(' ', $product->name);
            $mainTerm = $searchTerms[0] ?? $product->name;
            
            $similarProducts = Product::search($mainTerm)
                ->where('status', 'active')
                ->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->take($limit)
                ->get();

            return $similarProducts->load(['seller', 'category'])->toArray();
        } catch (\Exception $e) {
            \Log::warning('Similar products search failed', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to category-based similarity
            return $this->productRepository->getRelated($product, $limit)->toArray();
        }
    }

    /**
     * Get debug information for search
     */
    public function getSearchDebugInfo(string $query): array
    {
        try {
            // Get raw Elasticsearch response
            $rawResults = Product::search($query)->raw();
            
            // Get processed results
            $processedResults = Product::search($query)->get();
            
            return [
                'query' => $query,
                'elasticsearch_response' => $rawResults,
                'processed_count' => $processedResults->count(),
                'scout_driver' => config('scout.driver'),
                'elasticsearch_config' => config('scout.elasticsearch'),
                'model_searchable_data' => (new Product())->toSearchableArray()
            ];
        } catch (\Exception $e) {
            return [
                'query' => $query,
                'error' => $e->getMessage(),
                'scout_driver' => config('scout.driver'),
                'elasticsearch_config' => config('scout.elasticsearch')
            ];
        }
    }

     

    public function getFeaturedProducts(int $limit = 12): array
    {
        return $this->productRepository->getFeatured($limit)->toArray();
    }

    public function getRecentProducts(int $limit = 12): array
    {
        return $this->productRepository->getRecent($limit)->toArray();
    }

    public function getTopRatedProducts(int $limit = 12): array
    {
        return $this->productRepository->getTopRated($limit)->toArray();
    }

    public function getBestSellingProducts(int $limit = 12): array
    {
        return $this->productRepository->getBestSelling($limit)->toArray();
    }

    public function getRelatedProducts(int $productId, int $limit = 6): array
    {
        $product = $this->getProductById($productId);
        return $this->productRepository->getRelated($product, $limit)->toArray();
    }

    public function updateStock(int $id, int $quantity): Product
    {
        $product = $this->getProductById($id);
        
        $updateData = [
            'stock_quantity' => $quantity,
            'stock_status' => $this->determineStockStatus($quantity)
        ];
        
        $updatedProduct = $this->productRepository->update($product, $updateData); 
        
        return $updatedProduct;
    }

    public function decrementStock(int $id, int $quantity): Product
    {
        $product = $this->getProductById($id);
        
        if ($product->track_stock && $product->stock_quantity < $quantity) {
            throw new \Exception('Insufficient stock');
        }
        
        $newQuantity = $product->track_stock ? $product->stock_quantity - $quantity : $product->stock_quantity;
        
        return $this->updateStock($id, $newQuantity);
    }

    public function incrementStock(int $id, int $quantity): Product
    {
        $product = $this->getProductById($id);
        $newQuantity = $product->stock_quantity + $quantity;
        
        return $this->updateStock($id, $newQuantity);
    }

    public function getProductStats(int $sellerId = null): array
    {
        return [
            'total_products' => $this->productRepository->getTotalCount($sellerId),
            'active_products' => $this->productRepository->getCountByStatus('active', $sellerId),
            'draft_products' => $this->productRepository->getCountByStatus('draft', $sellerId),
            'inactive_products' => $this->productRepository->getCountByStatus('inactive', $sellerId),
            'out_of_stock' => $this->productRepository->getOutOfStockCount($sellerId),
            'low_stock' => $this->productRepository->getLowStockCount($sellerId),
            'featured_products' => $this->productRepository->getFeaturedCount($sellerId),
            'total_views' => $this->productRepository->getTotalViews($sellerId),
        ];
    }
    /**
     * Get popular search terms (mock implementation)
     */
    protected function getPopularSearchTerms(string $query, int $limit): array
    {
        // En una implementación real, esto vendría de una tabla de búsquedas guardadas
        // Por ahora, devolvemos términos relacionados mock
        $terms = [
            'laptop' => ['laptop gaming', 'laptop office', 'laptop student'],
            'phone' => ['smartphone', 'mobile phone', 'cell phone'],
            'book' => ['textbook', 'novel', 'ebook'],
            'shirt' => ['t-shirt', 'dress shirt', 'polo shirt'],
        ];
        
        $queryLower = strtolower($query);
        foreach ($terms as $key => $suggestions) {
            if (str_contains($queryLower, $key)) {
                return array_slice($suggestions, 0, $limit);
            }
        }
        
        return [];
    }

    private function generateUniqueSlug(string $name, int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;
        
        while ($this->productRepository->slugExists($slug, $excludeId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    private function generateUniqueSku(): string
    {
        do {
            $sku = 'PRD-' . strtoupper(Str::random(8));
        } while ($this->productRepository->skuExists($sku));
        
        return $sku;
    }

    private function determineStockStatus(int $quantity): string
    {
        if ($quantity <= 0) {
            return 'out_of_stock';
        }
        
        return 'in_stock';
    }

}