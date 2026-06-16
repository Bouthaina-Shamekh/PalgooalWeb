<?php

namespace App\Payments\DTOs;

/**
 * Represents a verified, parsed webhook event from a payment gateway.
 *
 * Returned by PaymentGatewayInterface::verifyWebhook() AFTER signature
 * verification. If signature verification fails, verifyWebhook() throws
 * WebhookVerificationException instead of returning this DTO.
 */
class WebhookEvent
{
    /** Gateway event type constants — normalize across provider-specific names. */
    public const TYPE_PAYMENT_SUCCEEDED = 'payment.succeeded';
    public const TYPE_PAYMENT_FAILED    = 'payment.failed';
    public const TYPE_REFUND_ISSUED     = 'refund.issued';
    public const TYPE_UNKNOWN           = 'unknown';

    public function __construct(
        /** Normalized event type — one of the TYPE_* constants above. */
        public readonly string $type,

        /** The session ID this event relates to (maps to payment_attempts.gateway_session_id). */
        public readonly string $sessionId,

        /** Gateway-assigned transaction ID for the completed payment (if any). */
        public readonly ?string $transactionId,

        /** Amount confirmed by the gateway in the smallest currency unit (cents). */
        public readonly ?int $amountCents,

        /** ISO 4217 currency code (e.g., 'USD'). */
        public readonly ?string $currency,

        /** Full raw payload from the gateway, preserved for audit/debugging. */
        public readonly array $raw = [],
    ) {}

    public function isPaymentSucceeded(): bool
    {
        return $this->type === self::TYPE_PAYMENT_SUCCEEDED;
    }

    public function isPaymentFailed(): bool
    {
        return $this->type === self::TYPE_PAYMENT_FAILED;
    }
}
