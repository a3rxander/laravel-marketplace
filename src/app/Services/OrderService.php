<?php

namespace App\Services;

use App\Models\Order;
use App\Jobs\ProcessOrderPayment;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderService
{
    public function getOrders(array $filters = []): LengthAwarePaginator
    {
        $query = Order::with(['user', 'orderItems.product']);
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        
        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getOrderById(int $id): Order
    {
        return Order::with(['user', 'orderItems.product.seller'])->findOrFail($id);
    }

    public function createOrder(array $data): Order
    {
        $order = Order::create($data);
        
        // Process payment job
        ProcessOrderPayment::dispatch($order, $data['payment_reference'] ?? 'manual')
            ->onQueue('orders');
        
        return $order;
    }

    public function updateOrder(int $id, array $data): Order
    {
        $order = $this->getOrderById($id);
        $order->update($data);
        return $order;
    }

    public function deleteOrder(int $id): bool
    {
        $order = $this->getOrderById($id);
        return $order->delete();
    }

    public function getUserOrders(int $userId, array $filters = []): LengthAwarePaginator
    {
        $filters['user_id'] = $userId;
        return $this->getOrders($filters);
    }

    public function updateOrderStatus(int $id, string $status, ?string $notes = null): Order
    {
        $order = $this->getOrderById($id);
        
        $updateData = ['status' => $status];
        if ($notes) {
            $updateData['admin_notes'] = $notes;
        }
        
        $order->update($updateData);
        return $order;
    }

    public function cancelOrder(int $id, ?string $reason = null): Order
    {
        return $this->updateOrderStatus($id, 'cancelled', $reason);
    }

    public function confirmOrder(int $id): Order
    {
        return $this->updateOrderStatus($id, 'confirmed');
    }

    public function shipOrder(int $id, string $trackingNumber, ?string $shippingMethod = null): Order
    {
        $order = $this->getOrderById($id);
        
        $order->update([
            'status' => 'shipped',
            'tracking_number' => $trackingNumber,
            'shipping_method' => $shippingMethod,
            'shipped_at' => now()
        ]);
        
        return $order;
    }

    public function deliverOrder(int $id): Order
    {
        $order = $this->getOrderById($id);
        
        $order->update([
            'status' => 'delivered',
            'delivered_at' => now()
        ]);
        
        return $order;
    }

    public function refundOrder(int $id, ?float $refundAmount = null, ?string $reason = null): Order
    {
        $order = $this->getOrderById($id);
        
        $order->update([
            'status' => 'refunded',
            'refund_amount' => $refundAmount ?? $order->total_amount,
            'refund_reason' => $reason,
            'refunded_at' => now()
        ]);
        
        return $order;
    }

    public function getSellerOrders(int $sellerId, array $filters = []): LengthAwarePaginator
    {
        $query = Order::whereHas('orderItems', function($q) use ($sellerId) {
            $q->where('seller_id', $sellerId);
        })->with(['user', 'orderItems' => function($q) use ($sellerId) {
            $q->where('seller_id', $sellerId)->with('product');
        }]);
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function generateInvoice(int $id): array
    {
        $order = $this->getOrderById($id);
        
        return [
            'order' => $order,
            'invoice_number' => 'INV-' . $order->order_number,
            'generated_at' => now(),
            'total_amount' => $order->total_amount
        ];
    }
}
