<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'seller_id',
        'category_id',
        'name',
        'slug',
        'description',
        'short_description',
        'sku',
        'price',
        'compare_price',
        'cost_price',
        'stock_quantity',
        'min_stock_level',
        'track_stock',
        'stock_status',
        'weight',
        'dimensions',
        'status',
        'is_featured',
        'is_digital',
        'images',
        'gallery',
        'attributes',
        'variants',
        'meta_data',
        'rating',
        'total_reviews',
        'total_sales',
        'view_count',
        'published_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'rating' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_digital' => 'boolean',
        'track_stock' => 'boolean',
        'dimensions' => 'array',
        'images' => 'array',
        'gallery' => 'array',
        'attributes' => 'array',
        'variants' => 'array',
        'meta_data' => 'array',
        'published_at' => 'datetime',
    ];

    // Relationships
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_status', 'in_stock');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'active')->whereNotNull('published_at');
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeBySeller($query, $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<=', 'min_stock_level')
                    ->where('track_stock', true);
    }

    public function scopeTopRated($query)
    {
        return $query->where('rating', '>', 0)->orderBy('rating', 'desc');
    }

    public function scopeBestSelling($query)
    {
        return $query->where('total_sales', '>', 0)->orderBy('total_sales', 'desc');
    }

    // Accessors & Mutators
    public function getDiscountPercentageAttribute(): float
    {
        if (!$this->compare_price || $this->compare_price <= $this->price) {
            return 0;
        }

        return round((($this->compare_price - $this->price) / $this->compare_price) * 100, 2);
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->compare_price && $this->compare_price > $this->price;
    }

    public function getMainImageAttribute(): ?string
    {
        return $this->images[0] ?? null;
    }

    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }

    public function getFormattedComparePriceAttribute(): ?string
    {
        return $this->compare_price ? '$' . number_format($this->compare_price, 2) : null;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->track_stock && $this->stock_quantity <= $this->min_stock_level;
    }

    public function getIsOutOfStockAttribute(): bool
    {
        return $this->stock_status === 'out_of_stock' || 
               ($this->track_stock && $this->stock_quantity <= 0);
    }

    public function getIsAvailableAttribute(): bool
    {
        return $this->status === 'active' && 
               $this->seller->status === 'approved' && 
               !$this->is_out_of_stock;
    }

    // Helper methods
    public function canBePurchased(int $quantity = 1): bool
    {
        if (!$this->is_available) {
            return false;
        }

        if ($this->track_stock && $this->stock_quantity < $quantity) {
            return false;
        }

        return true;
    }

    public function decrementStock(int $quantity): void
    {
        if ($this->track_stock) {
            $this->decrement('stock_quantity', $quantity);
            $this->updateStockStatus();
        }
    }

    public function incrementStock(int $quantity): void
    {
        if ($this->track_stock) {
            $this->increment('stock_quantity', $quantity);
            $this->updateStockStatus();
        }
    }

    public function updateStockStatus(): void
    {
        if (!$this->track_stock) {
            return;
        }

        $status = $this->stock_quantity <= 0 ? 'out_of_stock' : 'in_stock';
        $this->update(['stock_status' => $status]);
    }

    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    public function updateRating(float $rating, int $totalReviews): void
    {
        $this->update([
            'rating' => $rating,
            'total_reviews' => $totalReviews,
        ]);
    }

    public function incrementSales(int $quantity = 1): void
    {
        $this->increment('total_sales', $quantity);
    }

    // Scout configuration
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'sku' => $this->sku,
            'price' => $this->price,
            'category_id' => $this->category_id,
            'seller_id' => $this->seller_id,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'rating' => $this->rating,
            'total_sales' => $this->total_sales,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === 'active' && $this->seller->status === 'approved';
    }
}