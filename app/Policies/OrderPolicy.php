<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /** Admins may view and transition every order. */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    /** Customers may only read their own orders. */
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id;
    }

    /** Only admins (handled by before()) may change fulfilment state. */
    public function update(User $user, Order $order): bool
    {
        return false;
    }

    /** Only admins (handled by before()) may verify payments. */
    public function verifyPayment(User $user, Order $order): bool
    {
        return false;
    }

    /** Only admins (handled by before()) may reject payments. */
    public function rejectPayment(User $user, Order $order): bool
    {
        return false;
    }
}
