<?php

namespace App\Payments\DTOs;

/**
 * Represents a hosted-checkout session created by a payment gateway.
 *
 * Returned by PaymentGatewayInterface::createSession().
 * The client browser is redirected to `checkoutUrl` to complete payment.
 */
class PaymentSession
{
    public function __construct(
        /** Gateway-assigned session identifier. Stored in payment_attempts.gateway_session_id. */
        public readonly string $sessionId,

        /** URL to redirect the client browser to begin hosted checkout. */
        public readonly string $checkoutUrl,
    ) {}
}
