<?php

namespace Tests\Feature;

use App\Jobs\ProvisionSubscription;
use App\Models\Client;
use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\Plan;
use App\Models\Server;
use App\Models\Tenancy\Subscription;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\DTOs\TransactionStatus;
use App\Payments\DTOs\WebhookEvent;
use App\Payments\Exceptions\PaymentException;
use App\Payments\Exceptions\WebhookVerificationException;
use App\Payments\PaymentManager;
use App\Services\Billing\InvoiceSettlementService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class PaymentExternalSuccessEvidenceTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    public function test_winning_success_records_gateway_evidence_and_local_settlement_once(): void
    {
        [$invoice, $attempt] = $this->makeInvoiceAndAttempt('winner-evidence');
        $event = $this->successEvent($invoice, $attempt, 'txn-winner');

        $this->sendEvent($event)->assertStatus(202);
        $firstEvidenceAt = $attempt->fresh()->gateway_succeeded_at;

        $this->sendEvent($event, null, false)->assertStatus(202);

        $attempt = $attempt->fresh();
        $this->assertNotNull($firstEvidenceAt);
        $this->assertTrue($firstEvidenceAt->equalTo($attempt->gateway_succeeded_at));
        $this->assertSame(PaymentAttempt::STATUS_SUCCEEDED, $attempt->status);
        $this->assertNotNull($attempt->settled_at);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame($attempt->id, $invoice->fresh()->payment_attempt_id);
    }

    public function test_additional_success_is_durable_detectable_and_has_no_duplicate_side_effects(): void
    {
        Queue::fake();
        [$invoice, $winner, $additional, $coupon, $subscription, $order] = $this->makeProvisionableInvoiceWithTwoAttempts();

        $this->sendEvent($this->successEvent($invoice, $winner, 'txn-winner'))->assertStatus(202);
        $this->sendEvent($this->successEvent($invoice, $additional, 'txn-additional'))->assertStatus(202);

        $additional = $additional->fresh();
        $firstEvidenceAt = $additional->gateway_succeeded_at;
        $this->assertNotNull($firstEvidenceAt);
        $this->assertSame(PaymentAttempt::STATUS_INITIATED, $additional->status);
        $this->assertNull($additional->settled_at);
        $this->assertSame('txn-additional', $additional->gateway_transaction_id);
        $this->assertNotNull($additional->webhook_verified_at);
        $this->assertSame(WebhookEvent::TYPE_PAYMENT_SUCCEEDED, $additional->gateway_status_raw);

        $successSnapshot = $this->gatewaySuccessSnapshot($additional);

        $this->sendEvent($this->successEvent($invoice, $additional, 'txn-additional'))->assertStatus(202);
        $this->assertSame($successSnapshot, $this->gatewaySuccessSnapshot($additional->fresh()));

        $this->sendEvent($this->successEvent($invoice, $additional, 'txn-different'))->assertStatus(202);
        $this->assertSame($successSnapshot, $this->gatewaySuccessSnapshot($additional->fresh()));

        $this->sendEvent($this->failureEvent($additional))->assertStatus(202);
        $this->assertSame($successSnapshot, $this->gatewaySuccessSnapshot($additional->fresh()));

        $invoice = $invoice->fresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertSame($winner->id, $invoice->payment_attempt_id);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertSame(1, $coupon->fresh()->used_count);
        Queue::assertPushed(ProvisionSubscription::class, 1);

        $detectedIds = PaymentAttempt::query()
            ->join('invoices', 'invoices.id', '=', 'payment_attempts.invoice_id')
            ->whereNotNull('payment_attempts.gateway_succeeded_at')
            ->whereNull('payment_attempts.settled_at')
            ->where('invoices.status', 'paid')
            ->whereColumn('invoices.payment_attempt_id', '!=', 'payment_attempts.id')
            ->pluck('payment_attempts.id')
            ->all();

        $this->assertSame([$additional->id], $detectedIds);
    }

    public function test_failed_then_verified_success_still_reaches_settlement(): void
    {
        [$invoice, $attempt] = $this->makeInvoiceAndAttempt('failed-recovery');

        $this->sendEvent($this->failureEvent($attempt))->assertStatus(202);
        $this->assertSame(PaymentAttempt::STATUS_FAILED, $attempt->fresh()->status);
        $this->assertNull($attempt->fresh()->gateway_succeeded_at);

        $this->sendEvent($this->successEvent($invoice, $attempt, 'txn-recovered'))->assertStatus(202);

        $attempt = $attempt->fresh();
        $this->assertNotNull($attempt->gateway_succeeded_at);
        $this->assertSame(PaymentAttempt::STATUS_SUCCEEDED, $attempt->status);
        $this->assertNotNull($attempt->settled_at);
        $this->assertSame($attempt->id, $invoice->fresh()->payment_attempt_id);
    }

    public function test_unverified_or_mismatched_success_never_records_gateway_evidence(): void
    {
        [$invalidInvoice, $invalidAttempt] = $this->makeInvoiceAndAttempt('invalid-signature');
        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('name')->andReturn('lahza');
        $gateway->shouldReceive('verifyWebhook')
            ->once()
            ->andThrow(new WebhookVerificationException('invalid signature'));
        $this->bindGateway($gateway);

        $this->postJson(route('payment.webhook', ['gateway' => 'lahza']), [], [
            'x-lahza-signature' => 'invalid',
        ])->assertStatus(401);
        $this->assertNull($invalidAttempt->fresh()->gateway_succeeded_at);
        $this->assertSame('unpaid', $invalidInvoice->fresh()->status);

        [$amountInvoice, $amountAttempt] = $this->makeInvoiceAndAttempt('amount-mismatch');
        $amountEvent = new WebhookEvent(
            WebhookEvent::TYPE_PAYMENT_SUCCEEDED,
            $amountAttempt->gateway_session_id,
            null,
            $amountInvoice->total_cents + 1,
            $amountInvoice->currency,
            ['event' => 'charge.success'],
        );
        $this->sendEvent($amountEvent)->assertStatus(401);
        $this->assertNull($amountAttempt->fresh()->gateway_succeeded_at);

        [$currencyInvoice, $currencyAttempt] = $this->makeInvoiceAndAttempt('currency-mismatch');
        $currencyEvent = new WebhookEvent(
            WebhookEvent::TYPE_PAYMENT_SUCCEEDED,
            $currencyAttempt->gateway_session_id,
            null,
            $currencyInvoice->total_cents,
            'ILS',
            ['event' => 'charge.success'],
        );
        $this->sendEvent($currencyEvent)->assertStatus(401);
        $this->assertNull($currencyAttempt->fresh()->gateway_succeeded_at);
    }

    public function test_transaction_reconciliation_mismatch_does_not_record_gateway_evidence(): void
    {
        [$invoice, $attempt] = $this->makeInvoiceAndAttempt('transaction-mismatch');
        $event = $this->successEvent($invoice, $attempt, 'txn-mismatch');
        $transaction = new TransactionStatus(
            'txn-mismatch',
            TransactionStatus::STATUS_FAILED,
            $invoice->total_cents,
            $invoice->currency,
            ['status' => 'failed'],
        );

        $this->sendEvent($event, $transaction)->assertStatus(401);

        $this->assertNull($attempt->fresh()->gateway_succeeded_at);
        $this->assertSame(PaymentAttempt::STATUS_FAILED, $attempt->fresh()->status);
        $this->assertSame('unpaid', $invoice->fresh()->status);
    }

    public function test_technical_reconciliation_failure_preserves_signed_webhook_policy(): void
    {
        [$invoice, $attempt] = $this->makeInvoiceAndAttempt('transaction-unavailable');
        $event = $this->successEvent($invoice, $attempt, 'txn-unavailable');

        $this->sendEvent($event, new PaymentException('gateway temporarily unavailable'))
            ->assertStatus(202);

        $this->assertNotNull($attempt->fresh()->gateway_succeeded_at);
        $this->assertSame(PaymentAttempt::STATUS_SUCCEEDED, $attempt->fresh()->status);
        $this->assertSame($attempt->id, $invoice->fresh()->payment_attempt_id);
    }

    public function test_gateway_success_evidence_survives_local_settlement_failure(): void
    {
        [$invoice, $attempt] = $this->makeInvoiceAndAttempt('settlement-failure');
        $event = $this->successEvent($invoice, $attempt, 'txn-settlement-failure');
        $settlement = Mockery::mock(InvoiceSettlementService::class);
        $settlement->shouldReceive('markPaid')->once()->andThrow(new \RuntimeException('activation failed'));
        $this->app->instance(InvoiceSettlementService::class, $settlement);

        $this->sendEvent($event)->assertStatus(500);

        $attempt = $attempt->fresh();
        $this->assertNotNull($attempt->gateway_succeeded_at);
        $this->assertSame('txn-settlement-failure', $attempt->gateway_transaction_id);
        $this->assertSame(PaymentAttempt::STATUS_INITIATED, $attempt->status);
        $this->assertNull($attempt->settled_at);
        $this->assertSame('unpaid', $invoice->fresh()->status);
        $this->assertNull($invoice->fresh()->payment_attempt_id);
    }

    public function test_first_success_with_null_transaction_keeps_immutable_null_snapshot(): void
    {
        [$invoice, $winner] = $this->makeInvoiceAndAttempt('null-transaction-winner');
        $additional = $this->makeAttempt($invoice, 'null-transaction-additional');
        $this->sendEvent($this->successEvent($invoice, $winner, 'txn-winner'))->assertStatus(202);

        $this->sendEvent($this->successEvent($invoice, $additional, null))->assertStatus(202);
        $snapshot = $this->gatewaySuccessSnapshot($additional->fresh());
        $this->assertNull($snapshot['gateway_transaction_id']);

        $this->sendEvent($this->successEvent($invoice, $additional, 'txn-late'))->assertStatus(202);

        $this->assertSame($snapshot, $this->gatewaySuccessSnapshot($additional->fresh()));
        $this->assertSame($winner->id, $invoice->fresh()->payment_attempt_id);
    }

    public function test_existing_evidence_does_not_block_later_local_settlement_retry(): void
    {
        [$invoice, $attempt] = $this->makeInvoiceAndAttempt('settlement-retry');
        $event = $this->successEvent($invoice, $attempt, 'txn-settlement-retry');
        $settlement = Mockery::mock(InvoiceSettlementService::class);
        $settlement->shouldReceive('markPaid')->once()->andThrow(new \RuntimeException('activation failed'));
        $this->app->instance(InvoiceSettlementService::class, $settlement);

        $this->sendEvent($event)->assertStatus(500);
        $evidenceSnapshot = $this->gatewayEvidenceSnapshot($attempt->fresh());
        $this->app->forgetInstance(InvoiceSettlementService::class);

        $this->sendEvent($event)->assertStatus(202);

        $attempt = $attempt->fresh();
        $this->assertSame($evidenceSnapshot, $this->gatewayEvidenceSnapshot($attempt));
        $this->assertSame(PaymentAttempt::STATUS_SUCCEEDED, $attempt->status);
        $this->assertNotNull($attempt->settled_at);
        $this->assertSame($attempt->id, $invoice->fresh()->payment_attempt_id);
    }

    public function test_refunded_attempt_remains_immutable_against_success_and_failure(): void
    {
        [$invoice, $attempt] = $this->makeInvoiceAndAttempt('refunded-evidence');
        $attempt->update([
            'status' => PaymentAttempt::STATUS_REFUNDED,
            'gateway_transaction_id' => 'txn-refunded',
            'gateway_status_raw' => 'refund.succeeded',
            'gateway_response' => ['refund_id' => 'refund-one'],
            'webhook_verified_at' => now()->subMinutes(3),
            'gateway_succeeded_at' => now()->subMinutes(2),
            'settled_at' => now()->subMinutes(2),
            'refunded_at' => now()->subMinute(),
            'refund_amount_cents' => 2500,
        ]);
        $invoice->update([
            'status' => 'paid',
            'payment_attempt_id' => $attempt->id,
        ]);
        $snapshot = $attempt->fresh()->getAttributes();

        $this->sendEvent($this->successEvent($invoice, $attempt, 'txn-replayed'), null, false)
            ->assertStatus(202);
        $this->sendEvent($this->failureEvent($attempt))->assertStatus(202);

        $this->assertSame($snapshot, $attempt->fresh()->getAttributes());
    }

    public function test_schema_allows_same_gateway_transaction_id_on_two_attempts(): void
    {
        [$firstInvoice, $first] = $this->makeInvoiceAndAttempt('duplicate-transaction-one');
        [$secondInvoice, $second] = $this->makeInvoiceAndAttempt('duplicate-transaction-two');

        $first->update(['gateway_transaction_id' => 'txn-schema-duplicate']);
        $second->update(['gateway_transaction_id' => 'txn-schema-duplicate']);

        $this->assertNotSame($firstInvoice->id, $secondInvoice->id);
        $this->assertSame(2, PaymentAttempt::query()
            ->where('gateway', 'lahza')
            ->where('gateway_transaction_id', 'txn-schema-duplicate')
            ->count());
    }

    private function sendEvent(
        WebhookEvent $event,
        TransactionStatus|\Throwable|null $transaction = null,
        bool $expectTransactionLookup = true,
    )
    {
        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('name')->andReturn('lahza');
        $gateway->shouldReceive('verifyWebhook')->once()->andReturn($event);

        if ($expectTransactionLookup && $event->isPaymentSucceeded() && $event->transactionId !== null) {
            $transactionExpectation = $gateway->shouldReceive('getTransaction')
                ->once()
                ->with($event->transactionId);

            if ($transaction instanceof \Throwable) {
                $transactionExpectation->andThrow($transaction);
            } else {
                $transactionExpectation->andReturn($transaction ?? new TransactionStatus(
                    $event->transactionId,
                    TransactionStatus::STATUS_SUCCEEDED,
                    $event->amountCents,
                    $event->currency,
                    ['status' => 'success'],
                ));
            }
        } else {
            $gateway->shouldNotReceive('getTransaction');
        }

        $this->bindGateway($gateway);

        return $this->postJson(route('payment.webhook', ['gateway' => 'lahza']), [], [
            'x-lahza-signature' => 'verified-by-fake',
        ]);
    }

    private function bindGateway(PaymentGatewayInterface $gateway): void
    {
        $manager = Mockery::mock(PaymentManager::class);
        $manager->shouldReceive('gateway')->once()->andReturn($gateway);
        $this->app->instance(PaymentManager::class, $manager);
    }

    private function successEvent(Invoice $invoice, PaymentAttempt $attempt, ?string $transactionId): WebhookEvent
    {
        return new WebhookEvent(
            WebhookEvent::TYPE_PAYMENT_SUCCEEDED,
            $attempt->gateway_session_id,
            $transactionId,
            $invoice->total_cents,
            $invoice->currency,
            ['event' => 'charge.success', 'transaction_id' => $transactionId],
        );
    }

    private function failureEvent(PaymentAttempt $attempt): WebhookEvent
    {
        return new WebhookEvent(
            WebhookEvent::TYPE_PAYMENT_FAILED,
            $attempt->gateway_session_id,
            'txn-stale-failure',
            $attempt->gateway_amount_cents,
            $attempt->currency,
            ['event' => 'charge.failed'],
        );
    }

    private function gatewaySuccessSnapshot(PaymentAttempt $attempt): array
    {
        return [
            'status' => $attempt->status,
            'gateway_succeeded_at' => $attempt->gateway_succeeded_at?->toISOString(),
            'gateway_transaction_id' => $attempt->gateway_transaction_id,
            'gateway_status_raw' => $attempt->gateway_status_raw,
            'gateway_response' => $attempt->gateway_response,
            'webhook_verified_at' => $attempt->webhook_verified_at?->toISOString(),
            'settled_at' => $attempt->settled_at?->toISOString(),
        ];
    }

    private function gatewayEvidenceSnapshot(PaymentAttempt $attempt): array
    {
        return [
            'gateway_succeeded_at' => $attempt->gateway_succeeded_at?->toISOString(),
            'gateway_transaction_id' => $attempt->gateway_transaction_id,
            'gateway_status_raw' => $attempt->gateway_status_raw,
            'gateway_response' => $attempt->gateway_response,
            'webhook_verified_at' => $attempt->webhook_verified_at?->toISOString(),
        ];
    }

    private function makeInvoiceAndAttempt(string $sessionId): array
    {
        $client = $this->makeClient();
        $invoice = $this->makeInvoice($client);
        $attempt = $this->makeAttempt($invoice, $sessionId);
        $invoice->update([
            'payment_session_status' => Invoice::PAYMENT_SESSION_READY,
            'payment_session_attempt_id' => $attempt->id,
        ]);

        return [$invoice, $attempt];
    }

    private function makeProvisionableInvoiceWithTwoAttempts(): array
    {
        $client = $this->makeClient();
        $server = Server::query()->create([
            'name' => 'External Success WHM',
            'type' => 'cpanel',
            'hostname' => Str::lower(Str::random(12)) . '.example.test',
            'username' => 'root',
            'api_token' => 'test-token',
            'is_active' => true,
        ]);
        $plan = Plan::query()->create([
            'name' => 'External Success Plan',
            'slug' => 'external-success-' . Str::lower(Str::random(10)),
            'plan_type' => Plan::TYPE_HOSTING,
            'server_id' => $server->id,
            'server_package' => 'external_success_package',
            'is_active' => true,
        ]);
        $subscription = Subscription::query()->create([
            'client_id' => $client->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'provisioning_status' => Subscription::PROVISIONING_PENDING,
            'price_cents' => 2500,
            'billing_cycle' => 'monthly',
            'username' => Str::lower(Str::random(12)),
            'server_id' => $server->id,
            'server_package' => 'external_success_package',
            'domain_option' => 'subdomain',
            'domain_name' => Str::lower(Str::random(12)) . '.example.test',
            'subdomain' => Str::lower(Str::random(12)),
        ]);
        $order = Order::query()->create([
            'client_id' => $client->id,
            'status' => Order::STATUS_PENDING,
            'type' => 'subscription',
        ]);
        $coupon = Coupon::query()->create([
            'code' => 'EVIDENCE-' . strtoupper(Str::random(8)),
            'discount_type' => 'fixed',
            'discount_value' => 1,
            'used_count' => 0,
            'is_active' => true,
        ]);
        $invoice = $this->makeInvoice($client, $order, $coupon);
        InvoiceItem::query()->create([
            'invoice_id' => $invoice->id,
            'item_type' => 'subscription',
            'reference_id' => $subscription->id,
            'description' => 'External success subscription',
            'qty' => 1,
            'unit_price_cents' => 2500,
            'total_cents' => 2500,
        ]);
        $winner = $this->makeAttempt($invoice, 'external-winner');
        $additional = $this->makeAttempt($invoice, 'external-additional');
        $invoice->update([
            'payment_session_status' => Invoice::PAYMENT_SESSION_READY,
            'payment_session_attempt_id' => $winner->id,
        ]);

        return [$invoice, $winner, $additional, $coupon, $subscription, $order];
    }

    private function makeInvoice(Client $client, ?Order $order = null, ?Coupon $coupon = null): Invoice
    {
        return Invoice::query()->create([
            'client_id' => $client->id,
            'order_id' => $order?->id,
            'coupon_id' => $coupon?->id,
            'number' => 'INV-' . strtoupper(Str::random(12)),
            'status' => 'unpaid',
            'subtotal_cents' => 2500,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 2500,
            'currency' => 'USD',
            'payment_session_status' => Invoice::PAYMENT_SESSION_IDLE,
        ]);
    }

    private function makeAttempt(Invoice $invoice, string $sessionId): PaymentAttempt
    {
        return PaymentAttempt::query()->create([
            'invoice_id' => $invoice->id,
            'order_id' => $invoice->order_id,
            'client_id' => $invoice->client_id,
            'gateway' => 'lahza',
            'idempotency_key' => (string) Str::uuid(),
            'gateway_session_id' => $sessionId,
            'gateway_amount_cents' => $invoice->total_cents,
            'currency' => $invoice->currency,
            'status' => PaymentAttempt::STATUS_INITIATED,
        ]);
    }

    private function makeClient(): Client
    {
        return Client::query()->create([
            'first_name' => 'External',
            'last_name' => 'Evidence',
            'email' => Str::lower(Str::random(16)) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'External Evidence Test',
            'can_login' => true,
        ]);
    }
}
