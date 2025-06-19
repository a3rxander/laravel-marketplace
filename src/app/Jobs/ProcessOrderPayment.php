<?php

namespace App\Jobs;

use App\Models\Order;
use App\Jobs\SendOrderConfirmationEmail;
use App\Jobs\UpdateProductInventory;
use App\Jobs\CalculateSellerCommissions;
use App\Jobs\GenerateSellerNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessOrderPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    
    public function __construct(
        public Order $order,
        public string $paymentReference
    ) {
        $this->onQueue('orders');
    }

    public function handle(): void
    {
        try {
            DB::beginTransaction();
            
            // Actualizar estado de la orden
            $this->order->update([
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'payment_reference' => $this->paymentReference,
                'paid_at' => now(),
                'confirmed_at' => now(),
            ]);

            // Actualizar items de la orden
            $this->order->orderItems()->update([
                'status' => 'confirmed'
            ]);

            DB::commit();

            Log::info("Order {$this->order->order_number} payment processed successfully");

            // Despachar trabajos relacionados en secuencia
            SendOrderConfirmationEmail::dispatch($this->order)
                ->onQueue('emails')
                ->delay(now()->addSeconds(10));

            UpdateProductInventory::dispatch($this->order)
                ->onQueue('inventory')
                ->delay(now()->addSeconds(20));

            CalculateSellerCommissions::dispatch($this->order)
                ->onQueue('commissions')
                ->delay(now()->addSeconds(30));

            GenerateSellerNotification::dispatch($this->order)
                ->onQueue('notifications')
                ->delay(now()->addSeconds(40));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to process order payment: " . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessOrderPayment failed for order {$this->order->id}: " . $exception->getMessage());
        
        // Marcar la orden como fallida
        $this->order->update([
            'status' => 'cancelled',
            'payment_status' => 'failed',
            'admin_notes' => 'Payment processing failed: ' . $exception->getMessage()
        ]);
    }
}