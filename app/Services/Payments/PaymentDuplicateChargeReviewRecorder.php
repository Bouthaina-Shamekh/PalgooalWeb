<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use App\Models\PaymentAttempt;
use App\Models\PaymentDuplicateChargeReview;
use App\Models\User;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class PaymentDuplicateChargeReviewRecorder
{
    private const VERIFICATION_RESULTS = [
        PaymentDuplicateChargeReviewService::VERIFICATION_SUCCEEDED,
        PaymentDuplicateChargeReviewService::VERIFICATION_FAILED,
        PaymentDuplicateChargeReviewService::VERIFICATION_PENDING,
        PaymentDuplicateChargeReviewService::VERIFICATION_REFUNDED,
        PaymentDuplicateChargeReviewService::VERIFICATION_GATEWAY_UNAVAILABLE,
        PaymentDuplicateChargeReviewService::VERIFICATION_TRANSACTION_NOT_FOUND,
        PaymentDuplicateChargeReviewService::VERIFICATION_INDETERMINATE,
        PaymentDuplicateChargeReviewService::VERIFICATION_NO_TRANSACTION_ID,
        PaymentDuplicateChargeReviewService::VERIFICATION_AMOUNT_MISMATCH,
        PaymentDuplicateChargeReviewService::VERIFICATION_CURRENCY_MISMATCH,
        PaymentDuplicateChargeReviewService::VERIFICATION_AMOUNT_CURRENCY_MISMATCH,
        PaymentDuplicateChargeReviewService::VERIFICATION_TRANSACTION_IDENTITY_MISMATCH,
    ];

    public function __construct(
        private readonly PaymentDuplicateChargeReviewService $detector,
    ) {}

    public function record(
        PaymentAttempt $paymentAttempt,
        User $reviewer,
        string $reviewStatus,
        ?string $resolution = null,
        ?string $note = null,
        ?string $verificationResult = null,
        ?CarbonInterface $verificationCheckedAt = null,
    ): PaymentDuplicateChargeReview {
        $this->validateReviewContract(
            $reviewer,
            $reviewStatus,
            $resolution,
            $verificationResult,
            $verificationCheckedAt,
        );

        return DB::transaction(function () use (
            $paymentAttempt,
            $reviewer,
            $reviewStatus,
            $resolution,
            $note,
            $verificationResult,
            $verificationCheckedAt,
        ): PaymentDuplicateChargeReview {
            $lockedAttempt = PaymentAttempt::query()
                ->whereKey($paymentAttempt->getKey())
                ->lockForUpdate()
                ->first();

            if ($lockedAttempt === null) {
                throw new DomainException('The payment attempt no longer exists.');
            }

            $lockedInvoice = $lockedAttempt->invoice_id === null
                ? null
                : Invoice::query()->whereKey($lockedAttempt->invoice_id)->lockForUpdate()->first();

            $candidate = $this->detector->review(
                attemptId: $lockedAttempt->id,
                limit: 1,
            )->first();

            if ($candidate === null || $lockedInvoice === null) {
                throw new DomainException(
                    "PaymentAttempt {$lockedAttempt->id} is not a current duplicate-charge candidate.",
                );
            }

            return PaymentDuplicateChargeReview::query()->create([
                'payment_attempt_id' => $lockedAttempt->id,
                'review_status' => $reviewStatus,
                'resolution' => $resolution,
                'needs_follow_up' => $reviewStatus === PaymentDuplicateChargeReview::STATUS_NEEDS_FOLLOW_UP,
                'reviewed_by' => $reviewer->id,
                'reviewer_name' => $reviewer->name,
                'reviewed_at' => now(),
                'note' => filled($note) ? trim($note) : null,
                'detection_classification' => $candidate['classification'],
                'verification_result' => $verificationResult,
                'verification_checked_at' => $verificationCheckedAt,
                'evidence_snapshot' => $this->evidenceSnapshot(
                    $lockedAttempt,
                    $lockedInvoice,
                    $candidate,
                ),
            ]);
        });
    }

    private function validateReviewContract(
        User $reviewer,
        string $reviewStatus,
        ?string $resolution,
        ?string $verificationResult,
        ?CarbonInterface $verificationCheckedAt,
    ): void {
        if (! $reviewer->exists || $reviewer->getKey() === null) {
            throw new InvalidArgumentException('The reviewer must be a persisted User.');
        }

        if (! in_array($reviewStatus, PaymentDuplicateChargeReview::STATUSES, true)) {
            throw new InvalidArgumentException("Unknown duplicate-charge review status: {$reviewStatus}.");
        }

        if ($resolution !== null && ! in_array($resolution, PaymentDuplicateChargeReview::RESOLUTIONS, true)) {
            throw new InvalidArgumentException("Unknown duplicate-charge resolution: {$resolution}.");
        }

        if (
            $reviewStatus === PaymentDuplicateChargeReview::STATUS_NEEDS_FOLLOW_UP
            && $resolution === PaymentDuplicateChargeReview::RESOLUTION_NOT_DUPLICATE
        ) {
            throw new InvalidArgumentException(
                'A needs_follow_up review may only have a null or confirmed_duplicate resolution.',
            );
        }

        if ($reviewStatus === PaymentDuplicateChargeReview::STATUS_RESOLVED && $resolution === null) {
            throw new InvalidArgumentException('A resolved review requires a resolution.');
        }

        if (
            $verificationResult !== null
            && ! in_array($verificationResult, self::VERIFICATION_RESULTS, true)
        ) {
            throw new InvalidArgumentException("Unknown gateway verification result: {$verificationResult}.");
        }

        if (($verificationResult === null) !== ($verificationCheckedAt === null)) {
            throw new InvalidArgumentException(
                'Gateway verification result and checked timestamp must either both be present or both be null.',
            );
        }
    }

    /** @param array<string, int|string|null> $candidate */
    private function evidenceSnapshot(
        PaymentAttempt $attempt,
        Invoice $invoice,
        array $candidate,
    ): array {
        return [
            'payment_attempt_id' => $attempt->id,
            'invoice_id' => $invoice->id,
            'winning_payment_attempt_id' => $candidate['winning_attempt_id'],
            'gateway' => $attempt->gateway,
            'gateway_session_id' => $attempt->gateway_session_id,
            'gateway_transaction_id' => $attempt->gateway_transaction_id,
            'gateway_amount_cents' => $attempt->gateway_amount_cents,
            'currency' => $attempt->currency,
            'gateway_succeeded_at' => $attempt->gateway_succeeded_at?->toDateTimeString(),
            'settled_at' => $attempt->settled_at?->toDateTimeString(),
            'attempt_status' => $attempt->status,
            'invoice_status' => $invoice->status,
            'detection_classification' => $candidate['classification'],
        ];
    }
}
