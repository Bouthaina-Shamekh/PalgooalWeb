<?php

namespace App\Payments\Exceptions;

/**
 * Base exception for all payment-layer errors.
 *
 * Concrete gateway implementations should throw subclasses of this exception
 * rather than this class directly, so callers can catch at the appropriate
 * granularity.
 */
class PaymentException extends \RuntimeException {}
