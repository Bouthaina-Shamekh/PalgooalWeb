<?php

namespace App\Console\Commands;

use App\Services\Payments\PaymentDuplicateChargeReviewService;
use Illuminate\Console\Command;

class ReviewDuplicateCharges extends Command
{
    protected $signature = 'payments:review-duplicate-charges
        {--attempt= : Review one PaymentAttempt ID}
        {--invoice= : Review potential duplicates for one Invoice ID}
        {--limit=50 : Maximum number of attempts to return (1-500)}
        {--older-than= : Minimum age in minutes, based on gateway_succeeded_at}
        {--verify-gateway : Perform read-only getTransaction() lookups}';

    protected $description = 'Review potential duplicate external charges without changing financial state';

    public function handle(PaymentDuplicateChargeReviewService $review): int
    {
        $attemptId = $this->positiveIntegerOption('attempt');
        $invoiceId = $this->positiveIntegerOption('invoice');
        $limit = $this->positiveIntegerOption('limit');
        $olderThan = $this->nonNegativeIntegerOption('older-than');

        if ($attemptId === false || $invoiceId === false || $limit === false || $olderThan === false) {
            return self::INVALID;
        }

        if ($limit > 500) {
            $this->error('The --limit option must not exceed 500.');

            return self::INVALID;
        }

        $verifyGateway = (bool) $this->option('verify-gateway');

        $this->info($verifyGateway
            ? 'READ ONLY: getTransaction() lookups enabled; no database or gateway writes will be made.'
            : 'READ ONLY: database review only; no gateway API calls or writes will be made.');

        $rows = $review->review(
            attemptId: $attemptId ?: null,
            invoiceId: $invoiceId ?: null,
            limit: $limit,
            olderThanMinutes: $olderThan === '' ? null : $olderThan,
            verifyGateway: $verifyGateway,
        );

        if ($rows->isEmpty()) {
            $this->warn('No potential duplicate charges matched the requested filters.');

            return self::SUCCESS;
        }

        if ($rows->contains(
            'classification',
            PaymentDuplicateChargeReviewService::CLASSIFICATION_SHARED_TRANSACTION_ID,
        )) {
            $this->warn('WARNING: the same external transaction identifier appears on both attempts; this does not prove a second charge.');
        }

        $this->table([
            'Attempt', 'Invoice', 'Number', 'Client', 'Gateway', 'Session', 'Transaction',
            'Amount', 'Currency', 'Gateway succeeded', 'Settled', 'Winner',
            'Winner transaction', 'Invoice status', 'Classification', 'Txn reused', 'Verification',
        ], $rows->map(fn (array $row): array => array_values($row))->all());

        return self::SUCCESS;
    }

    private function positiveIntegerOption(string $name): int|false
    {
        $value = $this->option($name);

        if ($value === null || $value === '') {
            return $name === 'limit' ? 50 : 0;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1) {
            $this->error("The --{$name} option must be a positive integer.");

            return false;
        }

        return (int) $value;
    }

    private function nonNegativeIntegerOption(string $name): int|string|false
    {
        $value = $this->option($name);

        if ($value === null || $value === '') {
            return '';
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 0) {
            $this->error("The --{$name} option must be a non-negative integer.");

            return false;
        }

        return (int) $value;
    }
}
