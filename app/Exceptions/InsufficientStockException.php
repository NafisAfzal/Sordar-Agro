<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public static function forItem(?string $productName, ?string $variantLabel): self
    {
        $item = $productName
            ? $productName.($variantLabel ? " ({$variantLabel})" : '')
            : 'one of the selected items';

        return new self("Not enough stock for {$item}.");
    }
}
