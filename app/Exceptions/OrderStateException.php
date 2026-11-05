<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when an order state transition is attempted from a state that does
 * not allow it (cancelled order, already-reserved payment, etc.).
 */
class OrderStateException extends RuntimeException
{
    public const CANCELLED = 'cancelled';

    public const ALREADY_PROCESSED = 'already_processed';

    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }

    public static function cancelled(): self
    {
        return new self(self::CANCELLED);
    }

    public static function alreadyProcessed(): self
    {
        return new self(self::ALREADY_PROCESSED);
    }
}
