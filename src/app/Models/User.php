<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'date_of_birth',
        'gender',
        'avatar',
        'status',
        'last_login_at',
        'timezone',
        'language',
        'is_admin',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'date_of_birth' => 'date',
        'is_admin' => 'boolean',
        'password' => 'hashed',
    ];

    // Relationships
    public function seller(): HasOne
    {
        return $this->hasOne(Seller::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function approvedSellers(): HasMany
    {
        return $this->hasMany(Seller::class, 'approved_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true);
    }

    public function scopeSellers($query)
    {
        return $query->whereHas('seller');
    }

    public function scopeCustomers($query)
    {
        return $query->where('is_admin', false)->whereDoesntHave('seller');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    // Accessors & Mutators
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getIsSellerAttribute(): bool
    {
        return $this->seller !== null;
    }

    public function getIsApprovedSellerAttribute(): bool
    {
        return $this->seller && $this->seller->status === 'approved';
    }

    public function getDefaultAddressAttribute(): ?Address
    {
        return $this->addresses()->where('is_default', true)->first();
    }

    // Helper methods
    public function hasRole(string $role): bool
    {
        return match($role) {
            'admin' => $this->is_admin,
            'seller' => $this->is_seller,
            'customer' => !$this->is_admin && !$this->is_seller,
            default => false,
        };
    }

    public function canManage(User $user): bool
    {
        if (!$this->is_admin) {
            return false;
        }

        // Can't manage themselves
        if ($this->id === $user->id) {
            return false;
        }

        // Can't manage other admins
        return !$user->is_admin;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }
}