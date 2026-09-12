<?php

namespace App\WhatsApp\DTOs;

/**
 * Represents the outcome of a WhatsAppGatewayInterface::sendDocument() call.
 *
 * Mirrors the shape of App\Payments\DTOs\TransactionStatus /
 * App\Payments\DTOs\RefundResult: a small, provider-neutral result plus a
 * `raw` array preserved for audit/debugging. Deliberately minimal --
 * delivery receipts and webhook-driven status updates (sent -> delivered ->
 * read, or failure callbacks) are NOT modeled here; this only represents
 * the immediate, synchronous outcome of the send API call itself.
 */
class WhatsAppSendResult
{
    public const STATUS_SENT   = 'sent';
    public const STATUS_FAILED = 'failed';

    public function __construct(
        /** Whether the provider accepted the send request. */
        public readonly bool $success,

        /** Provider-assigned message identifier, or null if unavailable/failed. */
        public readonly ?string $providerMessageId,

        /** Provider-neutral status string -- one of the STATUS_* constants above, or a provider-specific value if neither fits. */
        public readonly ?string $providerStatus,

        /** Full raw response/context from the provider call, preserved for audit. */
        public readonly array $raw = [],
    ) {}

    public function isSuccess(): bool
    {
        return $this->success;
    }
}
