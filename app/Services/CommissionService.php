<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;

/**
 * Single source of truth for marketplace profit-share arithmetic.
 *
 * Share amounts are always taken from the values snapshotted on the order
 * line, never from a product's current profit_share_amount, so historical
 * sales remain financially accurate after a seller edits their product.
 */
class CommissionService
{
    /** Marketplace share charged per unit of a product. */
    public function marketplaceShareForProduct(Product $product): float
    {
        return (float) $product->profit_share_amount;
    }

    /** Gross value of an order line (unit price x quantity). */
    public function lineGross(OrderItem $item): float
    {
        return $item->lineTotal();
    }

    /** Marketplace share earned from an order line. */
    public function lineShare(OrderItem $item): float
    {
        return $item->marketplaceShareTotal();
    }

    /** Seller revenue after the marketplace share has been deducted. */
    public function sellerEarnings(float $gross, float $share): float
    {
        return $gross - $share;
    }
}
