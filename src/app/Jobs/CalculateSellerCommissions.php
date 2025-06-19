<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\SellerCommission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculateSellerCommissions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(public Order $order)
    {
        $this->onQueue('commissions');
    }

    public function handle(): void
    {
        try {
            DB::beginTransaction();

            $sellerCommissions = [];

            foreach ($this->order->orderItems as $item) {
                $commissionRate = $item->commission_rate;
                $commissionAmount = ($item->total_price * $commissionRate) / 100;
                $sellerEarnings = $item->total_price - $commissionAmount;

                // Actualizar el item con los cálculos
                $item->update([
                    'commission_amount' => $commissionAmount,
                    'seller_earnings' => $sellerEarnings
                ]);

                // Acumular por vendedor
                $sellerId = $item->seller_id;
                if (!isset($sellerCommissions[$sellerId])) {
                    $sellerCommissions[$sellerId] = [
                        'total_earnings' => 0,
                        'total_commission' => 0,
                        'items_count' => 0
                    ];
                }

                $sellerCommissions[$sellerId]['total_earnings'] += $sellerEarnings;
                $sellerCommissions[$sellerId]['total_commission'] += $commissionAmount;
                $sellerCommissions[$sellerId]['items_count']++;
            }

            // Crear registros de comisión por vendedor
            foreach ($sellerCommissions as $sellerId => $data) {
                SellerCommission::create([
                    'seller_id' => $sellerId,
                    'order_id' => $this->order->id,
                    'total_amount' => $data['total_earnings'] + $data['total_commission'],
                    'commission_amount' => $data['total_commission'],
                    'seller_earnings' => $data['total_earnings'],
                    'commission_rate' => $this->order->orderItems()
                        ->where('seller_id', $sellerId)
                        ->avg('commission_rate'),
                    'status' => 'pending',
                    'due_date' => now()->addDays(7), // Pago en 7 días
                ]);
            }

            DB::commit();
            Log::info("Commissions calculated for order {$this->order->order_number}");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to calculate commissions: " . $e->getMessage());
            throw $e;
        }
    }
}