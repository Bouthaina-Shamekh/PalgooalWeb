<?php

namespace App\WhatsApp\Contracts;

use App\WhatsApp\DTOs\WhatsAppDocumentMessage;
use App\WhatsApp\DTOs\WhatsAppSendResult;

/**
 * Contract that every WhatsApp provider adapter must fulfil.
 *
 * Mirrors App\Payments\Contracts\PaymentGatewayInterface's design
 * principles:
 *
 * 1. PROVIDER-AGNOSTIC — callers depend on this interface, never on a
 *    concrete class. Swapping providers (e.g. adding a real Meta Cloud API
 *    adapter later) requires zero changes to any future invoice-sending
 *    code.
 *
 * 2. NO PROVIDER-SPECIFIC CONCEPTS LEAK INTO THE CONTRACT — no
 *    phone_number_id, Graph API URL/shape, media_id, access_token, or
 *    template ID appears here or in WhatsAppDocumentMessage /
 *    WhatsAppSendResult. A concrete adapter translates to/from those
 *    concepts internally.
 *
 * Phase note (WhatsApp provider-architecture foundation phase): only
 * MockWhatsAppGateway implements this interface so far. No Meta Cloud API
 * adapter, real credentials, or invoice-sending wiring exists yet.
 */
interface WhatsAppGatewayInterface
{
    /**
     * Returns the canonical provider identifier (short, lowercase,
     * underscore-separated, e.g. "mock_whatsapp"). Mirrors
     * PaymentGatewayInterface::name()'s role as the stable, storable
     * identifier for this provider -- not currently persisted anywhere
     * (no delivery-audit schema exists yet), but kept consistent with that
     * convention for when it is.
     */
    public function name(): string;

    /**
     * Send one document message to one recipient.
     *
     * @throws \App\WhatsApp\Exceptions\WhatsAppSendException when the provider call itself fails.
     */
    public function sendDocument(WhatsAppDocumentMessage $message): WhatsAppSendResult;
}
