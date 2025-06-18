<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

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

        return $this->productRepository->create($data);
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

        return $this->productRepository->update($product, $data);
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
        
        return $this->productRepository->update($product, $updateData);
    }

    public function bulkUpdateStatus(array $productIds, string $status): int
    {
        return $this->productRepository->bulkUpdateStatus($productIds, $status);
    }

    public function incrementViewCount(int $id): void
    {
        $this->productRepository->incrementViewCount($id);
    }

    public function searchProducts(array $searchParams): LengthAwarePaginator
    {
        return $this->productRepository->search($searchParams);
    }

    public function getFeaturedProducts(int $limit = 12): array
    {
        return $this->productRepository->getFeatured($limit);
    }

    public function getRecentProducts(int $limit = 12): array
    {
        return $this->productRepository->getRecent($limit);
    }

    public function getTopRatedProducts(int $limit = 12): array
    {
        return $this->productRepository->getTopRated($limit);
    }

    public function getBestSellingProducts(int $limit = 12): array
    {
        return $this->productRepository->getBestSelling($limit);
    }

    public function getRelatedProducts(int $productId, int $limit = 6): array
    {
        $product = $this->getProductById($productId);
        return $this->productRepository->getRelated($product, $limit);
    }

    public function updateStock(int $id, int $quantity): Product
    {
        $product = $this->getProductById($id);
        
        $updateData = [
            'stock_quantity' => $quantity,
            'stock_status' => $this->determineStockStatus($quantity)
        ];
        
        return $this->productRepository->update($product, $updateData);
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