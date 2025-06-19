<?php

namespace App\Jobs;

use App\Models\Product;
use App\Mail\LowStockAlertMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendLowStockAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 60;

    public function __construct(public Product $product)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        try {
            if ($this->product->seller && $this->product->seller->user) {
                Mail::to($this->product->seller->user->email)
                    ->send(new LowStockAlertMail($this->product));
                
                Log::info("Low stock alert sent for product {$this->product->sku}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to send low stock alert: " . $e->getMessage());
            throw $e;
        }
    }
}