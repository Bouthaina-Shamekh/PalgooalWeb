<?php

namespace App\Payments;

use App\Models\PaymentGateway as PaymentGatewayModel;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\Exceptions\GatewayNotAvailableException;
use App\Payments\Gateways\MockGateway;

/**
 * Resolves the active payment gateway.
 *
 * ADR-007 Phase 1  — initial config-based resolution.
 * ADR-007 Phase 5A — DB-first resolution with config fallback.
 *
 * Resolution order:
 *   1. payment_gateways table — active row (is_active = true)
 *   2. config('payment.default_gateway') — .env / config fallback
 *   3. MockGateway — absolute last resort
 *
 * Usage (unchanged for all callers):
 *   $gateway = app(PaymentManager::class)->gateway();
 *   $enabled = app(PaymentManager::class)->isEnabled();
 *
 * The resolved gateway instance is bound in the container, allowing
 * LahzaGateway (Phase 5B) to receive injected config from its constructor.
 *
 * @see \App\Models\PaymentGateway
 * @see \App\Payments\Contracts\PaymentGatewayInterface
 */
class PaymentManager
{
    /**
     * Resolve and return the currently active payment gateway instance.
     *
     * Phase 5A adds DB-first lookup:
     *   - Reads the active PaymentGateway row from the database.
     *   - Passes the row to the gateway class constructor via the container.
     *   - Falls back to config-based resolution if no DB row exists.
     *
     * @return \App\Payments\Contracts\PaymentGatewayInterface
     * @throws \App\Payments\Exceptions\GatewayNotAvailableException
     */
    public function gateway(): PaymentGatewayInterface
    {
        // --- Step 1: Try DB-configured gateway ---
        $dbGateway = $this->resolveFromDatabase();
        if ($dbGateway !== null) {
            return $dbGateway;
        }

        // --- Step 2: Fall back to config / .env ---
        return $this->resolveFromConfig();
    }

    /**
     * Whether the payment gateway is enabled for public-facing checkout.
     *
     * Phase 5A: reads is_active from the DB row first.
     * If no active DB row exists, falls back to PAYMENT_GATEWAY_ENABLED in .env.
     *
     * When false (blocks):
     *   - CheckoutController::process()
     *   - InvoiceCheckoutController::process()
     *
     * When false (does NOT block):
     *   - Admin bulk-mark-paid (admin bypass)
     *   - DomainRenewalService auto-renew (internal job bypass)
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        // If DB has an active gateway row, treat it as enabled.
        try {
            if (PaymentGatewayModel::where('is_active', true)->exists()) {
                return true;
            }
        } catch (\Throwable) {
            // Table may not exist yet (before migration) — fall through.
        }

        // Fall back to .env flag.
        return (bool) config('payment.enabled', true);
    }

    /**
     * Return the active PaymentGateway model row, or null if not available.
     * Callers (e.g. PaymentGatewayController, LahzaGateway) can use this
     * to retrieve decrypted API keys without going through gateway().
     */
    public function activeRow(): ?PaymentGatewayModel
    {
        try {
            return PaymentGatewayModel::active()->first();
        } catch (\Throwable) {
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // Private resolution helpers
    // -------------------------------------------------------------------------

    /**
     * Try to resolve the gateway from the payment_gateways table.
     *
     * The gateway class is instantiated via the service container so that
     * LahzaGateway can receive the PaymentGateway model in its constructor:
     *
     *   class LahzaGateway {
     *       public function __construct(PaymentGatewayModel $config) { ... }
     *   }
     *
     * Returns null if:
     *  - The table does not exist (before migrate)
     *  - No row has is_active = true
     *  - The driver key is not in the gateways map
     */
    private function resolveFromDatabase(): ?PaymentGatewayInterface
    {
        try {
            $row = PaymentGatewayModel::active()->first();
        } catch (\Throwable) {
            // Table may not exist yet.
            return null;
        }

        if ($row === null) {
            return null;
        }

        $map   = config('payment.gateways', []);
        $class = $map[$row->driver] ?? null;

        if ($class === null || !class_exists($class)) {
            // Driver registered in DB but not yet in config map (e.g. 'lahza' before Phase 5B).
            // Fall through to config fallback rather than crashing.
            return null;
        }

        // Bind the DB row in the container so the gateway class can typehint it.
        app()->instance(PaymentGatewayModel::class, $row);

        return app($class);
    }

    /**
     * Resolve from config('payment.default_gateway') — the pre-Phase-5A path.
     */
    private function resolveFromConfig(): PaymentGatewayInterface
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
}
