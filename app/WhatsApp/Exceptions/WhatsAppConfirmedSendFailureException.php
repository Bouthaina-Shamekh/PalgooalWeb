<?php

namespace App\WhatsApp\Exceptions;

/**
 * Thrown when a WhatsApp send attempt is CONFIRMED to have failed: the
 * provider actively rejected the request (a non-2xx HTTP response), so
 * there is positive proof nothing was accepted for delivery.
 *
 * Mirrors the role App\Payments\Exceptions\ConfirmedPreSessionFailureException
 * plays for PaymentSessionStarter: the type-based signal a future
 * orchestration service uses to know it is safe to release a claim and
 * allow a fresh attempt, without needing to parse exception messages.
 *
 * Carries an optional, provider-neutral providerErrorCode -- populated by
 * MetaCloudApiGateway from Meta's structured error.code / error.error_subcode
 * fields when present (normalized as "{code}" or "{code}:{error_subcode}").
 * This class itself has no Meta-specific shape and no business
 * interpretation baked in (e.g. it does NOT decide that a given code means
 * "the 24-hour conversation window is closed") -- that mapping belongs to
 * future orchestration/domain logic, not this exception.
 */
class WhatsAppConfirmedSendFailureException extends WhatsAppSendException
{
    private readonly ?string $providerErrorCode;

    public function __construct(
        string $message,
        ?string $providerErrorCode = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);

        $this->providerErrorCode = $providerErrorCode;
    }

    /**
     * Provider-neutral normalized error code, or null if the provider
     * response did not include one that could be safely parsed.
     */
    public function providerErrorCode(): ?string
    {
        return $this->providerErrorCode;
    }
}
