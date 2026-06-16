<?php

namespace App\Payments\Exceptions;

/**
 * Thrown when the configured payment gateway class cannot be resolved.
 *
 * Indicates a misconfiguration in PAYMENT_GATEWAY or config/payment.php
 * rather than a transient gateway error.
 */
class GatewayNotAvailableException extends PaymentException {}
