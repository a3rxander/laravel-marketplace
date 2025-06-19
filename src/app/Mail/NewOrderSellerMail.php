<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Seller;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Collection;

class NewOrderSellerMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public Collection $items,
        public Seller $seller
    ) {
    }

    public function build()
    {
        $totalAmount = $this->items->sum('total_price');
        
        return $this->subject("Nueva Orden Recibida #{$this->order->order_number}")
                    ->view('emails.new-order-seller')
                    ->with([
                        'order' => $this->order,
                        'items' => $this->items,
                        'seller' => $this->seller,
                        'customer' => $this->order->user,
                        'totalAmount' => $totalAmount,
                    ]);
    }
}