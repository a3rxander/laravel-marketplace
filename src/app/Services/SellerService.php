<?php

namespace App\Services;

use App\Models\Seller;
use App\Repositories\SellerRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

class SellerService
{
    public function __construct(
        private SellerRepository $sellerRepository,
        private OrderRepository $orderRepository,
        private ProductRepository $productRepository
    ) {}

    public function getSellers(array $filters = []): LengthAwarePaginator
    {
        return $this->sellerRepository->getPaginated($filters);
    }

    public function getSellerById(int $id): Seller
    {
        $seller = $this->sellerRepository->findById($id);
        
        if (!$seller) {
            throw new ModelNotFoundException('Seller not found');
        }
        
        return $seller;
    }

    public function getSellerByUserId(int $userId): ?Seller
    {
        return $this->sellerRepository->findByUserId($userId);
    }

    public function createSeller(array $data): Seller
    {
        //validate if exists seller then error
        if ($this->sellerRepository->findByUserId($data['user_id'])) 
        {
            throw new \Exception('Seller already exists');
        }

        // Generate slug if not provided
        if (!isset($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['business_name']);
        }

        // Set default values
        $data['status'] = 'pending';
        $data['commission_rate'] = $data['commission_rate'] ?? 10.00;
        $data['rating'] = 0.00;
        $data['total_reviews'] = 0;
        $data['total_sales'] = 0;
        $data['total_revenue'] = 0.00;

        return $this->sellerRepository->create($data);
    }

    public function updateSeller(int $id, array $data): Seller
    {
        $seller = $this->getSellerById($id);

        // Update slug if business name changed
        if (isset($data['business_name']) && $data['business_name'] !== $seller->business_name) {
            $data['slug'] = $this->generateUniqueSlug($data['business_name'], $id);
        }

        return $this->sellerRepository->update($seller, $data);
    }

    public function deleteSeller(int $id): bool
    {
        $seller = $this->getSellerById($id);
        return $this->sellerRepository->delete($seller);
    }

    public function approveSeller(int $id, array $data, int $approvedBy): Seller
    {
        $seller = $this->getSellerById($id);
        
        $updateData = [
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $approvedBy,
            'rejection_reason' => null,
        ];

        // Update commission rate if provided
        if (isset($data['commission_rate'])) {
            $updateData['commission_rate'] = $data['commission_rate'];
        }

        return $this->sellerRepository->update($seller, $updateData);
    }

    public function rejectSeller(int $id, string $reason): Seller
    {
        $seller = $this->getSellerById($id);
        
        $updateData = [
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_at' => null,
            'approved_by' => null,
        ];

        return $this->sellerRepository->update($seller, $updateData);
    }

    public function suspendSeller(int $id, string $reason = null): Seller
    {
        $seller = $this->getSellerById($id);
        
        $updateData = [
            'status' => 'suspended',
            'rejection_reason' => $reason,
        ];

        return $this->sellerRepository->update($seller, $updateData);
    }

    public function reactivateSeller(int $id): Seller
    {
        $seller = $this->getSellerById($id);
        
        $updateData = [
            'status' => 'approved',
            'rejection_reason' => null,
        ];

        return $this->sellerRepository->update($seller, $updateData);
    }

    public function getSellerDashboard(int $sellerId): array
    {
        $seller = $this->getSellerById($sellerId);
        
        // Get basic stats
        $stats = [
            'total_products' => $this->productRepository->getTotalCount($sellerId),
            'active_products' => $this->productRepository->getCountByStatus('active', $sellerId),
            'total_orders' => $this->orderRepository->getSellerOrderCount($sellerId),
            'total_revenue' => $seller->total_revenue,
            'total_sales' => $seller->total_sales,
            'rating' => $seller->rating,
            'total_reviews' => $seller->total_reviews,
        ];

        // Get recent orders
        $recentOrders = $this->orderRepository->getSellerRecentOrders($sellerId, 5);
        
        // Get top products
        $topProducts = $this->productRepository->getSellerTopProducts($sellerId, 5);
        
        // Get monthly sales data for chart
        $monthlySales = $this->orderRepository->getSellerMonthlySales($sellerId, 12);
        
        return [
            'stats' => $stats,
            'recent_orders' => $recentOrders,
            'top_products' => $topProducts,
            'monthly_sales' => $monthlySales,
        ];
    }

    public function getSellerSales(int $sellerId, array $filters = []): array
    {
        return [
            'orders' => $this->orderRepository->getSellerOrders($sellerId, $filters),
            'summary' => $this->orderRepository->getSellerSalesSummary($sellerId, $filters),
        ];
    }

    public function updateSellerStats(int $sellerId): Seller
    {
        $seller = $this->getSellerById($sellerId);
        
        $stats = $this->orderRepository->getSellerStats($sellerId);
        
        $updateData = [
            'total_sales' => $stats['total_sales'],
            'total_revenue' => $stats['total_revenue'],
        ];

        return $this->sellerRepository->update($seller, $updateData);
    }

    public function updateSellerRating(int $sellerId, float $rating, int $totalReviews): Seller
    {
        $seller = $this->getSellerById($sellerId);
        
        $updateData = [
            'rating' => $rating,
            'total_reviews' => $totalReviews,
        ];

        return $this->sellerRepository->update($seller, $updateData);
    }

    public function getTopSellers(int $limit = 10): array
    {
        return $this->sellerRepository->getTopSellers($limit);
    }

    public function getPendingSellers(): LengthAwarePaginator
    {
        return $this->sellerRepository->getPaginated(['status' => 'pending']);
    }

    public function getSellerStats(): array
    {
        return [
            'total_sellers' => $this->sellerRepository->getTotalCount(),
            'approved_sellers' => $this->sellerRepository->getCountByStatus('approved'),
            'pending_sellers' => $this->sellerRepository->getCountByStatus('pending'),
            'rejected_sellers' => $this->sellerRepository->getCountByStatus('rejected'),
            'suspended_sellers' => $this->sellerRepository->getCountByStatus('suspended'),
            'recent_applications' => $this->sellerRepository->getRecentApplications(7),
        ];
    }

    public function searchSellers(string $query, array $filters = []): LengthAwarePaginator
    {
        $filters['search'] = $query;
        return $this->sellerRepository->getPaginated($filters);
    }

    private function generateUniqueSlug(string $businessName, int $excludeId = null): string
    {
        $baseSlug = Str::slug($businessName);
        $slug = $baseSlug;
        $counter = 1;
        
        while ($this->sellerRepository->slugExists($slug, $excludeId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}