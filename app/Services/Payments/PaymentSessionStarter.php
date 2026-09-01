<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Payments\Exceptions\PaymentException;
use App\Payments\PaymentManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentSessionStarter
{
    protected const SUPPORTED_CURRENCIES = ['USD', 'ILS', 'JOD'];

    public function __construct(
        protected PaymentManager $payments,
    ) {}

    /**
     * Claim and start (or reuse) one hosted payment session without settling the invoice.
     *
     * @return array{ok: bool, status: string, checkout_url: ?string, attempt_id: ?int, message: ?string, http_status: int}
     */
    public function start(
        Invoice $invoice,
        int $clientId,
        string $returnUrl,
        string $cancelUrl,
    ): array {
        $gateway = $this->payments->gateway();
        $claim = $this->claim($invoice->id, $clientId, $gateway->name());

        if ($claim['action'] === 'paid') {
            return $this->result(true, 'paid', null, null, null, 200);
        }

        if ($claim['action'] === 'blocked') {
            return $this->result(false, 'failed', null, null, $claim['message'], 422);
        }

        if ($claim['action'] === 'ready') {
            return $this->result(true, 'ready', $claim['checkout_url'], $claim['attempt_id'], null, 200);
        }

        if ($claim['action'] === 'processing') {
            return $this->result(
                false,
                'creating',
                null,
                $claim['attempt_id'] ?? null,
                'جلسة الدفع قيد الإنشاء. يرجى الانتظار.',
                409,
            );
        }

        /** @var PaymentAttempt $attempt */
        $attempt = $claim['attempt'];
        $invoice = $claim['invoice'];

        try {
            if (DB::transactionLevel() !== 0) {
                throw new \LogicException('Payment provider calls must run outside database transactions.');
            }

            $session = $gateway->createSession(
                $invoice,
                $attempt->idempotency_key,
                $returnUrl,
                $cancelUrl,
            );
        } catch (\Throwable $exception) {
            Log::error('PaymentSessionStarter: createSession failed', [
                'invoice_id' => $invoice->id,
                'attempt_id' => $attempt->id,
                'gateway' => $gateway->name(),
                'error' => $exception->getMessage(),
            ]);

            if ($this->isConfirmedPreSessionFailure($exception)) {
                $this->recordConfirmedFailure($invoice->id, $attempt->id);

                return $this->result(
                    false,
                    'failed',
                    null,
                    $attempt->id,
                    'تعذر بدء عملية الدفع حالياً. يرجى المحاولة لاحقاً.',
                    503,
                );
            }

            $this->recordIndeterminateFailure($invoice->id, $attempt->id);

            return $this->result(
                false,
                'indeterminate',
                null,
                $attempt->id,
                'تعذر حسم نتيجة إنشاء جلسة الدفع. لن تُنشأ جلسة أخرى تلقائياً.',
                503,
            );
        }

        if (!$this->isSafeExternalUrl($session->checkoutUrl)) {
            Log::error('PaymentSessionStarter: gateway returned an unsafe checkout URL', [
                'invoice_id' => $invoice->id,
                'attempt_id' => $attempt->id,
                'gateway' => $gateway->name(),
            ]);
            $this->recordIndeterminateFailure($invoice->id, $attempt->id, 'unsafe_checkout_url');

            return $this->result(
                false,
                'indeterminate',
                null,
                $attempt->id,
                'تعذر حسم نتيجة إنشاء جلسة الدفع. لن تُنشأ جلسة أخرى تلقائياً.',
                503,
            );
        }

        $finalized = $this->finalize(
            $invoice->id,
            $attempt->id,
            $session->sessionId,
            $session->checkoutUrl,
        );

        if ($finalized === 'paid') {
            return $this->result(true, 'paid', null, $attempt->id, null, 200);
        }

        if ($finalized !== 'ready') {
            return $this->result(
                false,
                'indeterminate',
                null,
                $attempt->id,
                'تعذر تثبيت جلسة الدفع محلياً، ولن تُنشأ جلسة أخرى تلقائياً.',
                503,
            );
        }

        return $this->result(true, 'ready', $session->checkoutUrl, $attempt->id, null, 200);
    }

    protected function claim(int $invoiceId, int $clientId, string $gatewayName): array
    {
        return DB::transaction(function () use ($invoiceId, $clientId, $gatewayName): array {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoiceId);

            abort_if((int) $invoice->client_id !== $clientId, 404);

            if ($invoice->status === 'paid') {
                return ['action' => 'paid'];
            }

            if ($invoice->status === 'cancelled') {
                return ['action' => 'blocked', 'message' => 'This invoice has already been cancelled.'];
            }

            if (($blockedReason = $this->validatePayable($invoice)) !== null) {
                return ['action' => 'blocked', 'message' => $blockedReason];
            }

            $sessionStatus = $invoice->payment_session_status ?: Invoice::PAYMENT_SESSION_IDLE;

            if ($sessionStatus === Invoice::PAYMENT_SESSION_READY) {
                $attempt = PaymentAttempt::query()->find($invoice->payment_session_attempt_id);
                if ($attempt !== null && !$this->attemptMatchesInvoice($attempt, $invoice)) {
                    return ['action' => 'blocked', 'message' => $this->attemptMismatchMessage()];
                }

                $url = $attempt !== null
                    && (int) $attempt->invoice_id === $invoice->id
                    && $attempt->gateway === $gatewayName
                    && $attempt->status === PaymentAttempt::STATUS_INITIATED
                        ? $this->checkoutUrl($attempt)
                        : null;

                return $url !== null
                    ? ['action' => 'ready', 'checkout_url' => $url, 'attempt_id' => $attempt->id]
                    : ['action' => 'processing', 'attempt_id' => $attempt?->id];
            }

            if ($sessionStatus === Invoice::PAYMENT_SESSION_CREATING) {
                $attempt = PaymentAttempt::query()->find($invoice->payment_session_attempt_id);
                if ($attempt !== null && !$this->attemptMatchesInvoice($attempt, $invoice)) {
                    return ['action' => 'blocked', 'message' => $this->attemptMismatchMessage()];
                }

                return ['action' => 'processing', 'attempt_id' => $invoice->payment_session_attempt_id];
            }

            $attempt = PaymentAttempt::query()
                ->where('invoice_id', $invoice->id)
                ->where('gateway', $gatewayName)
                ->whereIn('status', [PaymentAttempt::STATUS_PENDING, PaymentAttempt::STATUS_INITIATED])
                ->latest('id')
                ->first();

            if ($attempt !== null) {
                if (!$this->attemptMatchesInvoice($attempt, $invoice)) {
                    return ['action' => 'blocked', 'message' => $this->attemptMismatchMessage()];
                }

                $url = $this->checkoutUrl($attempt);

                if ($attempt->status === PaymentAttempt::STATUS_INITIATED && $url !== null) {
                    $invoice->update([
                        'payment_session_status' => Invoice::PAYMENT_SESSION_READY,
                        'payment_session_attempt_id' => $attempt->id,
                    ]);

                    return ['action' => 'ready', 'checkout_url' => $url, 'attempt_id' => $attempt->id];
                }

                $attemptWasInitiated = $attempt->status === PaymentAttempt::STATUS_INITIATED;

                if ($attemptWasInitiated) {
                    $attempt->update(['status' => PaymentAttempt::STATUS_PENDING]);
                }

                $invoice->update([
                    'payment_session_status' => Invoice::PAYMENT_SESSION_CREATING,
                    'payment_session_attempt_id' => $attempt->id,
                ]);

                return $attemptWasInitiated
                    ? ['action' => 'create', 'invoice' => $invoice, 'attempt' => $attempt]
                    : ['action' => 'processing', 'attempt_id' => $attempt->id];
            }

            $attempt = PaymentAttempt::create([
                'invoice_id' => $invoice->id,
                'order_id' => $invoice->order_id,
                'client_id' => $invoice->client_id,
                'gateway' => $gatewayName,
                'idempotency_key' => (string) Str::uuid(),
                'gateway_amount_cents' => (int) $invoice->total_cents,
                'currency' => (string) $invoice->currency,
                'status' => PaymentAttempt::STATUS_PENDING,
            ]);

            $invoice->update([
                'payment_session_status' => Invoice::PAYMENT_SESSION_CREATING,
                'payment_session_attempt_id' => $attempt->id,
            ]);

            return ['action' => 'create', 'invoice' => $invoice, 'attempt' => $attempt];
        });
    }

    protected function validatePayable(Invoice $invoice): ?string
    {
        if (!in_array($invoice->status, ['draft', 'unpaid'], true)) {
            return 'هذه الفاتورة لم تعد مفتوحة للدفع.';
        }

        if ((int) $invoice->total_cents <= 0) {
            return 'لا يمكن بدء دفع لفاتورة بقيمة صفرية.';
        }

        $currency = trim((string) $invoice->currency);
        if (!preg_match('/^[A-Z]{3}$/', $currency)
            || !in_array($currency, self::SUPPORTED_CURRENCIES, true)
        ) {
            return 'عملة هذه الفاتورة غير مدعومة حالياً للدفع الإلكتروني.';
        }

        $order = $invoice->order;
        if ($order instanceof Order && $order->status === Order::STATUS_ACTIVE) {
            return 'تم تفعيل هذا الطلب مسبقًا.';
        }

        return null;
    }

    protected function attemptMatchesInvoice(PaymentAttempt $attempt, Invoice $invoice): bool
    {
        return (int) $attempt->gateway_amount_cents === (int) $invoice->total_cents
            && (string) $attempt->currency === (string) $invoice->currency;
    }

    protected function attemptMismatchMessage(): string
    {
        return t(
            'site.Payment_Attempt_Invoice_Mismatch',
            'بيانات محاولة الدفع لا تطابق الفاتورة الحالية وتحتاج إلى مراجعة.'
        );
    }

    protected function finalize(int $invoiceId, int $attemptId, string $sessionId, string $checkoutUrl): string
    {
        return DB::transaction(function () use ($invoiceId, $attemptId, $sessionId, $checkoutUrl): string {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoiceId);
            $attempt = PaymentAttempt::query()->lockForUpdate()->findOrFail($attemptId);

            if ($invoice->status === 'paid') {
                if ($attempt->status === PaymentAttempt::STATUS_PENDING) {
                    $attempt->update([
                        'status' => PaymentAttempt::STATUS_CANCELLED,
                        'gateway_status_raw' => 'invoice_paid_before_session_ready',
                    ]);
                }

                return 'paid';
            }

            if ($invoice->payment_session_status !== Invoice::PAYMENT_SESSION_CREATING
                || (int) $invoice->payment_session_attempt_id !== $attempt->id
                || $attempt->status !== PaymentAttempt::STATUS_PENDING
            ) {
                return 'stale';
            }

            if (!$this->attemptMatchesInvoice($attempt, $invoice)) {
                return 'stale';
            }

            $attempt->update([
                'status' => PaymentAttempt::STATUS_INITIATED,
                'gateway_session_id' => $sessionId,
                'gateway_response' => ['checkout_url' => $checkoutUrl],
            ]);
            $invoice->update(['payment_session_status' => Invoice::PAYMENT_SESSION_READY]);

            return 'ready';
        });
    }

    protected function recordIndeterminateFailure(
        int $invoiceId,
        int $attemptId,
        string $reason = 'session_creation_outcome_unknown',
    ): void {
        DB::transaction(function () use ($invoiceId, $attemptId, $reason): void {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoiceId);
            $attempt = PaymentAttempt::query()->lockForUpdate()->findOrFail($attemptId);

            if ($invoice->status !== 'paid'
                && $invoice->payment_session_status === Invoice::PAYMENT_SESSION_CREATING
                && (int) $invoice->payment_session_attempt_id === $attempt->id
                && $attempt->status === PaymentAttempt::STATUS_PENDING
            ) {
                $attempt->update([
                    'gateway_status_raw' => $reason,
                    'gateway_response' => ['reason' => $reason],
                ]);
            }
        });
    }

    protected function recordConfirmedFailure(int $invoiceId, int $attemptId): void
    {
        DB::transaction(function () use ($invoiceId, $attemptId): void {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoiceId);
            $attempt = PaymentAttempt::query()->lockForUpdate()->findOrFail($attemptId);

            if ($invoice->status !== 'paid'
                && $invoice->payment_session_status === Invoice::PAYMENT_SESSION_CREATING
                && (int) $invoice->payment_session_attempt_id === $attempt->id
                && $attempt->status === PaymentAttempt::STATUS_PENDING
            ) {
                $attempt->update([
                    'status' => PaymentAttempt::STATUS_FAILED,
                    'gateway_status_raw' => 'create_session_confirmed_failed',
                    'gateway_response' => ['reason' => 'confirmed_before_provider_session'],
                ]);
                $invoice->update([
                    'payment_session_status' => Invoice::PAYMENT_SESSION_IDLE,
                    'payment_session_attempt_id' => null,
                ]);
            }
        });
    }

    protected function isConfirmedPreSessionFailure(\Throwable $exception): bool
    {
        return $exception instanceof PaymentException
            && str_contains($exception->getMessage(), 'secret_key is not configured');
    }

    protected function checkoutUrl(?PaymentAttempt $attempt): ?string
    {
        $url = is_array($attempt?->gateway_response)
            ? ($attempt->gateway_response['checkout_url'] ?? null)
            : null;

        return is_string($url) && $this->isSafeExternalUrl($url) ? $url : null;
    }

    protected function isSafeExternalUrl(string $url): bool
    {
        $parts = parse_url($url);

        return is_array($parts)
            && isset($parts['scheme'], $parts['host'])
            && in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            && $parts['host'] !== '';
    }

    protected function result(
        bool $ok,
        string $status,
        ?string $checkoutUrl,
        ?int $attemptId,
        ?string $message,
        int $httpStatus,
    ): array {
        return [
            'ok' => $ok,
            'status' => $status,
            'checkout_url' => $checkoutUrl,
            'attempt_id' => $attemptId,
            'message' => $message,
            'http_status' => $httpStatus,
        ];
    }
}
