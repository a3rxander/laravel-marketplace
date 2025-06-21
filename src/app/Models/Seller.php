<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seller extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'slug',
        'description',
        'business_email',
        'business_phone',
        'business_registration_number',
        'tax_id',
        'logo',
        'banner',
        'business_hours',
        'status',
        'commission_rate',
        'rating',
        'total_reviews',
        'total_sales',
        'total_revenue',
        'approved_at',
        'approved_by',
        'rejection_reason'
    ];

    protected $casts = [
        'business_hours' => 'array',
        'commission_rate' => 'decimal:2',
        'rating' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'approved_at' => 'datetime'
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(SellerCommission::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(SellerNotification::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'approved');
    }

    // Accessors
    public function getIsApprovedAttribute(): bool
    {
        return $this->status === 'approved';
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }

    public function getFormattedRatingAttribute(): string
    {
        return number_format($this->rating, 1);
    }

    public function getFormattedRevenueAttribute(): string
    {
        return  number_format($this->total_revenue, 2);
    }

    // Helper methods
    public function canSell(): bool
    {
        return $this->status === 'approved';
    }

    public function incrementSales(int $quantity = 1, float $amount = 0): void
    {
        $this->increment('total_sales', $quantity);
        $this->increment('total_revenue', $amount);
    }

    public function updateRating(float $newRating, int $totalReviews): void
    {
        $this->update([
            'rating' => $newRating,
            'total_reviews' => $totalReviews
        ]);
    }
}