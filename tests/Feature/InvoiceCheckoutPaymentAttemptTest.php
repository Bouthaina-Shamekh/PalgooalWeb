<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\PaymentAttempt;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\DTOs\PaymentSession;
use App\Payments\Exceptions\PaymentException;
use App\Payments\PaymentManager;
use App\Services\Billing\InvoiceSettlementService;
use App\Services\Billing\OrderActivationService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class InvoiceCheckoutPaymentAttemptTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    public function test_idle_invoice_is_claimed_and_one_session_becomes_ready(): void
    {
        [$client, $invoice] = $this->makeInvoice();
        $this->fakeGateway(function () use ($invoice) {
            $claimedInvoice = $invoice->fresh();
            $this->assertSame(Invoice::PAYMENT_SESSION_CREATING, $claimedInvoice->payment_session_status);
            $this->assertSame(PaymentAttempt::STATUS_PENDING, $claimedInvoice->paymentSessionAttempt->status);

            return new PaymentSession('session-1', 'https://pay.test/session-1');
        }, 1);

        $response = $this->actingAs($client, 'client')->post(route('client.invoices.checkout.process', $invoice));

        $response->assertRedirect('https://pay.test/session-1');
        $this->assertSame(1, PaymentAttempt::query()->count());
        $attempt = PaymentAttempt::query()->sole();
        $this->assertSame(PaymentAttempt::STATUS_INITIATED, $attempt->status);
        $this->assertSame(Invoice::PAYMENT_SESSION_READY, $invoice->fresh()->payment_session_status);
        $this->assertSame($attempt->id, $invoice->fresh()->payment_session_attempt_id);
    }

    public function test_second_request_that_sees_creating_does_not_create_or_call_provider(): void
    {
        [$client, $invoice] = $this->makeInvoice();
        $attempt = $this->makePendingAttempt($invoice);
        $invoice->update([
            'payment_session_status' => Invoice::PAYMENT_SESSION_CREATING,
            'payment_session_attempt_id' => $attempt->id,
        ]);
        $this->fakeGateway(null, 0);

        $response = $this->actingAs($client, 'client')->post(route('client.invoices.checkout.process', $invoice));

        $response->assertRedirect(route('client.invoices.checkout', ['invoice' => $invoice, 'state' => 'processing']));
        $this->assertSame(1, PaymentAttempt::query()->count());
        $this->assertSame(PaymentAttempt::STATUS_PENDING, $attempt->fresh()->status);
    }

    public function test_ready_invoice_reuses_safe_checkout_url_without_provider_call(): void
    {
        [$client, $invoice] = $this->makeInvoice();
        $attempt = $this->makePendingAttempt($invoice, PaymentAttempt::STATUS_INITIATED, [
            'checkout_url' => 'https://pay.test/existing',
        ]);
        $invoice->update([
            'payment_session_status' => Invoice::PAYMENT_SESSION_READY,
            'payment_session_attempt_id' => $attempt->id,
        ]);
        $this->fakeGateway(null, 0);

        $this->actingAs($client, 'client')
            ->post(route('client.invoices.checkout.process', $invoice))
            ->assertRedirect('https://pay.test/existing');

        $this->assertSame(1, PaymentAttempt::query()->count());
    }

    public function test_timeout_keeps_claim_pending_and_later_request_does_not_retry(): void
    {
        [$client, $invoice] = $this->makeInvoice();
        $this->fakeGateway(fn () => throw new \RuntimeException('connection reset'), 1);

        $this->actingAs($client, 'client')->post(route('client.invoices.checkout.process', $invoice));
        $this->actingAs($client, 'client')->post(route('client.invoices.checkout.process', $invoice));

        $attempt = PaymentAttempt::query()->sole();
        $this->assertSame(PaymentAttempt::STATUS_PENDING, $attempt->status);
        $this->assertSame('session_creation_outcome_unknown', $attempt->gateway_status_raw);
        $this->assertSame(Invoice::PAYMENT_SESSION_CREATING, $invoice->fresh()->payment_session_status);
        $this->assertSame($attempt->id, $invoice->fresh()->payment_session_attempt_id);
    }

    public function test_confirmed_pre_request_failure_releases_claim(): void
    {
        [$client, $invoice] = $this->makeInvoice();
        $this->fakeGateway(
            fn () => throw new PaymentException('LahzaGateway: secret_key is not configured.'),
            1,
        );

        $this->actingAs($client, 'client')->post(route('client.invoices.checkout.process', $invoice));

        $attempt = PaymentAttempt::query()->sole();
        $this->assertSame(PaymentAttempt::STATUS_FAILED, $attempt->status);
        $this->assertSame(Invoice::PAYMENT_SESSION_IDLE, $invoice->fresh()->payment_session_status);
        $this->assertNull($invoice->fresh()->payment_session_attempt_id);
    }

    public function test_owner_attempt_can_settle_claimed_invoice(): void
    {
        [, $invoice] = $this->makeInvoice();
        $attempt = $this->makePendingAttempt($invoice);
        $invoice->update([
            'payment_session_status' => Invoice::PAYMENT_SESSION_CREATING,
            'payment_session_attempt_id' => $attempt->id,
        ]);

        $this->settlementService()->markPaid($invoice, 'lahza', $attempt);

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(Invoice::PAYMENT_SESSION_READY, $invoice->fresh()->payment_session_status);
        $this->assertSame($attempt->id, $invoice->fresh()->payment_attempt_id);
    }

    public function test_different_attempt_cannot_settle_claimed_invoice(): void
    {
        [, $invoice] = $this->makeInvoice();
        $owner = $this->makePendingAttempt($invoice);
        $different = $this->makePendingAttempt($invoice);
        $invoice->update([
            'payment_session_status' => Invoice::PAYMENT_SESSION_CREATING,
            'payment_session_attempt_id' => $owner->id,
        ]);

        try {
            $this->settlementService()->markPaid($invoice, 'lahza', $different);
            $this->fail('A non-owner PaymentAttempt settled the invoice.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('does not own', $exception->getMessage());
        }

        $this->assertSame('unpaid', $invoice->fresh()->status);
        $this->assertNull($invoice->fresh()->payment_attempt_id);
    }

    public function test_paid_during_provider_call_does_not_publish_or_return_session(): void
    {
        [$client, $invoice] = $this->makeInvoice();
        $this->fakeGateway(function () use ($invoice) {
            Invoice::query()->whereKey($invoice->id)->update(['status' => 'paid']);

            return new PaymentSession('session-late', 'https://pay.test/session-late');
        }, 1);

        $response = $this->actingAs($client, 'client')->post(route('client.invoices.checkout.process', $invoice));

        $response->assertRedirect(route('client.invoices.checkout', ['invoice' => $invoice, 'state' => 'paid']));
        $attempt = PaymentAttempt::query()->sole();
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertNotSame(Invoice::PAYMENT_SESSION_READY, $invoice->fresh()->payment_session_status);
        $this->assertSame(PaymentAttempt::STATUS_CANCELLED, $attempt->status);
        $this->assertNull($attempt->gateway_session_id);
    }

    public function test_provider_is_called_at_transaction_level_zero(): void
    {
        [$client, $invoice] = $this->makeInvoice();
        $this->fakeGateway(function () {
            $this->assertSame(0, DB::transactionLevel());

            return new PaymentSession('session-outside-tx', 'https://pay.test/outside-tx');
        }, 1);

        $this->actingAs($client, 'client')->post(route('client.invoices.checkout.process', $invoice));
    }

    public function test_non_owner_cannot_claim_invoice_or_call_provider(): void
    {
        [, $invoice] = $this->makeInvoice();
        $otherClient = $this->makeClient();
        $this->fakeGateway(null, 0, expectManagerCalls: false);

        $this->actingAs($otherClient, 'client')
            ->post(route('client.invoices.checkout.process', $invoice))
            ->assertNotFound();

        $this->assertSame(0, PaymentAttempt::query()->count());
        $this->assertSame(Invoice::PAYMENT_SESSION_IDLE, $invoice->fresh()->payment_session_status);
    }

    private function makeInvoice(): array
    {
        $client = $this->makeClient();
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'number' => 'INV-' . uniqid(),
            'status' => 'unpaid',
            'subtotal_cents' => 2500,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 2500,
            'currency' => 'USD',
        ]);

        return [$client, $invoice];
    }

    private function makeClient(): Client
    {
        return Client::create([
            'first_name' => 'Payment',
            'last_name' => 'Tester',
            'email' => uniqid('payment_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Payment Test Co',
            'can_login' => true,
        ]);
    }

    private function makePendingAttempt(
        Invoice $invoice,
        string $status = PaymentAttempt::STATUS_PENDING,
        ?array $gatewayResponse = null,
    ): PaymentAttempt {
        return PaymentAttempt::create([
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'gateway' => 'lahza',
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            'gateway_amount_cents' => $invoice->total_cents,
            'currency' => $invoice->currency,
            'status' => $status,
            'gateway_response' => $gatewayResponse,
        ]);
    }

    private function fakeGateway(
        ?callable $createSession,
        int $expectedCalls,
        bool $expectManagerCalls = true,
    ): PaymentGatewayInterface {
        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('name')->andReturn('lahza');

        if ($expectedCalls === 0) {
            $gateway->shouldNotReceive('createSession');
        } else {
            $gateway->shouldReceive('createSession')
                ->times($expectedCalls)
                ->andReturnUsing($createSession);
        }

        $manager = Mockery::mock(PaymentManager::class);
        if ($expectManagerCalls) {
            $manager->shouldReceive('isEnabled')->andReturnTrue();
            $manager->shouldReceive('gateway')->andReturn($gateway);
        } else {
            $manager->shouldNotReceive('isEnabled');
            $manager->shouldNotReceive('gateway');
        }
        $this->app->instance(PaymentManager::class, $manager);

        return $gateway;
    }

    private function settlementService(): InvoiceSettlementService
    {
        return new InvoiceSettlementService(Mockery::mock(OrderActivationService::class));
    }
}
