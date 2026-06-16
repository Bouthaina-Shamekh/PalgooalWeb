<?php

namespace App\Http\Controllers;

use App\Payments\Exceptions\PaymentException;
use App\Payments\Exceptions\WebhookVerificationException;
use App\Payments\PaymentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * PaymentWebhookController — ADR-007 Phase 3 (Webhook Stub)
 *
 * Receives inbound HTTP callbacks from payment gateways.
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │  PHASE 3 SCOPE — STUB ONLY                                     │
 * │                                                                 │
 * │  This controller is intentionally incomplete. It:              │
 * │   ✅ Resolves the active gateway via PaymentManager            │
 * │   ✅ Rejects unknown gateways (404)                            │
 * │   ✅ Delegates verification to the gateway (verifyWebhook)     │
 * │   ✅ Returns 401 on verification failure                       │
 * │   ✅ Returns 202 on verification success                       │
 * │   ✅ Logs every inbound event to the payment-webhook channel   │
 * │                                                                 │
 * │  It does NOT (by design, in Phase 3):                          │
 * │   ❌ Create or update PaymentAttempt records                   │
 * │   ❌ Call InvoiceSettlementService::markPaid()                 │
 * │   ❌ Look up invoices or orders                                │
 * │   ❌ Activate subscriptions                                    │
 * │   ❌ Perform any settlement logic                              │
 * │                                                                 │
 * │  Phase 4 will add full settlement inside this controller.      │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * Route: POST /payment/webhook/{gateway}
 *   Registered in routes/payment.php via bootstrap/app.php `then:` callback.
 *   No CSRF, no session, no auth — only throttle:60,1 applies.
 *
 * Response contract:
 *   HTTP 202  {"status": "accepted"}   — webhook verified
 *   HTTP 401  {"status": "rejected"}   — verification failed / gateway mismatch
 *   HTTP 404  {"status": "not_found"}  — unknown gateway key
 */
class PaymentWebhookController extends Controller
{
    /**
     * Handle an inbound payment gateway webhook.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $gateway  URL segment; must match PAYMENT_GATEWAY in .env
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, string $gateway): JsonResponse
    {
        // ── Step 3: Gateway Resolution ────────────────────────────────────────
        //
        // The URL segment {gateway} must exactly match the configured gateway key.
        //
        //   PAYMENT_GATEWAY=mock  → POST /payment/webhook/mock  → OK
        //   PAYMENT_GATEWAY=mock  → POST /payment/webhook/lahza → 404
        //
        // This prevents routing real gateway webhooks to the wrong handler when
        // the configured gateway is switched in a future phase.
        //
        $configuredKey = config('payment.default_gateway');

        if ($gateway !== $configuredKey) {
            Log::channel('payment-webhook')->notice('Webhook received for unknown gateway', [
                'gateway_requested'  => $gateway,
                'gateway_configured' => $configuredKey,
                'received_at'        => now()->toIso8601String(),
            ]);

            return response()->json(['status' => 'not_found'], 404);
        }

        $manager         = app(PaymentManager::class);
        $gatewayInstance = $manager->gateway();

        // ── Raw payload (MUST be read before any parsing) ─────────────────────
        //
        // HMAC signature verification (Phase 5) requires the raw request bytes,
        // not a decoded array. Parsing the body first corrupts whitespace and
        // alters the byte sequence, causing signature mismatches.
        //
        $rawPayload      = $request->getContent();
        $signatureHeader = $request->header('X-Webhook-Signature', '');

        // ── Step 4: Webhook Verification ──────────────────────────────────────
        //
        // Delegate to the gateway's verifyWebhook() implementation.
        //
        // MockGateway (Phase 3): throws PaymentException — it does not support
        //   webhooks and every call is treated as a rejection.
        //
        // Real gateways (Phase 5): will verify the HMAC signature and return a
        //   WebhookEvent DTO if valid, or throw WebhookVerificationException if
        //   the signature is invalid/missing.
        //
        // Both exception types result in 401 here. The distinction matters for
        // the log level: WebhookVerificationException = suspicious (bad sig),
        // PaymentException = not supported (gateway limitation).
        //
        try {
            $event = $gatewayInstance->verifyWebhook($rawPayload, $signatureHeader);
        } catch (WebhookVerificationException $e) {
            // Signature verification explicitly failed — potential spoofed request.
            Log::channel('payment-webhook')->warning('Webhook signature verification failed', [
                'gateway'     => $gateway,
                'verified'    => false,
                'reason'      => $e->getMessage(),
                'received_at' => now()->toIso8601String(),
            ]);

            return response()->json(['status' => 'rejected'], 401);
        } catch (PaymentException $e) {
            // Gateway does not support webhooks in the current phase (e.g. MockGateway).
            Log::channel('payment-webhook')->info('Webhook rejected — gateway does not support webhooks in current phase', [
                'gateway'     => $gateway,
                'verified'    => false,
                'reason'      => $e->getMessage(),
                'received_at' => now()->toIso8601String(),
            ]);

            return response()->json(['status' => 'rejected'], 401);
        }

        // ── Step 5: PaymentAttempt Lookup — STUB (no writes in Phase 3) ──────
        //
        // In Phase 4, this section will:
        //
        //   1. Look up the existing PaymentAttempt record using either:
        //        - $event->sessionId      → payment_attempts.gateway_session_id
        //        - $event->transactionId  → payment_attempts.gateway_transaction_id
        //        - idempotency_key        → payment_attempts.idempotency_key
        //
        //   2. Guard against duplicate delivery (idempotency):
        //        if ($attempt->isSucceeded()) { return response()->json(['status' => 'accepted'], 202); }
        //
        //   3. Validate amount:
        //        if ($event->amountCents !== $attempt->gateway_amount_cents) { /* reject */ }
        //
        //   4. Update the attempt and call markPaid():
        //        $attempt->update(['status' => PaymentAttempt::STATUS_PENDING, ...]);
        //        app(InvoiceSettlementService::class)->markPaid($invoice, $gateway, $attempt);
        //
        // None of the above runs in Phase 3.

        // ── Step 6: Logging ───────────────────────────────────────────────────
        Log::channel('payment-webhook')->info('Webhook received and verified', [
            'gateway'        => $gateway,
            'transaction_id' => $event->transactionId,
            'verified'       => true,
            'received_at'    => now()->toIso8601String(),
        ]);

        // ── Step 7: Response Contract ─────────────────────────────────────────
        //
        // HTTP 202 Accepted — tells the gateway "we received it".
        // We do NOT return 200 OK because the settlement (subscription activation)
        // is asynchronous and not yet complete at this point in the request cycle.
        // 202 is the semantically correct status for "queued for processing".
        //
        return response()->json(['status' => 'accepted'], 202);
    }
}
