<?php

namespace App\Payments\Gateways;

use App\Models\Invoice;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\DTOs\PaymentSession;
use App\Payments\DTOs\RefundResult;
use App\Payments\DTOs\TransactionStatus;
use App\Payments\DTOs\WebhookEvent;
use App\Payments\Exceptions\PaymentException;

/**
 * Mock payment gateway — replaces all hardcoded 'mock_gateway' strings.
 *
 * ADR-007 Phase 1: This class is the single source of truth for the mock
 * gateway name. Previously, 'mock_gateway' was hardcoded in 5 locations.
 * Now every caller resolves the name via app(PaymentManager::class)->gateway()->name().
 *
 * MockGateway is a legacy settlement name provider only in Phase 1.
 * It must not fake successful transactions or refunds.
 *
 * Behaviour:
 *  - name()           → 'mock_gateway'  (the DB-stored identifier — only method used in Phase 1)
 *  - createSession()  → throws PaymentException (hosted checkout not in Phase 1)
 *  - verifyWebhook()  → throws PaymentException (webhook infrastructure not in Phase 1)
 *  - getTransaction() → throws PaymentException (no real transaction to look up)
 *  - refund()         → throws PaymentException (no real payment to refund)
 *
 * Settlement in Phase 1 remains synchronous:
 *   InvoiceSettlementService::markPaid($invoice, $gateway->name())
 *
 * Phase 5 note:
 *   When a real gateway is configured (PAYMENT_GATEWAY=lahza), PaymentManager::gateway()
 *   returns the LahzaGateway instead of MockGateway. MockGateway is never instantiated
 *   in production once that flag is set.
 */
class MockGateway implements PaymentGatewayInterface
{
    /**
     * The canonical name stored in the database.
     *
     * Used as a constant in DomainRenewalService to compare against the
     * `payment_method` column value stored historically in the DB.
     * Do not change this value — it would break the comparison against
     * existing rows in `domains` and `invoices` tables.
     */
    public const GATEWAY_NAME = 'mock_gateway';

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return self::GATEWAY_NAME;
    }

    /**
     * Not implemented in Phase 1.
     *
     * MockGateway settles synchronously via InvoiceSettlementService::markPaid().
     * Hosted checkout session creation is introduced in ADR-007 Phase 4.
     *
     * @throws \App\Payments\Exceptions\PaymentException Always.
     */
    public function createSession(
        Invoice $invoice,
        string $idempotencyKey,
        string $returnUrl,
        string $cancelUrl
    ): PaymentSession {
        throw new PaymentException(
            'MockGateway does not support createSession(). ' .
            'Phase 1 checkout settles synchronously. ' .
            'Hosted checkout is introduced in ADR-007 Phase 4.'
        );
    }

    /**
     * Not implemented in Phase 1.
     *
     * Webhook infrastructure is introduced in ADR-007 Phase 3.
     * MockGateway never receives external webhooks.
     *
     * @throws \App\Payments\Exceptions\PaymentException Always.
     */
    public function verifyWebhook(string $rawPayload, string $signatureHeader): WebhookEvent
    {
        throw new PaymentException(
            'MockGateway does not receive webhooks. ' .
            'Webhook infrastructure is introduced in ADR-007 Phase 3.'
        );
    }

    /**
     * Not implemented in Phase 1.
     *
     * MockGateway is a legacy settlement name provider only.
     * It must not fake successful transactions or refunds.
     * Transaction lookup requires a real gateway with an external API (Phase 5).
     *
     * @throws \App\Payments\Exceptions\PaymentException Always.
     */
    public function getTransaction(string $gatewayTransactionId): TransactionStatus
    {
        throw new PaymentException(
            'MockGateway does not support transaction lookup in Phase 1. ' .
            'Transaction lookup requires a real gateway implementation (ADR-007 Phase 5).'
        );
    }

    /**
     * Not implemented in Phase 1.
     *
     * MockGateway is a legacy settlement name provider only.
     * It must not fake successful transactions or refunds.
     * Refunds require a real gateway with an external API (Phase 5).
     *
     * @throws \App\Payments\Exceptions\PaymentException Always.
     */
    public function refund(string $gatewayTransactionId, int $amountCents): RefundResult
    {
        throw new PaymentException(
            'MockGateway does not support refunds in Phase 1. ' .
            'Refund processing requires a real gateway implementation (ADR-007 Phase 5).'
        );
    }
}
