<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\PaymentAttempt;
use App\Models\PaymentDuplicateChargeReview;
use App\Models\User;
use App\Payments\PaymentManager;
use App\Services\Payments\PaymentDuplicateChargeReviewRecorder;
use App\Services\Payments\PaymentDuplicateChargeReviewService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class PaymentDuplicateChargeReviewHistoryTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    public function test_current_candidate_accepts_review_and_captures_safe_immutable_evidence(): void
    {
        [$invoice, $winner, $additional] = $this->makeCandidate();
        $reviewer = $this->makeReviewer('Financial Reviewer');
        $additional->update([
            'gateway_response' => [
                'secret' => 'must-not-be-copied',
                'raw_webhook' => ['card' => 'must-not-be-copied'],
            ],
        ]);
        $beforeInvoice = $invoice->fresh()->getAttributes();
        $beforeWinner = $winner->fresh()->getAttributes();
        $beforeAdditional = $additional->fresh()->getAttributes();

        $this->assertSame(0, $additional->duplicateChargeReviews()->count());

        $review = $this->recorder()->record(
            $additional,
            $reviewer,
            PaymentDuplicateChargeReview::STATUS_NEEDS_FOLLOW_UP,
            note: 'Provider confirmation is still required.',
        );

        $this->assertSame($additional->id, $review->payment_attempt_id);
        $this->assertSame($reviewer->id, $review->reviewed_by);
        $this->assertSame('Financial Reviewer', $review->reviewer_name);
        $this->assertTrue($review->needs_follow_up);
        $this->assertNull($review->resolution);
        $this->assertNull($review->verification_result);
        $this->assertNull($review->verification_checked_at);
        $this->assertSame(
            PaymentDuplicateChargeReviewService::CLASSIFICATION_DISTINCT_TRANSACTION_IDS,
            $review->detection_classification,
        );
        $this->assertSame([
            'payment_attempt_id' => $additional->id,
            'invoice_id' => $invoice->id,
            'winning_payment_attempt_id' => $winner->id,
            'gateway' => 'review_history_gateway',
            'gateway_session_id' => $additional->gateway_session_id,
            'gateway_transaction_id' => 'txn-additional',
            'gateway_amount_cents' => 2500,
            'currency' => 'USD',
            'gateway_succeeded_at' => $additional->gateway_succeeded_at->toDateTimeString(),
            'settled_at' => null,
            'attempt_status' => PaymentAttempt::STATUS_INITIATED,
            'invoice_status' => 'paid',
            'detection_classification' => PaymentDuplicateChargeReviewService::CLASSIFICATION_DISTINCT_TRANSACTION_IDS,
        ], $review->evidence_snapshot);
        $snapshotJson = json_encode($review->evidence_snapshot, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('gateway_response', $snapshotJson);
        $this->assertStringNotContainsString('must-not-be-copied', $snapshotJson);
        $this->assertSame($beforeInvoice, $invoice->fresh()->getAttributes());
        $this->assertSame($beforeWinner, $winner->fresh()->getAttributes());
        $this->assertSame($beforeAdditional, $additional->fresh()->getAttributes());
        $this->assertSame(2, PaymentAttempt::query()->where('invoice_id', $invoice->id)->count());
    }

    public function test_non_candidate_attempt_is_rejected_without_creating_history(): void
    {
        $invoice = $this->makeInvoice('unpaid');
        $attempt = $this->makeAttempt($invoice, [
            'gateway_succeeded_at' => now(),
            'gateway_transaction_id' => 'txn-not-candidate',
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('is not a current duplicate-charge candidate');

        try {
            $this->recorder()->record(
                $attempt,
                $this->makeReviewer(),
                PaymentDuplicateChargeReview::STATUS_NEEDS_FOLLOW_UP,
            );
        } finally {
            $this->assertSame(0, PaymentDuplicateChargeReview::query()->count());
        }
    }

    public function test_all_supported_status_and_resolution_combinations_are_enforced(): void
    {
        [, , $attempt] = $this->makeCandidate();
        $reviewer = $this->makeReviewer();
        $accepted = [
            [PaymentDuplicateChargeReview::STATUS_NEEDS_FOLLOW_UP, null, true],
            [PaymentDuplicateChargeReview::STATUS_NEEDS_FOLLOW_UP, PaymentDuplicateChargeReview::RESOLUTION_CONFIRMED_DUPLICATE, true],
            [PaymentDuplicateChargeReview::STATUS_RESOLVED, PaymentDuplicateChargeReview::RESOLUTION_CONFIRMED_DUPLICATE, false],
            [PaymentDuplicateChargeReview::STATUS_RESOLVED, PaymentDuplicateChargeReview::RESOLUTION_NOT_DUPLICATE, false],
        ];

        foreach ($accepted as [$status, $resolution, $needsFollowUp]) {
            $review = $this->recorder()->record($attempt, $reviewer, $status, $resolution);
            $this->assertSame($status, $review->review_status);
            $this->assertSame($resolution, $review->resolution);
            $this->assertSame($needsFollowUp, $review->needs_follow_up);
        }

        $invalid = [
            [PaymentDuplicateChargeReview::STATUS_RESOLVED, null],
            [PaymentDuplicateChargeReview::STATUS_NEEDS_FOLLOW_UP, PaymentDuplicateChargeReview::RESOLUTION_NOT_DUPLICATE],
            [PaymentDuplicateChargeReview::STATUS_RESOLVED, 'already_refunded'],
            [PaymentDuplicateChargeReview::STATUS_RESOLVED, 'unknown_resolution'],
            ['unknown_status', null],
        ];

        foreach ($invalid as [$status, $resolution]) {
            try {
                $this->recorder()->record($attempt, $reviewer, $status, $resolution);
                $this->fail("Invalid review contract was accepted: {$status}/{$resolution}");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertSame(count($accepted), $attempt->duplicateChargeReviews()->count());
    }

    public function test_history_is_append_only_and_current_review_uses_reviewed_at_then_id(): void
    {
        Carbon::setTestNow('2026-08-28 12:00:00');
        [, , $attempt] = $this->makeCandidate();
        $reviewer = $this->makeReviewer();

        $first = $this->recorder()->record(
            $attempt,
            $reviewer,
            PaymentDuplicateChargeReview::STATUS_NEEDS_FOLLOW_UP,
            note: 'First decision.',
        );
        $firstSnapshot = $first->fresh()->getAttributes();
        $second = $this->recorder()->record(
            $attempt,
            $reviewer,
            PaymentDuplicateChargeReview::STATUS_RESOLVED,
            PaymentDuplicateChargeReview::RESOLUTION_CONFIRMED_DUPLICATE,
            'Second decision.',
        );

        $this->assertSame(2, $attempt->duplicateChargeReviews()->count());
        $this->assertSame($firstSnapshot, $first->fresh()->getAttributes());
        $this->assertTrue($attempt->currentDuplicateChargeReview()->first()->is($second));
        $this->assertSame($first->reviewed_at->toDateTimeString(), $second->reviewed_at->toDateTimeString());
        $this->assertGreaterThan($first->id, $second->id);

        Carbon::setTestNow();
    }

    public function test_reviewer_deletion_nulls_fk_but_preserves_name_snapshot(): void
    {
        [, , $attempt] = $this->makeCandidate();
        $reviewer = $this->makeReviewer('Reviewer To Delete');
        $review = $this->recorder()->record(
            $attempt,
            $reviewer,
            PaymentDuplicateChargeReview::STATUS_RESOLVED,
            PaymentDuplicateChargeReview::RESOLUTION_NOT_DUPLICATE,
        );

        $reviewer->delete();
        $review = $review->fresh();

        $this->assertNull($review->reviewed_by);
        $this->assertSame('Reviewer To Delete', $review->reviewer_name);
        $this->assertDatabaseHas('payment_duplicate_charge_reviews', ['id' => $review->id]);
    }

    public function test_payment_attempt_deletion_is_restricted_when_review_history_exists(): void
    {
        [, , $attempt] = $this->makeCandidate();
        $review = $this->recorder()->record(
            $attempt,
            $this->makeReviewer(),
            PaymentDuplicateChargeReview::STATUS_NEEDS_FOLLOW_UP,
        );

        try {
            $attempt->delete();
            $this->fail('PaymentAttempt deletion unexpectedly removed durable review history.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }

        $this->assertDatabaseHas('payment_attempts', ['id' => $attempt->id]);
        $this->assertDatabaseHas('payment_duplicate_charge_reviews', ['id' => $review->id]);
    }

    public function test_recorder_never_calls_gateway_or_financial_mutation_services(): void
    {
        $manager = Mockery::mock(PaymentManager::class);
        $manager->shouldNotReceive('gateway');
        $this->app->instance(PaymentManager::class, $manager);
        [$invoice, $winner, $additional] = $this->makeCandidate('txn-shared', 'txn-shared');
        $beforeInvoice = $invoice->fresh()->getAttributes();
        $beforeWinner = $winner->fresh()->getAttributes();
        $beforeAdditional = $additional->fresh()->getAttributes();

        $review = $this->recorder()->record(
            $additional,
            $this->makeReviewer(),
            PaymentDuplicateChargeReview::STATUS_RESOLVED,
            PaymentDuplicateChargeReview::RESOLUTION_NOT_DUPLICATE,
        );

        $this->assertSame(
            PaymentDuplicateChargeReviewService::CLASSIFICATION_SHARED_TRANSACTION_ID,
            $review->detection_classification,
        );
        $this->assertSame($beforeInvoice, $invoice->fresh()->getAttributes());
        $this->assertSame($beforeWinner, $winner->fresh()->getAttributes());
        $this->assertSame($beforeAdditional, $additional->fresh()->getAttributes());
        $this->assertSame(2, PaymentAttempt::query()->count());

        $source = file_get_contents(app_path('Services/Payments/PaymentDuplicateChargeReviewRecorder.php'));
        $this->assertStringNotContainsString('getTransaction(', $source);
        $this->assertStringNotContainsString('refund(', $source);
        $this->assertStringNotContainsString('markPaid(', $source);
        $this->assertStringNotContainsString('dispatch(', $source);
    }

    private function recorder(): PaymentDuplicateChargeReviewRecorder
    {
        return app(PaymentDuplicateChargeReviewRecorder::class);
    }

    /** @return array{Invoice, PaymentAttempt, PaymentAttempt} */
    private function makeCandidate(
        string $winnerTransactionId = 'txn-winner',
        string $additionalTransactionId = 'txn-additional',
    ): array {
        $invoice = $this->makeInvoice('paid');
        $winner = $this->makeAttempt($invoice, [
            'status' => PaymentAttempt::STATUS_SUCCEEDED,
            'gateway_transaction_id' => $winnerTransactionId,
            'gateway_succeeded_at' => now()->subMinutes(3),
            'settled_at' => now()->subMinutes(2),
        ]);
        $additional = $this->makeAttempt($invoice, [
            'gateway_transaction_id' => $additionalTransactionId,
            'gateway_succeeded_at' => now()->subMinute(),
        ]);
        $invoice->update(['payment_attempt_id' => $winner->id]);

        return [$invoice, $winner, $additional];
    }

    private function makeAttempt(Invoice $invoice, array $overrides = []): PaymentAttempt
    {
        return PaymentAttempt::query()->create(array_merge([
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'gateway' => 'review_history_gateway',
            'idempotency_key' => (string) Str::uuid(),
            'gateway_session_id' => 'session-'.Str::uuid(),
            'gateway_amount_cents' => 2500,
            'currency' => 'USD',
            'status' => PaymentAttempt::STATUS_INITIATED,
        ], $overrides));
    }

    private function makeInvoice(string $status): Invoice
    {
        $client = Client::query()->create([
            'first_name' => 'Review',
            'last_name' => 'History',
            'email' => Str::lower(Str::random(16)).'@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Review History Test',
            'can_login' => true,
        ]);

        return Invoice::query()->create([
            'client_id' => $client->id,
            'number' => 'INV-'.strtoupper(Str::random(12)),
            'status' => $status,
            'subtotal_cents' => 2500,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 2500,
            'currency' => 'USD',
            'payment_session_status' => Invoice::PAYMENT_SESSION_IDLE,
        ]);
    }

    private function makeReviewer(string $name = 'Payment Reviewer'): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => Str::lower(Str::random(16)).'@example.test',
            'password' => bcrypt('secret-password'),
            'super_admin' => true,
        ]);
    }
}
