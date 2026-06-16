<?php

namespace App\Payments\DTOs;

/**
 * Represents the outcome of a refund request sent to a payment gateway.
 *
 * Returned by PaymentGatewayInterface::refund().
 */
class RefundResult
{
    public function __construct(
        /** Gateway-assigned identifier for this refund. */
        public readonly string $refundId,

        /** Amount actually refunded in the smallest currency unit (cents). */
        public readonly int $refundedCents,

        /** Refund status: 'succeeded', 'pending', or 'failed'. */
        public readonly string $status,

        /** Full raw response from the gateway API, preserved for audit. */
        public readonly array $raw = [],
    ) {}

    public function isSucceeded(): bool
    {
        return $this->status === 'succeeded';
    }
}
