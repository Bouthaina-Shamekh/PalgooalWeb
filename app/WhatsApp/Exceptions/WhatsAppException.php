<?php

namespace App\WhatsApp\Exceptions;

/**
 * Base exception for all WhatsApp-transport-layer errors.
 *
 * Mirrors App\Payments\Exceptions\PaymentException: concrete gateway
 * implementations should generally throw a more specific subclass
 * (WhatsAppConfigurationException, WhatsAppSendException) so callers can
 * catch at the appropriate granularity, but -- matching the same precedent
 * set by PaymentException being thrown directly for MockGateway's
 * not-implemented cases -- this base class may also be thrown directly for
 * a generic WhatsApp-domain invariant violation that doesn't fit either
 * subclass (e.g. an invalid WhatsAppDocumentMessage constructed with an
 * empty filename or empty contents -- not a configuration problem, and not
 * a failure that happened while actually talking to a gateway).
 *
 * Deliberately NOT a parent of InvalidWhatsAppPhoneException
 * (App\WhatsApp\Exceptions\InvalidWhatsAppPhoneException, introduced in the
 * phone-normalization phase): that exception already has an established,
 * tested, independent \RuntimeException-based identity and is reused as-is
 * here, unchanged.
 */
class WhatsAppException extends \RuntimeException {}
