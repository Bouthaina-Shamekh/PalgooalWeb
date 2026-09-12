<?php

namespace App\WhatsApp\Exceptions;

/**
 * Thrown when the WhatsApp provider cannot be resolved: no provider is
 * configured at all, or the configured provider key has no mapped/existing
 * class in config('whatsapp.providers').
 *
 * Mirrors App\Payments\Exceptions\GatewayNotAvailableException, with one
 * deliberate difference in how WhatsAppManager uses it -- see
 * WhatsAppManager's class docblock: unlike PaymentManager (which falls back
 * to MockGateway when nothing is configured), WhatsAppManager throws this
 * exception in that situation instead of silently defaulting to a mock, so
 * a misconfigured production environment fails loudly rather than appearing
 * to have sent a real message.
 */
class WhatsAppConfigurationException extends WhatsAppException {}
