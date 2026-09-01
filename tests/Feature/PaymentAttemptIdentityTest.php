<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\PaymentAttempt;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\DTOs\WebhookEvent;
use App\Payments\PaymentManager;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class PaymentAttemptIdentityTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    public function test_refunded_is_terminal_against_repeated_failure_and_success_webhooks(): void
    {
        $invoice = $this->makeInvoice('paid');
        $attempt = $this->makeAttempt('lahza', 'refunded-session', $invoice, PaymentAttempt::STATUS_REFUNDED, [
            'gateway_transaction_id' => 'txn-original',
            'gateway_status_raw' => 'refund.succeeded',
            'gateway_response' => ['refund_id' => 'refund-original'],
            'webhook_verified_at' => now()->subMinutes(5),
            'settled_at' => now()->subMinutes(4),
            'refunded_at' => now()->subMinute(),
            'refund_amount_cents' => 2500,
        ]);
        $invoice->update(['payment_attempt_id' => $attempt->id]);
        $snapshot = $attempt->fresh()->getAttributes();

        $failure = new WebhookEvent(
            WebhookEvent::TYPE_PAYMENT_FAILED,
            'refunded-session',
            'txn-stale-failure',
            2500,
            'USD',
            ['event' => 'charge.failed'],
        );
        $success = new WebhookEvent(
            WebhookEvent::TYPE_PAYMENT_SUCCEEDED,
            'refunded-session',
            'txn-stale-success',
            2500,
            'USD',
            ['event' => 'charge.success'],
        );

        $this->sendEvent($failure)->assertStatus(202);
        $this->sendEvent($failure)->assertStatus(202);
        $this->sendEvent($success)->assertStatus(202);

        $this->assertSame($snapshot, $attempt->fresh()->getAttributes());
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame($attempt->id, $invoice->fresh()->payment_attempt_id);
    }

    public function test_model_terminal_contract_is_limited_to_succeeded_and_refunded(): void
    {
        foreach ([PaymentAttempt::STATUS_SUCCEEDED, PaymentAttempt::STATUS_REFUNDED] as $status) {
            $this->assertTrue($this->makeAttempt('lahza', null, null, $status)->isWebhookTerminal());
        }

        foreach ([
            PaymentAttempt::STATUS_PENDING,
            PaymentAttempt::STATUS_INITIATED,
            PaymentAttempt::STATUS_FAILED,
            PaymentAttempt::STATUS_CANCELLED,
        ] as $status) {
            $this->assertFalse($this->makeAttempt('lahza', null, null, $status)->isWebhookTerminal());
        }
    }

    public function test_database_rejects_duplicate_session_within_same_gateway(): void
    {
        $this->makeAttempt('lahza', 'same-gateway-session');

        try {
            $this->makeAttempt('lahza', 'same-gateway-session');
            $this->fail('The database accepted a duplicate gateway/session identity.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(1, PaymentAttempt::query()->count());
    }

    public function test_same_session_is_allowed_across_different_gateways(): void
    {
        $lahza = $this->makeAttempt('lahza', 'shared-session');
        $mock = $this->makeAttempt('mock_gateway', 'shared-session');

        $this->assertNotSame($lahza->id, $mock->id);
        $this->assertSame(2, PaymentAttempt::query()->where('gateway_session_id', 'shared-session')->count());
    }

    public function test_multiple_null_session_ids_remain_allowed_before_provider_response(): void
    {
        $first = $this->makeAttempt('lahza');
        $second = $this->makeAttempt('lahza');

        $this->assertNull($first->gateway_session_id);
        $this->assertNull($second->gateway_session_id);
        $this->assertSame(2, PaymentAttempt::query()->whereNull('gateway_session_id')->count());
    }

    public function test_webhook_gateway_and_session_identity_selects_the_correct_attempt(): void
    {
        $lahzaInvoice = $this->makeInvoice();
        $lahzaAttempt = $this->makeAttempt('lahza', 'gateway-scoped-session', $lahzaInvoice);
        $lahzaInvoice->update([
            'payment_session_status' => Invoice::PAYMENT_SESSION_READY,
            'payment_session_attempt_id' => $lahzaAttempt->id,
        ]);

        $otherInvoice = $this->makeInvoice();
        $otherAttempt = $this->makeAttempt('mock_gateway', 'gateway-scoped-session', $otherInvoice);

        $this->sendEvent(new WebhookEvent(
            WebhookEvent::TYPE_PAYMENT_SUCCEEDED,
            'gateway-scoped-session',
            null,
            2500,
            'USD',
            ['event' => 'charge.success'],
        ))->assertStatus(202);

        $this->assertSame(PaymentAttempt::STATUS_SUCCEEDED, $lahzaAttempt->fresh()->status);
        $this->assertSame('paid', $lahzaInvoice->fresh()->status);
        $this->assertSame($lahzaAttempt->id, $lahzaInvoice->fresh()->payment_attempt_id);
        $this->assertSame(PaymentAttempt::STATUS_INITIATED, $otherAttempt->fresh()->status);
        $this->assertSame('unpaid', $otherInvoice->fresh()->status);
        $this->assertNull($otherInvoice->fresh()->payment_attempt_id);
    }

    private function sendEvent(WebhookEvent $event)
    {
        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('name')->andReturn('lahza');
        $gateway->shouldReceive('verifyWebhook')->once()->andReturn($event);
        $gateway->shouldNotReceive('getTransaction');

        $manager = Mockery::mock(PaymentManager::class);
        $manager->shouldReceive('gateway')->once()->andReturn($gateway);
        $this->app->instance(PaymentManager::class, $manager);

        return $this->postJson(route('payment.webhook', ['gateway' => 'lahza']), [], [
            'x-lahza-signature' => 'verified-by-fake',
        ]);
    }

    private function makeAttempt(
        string $gateway,
        ?string $sessionId = null,
        ?Invoice $invoice = null,
        string $status = PaymentAttempt::STATUS_INITIATED,
        array $overrides = [],
    ): PaymentAttempt {
        return PaymentAttempt::query()->create(array_merge([
            'invoice_id' => $invoice?->id,
            'client_id' => $invoice?->client_id,
            'gateway' => $gateway,
            'idempotency_key' => (string) Str::uuid(),
            'gateway_session_id' => $sessionId,
            'gateway_amount_cents' => 2500,
            'currency' => 'USD',
            'status' => $status,
        ], $overrides));
    }

    private function makeInvoice(string $status = 'unpaid'): Invoice
    {
        $client = Client::query()->create([
            'first_name' => 'Payment',
            'last_name' => 'Identity',
            'email' => Str::lower(Str::random(16)) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Payment Identity Test',
            'can_login' => true,
        ]);

        return Invoice::query()->create([
            'client_id' => $client->id,
            'number' => 'INV-' . strtoupper(Str::random(12)),
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
