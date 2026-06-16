<?php

namespace App\Payments\DTOs;

/**
 * Represents the current status of a gateway transaction.
 *
 * Returned by PaymentGatewayInterface::getTransaction().
 * Used for manual reconciliation or to verify state after a missed webhook.
 */
class TransactionStatus
{
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_PENDING   = 'pending';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_REFUNDED  = 'refunded';

    public function __construct(
        /** Gateway-assigned transaction identifier. */
        public readonly string $transactionId,

        /** Current status — one of the STATUS_* constants above. */
        public readonly string $status,

        /** Amount in the smallest currency unit (cents), or null if unknown. */
        public readonly ?int $amountCents,

        /** ISO 4217 currency code (e.g., 'USD'), or null if unknown. */
        public readonly ?string $currency,

        /** Full raw response from the gateway API, preserved for audit. */
        public readonly array $raw = [],
    ) {}

    public function isSucceeded(): bool
    {
        return $this->status === self::STATUS_SUCCEEDED;
    }
}
