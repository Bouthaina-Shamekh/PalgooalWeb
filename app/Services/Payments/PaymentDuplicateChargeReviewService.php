<?php

namespace App\Services\Payments;

use App\Models\PaymentAttempt;
use App\Models\PaymentGateway as PaymentGatewayModel;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\DTOs\TransactionStatus;
use Illuminate\Support\Collection;
use Throwable;

final class PaymentDuplicateChargeReviewService
{
    public const CLASSIFICATION_POTENTIAL_DUPLICATE_CHARGE = 'potential_duplicate_charge';

    public const CLASSIFICATION_SHARED_TRANSACTION_ID = 'shared_transaction_id';

    public const CLASSIFICATION_DISTINCT_TRANSACTION_IDS = 'distinct_transaction_ids';

    public const CLASSIFICATION_INCONSISTENT_SETTLEMENT_STATE = 'inconsistent_settlement_state';

    public const CLASSIFICATION_ALREADY_REFUNDED = 'already_refunded';

    public const VERIFICATION_NOT_REQUESTED = 'not_requested';

    public const VERIFICATION_SUCCEEDED = 'verified_succeeded';

    public const VERIFICATION_FAILED = 'verified_failed';

    public const VERIFICATION_PENDING = 'verified_pending';

    public const VERIFICATION_REFUNDED = 'verified_refunded';

    public const VERIFICATION_GATEWAY_UNAVAILABLE = 'gateway_unavailable';

    public const VERIFICATION_TRANSACTION_NOT_FOUND = 'transaction_not_found';

    public const VERIFICATION_INDETERMINATE = 'indeterminate';

    public const VERIFICATION_NO_TRANSACTION_ID = 'unavailable_no_transaction_id';

    public const VERIFICATION_AMOUNT_MISMATCH = 'amount_mismatch';

    public const VERIFICATION_CURRENCY_MISMATCH = 'currency_mismatch';

    public const VERIFICATION_AMOUNT_CURRENCY_MISMATCH = 'amount_currency_mismatch';

    public const VERIFICATION_TRANSACTION_IDENTITY_MISMATCH = 'transaction_identity_mismatch';

    /**
     * Return operational evidence only. This method never mutates local or gateway state.
     *
     * @return Collection<int, array<string, int|string|null>>
     */
    public function review(
        ?int $attemptId = null,
        ?int $invoiceId = null,
        int $limit = 50,
        ?int $olderThanMinutes = null,
        bool $verifyGateway = false,
    ): Collection {
        $query = PaymentAttempt::query()
            ->join('invoices', 'invoices.id', '=', 'payment_attempts.invoice_id')
            ->whereNotNull('payment_attempts.gateway_succeeded_at')
            ->whereNull('payment_attempts.settled_at')
            ->where('invoices.status', 'paid')
            ->whereNull('invoices.deleted_at')
            ->whereNotNull('invoices.payment_attempt_id')
            ->whereColumn('invoices.payment_attempt_id', '!=', 'payment_attempts.id')
            ->select('payment_attempts.*')
            ->with(['invoice.paymentAttempt'])
            ->orderBy('payment_attempts.gateway_succeeded_at')
            ->orderBy('payment_attempts.id');

        if ($attemptId !== null) {
            $query->where('payment_attempts.id', $attemptId);
        }

        if ($invoiceId !== null) {
            $query->where('payment_attempts.invoice_id', $invoiceId);
        }

        if ($olderThanMinutes !== null) {
            $query->where(
                'payment_attempts.gateway_succeeded_at',
                '<=',
                now()->subMinutes($olderThanMinutes),
            );
        }

        return $query->limit($limit)->get()->map(
            fn (PaymentAttempt $attempt): array => $this->describe($attempt, $verifyGateway),
        );
    }

    /** @return array<string, int|string|null> */
    private function describe(PaymentAttempt $attempt, bool $verifyGateway): array
    {
        $invoice = $attempt->invoice;
        $winner = $invoice?->paymentAttempt;
        $transactionId = $attempt->gateway_transaction_id;
        $winningTransactionId = $winner?->gateway_transaction_id;

        return [
            'attempt_id' => $attempt->id,
            'invoice_id' => $invoice?->id,
            'invoice_number' => $invoice?->number,
            'client_id' => $attempt->client_id ?? $invoice?->client_id,
            'gateway' => $attempt->gateway,
            'gateway_session_id' => $attempt->gateway_session_id,
            'gateway_transaction_id' => $transactionId,
            'amount_cents' => $attempt->gateway_amount_cents,
            'currency' => $attempt->currency,
            'gateway_succeeded_at' => $attempt->gateway_succeeded_at?->toDateTimeString(),
            'settled_at' => $attempt->settled_at?->toDateTimeString(),
            'winning_attempt_id' => $winner?->id,
            'winning_transaction_id' => $winningTransactionId,
            'invoice_status' => $invoice?->status,
            'classification' => $this->classification($attempt, $winner),
            'transaction_id_reused' => $this->transactionIdIsReused($attempt) ? 'yes' : 'no',
            'verification' => $this->verification($attempt, $verifyGateway),
        ];
    }

    private function classification(PaymentAttempt $attempt, ?PaymentAttempt $winner): string
    {
        if (
            $winner === null
            || $winner->invoice_id !== $attempt->invoice_id
            || $winner->status !== PaymentAttempt::STATUS_SUCCEEDED
            || $winner->settled_at === null
        ) {
            return self::CLASSIFICATION_INCONSISTENT_SETTLEMENT_STATE;
        }

        if ($attempt->status === PaymentAttempt::STATUS_REFUNDED || $attempt->refunded_at !== null) {
            return self::CLASSIFICATION_ALREADY_REFUNDED;
        }

        $transactionId = $attempt->gateway_transaction_id;
        $winningTransactionId = $winner->gateway_transaction_id;

        if ($transactionId !== null && $winningTransactionId !== null) {
            return $transactionId === $winningTransactionId
                ? self::CLASSIFICATION_SHARED_TRANSACTION_ID
                : self::CLASSIFICATION_DISTINCT_TRANSACTION_IDS;
        }

        return self::CLASSIFICATION_POTENTIAL_DUPLICATE_CHARGE;
    }

    private function transactionIdIsReused(PaymentAttempt $attempt): bool
    {
        if ($attempt->gateway_transaction_id === null) {
            return false;
        }

        return PaymentAttempt::query()
            ->where('gateway', $attempt->gateway)
            ->where('gateway_transaction_id', $attempt->gateway_transaction_id)
            ->whereKeyNot($attempt->id)
            ->exists();
    }

    private function verification(PaymentAttempt $attempt, bool $verifyGateway): string
    {
        if (! $verifyGateway) {
            return self::VERIFICATION_NOT_REQUESTED;
        }

        if ($attempt->gateway_transaction_id === null) {
            return self::VERIFICATION_NO_TRANSACTION_ID;
        }

        $gateway = $this->resolveStoredGateway($attempt->gateway);

        if ($gateway === null) {
            return self::VERIFICATION_GATEWAY_UNAVAILABLE;
        }

        try {
            $transaction = $gateway->getTransaction($attempt->gateway_transaction_id);
        } catch (Throwable $exception) {
            $message = strtolower($exception->getMessage());

            return str_contains($message, 'not found') || str_contains($message, '404')
                ? self::VERIFICATION_TRANSACTION_NOT_FOUND
                : self::VERIFICATION_GATEWAY_UNAVAILABLE;
        }

        if ($transaction->transactionId !== $attempt->gateway_transaction_id) {
            return self::VERIFICATION_TRANSACTION_IDENTITY_MISMATCH;
        }

        return match ($transaction->status) {
            TransactionStatus::STATUS_SUCCEEDED => $this->verifySucceededTransaction($attempt, $transaction),
            TransactionStatus::STATUS_FAILED => self::VERIFICATION_FAILED,
            TransactionStatus::STATUS_PENDING => self::VERIFICATION_PENDING,
            TransactionStatus::STATUS_REFUNDED => self::VERIFICATION_REFUNDED,
            default => self::VERIFICATION_INDETERMINATE,
        };
    }

    private function verifySucceededTransaction(
        PaymentAttempt $attempt,
        TransactionStatus $transaction,
    ): string {
        $invoice = $attempt->invoice;
        $attemptAmount = $attempt->gateway_amount_cents;
        $transactionCurrency = $this->normalizeCurrency($transaction->currency);
        $attemptCurrency = $this->normalizeCurrency($attempt->currency);
        $invoiceCurrency = $this->normalizeCurrency($invoice?->currency);

        $amountMismatch = $transaction->amountCents !== null && (
            $attemptAmount === null
            || $transaction->amountCents !== (int) $attemptAmount
            || ($invoice !== null && $transaction->amountCents !== (int) $invoice->total_cents)
            || ($invoice !== null && (int) $attemptAmount !== (int) $invoice->total_cents)
        );
        $currencyMismatch = $transactionCurrency !== null && (
            $attemptCurrency === null
            || $transactionCurrency !== $attemptCurrency
            || ($invoice !== null && $transactionCurrency !== $invoiceCurrency)
            || ($invoice !== null && $attemptCurrency !== $invoiceCurrency)
        );

        if ($amountMismatch && $currencyMismatch) {
            return self::VERIFICATION_AMOUNT_CURRENCY_MISMATCH;
        }

        if ($amountMismatch) {
            return self::VERIFICATION_AMOUNT_MISMATCH;
        }

        if ($currencyMismatch) {
            return self::VERIFICATION_CURRENCY_MISMATCH;
        }

        if ($transaction->amountCents === null || $transactionCurrency === null) {
            return self::VERIFICATION_INDETERMINATE;
        }

        return self::VERIFICATION_SUCCEEDED;
    }

    private function normalizeCurrency(?string $currency): ?string
    {
        return $currency === null ? null : strtoupper(trim($currency));
    }

    /**
     * Resolve the gateway named on the historical attempt. Never fall back to
     * PaymentManager, the configured default, or the currently active row.
     */
    private function resolveStoredGateway(string $gatewayName): ?PaymentGatewayInterface
    {
        $class = config("payment.gateways.{$gatewayName}");

        if (! is_string($class) || ! class_exists($class)) {
            return null;
        }

        try {
            $reflection = new \ReflectionClass($class);
            $constructor = $reflection->getConstructor();

            if ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0) {
                $gatewayConfig = PaymentGatewayModel::query()
                    ->where('driver', $gatewayName)
                    ->first();

                if ($gatewayConfig === null) {
                    return null;
                }

                $gateway = app()->make($class, ['config' => $gatewayConfig]);
            } else {
                $gateway = app($class);
            }
        } catch (Throwable) {
            return null;
        }

        if (! $gateway instanceof PaymentGatewayInterface || $gateway->name() !== $gatewayName) {
            return null;
        }

        return $gateway;
    }
}
