<?php

namespace App\Jobs;

use App\Models\Order;
use App\Mail\OrderConfirmationMail;
use App\Mail\NewOrderSellerMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendOrderConfirmationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(public Order $order)
    {
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        try {
            // Email al comprador
            Mail::to($this->order->user->email)
                ->send(new OrderConfirmationMail($this->order));

            // Emails a los vendedores (agrupados por vendedor)
            $sellerGroups = $this->order->orderItems()
                ->with('seller.user')
                ->get()
                ->groupBy('seller_id');

            foreach ($sellerGroups as $sellerId => $items) {
                $seller = $items->first()->seller;
                if ($seller && $seller->user) {
                    Mail::to($seller->user->email)
                        ->send(new NewOrderSellerMail($this->order, $items, $seller));
                }
            }

            Log::info("Order confirmation emails sent for order {$this->order->order_number}");

        } catch (\Exception $e) {
            Log::error("Failed to send order confirmation emails: " . $e->getMessage());
            throw $e;
        }
    }
}