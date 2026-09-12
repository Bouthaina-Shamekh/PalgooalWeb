<?php

namespace App\WhatsApp;

use App\WhatsApp\Contracts\WhatsAppGatewayInterface;
use App\WhatsApp\Exceptions\WhatsAppConfigurationException;

/**
 * Resolves the active WhatsApp provider.
 *
 * Resolution is config-only (config('whatsapp.default_provider') +
 * config('whatsapp.providers') map) -- no database-backed resolution, no
 * delivery-audit persistence. Both are explicitly out of scope for this
 * provider-architecture-foundation phase.
 *
 * Usage:
 *   $gateway = app(WhatsAppManager::class)->gateway();
 *
 * DELIBERATE DIVERGENCE FROM PaymentManager: PaymentManager's config
 * fallback (`config('payment.default_gateway', 'mock')`) silently resolves
 * to MockGateway whenever nothing is configured -- acceptable there because
 * MockGateway itself refuses to fake any real payment action (it throws for
 * everything except name()). WhatsAppManager does NOT copy that silent
 * default: MockWhatsAppGateway DOES fake a successful send (by design, for
 * testability -- see its docblock), so if this manager silently defaulted
 * to it the same way, a production environment with a missing
 * WHATSAPP_PROVIDER setting would appear to have sent a real WhatsApp
 * message when nothing was actually sent anywhere. Instead:
 *   - config('whatsapp.default_provider') has NO implicit default value.
 *   - An unset/empty provider throws WhatsAppConfigurationException.
 *   - "mock" is only ever resolved when a caller has EXPLICITLY set
 *     WHATSAPP_PROVIDER=mock (e.g. local development or a test's own
 *     config() override) -- never as an unconfigured fallback.
 *
 * @see \App\WhatsApp\Contracts\WhatsAppGatewayInterface
 */
class WhatsAppManager
{
    /**
     * Resolve and return the currently configured (default) WhatsApp
     * provider instance.
     *
     * @throws WhatsAppConfigurationException when no provider is configured,
     *         or the configured provider key has no valid mapped class.
     */
    public function gateway(): WhatsAppGatewayInterface
    {
        return $this->gatewayFor($this->defaultProviderKey());
    }

    /**
     * The currently configured default provider KEY (e.g. "mock", "meta") --
     * the same config('whatsapp.providers') map key gateway() resolves via
     * gatewayFor(). Exposed directly so a caller that needs to FREEZE which
     * provider it used (not just hold a gateway instance) can store the one
     * string that is actually valid to pass back into gatewayFor() later.
     *
     * Deliberately NOT the same value as WhatsAppGatewayInterface::name()
     * (a gateway's own canonical identifier, e.g. "mock_whatsapp"): the two
     * can differ, and only the config key is a valid gatewayFor() argument.
     * InvoiceWhatsAppDeliveryService freezes THIS value, not ->name(), onto
     * InvoiceWhatsAppDeliveryAttempt::$provider for exactly this reason.
     *
     * @throws WhatsAppConfigurationException when no provider is configured.
     */
    public function defaultProviderKey(): string
    {
        $key = config('whatsapp.default_provider');

        if (!is_string($key) || trim($key) === '') {
            throw new WhatsAppConfigurationException(
                'No WhatsApp provider is configured. Set WHATSAPP_PROVIDER in .env ' .
                '(e.g. "mock" for local development/testing) and ensure it is mapped ' .
                'in config/whatsapp.php. WhatsApp sending fails closed rather than ' .
                'silently falling back to a mock provider.',
            );
        }

        return $key;
    }

    /**
     * Resolve a SPECIFIC, named WhatsApp provider instance, regardless of
     * whatever is currently configured as the default.
     *
     * Added for InvoiceWhatsAppDeliveryService: the provider that actually
     * handles a given delivery attempt is frozen onto that attempt row at
     * claim time (InvoiceWhatsAppDeliveryAttempt::$provider) and MUST stay
     * authoritative for the entire lifetime of that attempt, even if
     * config('whatsapp.default_provider') changes between claim and
     * execute -- an attempt must never silently switch providers mid-flight.
     * gateway() and gatewayFor() therefore share this same lookup/validation
     * logic; gateway() is just gatewayFor(the current default).
     *
     * @throws WhatsAppConfigurationException when $providerKey has no valid
     *         mapped class in config('whatsapp.providers').
     */
    public function gatewayFor(string $providerKey): WhatsAppGatewayInterface
    {
        $map = config('whatsapp.providers', []);
        $class = $map[$providerKey] ?? null;

        if ($class === null || !class_exists($class)) {
            throw new WhatsAppConfigurationException(
                "WhatsApp provider class for key [{$providerKey}] does not exist or is not mapped " .
                'in config/whatsapp.php. Check WHATSAPP_PROVIDER in your .env and the ' .
                "providers map in config/whatsapp.php.",
            );
        }

        return app($class);
    }
}
