<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateProductInventory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(public Order $order)
    {
        $this->onQueue('inventory');
    }

    public function handle(): void
    {
        try {
            DB::beginTransaction();

            foreach ($this->order->orderItems as $item) {
                $product = Product::find($item->product_id);
                
                if ($product && $product->track_stock) {
                    // Reducir stock
                    $newStock = $product->stock_quantity - $item->quantity;
                    
                    $product->update([
                        'stock_quantity' => max(0, $newStock),
                        'stock_status' => $newStock <= 0 ? 'out_of_stock' : 
                                       ($newStock <= $product->min_stock_level ? 'low_stock' : 'in_stock'),
                        'total_sales' => $product->total_sales + $item->quantity
                    ]);

                    // Si el stock está bajo, crear notificación para el vendedor
                    if ($newStock <= $product->min_stock_level && $newStock > 0) {
                        \App\Jobs\SendLowStockAlert::dispatch($product)
                            ->onQueue('notifications')
                            ->delay(now()->addMinutes(5));
                    }
                }
            }

            DB::commit();
            Log::info("Inventory updated for order {$this->order->order_number}");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update inventory: " . $e->getMessage());
            throw $e;
        }
    }
}   