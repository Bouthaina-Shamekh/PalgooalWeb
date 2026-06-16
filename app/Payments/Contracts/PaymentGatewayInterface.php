<?php

namespace App\Payments\Contracts;

use App\Models\Invoice;
use App\Payments\DTOs\PaymentSession;
use App\Payments\DTOs\RefundResult;
use App\Payments\DTOs\TransactionStatus;
use App\Payments\DTOs\WebhookEvent;

/**
 * Contract that every payment gateway implementation must fulfil.
 *
 * ADR-007 — Payment Gateway Integration (Phase 1: Gateway-Agnostic Architecture)
 *
 * Design principles enforced by this contract:
 *
 * 1. GATEWAY-AGNOSTIC — Callers depend on this interface, never on a concrete class.
 *    Swapping gateways requires zero changes to checkout, settlement, or renewal code.
 *
 * 2. WEBHOOK-FIRST — createSession() returns a URL for a hosted checkout page.
 *    Activation happens ONLY after verifyWebhook() confirms a real payment.
 *    No activation must occur from a client-side redirect alone.
 *
 * 3. IDEMPOTENT — The $idempotencyKey in createSession() prevents duplicate sessions
 *    on browser back / network retry. The webhook handler checks PaymentAttempt.status
 *    before calling InvoiceSettlementService::markPaid().
 *
 * 4. SERVER-SIDE AMOUNT AUTHORITY — The invoice amount is read from the database
 *    inside markPaid(). The gateway-confirmed amount in WebhookEvent is validated
 *    against invoice.total_cents before settlement is allowed.
 *
 * Phase 1 note:
 *   MockGateway provides a full implementation of this interface.
 *   createSession() and verifyWebhook() throw PaymentException (not used in Phase 1).
 *   The settlement flow in Phase 1 remains synchronous via InvoiceSettlementService::markPaid().
 *   Phase 2 introduces the PaymentAttempt model.
 *   Phase 3 wires verifyWebhook() into an actual webhook endpoint.
 *   Phase 4 redirects checkout to createSession() instead of settling synchronously.
 *   Phase 5 replaces MockGateway with a real provider class.
 */
interface PaymentGatewayInterface
{
    /**
     * Returns the canonical gateway identifier stored in the database.
     *
     * This value is written to:
     *  - invoices.payment_method (via InvoiceSettlementService::markPaid())
     *  - domains.payment_method  (via InvoiceSettlementService::syncStandaloneInvoiceDomain())
     *  - payment_attempts.gateway (Phase 2+)
     *
     * Must be a short, lowercase, underscore-separated string.
     * Examples: 'mock_gateway', 'lahza', 'stripe', 'bank_transfer'
     *
     * @return string
     */
    public function name(): string;

    /**
     * Initiate a hosted-checkout session with the payment gateway.
     *
     * The returned PaymentSession contains:
     *  - sessionId:   Stored as payment_attempts.gateway_session_id (Phase 2+).
     *                 The webhook event references this ID so we can match it
     *                 to the correct invoice without trusting any client parameter.
     *  - checkoutUrl: Redirect the client browser here to enter card details.
     *                 Card data never passes through this application.
     *
     * The $idempotencyKey must be a UUID generated at Order creation time and
     * stored in payment_attempts.idempotency_key. Passing the same key twice
     * must return the same session (not create a duplicate charge).
     *
     * @param  \App\Models\Invoice  $invoice         Invoice to be paid.
     * @param  string               $idempotencyKey  UUID — prevents duplicate sessions.
     * @param  string               $returnUrl       Redirect here after payment (informational only — do NOT activate from here).
     * @param  string               $cancelUrl       Redirect here if client cancels.
     * @return \App\Payments\DTOs\PaymentSession
     * @throws \App\Payments\Exceptions\PaymentException on gateway API error.
     */
    public function createSession(
        Invoice $invoice,
        string $idempotencyKey,
        string $returnUrl,
        string $cancelUrl
    ): PaymentSession;

    /**
     * Verify an inbound webhook request and return a structured event.
     *
     * Security invariant: this method MUST verify the HMAC signature of the
     * raw request payload before parsing any data. If verification fails,
     * throw WebhookVerificationException. The caller (webhook controller)
     * must return HTTP 401 in response — NOT HTTP 500 (which triggers retry).
     *
     * The returned WebhookEvent is normalized across provider-specific field
     * names into a common DTO so the webhook controller is gateway-agnostic.
     *
     * Processing rules for the webhook controller:
     *  1. Call verifyWebhook() — throws on invalid signature.
     *  2. Check event->isPaymentSucceeded() — ignore all other event types (return 200).
     *  3. Lookup PaymentAttempt by event->sessionId.
     *  4. Validate event->amountCents === invoice->total_cents (throw on mismatch).
     *  5. Check PaymentAttempt.status !== 'succeeded' (idempotency guard).
     *  6. Call InvoiceSettlementService::markPaid($invoice, $this->name()).
     *
     * @param  string  $rawPayload        Raw request body bytes (do NOT JSON-decode before passing).
     * @param  string  $signatureHeader   Full value of the gateway's signature header.
     * @return \App\Payments\DTOs\WebhookEvent
     * @throws \App\Payments\Exceptions\WebhookVerificationException on invalid signature.
     * @throws \App\Payments\Exceptions\PaymentException on parsing error.
     */
    public function verifyWebhook(string $rawPayload, string $signatureHeader): WebhookEvent;

    /**
     * Fetch the current status of a transaction by its gateway-assigned ID.
     *
     * Used for:
     *  - Manual reconciliation when a webhook was not delivered.
     *  - Admin "check payment status" action in the invoice UI.
     *  - Automated recovery job that polls for transactions with missing webhooks.
     *
     * @param  string  $gatewayTransactionId  Stored in payment_attempts.gateway_transaction_id (Phase 2+).
     * @return \App\Payments\DTOs\TransactionStatus
     * @throws \App\Payments\Exceptions\PaymentException on gateway API error.
     */
    public function getTransaction(string $gatewayTransactionId): TransactionStatus;

    /**
     * Issue a full or partial refund for a completed transaction.
     *
     * The amount must not exceed the original transaction amount.
     * Partial refunds should accumulate in payment_attempts.refund_amount_cents (Phase 2+).
     *
     * @param  string  $gatewayTransactionId  The original transaction to refund.
     * @param  int     $amountCents           Amount to refund in the invoice's currency (USD cents).
     * @return \App\Payments\DTOs\RefundResult
     * @throws \App\Payments\Exceptions\PaymentException on gateway API error or invalid amount.
     */
    public function refund(string $gatewayTransactionId, int $amountCents): RefundResult;
}
