<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\DTOs\PaymentSession;
use App\Payments\DTOs\RefundResult;
use App\Payments\DTOs\TransactionStatus;
use App\Payments\DTOs\WebhookEvent;
use App\Payments\Exceptions\PaymentException;
use App\Services\Payments\PaymentDuplicateChargeReviewService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentDuplicateChargeReviewTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    protected function setUp(): void
    {
        parent::setUp();

        ReviewGatewayFake::reset();
        config()->set('payment.gateways.review_gateway', ReviewGatewayFake::class);
    }

    public function test_command_detects_only_non_winning_external_success_for_a_paid_invoice(): void
    {
        [$invoice, $winner, $additional] = $this->makePotentialDuplicate();
        $unpaid = $this->makeInvoice('unpaid');
        $this->makeAttempt($unpaid, ['gateway_succeeded_at' => now()]);
        $this->makeAttempt($invoice);

        $this->artisan('payments:review-duplicate-charges', ['--invoice' => $invoice->id])
            ->expectsOutputToContain('READ ONLY: database review only')
            ->expectsOutputToContain((string) $additional->id)
            ->assertSuccessful();

        $rows = app(PaymentDuplicateChargeReviewService::class)->review(invoiceId: $invoice->id);

        $this->assertCount(1, $rows);
        $this->assertSame($additional->id, $rows->first()['attempt_id']);
        $this->assertNotSame($winner->id, $rows->first()['attempt_id']);
        $this->assertSame('no', $rows->first()['transaction_id_reused']);
        $this->assertSame(0, ReviewGatewayFake::$lookupCount);
    }

    public function test_local_classifications_cover_shared_distinct_refunded_and_inconsistent_states(): void
    {
        [$sharedInvoice, $sharedWinner] = $this->makePotentialDuplicate('txn-shared', 'txn-shared');
        [$distinctInvoice] = $this->makePotentialDuplicate('txn-winner', 'txn-additional');
        [$refundedInvoice] = $this->makePotentialDuplicate('txn-winner-refund', 'txn-refunded', [
            'status' => PaymentAttempt::STATUS_REFUNDED,
            'refunded_at' => now(),
        ]);

        $wrongInvoice = $this->makeInvoice('paid');
        $wrongWinner = $this->makeAttempt($wrongInvoice, [
            'status' => PaymentAttempt::STATUS_SUCCEEDED,
            'settled_at' => now(),
            'gateway_transaction_id' => 'txn-wrong-winner',
        ]);
        $inconsistentInvoice = $this->makeInvoice('paid');
        $inconsistent = $this->makeAttempt($inconsistentInvoice, [
            'gateway_succeeded_at' => now(),
            'gateway_transaction_id' => 'txn-inconsistent',
        ]);
        $inconsistentInvoice->update(['payment_attempt_id' => $wrongWinner->id]);

        $service = app(PaymentDuplicateChargeReviewService::class);

        $this->artisan('payments:review-duplicate-charges', ['--invoice' => $sharedInvoice->id])
            ->expectsOutputToContain('the same external transaction identifier appears on both attempts')
            ->assertSuccessful();

        $this->assertSame(
            PaymentDuplicateChargeReviewService::CLASSIFICATION_SHARED_TRANSACTION_ID,
            $service->review(invoiceId: $sharedInvoice->id)->first()['classification'],
        );
        $this->assertSame('yes', $service->review(invoiceId: $sharedInvoice->id)->first()['transaction_id_reused']);
        $this->assertSame($sharedWinner->gateway_transaction_id, $service->review(invoiceId: $sharedInvoice->id)->first()['winning_transaction_id']);
        $this->assertSame(
            PaymentDuplicateChargeReviewService::CLASSIFICATION_DISTINCT_TRANSACTION_IDS,
            $service->review(invoiceId: $distinctInvoice->id)->first()['classification'],
        );
        $this->assertSame(
            PaymentDuplicateChargeReviewService::CLASSIFICATION_ALREADY_REFUNDED,
            $service->review(invoiceId: $refundedInvoice->id)->first()['classification'],
        );
        $this->assertSame(
            PaymentDuplicateChargeReviewService::CLASSIFICATION_INCONSISTENT_SETTLEMENT_STATE,
            $service->review(attemptId: $inconsistent->id)->first()['classification'],
        );
    }

    public function test_verify_gateway_uses_only_get_transaction_and_maps_all_results(): void
    {
        $statuses = [
            'txn-success' => TransactionStatus::STATUS_SUCCEEDED,
            'txn-failed' => TransactionStatus::STATUS_FAILED,
            'txn-pending' => TransactionStatus::STATUS_PENDING,
            'txn-refunded' => TransactionStatus::STATUS_REFUNDED,
            'txn-unknown' => 'unknown',
        ];

        foreach ($statuses as $transactionId => $status) {
            ReviewGatewayFake::$transactions[$transactionId] = new TransactionStatus(
                $transactionId,
                $status,
                2500,
                $status === TransactionStatus::STATUS_SUCCEEDED ? ' usd ' : 'USD',
            );
            [$invoice] = $this->makePotentialDuplicate('winner-'.$transactionId, $transactionId);
            $invoices[$transactionId] = $invoice;
        }

        $expected = [
            'txn-success' => PaymentDuplicateChargeReviewService::VERIFICATION_SUCCEEDED,
            'txn-failed' => PaymentDuplicateChargeReviewService::VERIFICATION_FAILED,
            'txn-pending' => PaymentDuplicateChargeReviewService::VERIFICATION_PENDING,
            'txn-refunded' => PaymentDuplicateChargeReviewService::VERIFICATION_REFUNDED,
            'txn-unknown' => PaymentDuplicateChargeReviewService::VERIFICATION_INDETERMINATE,
        ];

        $service = app(PaymentDuplicateChargeReviewService::class);

        foreach ($expected as $transactionId => $verification) {
            $row = $service->review(invoiceId: $invoices[$transactionId]->id, verifyGateway: true)->first();
            $this->assertSame($verification, $row['verification']);
        }

        $this->assertSame(count($expected), ReviewGatewayFake::$lookupCount);
        $this->assertSame(0, ReviewGatewayFake::$writeCallCount);
    }

    public function test_succeeded_verification_requires_matching_financial_and_transaction_identity(): void
    {
        $cases = [
            'txn-amount-mismatch' => [2600, 'USD', 'txn-amount-mismatch', PaymentDuplicateChargeReviewService::VERIFICATION_AMOUNT_MISMATCH],
            'txn-currency-mismatch' => [2500, 'ILS', 'txn-currency-mismatch', PaymentDuplicateChargeReviewService::VERIFICATION_CURRENCY_MISMATCH],
            'txn-both-mismatch' => [2600, 'ILS', 'txn-both-mismatch', PaymentDuplicateChargeReviewService::VERIFICATION_AMOUNT_CURRENCY_MISMATCH],
            'txn-identity-mismatch' => [2500, 'USD', 'txn-other', PaymentDuplicateChargeReviewService::VERIFICATION_TRANSACTION_IDENTITY_MISMATCH],
            'txn-null-amount' => [null, 'USD', 'txn-null-amount', PaymentDuplicateChargeReviewService::VERIFICATION_INDETERMINATE],
            'txn-null-currency' => [2500, null, 'txn-null-currency', PaymentDuplicateChargeReviewService::VERIFICATION_INDETERMINATE],
        ];

        foreach ($cases as $attemptTransactionId => [$amount, $currency, $returnedTransactionId, $expected]) {
            [$invoice, , $additional] = $this->makePotentialDuplicate(
                'winner-'.$attemptTransactionId,
                $attemptTransactionId,
            );
            ReviewGatewayFake::$transactions[$attemptTransactionId] = new TransactionStatus(
                $returnedTransactionId,
                TransactionStatus::STATUS_SUCCEEDED,
                $amount,
                $currency,
            );
            $beforeInvoice = $invoice->fresh()->getAttributes();
            $beforeAdditional = $additional->fresh()->getAttributes();

            $row = app(PaymentDuplicateChargeReviewService::class)->review(
                attemptId: $additional->id,
                verifyGateway: true,
            )->first();

            $this->assertSame($expected, $row['verification']);
            $this->assertNotSame(
                PaymentDuplicateChargeReviewService::VERIFICATION_SUCCEEDED,
                $row['verification'],
            );
            $this->assertSame($beforeInvoice, $invoice->fresh()->getAttributes());
            $this->assertSame($beforeAdditional, $additional->fresh()->getAttributes());
        }

        $this->assertSame(count($cases), ReviewGatewayFake::$lookupCount);
        $this->assertSame(0, ReviewGatewayFake::$writeCallCount);
    }

    public function test_succeeded_verification_checks_gateway_amount_against_both_attempt_and_invoice(): void
    {
        [$invoice, , $additional] = $this->makePotentialDuplicate(
            'winner-local-amount-mismatch',
            'txn-local-amount-mismatch',
            ['gateway_amount_cents' => 2600],
        );
        ReviewGatewayFake::$transactions[$additional->gateway_transaction_id] = new TransactionStatus(
            $additional->gateway_transaction_id,
            TransactionStatus::STATUS_SUCCEEDED,
            2600,
            'USD',
        );

        $row = app(PaymentDuplicateChargeReviewService::class)->review(
            invoiceId: $invoice->id,
            verifyGateway: true,
        )->first();

        $this->assertSame(
            PaymentDuplicateChargeReviewService::VERIFICATION_AMOUNT_MISMATCH,
            $row['verification'],
        );
        $this->assertSame(2500, $invoice->fresh()->total_cents);
        $this->assertSame(2600, $additional->fresh()->gateway_amount_cents);
        $this->assertSame(0, ReviewGatewayFake::$writeCallCount);
    }

    public function test_missing_transaction_and_gateway_failures_are_reported_without_stopping_review(): void
    {
        [$noIdInvoice] = $this->makePotentialDuplicate('winner-no-id', null);
        [$failureInvoice] = $this->makePotentialDuplicate('winner-failures', 'txn-unavailable');
        $additionalNotFound = $this->makeAttempt($failureInvoice, [
            'gateway_succeeded_at' => now()->subMinute(),
            'gateway_transaction_id' => 'txn-not-found',
        ]);
        ReviewGatewayFake::$errors['txn-unavailable'] = new PaymentException('temporary outage');
        ReviewGatewayFake::$errors['txn-not-found'] = new PaymentException('404 transaction not found');

        $service = app(PaymentDuplicateChargeReviewService::class);

        $this->assertSame(
            PaymentDuplicateChargeReviewService::VERIFICATION_NO_TRANSACTION_ID,
            $service->review(invoiceId: $noIdInvoice->id, verifyGateway: true)->first()['verification'],
        );

        $rows = $service->review(invoiceId: $failureInvoice->id, verifyGateway: true)->keyBy('attempt_id');
        $this->assertSame(
            PaymentDuplicateChargeReviewService::VERIFICATION_GATEWAY_UNAVAILABLE,
            $rows->first()['verification'],
        );
        $this->assertSame(
            PaymentDuplicateChargeReviewService::VERIFICATION_TRANSACTION_NOT_FOUND,
            $rows[$additionalNotFound->id]['verification'],
        );
        $this->assertSame(2, ReviewGatewayFake::$lookupCount);

        $this->artisan('payments:review-duplicate-charges', [
            '--invoice' => $failureInvoice->id,
            '--verify-gateway' => true,
        ])->assertSuccessful();
        $this->assertSame(4, ReviewGatewayFake::$lookupCount);
    }

    public function test_review_and_verification_do_not_change_financial_records(): void
    {
        [$invoice, $winner, $additional] = $this->makePotentialDuplicate();
        $order = Order::query()->create([
            'client_id' => $invoice->client_id,
            'status' => Order::STATUS_ACTIVE,
            'type' => 'service',
        ]);
        $invoice->update(['order_id' => $order->id]);
        $winner->update(['order_id' => $order->id]);
        $additional->update(['order_id' => $order->id]);
        ReviewGatewayFake::$transactions[$additional->gateway_transaction_id] = new TransactionStatus(
            $additional->gateway_transaction_id,
            TransactionStatus::STATUS_SUCCEEDED,
            2500,
            'USD',
        );
        $beforeInvoice = $invoice->fresh()->getAttributes();
        $beforeWinner = $winner->fresh()->getAttributes();
        $beforeAdditional = $additional->fresh()->getAttributes();
        $beforeOrder = $order->fresh()->getAttributes();

        $this->artisan('payments:review-duplicate-charges', [
            '--attempt' => $additional->id,
            '--verify-gateway' => true,
        ])->expectsOutputToContain('verified_succeeded')->assertSuccessful();

        $this->assertSame($beforeInvoice, $invoice->fresh()->getAttributes());
        $this->assertSame($beforeWinner, $winner->fresh()->getAttributes());
        $this->assertSame($beforeAdditional, $additional->fresh()->getAttributes());
        $this->assertSame($beforeOrder, $order->fresh()->getAttributes());
        $this->assertSame(2, PaymentAttempt::query()->where('invoice_id', $invoice->id)->count());
        $this->assertSame(0, ReviewGatewayFake::$writeCallCount);
    }

    /** @return array{Invoice, PaymentAttempt, PaymentAttempt} */
    private function makePotentialDuplicate(
        ?string $winnerTransactionId = 'txn-winner',
        ?string $additionalTransactionId = 'txn-additional',
        array $additionalOverrides = [],
    ): array {
        $invoice = $this->makeInvoice('paid');
        $winner = $this->makeAttempt($invoice, [
            'status' => PaymentAttempt::STATUS_SUCCEEDED,
            'gateway_transaction_id' => $winnerTransactionId,
            'gateway_succeeded_at' => now()->subMinutes(3),
            'settled_at' => now()->subMinutes(2),
        ]);
        $additional = $this->makeAttempt($invoice, array_merge([
            'gateway_transaction_id' => $additionalTransactionId,
            'gateway_succeeded_at' => now()->subMinute(),
        ], $additionalOverrides));
        $invoice->update(['payment_attempt_id' => $winner->id]);

        return [$invoice, $winner, $additional];
    }

    private function makeAttempt(Invoice $invoice, array $overrides = []): PaymentAttempt
    {
        return PaymentAttempt::query()->create(array_merge([
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'gateway' => 'review_gateway',
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
            'first_name' => 'Duplicate',
            'last_name' => 'Review',
            'email' => Str::lower(Str::random(16)).'@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Duplicate Review Test',
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
}

class ReviewGatewayFake implements PaymentGatewayInterface
{
    /** @var array<string, TransactionStatus> */
    public static array $transactions = [];

    /** @var array<string, \Throwable> */
    public static array $errors = [];

    public static int $lookupCount = 0;

    public static int $writeCallCount = 0;

    public static function reset(): void
    {
        self::$transactions = [];
        self::$errors = [];
        self::$lookupCount = 0;
        self::$writeCallCount = 0;
    }

    public function name(): string
    {
        return 'review_gateway';
    }

    public function createSession(Invoice $invoice, string $idempotencyKey, string $returnUrl, string $cancelUrl): PaymentSession
    {
        self::$writeCallCount++;

        throw new PaymentException('Unexpected createSession call.');
    }

    public function verifyWebhook(string $rawPayload, string $signatureHeader): WebhookEvent
    {
        self::$writeCallCount++;

        throw new PaymentException('Unexpected verifyWebhook call.');
    }

    public function getTransaction(string $gatewayTransactionId): TransactionStatus
    {
        self::$lookupCount++;

        if (isset(self::$errors[$gatewayTransactionId])) {
            throw self::$errors[$gatewayTransactionId];
        }

        return self::$transactions[$gatewayTransactionId]
            ?? throw new PaymentException('404 transaction not found');
    }

    public function refund(string $gatewayTransactionId, int $amountCents): RefundResult
    {
        self::$writeCallCount++;

        throw new PaymentException('Unexpected refund call.');
    }
}
