<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /** Admins may perform every product action; sellers are checked below. */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isSeller();
    }

    public function view(User $user, Product $product): bool
    {
        return $user->id === $product->seller_id;
    }

    public function create(User $user): bool
    {
        return $user->isSeller();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->id === $product->seller_id
            && $product->status !== 'approved';
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->id === $product->seller_id
            && $product->status !== 'approved';
    }

    /** Only admins (handled by before()) may approve or reject. */
    public function approve(User $user, Product $product): bool
    {
        return false;
    }

    public function reject(User $user, Product $product): bool
    {
        return false;
    }

    /** Only admins (handled by before()) may adjust inventory. */
    public function adjustStock(User $user, Product $product): bool
    {
        return false;
    }
}
