<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerCommission extends Model
{
    
    protected $fillable = [
        'seller_id',
        'order_id',
        'total_amount',
        'commission_amount',
        'seller_earnings',
        'commission_rate',
        'status',
        'due_date',
        'paid_at',
        'payment_reference'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'seller_earnings' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'due_date' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
