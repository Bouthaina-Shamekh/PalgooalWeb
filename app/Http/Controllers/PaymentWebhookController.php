<?php

namespace App\Http\Controllers;

use App\Models\PaymentAttempt;
use App\Payments\DTOs\WebhookEvent;
use App\Payments\Exceptions\PaymentException;
use App\Payments\Exceptions\WebhookVerificationException;
use App\Payments\PaymentManager;
use App\Services\Billing\InvoiceSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PaymentWebhookController — ADR-007 Phase 6 (full settlement wiring).
 *
 * This controller is the ONLY place in the codebase that is allowed to call
 * InvoiceSettlementService::markPaid(). No client-facing controller (in particular
 * InvoiceCheckoutController) may settle an invoice from a form submission or a
 * return-URL redirect — both are trivially spoofable by the browser. Only a
 * signature-verified webhook, cross-checked against the invoice's own amount and
 * currency in the database, is treated as proof of payment.
 *
 * Route: POST /payment/webhook/{gateway}
 *   Registered in routes/payment.php via bootstrap/app.php `then:` callback.
 *   No CSRF, no session, no auth — only throttle:60,1 applies.
 *
 * Response contract:
 *   HTTP 202  {"status": "accepted"}   — verified and processed (or already processed — idempotent)
 *   HTTP 401  {"status": "rejected"}   — signature invalid, or amount/currency mismatch
 *   HTTP 404  {"status": "not_found"}  — unknown/mismatched gateway key
 *   HTTP 500  {"status": "error"}      — internal failure while settling (gateway should retry)
 */
class PaymentWebhookController extends Controller
{
    public function handle(Request $request, string $gateway): JsonResponse
    {
        $manager = app(PaymentManager::class);

        try {
            $gatewayInstance = $manager->gateway();
        } catch (\Throwable $e) {
            Log::channel('payment-webhook')->error('Webhook: no payment gateway resolvable', [
                'gateway_requested' => $gateway,
                'error'             => $e->getMessage(),
            ]);

            return response()->json(['status' => 'not_found'], 404);
        }

        // Compare against the ACTIVE gateway's own name() rather than raw config, so this
        // stays correct when the gateway is switched via the payment_gateways DB table
        // (ADR-007 Phase 5A) without a matching change to the .env default_gateway value.
        if ($gateway !== $gatewayInstance->name()) {
            Log::channel('payment-webhook')->notice('Webhook received for unknown/mismatched gateway', [
                'gateway_requested' => $gateway,
                'gateway_active'    => $gatewayInstance->name(),
                'received_at'       => now()->toIso8601String(),
            ]);

            return response()->json(['status' => 'not_found'], 404);
        }

        // ── Raw payload (MUST be read before any parsing) ─────────────────────
        //
        // HMAC signature verification requires the raw request bytes, not a decoded
        // array. Parsing the body first corrupts whitespace and alters the byte
        // sequence, causing signature mismatches.
        //
        $rawPayload = $request->getContent();

        // Lahza's documented header is `x-lahza-signature` (see LahzaGateway::verifyWebhook()).
        // The generic `X-Webhook-Signature` header is kept as a fallback for any future
        // gateway that uses it, so this controller stays usable without modification.
        $signatureHeader = (string) ($request->header('x-lahza-signature')
            ?? $request->header('X-Webhook-Signature')
            ?? '');

        try {
            $event = $gatewayInstance->verifyWebhook($rawPayload, $signatureHeader);
        } catch (WebhookVerificationException $e) {
            Log::channel('payment-webhook')->warning('Webhook signature verification failed', [
                'gateway'     => $gateway,
                'verified'    => false,
                'reason'      => $e->getMessage(),
                'received_at' => now()->toIso8601String(),
            ]);

            return response()->json(['status' => 'rejected'], 401);
        } catch (PaymentException $e) {
            Log::channel('payment-webhook')->info('Webhook rejected — gateway does not support webhooks in current phase', [
                'gateway'     => $gateway,
                'verified'    => false,
                'reason'      => $e->getMessage(),
                'received_at' => now()->toIso8601String(),
            ]);

            return response()->json(['status' => 'rejected'], 401);
        }

        Log::channel('payment-webhook')->info('Webhook received and verified', [
            'gateway'        => $gateway,
            'type'           => $event->type,
            'session_id'     => $event->sessionId,
            'transaction_id' => $event->transactionId,
            'verified'       => true,
            'received_at'    => now()->toIso8601String(),
        ]);

        // Only a genuine "payment succeeded" event can ever lead to settlement.
        // Everything else (initialize/pending/failed/refund/unknown) is acknowledged
        // and, on an explicit failure event, best-effort reflected onto the attempt —
        // but never touches Invoice/Order.
        if (!$event->isPaymentSucceeded()) {
            if ($event->isPaymentFailed()) {
                $this->markAttemptFailedFromEvent($gatewayInstance->name(), $event);
            }

            return response()->json(['status' => 'accepted'], 202);
        }

        $attempt = PaymentAttempt::where('gateway_session_id', $event->sessionId)
            ->forGateway($gatewayInstance->name())
            ->first();

        if (!$attempt) {
            // Do NOT create a speculative PaymentAttempt or Invoice — an unmatched event
            // proves nothing about which invoice it belongs to.
            Log::channel('payment-webhook')->warning('Webhook: no matching PaymentAttempt for session', [
                'gateway'        => $gatewayInstance->name(),
                'session_id'     => $event->sessionId,
                'transaction_id' => $event->transactionId,
            ]);

            return response()->json(['status' => 'accepted'], 202);
        }

        // Idempotent fast path — the exact same "succeeded" webhook delivered again.
        if ($attempt->isWebhookTerminal()) {
            return response()->json(['status' => 'accepted'], 202);
        }

        $invoice = $attempt->invoice;
        if (!$invoice) {
            Log::channel('payment-webhook')->error('Webhook: PaymentAttempt has no linked invoice', [
                'attempt_id' => $attempt->id,
            ]);

            return response()->json(['status' => 'accepted'], 202);
        }

        // ── Amount / currency verification against the DB-authoritative invoice ───────
        //
        // The webhook is trusted for AUTHENTICITY (HMAC-verified) but never for the
        // amount by itself — it must match what our own invoice row says is owed.
        //
        $invoiceCurrency = strtoupper(trim((string) $invoice->currency));
        $attemptCurrency = strtoupper(trim((string) $attempt->currency));
        $eventCurrency = $event->currency !== null ? strtoupper(trim($event->currency)) : null;
        $amountMatches = $event->amountCents !== null
            && $event->amountCents === (int) $invoice->total_cents
            && $event->amountCents === (int) $attempt->gateway_amount_cents
            && (int) $attempt->gateway_amount_cents === (int) $invoice->total_cents;
        $currencyMatches = $eventCurrency !== null
            && $eventCurrency === $invoiceCurrency
            && $eventCurrency === $attemptCurrency
            && $attemptCurrency === $invoiceCurrency;

        if (!$amountMatches || !$currencyMatches) {
            Log::channel('payment-webhook')->warning('Webhook SECURITY WARNING: amount/currency mismatch — settlement blocked', [
                'attempt_id'          => $attempt->id,
                'invoice_id'          => $invoice->id,
                'event_amount_cents'  => $event->amountCents,
                'invoice_total_cents' => $invoice->total_cents,
                'event_currency'      => $event->currency,
                'invoice_currency'    => $invoice->currency,
                'attempt_amount_cents'=> $attempt->gateway_amount_cents,
                'attempt_currency'    => $attempt->currency,
            ]);

            $failureUpdate = [
                'status'                 => PaymentAttempt::STATUS_FAILED,
                'gateway_status_raw'     => 'amount_or_currency_mismatch',
                'gateway_response'       => $event->raw,
                'webhook_verified_at'    => now(),
            ];
            if ($event->transactionId !== null) {
                $failureUpdate['gateway_transaction_id'] = $event->transactionId;
            }

            $this->markAttemptFailed($attempt->id, $failureUpdate);

            return response()->json(['status' => 'rejected'], 401);
        }

        // ── Optional secondary reconciliation via getTransaction() ─────────────────────
        //
        // Best-effort defense in depth: if the gateway can be asked directly about this
        // transaction, and it disagrees with the webhook, block settlement. A technical
        // failure of this SECONDARY call (gateway API unreachable) does not by itself
        // block settlement — the primary proof is the already-verified webhook signature.
        //
        if ($event->transactionId !== null) {
            try {
                $tx = $gatewayInstance->getTransaction($event->transactionId);

                $txMismatch = !$tx->isSucceeded()
                    || ($tx->amountCents !== null && $tx->amountCents !== (int) $invoice->total_cents)
                    || ($tx->amountCents !== null && $tx->amountCents !== (int) $attempt->gateway_amount_cents)
                    || ($tx->currency !== null && strtoupper(trim($tx->currency)) !== $invoiceCurrency)
                    || ($tx->currency !== null && strtoupper(trim($tx->currency)) !== $attemptCurrency);

                if ($txMismatch) {
                    Log::channel('payment-webhook')->warning('Webhook SECURITY WARNING: getTransaction() reconciliation mismatch — settlement blocked', [
                        'attempt_id'     => $attempt->id,
                        'invoice_id'     => $invoice->id,
                        'tx_status'      => $tx->status,
                        'tx_amount'      => $tx->amountCents,
                        'tx_currency'    => $tx->currency,
                    ]);

                    $this->markAttemptFailed($attempt->id, [
                        'status'             => PaymentAttempt::STATUS_FAILED,
                        'gateway_status_raw' => 'transaction_reconciliation_mismatch',
                        'gateway_response'   => $tx->raw,
                    ]);

                    return response()->json(['status' => 'rejected'], 401);
                }
            } catch (\Throwable $e) {
                Log::channel('payment-webhook')->warning('Webhook: getTransaction() reconciliation call failed (non-fatal, proceeding on verified webhook alone)', [
                    'attempt_id' => $attempt->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        // Persist verified gateway success independently from local settlement. This
        // short transaction makes the external financial evidence durable even if the
        // later invoice/order activation transaction fails and the gateway retries.
        try {
            $shouldSettle = DB::transaction(function () use ($attempt, $event): bool {
                $lockedAttempt = PaymentAttempt::whereKey($attempt->id)->lockForUpdate()->first();

                if (!$lockedAttempt || $lockedAttempt->isWebhookTerminal()) {
                    return false;
                }

                $lockedInvoice = $lockedAttempt->invoice;
                if (!$lockedInvoice) {
                    throw new \RuntimeException('PaymentAttempt lost its invoice link before settlement.');
                }

                $lockedInvoiceCurrency = strtoupper(trim((string) $lockedInvoice->currency));
                $lockedAttemptCurrency = strtoupper(trim((string) $lockedAttempt->currency));
                $lockedEventCurrency = $event->currency !== null ? strtoupper(trim($event->currency)) : null;
                if ($event->amountCents === null
                    || $event->amountCents !== (int) $lockedInvoice->total_cents
                    || $event->amountCents !== (int) $lockedAttempt->gateway_amount_cents
                    || $lockedEventCurrency === null
                    || $lockedEventCurrency !== $lockedInvoiceCurrency
                    || $lockedEventCurrency !== $lockedAttemptCurrency
                ) {
                    throw new \RuntimeException('Payment amount or currency changed before settlement.');
                }

                if (!$lockedAttempt->hasVerifiedGatewaySuccess()) {
                    $verifiedAt = now();

                    $lockedAttempt->update([
                        'gateway_transaction_id' => $event->transactionId,
                        'gateway_status_raw' => $event->type,
                        'gateway_response' => $event->raw,
                        'webhook_verified_at' => $verifiedAt,
                        'gateway_succeeded_at' => $verifiedAt,
                    ]);
                } elseif ($lockedAttempt->gateway_transaction_id !== $event->transactionId) {
                    Log::channel('payment-webhook')->warning(
                        'Verified success replay carried a different gateway transaction ID for the same payment attempt',
                        [
                            'attempt_id' => $lockedAttempt->id,
                            'invoice_id' => $lockedInvoice->id,
                            'gateway' => $lockedAttempt->gateway,
                            'stored_transaction_id' => $lockedAttempt->gateway_transaction_id,
                            'received_transaction_id' => $event->transactionId,
                            'gateway_session_id' => $lockedAttempt->gateway_session_id,
                        ],
                    );
                }

                return true;
            });

            if ($shouldSettle) {
                $settlementAttempt = PaymentAttempt::query()->findOrFail($attempt->id);
                $settlementInvoice = $settlementAttempt->invoice;

                if (!$settlementInvoice) {
                    throw new \RuntimeException('PaymentAttempt lost its invoice link before settlement.');
                }

                // InvoiceSettlementService remains the only writer of status=succeeded,
                // settled_at, the winning invoice pointer, and activation side effects.
                app(InvoiceSettlementService::class)->markPaid(
                    $settlementInvoice,
                    $gatewayInstance->name(),
                    $settlementAttempt,
                );
            }
        } catch (\Throwable $e) {
            Log::channel('payment-webhook')->error('Webhook: settlement transaction failed', [
                'attempt_id' => $attempt->id,
                'invoice_id' => $invoice->id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }

        $processedAttempt = PaymentAttempt::query()->find($attempt->id);
        $processedInvoice = $processedAttempt?->invoice;

        if ($processedAttempt
            && $processedInvoice
            && (int) $processedInvoice->payment_attempt_id === (int) $processedAttempt->id
            && $processedAttempt->settled_at !== null
        ) {
            Log::channel('payment-webhook')->info('Webhook settled invoice', [
                'attempt_id'     => $processedAttempt->id,
                'invoice_id'     => $processedInvoice->id,
                'gateway'        => $processedAttempt->gateway,
                'transaction_id' => $processedAttempt->gateway_transaction_id,
                'processed_at'   => now()->toIso8601String(),
            ]);
        } elseif ($processedAttempt
            && $processedInvoice
            && $processedAttempt->hasVerifiedGatewaySuccess()
            && $processedInvoice->status === 'paid'
            && (int) $processedInvoice->payment_attempt_id !== (int) $processedAttempt->id
        ) {
            Log::channel('payment-webhook')->warning(
                'Additional verified successful payment detected for already-settled invoice',
                [
                    'invoice_id' => $processedInvoice->id,
                    'winning_attempt_id' => $processedInvoice->payment_attempt_id,
                    'additional_attempt_id' => $processedAttempt->id,
                    'gateway' => $processedAttempt->gateway,
                    'gateway_transaction_id' => $processedAttempt->gateway_transaction_id,
                    'amount' => $processedAttempt->gateway_amount_cents,
                    'currency' => $processedAttempt->currency,
                ],
            );
        }

        return response()->json(['status' => 'accepted'], 202);
    }

    /**
     * Best-effort: reflect an explicit gateway-reported failure onto the matching attempt.
     * Never touches Invoice/Order — a failed payment leaves both exactly as they were.
     */
    protected function markAttemptFailedFromEvent(string $gatewayName, WebhookEvent $event): void
    {
        $attempt = PaymentAttempt::where('gateway_session_id', $event->sessionId)
            ->forGateway($gatewayName)
            ->first();

        if (!$attempt) {
            return;
        }

        $failureUpdate = [
            'status'              => PaymentAttempt::STATUS_FAILED,
            'gateway_status_raw'  => $event->type,
            'gateway_response'    => $event->raw,
            'webhook_verified_at' => now(),
        ];
        if ($event->transactionId !== null) {
            $failureUpdate['gateway_transaction_id'] = $event->transactionId;
        }

        $this->markAttemptFailed($attempt->id, $failureUpdate);
    }

    /**
     * Record a verified negative outcome without allowing stale data to downgrade success.
     */
    protected function markAttemptFailed(int $attemptId, array $attributes): void
    {
        DB::transaction(function () use ($attemptId, $attributes) {
            $lockedAttempt = PaymentAttempt::query()->lockForUpdate()->find($attemptId);

            if (!$lockedAttempt) {
                return;
            }

            if ($lockedAttempt->isWebhookTerminal() || $lockedAttempt->hasVerifiedGatewaySuccess()) {
                Log::channel('payment-webhook')->info('Webhook: stale negative event ignored for protected payment attempt', [
                    'attempt_id' => $lockedAttempt->id,
                    'gateway' => $lockedAttempt->gateway,
                    'status' => $lockedAttempt->status,
                    'gateway_succeeded_at' => $lockedAttempt->gateway_succeeded_at?->toIso8601String(),
                ]);

                return;
            }

            $lockedAttempt->update($attributes);
        });
    }
}
