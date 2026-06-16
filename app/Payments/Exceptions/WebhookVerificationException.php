<?php

namespace App\Payments\Exceptions;

/**
 * Thrown when an inbound webhook payload fails signature verification.
 *
 * The webhook handler MUST catch this exception and return HTTP 401.
 * Do NOT return HTTP 500 — that would trigger gateway retry on a fraudulent request.
 */
class WebhookVerificationException extends PaymentException {}
