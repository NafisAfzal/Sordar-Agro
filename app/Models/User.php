<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'role',
        'must_change_password', 'is_active', 'created_by',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // ---- Role helpers -------------------------------------------------
    public function isAdmin(): bool    { return $this->role === 'admin'; }
    public function isSeller(): bool   { return $this->role === 'seller'; }
    public function isCustomer(): bool { return $this->role === 'customer'; }

    /**
     * Sellers retain all customer (buyer) abilities, so "can shop" is true
     * for both customers and sellers.
     */
    public function canShop(): bool
    {
        return in_array($this->role, ['customer', 'seller'], true);
    }

    /** Where to send the user after login, based on role. */
    public function homeRoute(): string
    {
        return match ($this->role) {
            'admin'  => 'admin.dashboard',
            'seller' => 'seller.dashboard',
            default  => 'home',
        };
    }

    // ---- Relationships ------------------------------------------------
    public function products(): HasMany { return $this->hasMany(Product::class, 'seller_id'); }
    public function orders(): HasMany   { return $this->hasMany(Order::class); }
    public function cartItems(): HasMany { return $this->hasMany(Cart::class); }
    public function wishlists(): HasMany { return $this->hasMany(Wishlist::class); }
    public function createdSellers(): HasMany { return $this->hasMany(User::class, 'created_by'); }
}
