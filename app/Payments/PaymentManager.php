<?php

namespace App\Payments;

use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\Exceptions\GatewayNotAvailableException;
use App\Payments\Gateways\MockGateway;

/**
 * Resolves the active payment gateway from configuration.
 *
 * ADR-007 Phase 1 — single resolution point for all payment gateway access.
 *
 * Usage:
 *   $gateway = app(PaymentManager::class)->gateway();
 *   $name    = $gateway->name();     // e.g. 'mock_gateway'
 *   $enabled = app(PaymentManager::class)->isEnabled();
 *
 * The active gateway is determined by:
 *   PAYMENT_GATEWAY=mock        → MockGateway  (Phase 1 default)
 *   PAYMENT_GATEWAY=lahza       → LahzaGateway (Phase 5)
 *   PAYMENT_GATEWAY=stripe      → StripeGateway (Phase 5)
 *
 * To register a new gateway, add it to config/payment.php `gateways` map.
 */
class PaymentManager
{
    /**
     * Resolve and return the currently configured payment gateway instance.
     *
     * The gateway class is resolved through the Laravel service container,
     * so constructor dependencies (HTTP client, config, etc.) are injected
     * automatically for real gateway implementations.
     *
     * @return \App\Payments\Contracts\PaymentGatewayInterface
     * @throws \App\Payments\Exceptions\GatewayNotAvailableException
     *         If the configured gateway key maps to a non-existent class.
     */
    public function gateway(): PaymentGatewayInterface
    {
        $key   = config('payment.default_gateway', 'mock');
        $map   = config('payment.gateways', []);
        $class = $map[$key] ?? MockGateway::class;

        if (!class_exists($class)) {
            throw new GatewayNotAvailableException(
                "Payment gateway class [{$class}] configured for key [{$key}] does not exist. " .
                'Check PAYMENT_GATEWAY in your .env and the gateways map in config/payment.php.'
            );
        }

        return app($class);
    }

    /**
     * Whether the payment gateway is enabled for public-facing checkout flows.
     *
     * When this returns false:
     *  - CheckoutController::process()        → returns 503 JSON / redirect with error
     *  - InvoiceCheckoutController::process() → redirects with error message
     *
     * When this returns false (does NOT block):
     *  - Admin bulk-mark-paid via InvoiceController::bulkAction() — admin bypass
     *  - DomainRenewalService auto-renew for mock_gateway domains — internal job bypass
     *
     * Controlled by PAYMENT_GATEWAY_ENABLED in .env.
     * Default: true (enabled) — preserves current behavior when flag not set.
     *
     * Set PAYMENT_GATEWAY_ENABLED=false in production until Phase 5 is complete.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool) config('payment.enabled', true);
    }
}
