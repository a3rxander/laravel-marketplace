<?php

namespace App\Repositories;

use App\Models\Order;
use Carbon\Carbon;

class OrderRepository
{
    /**
     * Obtiene el total de órdenes de un vendedor.
     */
    public function getSellerOrderCount(int $sellerId): int
    {
        return Order::where('seller_id', $sellerId)->count();
    }

    /**
     * Obtiene las órdenes recientes de un vendedor.
     */
    public function getSellerRecentOrders(int $sellerId, int $limit = 5)
    {
        return Order::where('seller_id', $sellerId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtiene las ventas mensuales de un vendedor.
     */
    public function getSellerMonthlySales(int $sellerId, Carbon $month)
    {
        return Order::where('seller_id', $sellerId)
            ->whereYear('created_at', $month->year)
            ->whereMonth('created_at', $month->month)
            ->sum('total_amount');
    }

    /**
     * Obtiene estadísticas generales de un vendedor.
     */
    public function getSellerStats(int $sellerId, Carbon $startDate, Carbon $endDate)
    {
        $orders = Order::where('seller_id', $sellerId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        return [
            'count' => $orders->count(),
            'total' => $orders->sum('total_amount'),
        ];
    }
}