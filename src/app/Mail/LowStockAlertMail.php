<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LowStockAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Product $product)
    {
    }

    public function build()
    {
        return $this->subject("Alerta de Stock Bajo - {$this->product->name}")
                    ->view('emails.low-stock-alert')
                    ->with([
                        'product' => $this->product,
                        'seller' => $this->product->seller,
                    ]);
    }
}