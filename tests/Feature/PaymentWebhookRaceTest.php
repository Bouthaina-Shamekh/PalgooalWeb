<?php

namespace Tests\Feature;

use App\Http\Controllers\PaymentWebhookController;
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
use App\Payments\Exceptions\WebhookVerificationException;
use App\Payments\PaymentManager;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class PaymentWebhookRaceTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    public function test_success_is_terminal_against_repeated_failure_cancel_and_mismatch_events(): void
    {
        [$invoice, $attempt] = $this->makeInvoiceAndAttempt('terminal-session');

        $this->sendEvent($this->successEvent($invoice, $attempt))->assertStatus(202);
        $successfulAttempt = $attempt->fresh();
        $successSnapshot = $this->successSnapshot($successfulAttempt);

        $this->sendEvent($this->failureEvent($attempt, 'payment.failed'))->assertStatus(202);
        $this->sendEvent($this->failureEvent($attempt, 'payment.failed'))->assertStatus(202);
        $this->sendEvent($this->failureEvent($attempt, 'payment.cancelled'))->assertStatus(202);
        $this->sendEvent(new WebhookEvent(
            WebhookEvent::TYPE_PAYMENT_SUCCEEDED,
            $attempt->gateway_session_id,
            null,
            $invoice->total_cents + 1,
            $invoice->currency,
            ['event' => 'charge.success', 'mismatch' => true],
        ))->assertStatus(202);

        $this->assertSame($successSnapshot, $this->successSnapshot($attempt->fresh()));
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame($attempt->id, $invoice->fresh()->payment_attempt_id);
    }

    public function test_verified_success_after_failure_corrects_attempt_and_settles_invoice(): void
    {
        [$invoice, $attempt] = $this->makeInvoiceAndAttempt('failure-then-success');

        $this->sendEvent($this->failureEvent($attempt))->assertStatus(202);
        $this->assertSame(PaymentAttempt::STATUS_FAILED, $attempt->fresh()->status);
        $this->assertSame('unpaid', $invoice->fresh()->status);

        $success = $this->successEvent($invoice, $attempt, 'txn-recovered');
        $this->sendEvent($success, new TransactionStatus(
            'txn-recovered',
            TransactionStatus::STATUS_SUCCEEDED,
            $invoice->total_cents,
            $invoice->currency,
            ['verified' => true],
        ))->assertStatus(202);

        $this->assertSame(PaymentAttempt::STATUS_SUCCEEDED, $attempt->fresh()->status);
        $this->assertNotNull($attempt->fresh()->settled_at);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame($attempt->id, $invoice->fresh()->payment_attempt_id);
    }

    public function test_stale_failure_writer_reloads_and_cannot_downgrade_success(): void
    {
        [$invoice, $attempt] = $this->makeInvoiceAndAttempt('stale-failure');
        $staleAttempt = $attempt->fresh();

        $this->sendEvent($this->successEvent($invoice, $attempt))->assertStatus(202);
        $successSnapshot = $this->successSnapshot($attempt->fresh());
        $this->assertSame(PaymentAttempt::STATUS_INITIATED, $staleAttempt->status);

        $controller = new class extends PaymentWebhookController
        {
            public function applyStaleFailure(int $attemptId): void
            {
                $this->markAttemptFailed($attemptId, [
                    'status' => PaymentAttempt::STATUS_FAILED,
                    'gateway_status_raw' => 'stale_failure_snapshot',
                    'gateway_transaction_id' => 'txn-stale',
                    'webhook_verified_at' => now(),
                ]);
            }
        };
        $controller->applyStaleFailure($staleAttempt->id);

        $this->assertSame($successSnapshot, $this->successSnapshot($attempt->fresh()));
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_reconciliation_mismatch_can_be_corrected_by_later_verified_success(): void
    {
        [$invoice, $attempt] = $this->makeInvoiceAndAttempt('reconciliation-recovery');
        $event = $this->successEvent($invoice, $attempt, 'txn-reconciliation');

        $this->sendEvent($event, new TransactionStatus(
            'txn-reconciliation',
            TransactionStatus::STATUS_FAILED,
            $invoice->total_cents,
            $invoice->currency,
            ['status' => 'failed'],
        ))->assertStatus(401);

        $this->assertSame(PaymentAttempt::STATUS_FAILED, $attempt->fresh()->status);
        $this->assertSame('transaction_reconciliation_mismatch', $attempt->fresh()->gateway_status_raw);
        $this->assertSame('unpaid', $invoice->fresh()->status);

        $this->sendEvent($event, new TransactionStatus(
            'txn-reconciliation',
            TransactionStatus::STATUS_SUCCEEDED,
            $invoice->total_cents,
            $invoice->currency,
            ['status' => 'success'],
        ))->assertStatus(202);

        $this->assertSame(PaymentAttempt::STATUS_SUCCEEDED, $attempt->fresh()->status);
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_failure_for_second_attempt_does_not_change_winning_attempt_or_paid_invoice(): void
    {
        [$invoice, $winner] = $this->makeInvoiceAndAttempt('winning-session');
        $this->sendEvent($this->successEvent($invoice, $winner))->assertStatus(202);

        $second = $this->makeAttempt($invoice, 'second-session');
        $this->sendEvent($this->failureEvent($second))->assertStatus(202);

        $this->assertSame(PaymentAttempt::STATUS_SUCCEEDED, $winner->fresh()->status);
        $this->assertSame(PaymentAttempt::STATUS_FAILED, $second->fresh()->status);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame($winner->id, $invoice->fresh()->payment_attempt_id);
    }

    public function test_duplicate_success_does_not_repeat_coupon_or_provisioning_side_effects(): void
    {
        Queue::fake();
        [$invoice, $attempt, $coupon, $subscription] = $this->makeProvisionableInvoiceAndAttempt();
        $event = $this->successEvent($invoice, $attempt);

        $this->sendEvent($event)->assertStatus(202);
        $this->sendEvent($event)->assertStatus(202);

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame($attempt->id, $invoice->fresh()->payment_attempt_id);
        $this->assertSame(PaymentAttempt::STATUS_SUCCEEDED, $attempt->fresh()->status);
        $this->assertSame(1, $coupon->fresh()->used_count);
        Queue::assertPushed(
            ProvisionSubscription::class,
            fn (ProvisionSubscription $job) => $job->subscriptionId === $subscription->id,
        );
        Queue::assertPushed(ProvisionSubscription::class, 1);
    }

    public function test_invalid_signature_does_not_mutate_attempt_or_invoice(): void
    {
        [$invoice, $attempt] = $this->makeInvoiceAndAttempt('invalid-signature');
        $attemptSnapshot = $attempt->fresh()->getAttributes();
        $invoiceSnapshot = $invoice->fresh()->getAttributes();

        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('name')->andReturn('lahza');
        $gateway->shouldReceive('verifyWebhook')
            ->once()
            ->andThrow(new WebhookVerificationException('invalid signature'));
        $manager = Mockery::mock(PaymentManager::class);
        $manager->shouldReceive('gateway')->once()->andReturn($gateway);
        $this->app->instance(PaymentManager::class, $manager);

        $this->postJson(route('payment.webhook', ['gateway' => 'lahza']), [], [
            'x-lahza-signature' => 'invalid',
        ])->assertStatus(401);

        $this->assertSame($attemptSnapshot, $attempt->fresh()->getAttributes());
        $this->assertSame($invoiceSnapshot, $invoice->fresh()->getAttributes());
    }

    private function sendEvent(WebhookEvent $event, ?TransactionStatus $transaction = null)
    {
        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('name')->andReturn('lahza');
        $gateway->shouldReceive('verifyWebhook')->once()->andReturn($event);
        if ($transaction !== null) {
            $gateway->shouldReceive('getTransaction')
                ->once()
                ->with($event->transactionId)
                ->andReturn($transaction);
        } else {
            $gateway->shouldNotReceive('getTransaction');
        }

        $manager = Mockery::mock(PaymentManager::class);
        $manager->shouldReceive('gateway')->once()->andReturn($gateway);
        $this->app->instance(PaymentManager::class, $manager);

        return $this->postJson(route('payment.webhook', ['gateway' => 'lahza']), [], [
            'x-lahza-signature' => 'verified-by-fake',
        ]);
    }

    private function successEvent(Invoice $invoice, PaymentAttempt $attempt, ?string $transactionId = null): WebhookEvent
    {
        return new WebhookEvent(
            WebhookEvent::TYPE_PAYMENT_SUCCEEDED,
            $attempt->gateway_session_id,
            $transactionId,
            $invoice->total_cents,
            $invoice->currency,
            ['event' => 'charge.success'],
        );
    }

    private function failureEvent(PaymentAttempt $attempt, string $rawEvent = 'charge.failed'): WebhookEvent
    {
        return new WebhookEvent(
            WebhookEvent::TYPE_PAYMENT_FAILED,
            $attempt->gateway_session_id,
            'txn-negative',
            $attempt->gateway_amount_cents,
            $attempt->currency,
            ['event' => $rawEvent],
        );
    }

    private function successSnapshot(PaymentAttempt $attempt): array
    {
        return [
            'status' => $attempt->status,
            'gateway_transaction_id' => $attempt->gateway_transaction_id,
            'gateway_status_raw' => $attempt->gateway_status_raw,
            'gateway_response' => $attempt->gateway_response,
            'webhook_verified_at' => $attempt->webhook_verified_at?->toISOString(),
            'settled_at' => $attempt->settled_at?->toISOString(),
        ];
    }

    private function makeInvoiceAndAttempt(string $sessionId): array
    {
        $client = $this->makeClient();
        $invoice = Invoice::query()->create([
            'client_id' => $client->id,
            'number' => 'INV-' . strtoupper(Str::random(12)),
            'status' => 'unpaid',
            'subtotal_cents' => 2500,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 2500,
            'currency' => 'USD',
            'payment_session_status' => Invoice::PAYMENT_SESSION_IDLE,
        ]);
        $attempt = $this->makeAttempt($invoice, $sessionId);
        $invoice->update([
            'payment_session_status' => Invoice::PAYMENT_SESSION_READY,
            'payment_session_attempt_id' => $attempt->id,
        ]);

        return [$invoice, $attempt];
    }

    private function makeProvisionableInvoiceAndAttempt(): array
    {
        $client = $this->makeClient();
        $server = Server::query()->create([
            'name' => 'Webhook Race WHM',
            'type' => 'cpanel',
            'hostname' => Str::lower(Str::random(12)) . '.example.test',
            'username' => 'root',
            'api_token' => 'test-token',
            'is_active' => true,
        ]);
        $plan = Plan::query()->create([
            'name' => 'Webhook Race Plan',
            'slug' => 'webhook-race-' . Str::lower(Str::random(10)),
            'plan_type' => Plan::TYPE_HOSTING,
            'server_id' => $server->id,
            'server_package' => 'webhook_race_package',
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
            'server_package' => 'webhook_race_package',
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
            'code' => 'RACE-' . strtoupper(Str::random(10)),
            'discount_type' => 'fixed',
            'discount_value' => 1,
            'used_count' => 0,
            'is_active' => true,
        ]);
        $invoice = Invoice::query()->create([
            'client_id' => $client->id,
            'order_id' => $order->id,
            'coupon_id' => $coupon->id,
            'number' => 'INV-' . strtoupper(Str::random(12)),
            'status' => 'unpaid',
            'subtotal_cents' => 2500,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 2500,
            'currency' => 'USD',
        ]);
        InvoiceItem::query()->create([
            'invoice_id' => $invoice->id,
            'item_type' => 'subscription',
            'reference_id' => $subscription->id,
            'description' => 'Webhook race subscription',
            'qty' => 1,
            'unit_price_cents' => 2500,
            'total_cents' => 2500,
        ]);
        $attempt = $this->makeAttempt($invoice, 'provisionable-session');
        $invoice->update([
            'payment_session_status' => Invoice::PAYMENT_SESSION_READY,
            'payment_session_attempt_id' => $attempt->id,
        ]);

        return [$invoice, $attempt, $coupon, $subscription];
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
            'first_name' => 'Webhook',
            'last_name' => 'Race',
            'email' => Str::lower(Str::random(16)) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Webhook Race Test',
            'can_login' => true,
        ]);
    }
}
