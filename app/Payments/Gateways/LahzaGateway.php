<?php

namespace App\Payments\Gateways;

use App\Models\Invoice;
use App\Models\PaymentGateway;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\DTOs\PaymentSession;
use App\Payments\DTOs\RefundResult;
use App\Payments\DTOs\TransactionStatus;
use App\Payments\DTOs\WebhookEvent;
use App\Payments\Exceptions\PaymentException;
use App\Payments\Exceptions\WebhookVerificationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Lahza payment gateway implementation.
 *
 * ADR-007 Phase 5B — Real gateway implementation replacing MockGateway.
 *
 * Lahza is a Palestinian payment gateway operated by Bank of Palestine.
 * Supported currencies: ILS, JOD, USD.
 * Checkout model: hosted redirect (client is redirected to Lahza's hosted page).
 * Webhook signature: HMAC-SHA512 via x-lahza-signature header.
 *
 * Configuration is read from the `payment_gateways` DB row (driver = 'lahza'),
 * injected by PaymentManager via the service container — keys are decrypted
 * transparently by Eloquent's `encrypted` cast on PaymentGateway model.
 *
 * Sandbox/Live: Lahza uses the SAME base URL for both environments.
 * The mode is determined by which set of keys is configured (test vs live keys).
 * Test keys begin with `sk_test_` / `pk_test_`, live keys with `sk_live_` / `pk_live_`.
 * The `$config->mode` value is used for logging and safety checks only.
 *
 * API base URL: https://api.lahza.io
 * Endpoints used:
 *   POST /transaction/initialize — create checkout session
 *   GET  /transaction/verify/{reference} — verify transaction by reference
 *   POST /refund — issue refund
 *
 * @see \App\Models\PaymentGateway
 * @see \App\Payments\Contracts\PaymentGatewayInterface
 * @see docs/ADR_007_PHASE5B_LAHZA_GATEWAY_REPORT.md
 */
class LahzaGateway implements PaymentGatewayInterface
{
    /**
     * The canonical gateway identifier. Stored in:
     *   - invoices.payment_method
     *   - payment_attempts.gateway
     *   - payment_gateways.driver
     */
    public const GATEWAY_NAME = 'lahza';

    /**
     * Lahza API base URL.
     * Same URL for both sandbox and live — key prefix determines environment.
     * Can be overridden via payment_gateways.settings['base_url'] if needed.
     */
    private const DEFAULT_BASE_URL = 'https://api.lahza.io';

    /**
     * HTTP request timeout in seconds.
     */
    private const TIMEOUT_SECONDS = 30;

    /**
     * @param  PaymentGateway  $config
     *   Injected by PaymentManager::resolveFromDatabase() via the container:
     *     app()->instance(PaymentGateway::class, $row);
     *     return app(LahzaGateway::class);
     *   Keys are decrypted automatically when accessed ($config->secret_key).
     */
    public function __construct(private readonly PaymentGateway $config) {}

    // =========================================================================
    // PaymentGatewayInterface — name()
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return self::GATEWAY_NAME;
    }

    // =========================================================================
    // PaymentGatewayInterface — createSession()
    // =========================================================================

    /**
     * Create a hosted checkout session with Lahza.
     *
     * Sends a POST to /transaction/initialize with:
     *   - email        — from invoice->client->email
     *   - amount       — from invoice->total_cents (authoritative, never from frontend)
     *   - currency     — from invoice->currency
     *   - reference    — the idempotency key (UUID stored in payment_attempts.idempotency_key)
     *   - callback_url — our webhook endpoint (/payment/webhook/lahza)
     *   - return_url   — where Lahza redirects after payment (informational only)
     *   - cancel_url   — where Lahza redirects on cancel
     *
     * On success, Lahza returns:
     *   { "status": true, "data": { "authorization_url": "https://...", "reference": "..." } }
     *
     * The reference in Lahza's response may differ from ours — we always use our
     * idempotency_key as the reference for reconciliation.
     *
     * @throws PaymentException if the API call fails or response is malformed.
     */
    public function createSession(
        Invoice $invoice,
        string $idempotencyKey,
        string $returnUrl,
        string $cancelUrl
    ): PaymentSession {
        $secretKey = $this->config->secret_key;

        if (empty($secretKey)) {
            throw new PaymentException(
                'LahzaGateway: secret_key is not configured. ' .
                'Add it via Admin → Settings → بوابات الدفع → Lahza → تعديل.'
            );
        }

        // Build the payload — all values come from the database, not the client.
        $payload = [
            'email'        => $invoice->client?->email ?? '',
            'amount'       => $invoice->total_cents,    // authoritative: DB cents
            'currency'     => strtoupper($invoice->currency ?? 'USD'),
            'reference'    => $idempotencyKey,           // our UUID = Lahza reference
            'callback_url' => route('payment.webhook', ['gateway' => self::GATEWAY_NAME]),
            'return_url'   => $returnUrl,
            'cancel_url'   => $cancelUrl,
            // Optional metadata: helps Lahza dashboard show invoice context
            'metadata'     => [
                'invoice_id' => $invoice->id,
            ],
        ];

        $this->logInfo('createSession: initializing', [
            'invoice_id'     => $invoice->id,
            'reference'      => $idempotencyKey,
            'amount_cents'   => $invoice->total_cents,
            'currency'       => $payload['currency'],
            'mode'           => $this->config->mode,
        ]);

        try {
            $response = Http::withToken($secretKey)
                ->timeout(self::TIMEOUT_SECONDS)
                ->acceptJson()
                ->post($this->baseUrl() . '/transaction/initialize', $payload);
        } catch (ConnectionException $e) {
            throw new PaymentException(
                'LahzaGateway: connection to Lahza API failed: ' . $e->getMessage(),
                0,
                $e
            );
        }

        if ($response->failed()) {
            $this->logError('createSession: API returned error', [
                'status'    => $response->status(),
                'reference' => $idempotencyKey,
            ]);
            throw new PaymentException(
                'LahzaGateway: API returned HTTP ' . $response->status() .
                ' for createSession. Check Lahza credentials and mode setting.'
            );
        }

        $body = $response->json();

        if (! is_array($body) || ($body['status'] ?? false) !== true) {
            $this->logError('createSession: unexpected response body', [
                'reference' => $idempotencyKey,
                'body'      => $body,
            ]);
            throw new PaymentException(
                'LahzaGateway: unexpected response from /transaction/initialize. ' .
                'status=' . ($body['status'] ?? 'missing') . '. ' .
                'message=' . ($body['message'] ?? 'none')
            );
        }

        $data           = $body['data'] ?? [];
        $checkoutUrl    = $data['authorization_url'] ?? null;
        $lahzaReference = $data['reference'] ?? $idempotencyKey;

        if (empty($checkoutUrl)) {
            throw new PaymentException(
                'LahzaGateway: authorization_url missing from /transaction/initialize response.'
            );
        }

        $this->logInfo('createSession: session created', [
            'reference'      => $idempotencyKey,
            'lahza_reference'=> $lahzaReference,
        ]);

        // sessionId = our idempotency_key (stored in payment_attempts.gateway_session_id)
        // This lets verifyWebhook() and getTransaction() match the webhook back to the attempt.
        return new PaymentSession(
            sessionId:   $idempotencyKey,
            checkoutUrl: $checkoutUrl,
        );
    }

    // =========================================================================
    // PaymentGatewayInterface — verifyWebhook()
    // =========================================================================

    /**
     * Verify a Lahza webhook request and return a normalized event.
     *
     * Security invariant:
     *   1. HMAC-SHA512 is computed over the raw payload bytes.
     *   2. hash_equals() prevents timing attacks.
     *   3. JSON is parsed ONLY AFTER signature verification.
     *
     * Lahza sends the signature in the x-lahza-signature header.
     * The webhook_secret in the DB is the key (not the secret key).
     *
     * Lahza event structure (expected):
     * {
     *   "event": "charge.success",
     *   "data": {
     *     "id": "txn_xxxxxxxx",
     *     "reference": "our-idempotency-key",
     *     "amount": 1500,
     *     "currency": "USD",
     *     "status": "success"
     *   }
     * }
     *
     * @throws WebhookVerificationException if HMAC fails.
     * @throws PaymentException if payload cannot be parsed.
     */
    public function verifyWebhook(string $rawPayload, string $signatureHeader): WebhookEvent
    {
        $webhookSecret = $this->config->webhook_secret;

        if (empty($webhookSecret)) {
            throw new WebhookVerificationException(
                'LahzaGateway: webhook_secret is not configured. ' .
                'Set it via Admin → Settings → بوابات الدفع → Lahza → تعديل.'
            );
        }

        // --- Step 1: Verify HMAC-SHA512 BEFORE parsing JSON ---
        $expected = hash_hmac('sha512', $rawPayload, $webhookSecret);

        if (! hash_equals($expected, strtolower($signatureHeader))) {
            $this->logError('verifyWebhook: HMAC mismatch', [
                'header_length'   => strlen($signatureHeader),
                'expected_prefix' => substr($expected, 0, 8) . '...',
            ]);
            throw new WebhookVerificationException(
                'LahzaGateway: webhook signature verification failed. ' .
                'The request may be forged or the webhook_secret is incorrect.'
            );
        }

        // --- Step 2: Parse JSON after successful signature verification ---
        $data = json_decode($rawPayload, true);

        if (! is_array($data)) {
            throw new PaymentException(
                'LahzaGateway: webhook payload is not valid JSON after HMAC verification.'
            );
        }

        // --- Step 3: Normalize to WebhookEvent DTO ---
        $event        = $data['event'] ?? '';
        $txData       = $data['data']  ?? [];
        $transactionId= $txData['id']        ?? null;
        $reference    = $txData['reference'] ?? null;    // our idempotency_key
        $amountRaw    = $txData['amount']    ?? null;    // Lahza sends cents already
        $currency     = $txData['currency']  ?? null;
        $status       = $txData['status']    ?? '';

        // Lahza event type → normalized type
        $normalizedType = match (true) {
            $event === 'charge.success' || $status === 'success' => WebhookEvent::TYPE_PAYMENT_SUCCEEDED,
            $event === 'charge.failed'  || $status === 'failed'  => WebhookEvent::TYPE_PAYMENT_FAILED,
            str_starts_with($event, 'refund')                    => WebhookEvent::TYPE_REFUND_ISSUED,
            default                                               => WebhookEvent::TYPE_UNKNOWN,
        };

        $amountCents = is_numeric($amountRaw) ? (int) $amountRaw : null;

        $this->logInfo('verifyWebhook: verified', [
            'event'          => $event,
            'normalized_type'=> $normalizedType,
            'reference'      => $reference,
            'transaction_id' => $transactionId,
            'amount_cents'   => $amountCents,
        ]);

        // sessionId = reference (our idempotency_key) — used to look up PaymentAttempt
        return new WebhookEvent(
            type:          $normalizedType,
            sessionId:     $reference ?? '',
            transactionId: $transactionId ? (string) $transactionId : null,
            amountCents:   $amountCents,
            currency:      $currency ? strtoupper($currency) : null,
            raw:           $data,
        );
    }

    // =========================================================================
    // PaymentGatewayInterface — getTransaction()
    // =========================================================================

    /**
     * Fetch the current state of a transaction from Lahza.
     *
     * Uses: GET /transaction/verify/{reference}
     *
     * The $gatewayTransactionId passed in is our reference (idempotency_key),
     * which is also what Lahza stores as the `reference` field.
     * Lahza's own `id` (txn_*) is stored separately in payment_attempts.gateway_transaction_id.
     *
     * This method accepts EITHER format and queries by reference.
     *
     * Lahza response:
     * {
     *   "status": true,
     *   "data": {
     *     "id": "txn_xxx",
     *     "reference": "our-uuid",
     *     "amount": 1500,
     *     "currency": "USD",
     *     "status": "success"
     *   }
     * }
     *
     * @throws PaymentException on API error.
     */
    public function getTransaction(string $gatewayTransactionId): TransactionStatus
    {
        $secretKey = $this->config->secret_key;

        if (empty($secretKey)) {
            throw new PaymentException(
                'LahzaGateway: secret_key is not configured for getTransaction().'
            );
        }

        try {
            $response = Http::withToken($secretKey)
                ->timeout(self::TIMEOUT_SECONDS)
                ->acceptJson()
                ->get($this->baseUrl() . '/transaction/verify/' . urlencode($gatewayTransactionId));
        } catch (ConnectionException $e) {
            throw new PaymentException(
                'LahzaGateway: connection failed in getTransaction(): ' . $e->getMessage(),
                0,
                $e
            );
        }

        if ($response->failed()) {
            throw new PaymentException(
                'LahzaGateway: API returned HTTP ' . $response->status() .
                ' for getTransaction(' . $gatewayTransactionId . ').'
            );
        }

        $body = $response->json();

        if (! is_array($body) || ($body['status'] ?? false) !== true) {
            throw new PaymentException(
                'LahzaGateway: unexpected response from /transaction/verify. ' .
                'status=' . ($body['status'] ?? 'missing')
            );
        }

        $txData    = $body['data']      ?? [];
        $lahzaId   = $txData['id']      ?? $gatewayTransactionId;
        $lahzaStatus = $txData['status'] ?? '';
        $amountRaw = $txData['amount']  ?? null;
        $currency  = $txData['currency'] ?? null;

        // Normalize Lahza status → TransactionStatus constant
        $normalizedStatus = match ($lahzaStatus) {
            'success'   => TransactionStatus::STATUS_SUCCEEDED,
            'pending'   => TransactionStatus::STATUS_PENDING,
            'failed'    => TransactionStatus::STATUS_FAILED,
            'refunded'  => TransactionStatus::STATUS_REFUNDED,
            default     => TransactionStatus::STATUS_PENDING,
        };

        return new TransactionStatus(
            transactionId: (string) $lahzaId,
            status:        $normalizedStatus,
            amountCents:   is_numeric($amountRaw) ? (int) $amountRaw : null,
            currency:      $currency ? strtoupper($currency) : null,
            raw:           $body,
        );
    }

    // =========================================================================
    // PaymentGatewayInterface — refund()
    // =========================================================================

    /**
     * Issue a full or partial refund via Lahza.
     *
     * Lahza Refund API (confirmed by account owner to exist):
     *   POST /refund
     *   Body: { "transaction_reference": "txn_xxx", "amount": 1500 }
     *   Auth: Bearer {secret_key}
     *
     * Note on Lahza refund documentation: the official docs page for refunds
     * was observed empty at time of Phase 5B implementation. The endpoint
     * and body structure below are based on the standard Lahza integration
     * pattern and will be updated if the actual response shape differs.
     *
     * Expected success response:
     * {
     *   "status": true,
     *   "data": { "id": "ref_xxx", "status": "success", "amount": 1500 }
     * }
     *
     * @param  string  $gatewayTransactionId  Lahza transaction ID (txn_*) or reference.
     * @param  int     $amountCents           Amount to refund in smallest currency unit.
     * @throws PaymentException on API error, invalid amount, or malformed response.
     */
    public function refund(string $gatewayTransactionId, int $amountCents): RefundResult
    {
        if ($amountCents <= 0) {
            throw new PaymentException(
                'LahzaGateway: refund amount must be greater than zero. Got: ' . $amountCents
            );
        }

        $secretKey = $this->config->secret_key;

        if (empty($secretKey)) {
            throw new PaymentException(
                'LahzaGateway: secret_key is not configured for refund().'
            );
        }

        $payload = [
            'transaction_reference' => $gatewayTransactionId,
            'amount'                => $amountCents,
        ];

        $this->logInfo('refund: requesting', [
            'transaction_reference' => $gatewayTransactionId,
            'amount_cents'          => $amountCents,
            'mode'                  => $this->config->mode,
        ]);

        try {
            $response = Http::withToken($secretKey)
                ->timeout(self::TIMEOUT_SECONDS)
                ->acceptJson()
                ->post($this->baseUrl() . '/refund', $payload);
        } catch (ConnectionException $e) {
            throw new PaymentException(
                'LahzaGateway: connection failed in refund(): ' . $e->getMessage(),
                0,
                $e
            );
        }

        if ($response->failed()) {
            $this->logError('refund: API returned error', [
                'status'                => $response->status(),
                'transaction_reference' => $gatewayTransactionId,
                'body'                  => $response->body(),
            ]);
            throw new PaymentException(
                'LahzaGateway: refund API returned HTTP ' . $response->status() .
                ' for transaction ' . $gatewayTransactionId . '. ' .
                'Response: ' . $response->body()
            );
        }

        $body = $response->json();

        if (! is_array($body) || ($body['status'] ?? false) !== true) {
            $this->logError('refund: unexpected response', [
                'transaction_reference' => $gatewayTransactionId,
                'body'                  => $body,
            ]);
            throw new PaymentException(
                'LahzaGateway: refund response status=false for transaction ' .
                $gatewayTransactionId . '. message=' . ($body['message'] ?? 'none')
            );
        }

        $refundData    = $body['data']          ?? [];
        $refundId      = $refundData['id']      ?? 'ref_' . uniqid();
        $refundStatus  = $refundData['status']  ?? 'pending';
        $refundedAmount= $refundData['amount']  ?? $amountCents;

        $normalizedStatus = match ($refundStatus) {
            'success'  => 'succeeded',
            'pending'  => 'pending',
            'failed'   => 'failed',
            default    => 'pending',
        };

        $this->logInfo('refund: completed', [
            'refund_id'             => $refundId,
            'status'                => $normalizedStatus,
            'refunded_cents'        => $refundedAmount,
            'transaction_reference' => $gatewayTransactionId,
        ]);

        return new RefundResult(
            refundId:      (string) $refundId,
            refundedCents: (int) $refundedAmount,
            status:        $normalizedStatus,
            raw:           $body,
        );
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Resolve the Lahza API base URL.
     *
     * Lahza uses one URL for both sandbox and live environments.
     * The environment is controlled by which keys are configured.
     * Overridable via payment_gateways.settings['base_url'] for edge cases.
     */
    private function baseUrl(): string
    {
        return rtrim($this->config->setting('base_url', self::DEFAULT_BASE_URL), '/');
    }

    /**
     * Log an info-level event to the payment-webhook channel.
     */
    private function logInfo(string $message, array $context = []): void
    {
        Log::channel('payment-webhook')->info('[LahzaGateway] ' . $message, $context);
    }

    /**
     * Log an error-level event to the payment-webhook channel.
     * Note: API keys are never included in context arrays.
     */
    private function logError(string $message, array $context = []): void
    {
        Log::channel('payment-webhook')->error('[LahzaGateway] ' . $message, $context);
    }
}
