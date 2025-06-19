namespace App\Jobs;

use App\Models\Order;
use App\Models\SellerNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateSellerNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(public Order $order)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        try {
            $sellerGroups = $this->order->orderItems()
                ->with('seller')
                ->get()
                ->groupBy('seller_id');

            foreach ($sellerGroups as $sellerId => $items) {
                $totalAmount = $items->sum('total_price');
                $itemsCount = $items->count();
                
                SellerNotification::create([
                    'seller_id' => $sellerId,
                    'type' => 'new_order',
                    'title' => 'Nueva orden recibida',
                    'message' => "Has recibido una nueva orden #{$this->order->order_number} con {$itemsCount} producto(s) por un total de $" . number_format($totalAmount, 2),
                    'data' => [
                        'order_id' => $this->order->id,
                        'order_number' => $this->order->order_number,
                        'total_amount' => $totalAmount,
                        'items_count' => $itemsCount,
                        'customer_name' => $this->order->user->name,
                    ],
                    'read_at' => null,
                ]);

                // Actualizar estadísticas del vendedor
                $seller = $items->first()->seller;
                if ($seller) {
                    $seller->increment('total_sales', $itemsCount);
                    $seller->increment('total_revenue', $totalAmount);
                }
            }

            Log::info("Seller notifications created for order {$this->order->order_number}");

        } catch (\Exception $e) {
            Log::error("Failed to generate seller notifications: " . $e->getMessage());
            throw $e;
        }
    }
}