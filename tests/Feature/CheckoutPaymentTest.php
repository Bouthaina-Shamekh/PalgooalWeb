<?php

namespace Tests\Feature;

use App\Jobs\ProvisionSubscription;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\Plan;
use App\Models\Template;
use App\Models\Tenancy\Subscription;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\DTOs\PaymentSession;
use App\Payments\DTOs\WebhookEvent;
use App\Payments\PaymentManager;
use App\Services\Payments\PaymentSessionStarter;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class CheckoutPaymentTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    public function test_template_checkout_creates_unpaid_records_and_returns_gateway_url(): void
    {
        $client = $this->makeClient();
        $template = $this->makeTemplate();
        $this->fakePaymentManager(
            fn () => new PaymentSession('template-session', 'https://pay.test/template-session'),
            1,
        );

        $response = $this->actingAs($client, 'client')
            ->postJson(route('checkout.process', ['template_id' => $template->id]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('checkout_url', 'https://pay.test/template-session')
            ->assertJsonPath('payment_session_status', 'ready');

        $this->assertSame(1, Order::query()->count());
        $this->assertSame(1, Invoice::query()->count());
        $this->assertSame(1, PaymentAttempt::query()->count());
        $this->assertSame(Order::STATUS_PENDING, Order::query()->sole()->status);
        $this->assertSame('draft', Invoice::query()->sole()->status);
        $this->assertSame(PaymentAttempt::STATUS_INITIATED, PaymentAttempt::query()->sole()->status);
    }

    public function test_non_template_plan_checkout_waits_for_verified_payment_before_provisioning(): void
    {
        Queue::fake();
        Http::fake();
        $client = $this->makeClient();
        $plan = $this->makePlan();
        $this->fakePaymentManager(
            fn () => new PaymentSession('plan-session', 'https://pay.test/plan-session'),
            1,
        );

        $response = $this->actingAs($client, 'client')->postJson(
            route('checkout.process', ['template_id' => 0, 'plan_id' => $plan->id]),
            [
                'domain' => 'hosting.example.test',
                'domain_option' => 'subdomain',
            ],
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('checkout_url', 'https://pay.test/plan-session')
            ->assertJsonPath('payment_session_status', 'ready');

        $order = Order::query()->sole();
        $invoice = Invoice::query()->sole();
        $subscription = Subscription::query()->sole();
        $attempt = PaymentAttempt::query()->sole();
        $subscriptionInvoiceItem = $invoice->items()->where('item_type', 'subscription')->sole();

        $this->assertSame(Order::STATUS_PENDING, $order->status);
        $this->assertNotSame('paid', $invoice->status);
        $this->assertSame('pending', $subscription->status);
        $this->assertSame(Subscription::PROVISIONING_PENDING, $subscription->provisioning_status);
        $this->assertSame($subscription->id, (int) $subscriptionInvoiceItem->reference_id);
        Queue::assertNotPushed(ProvisionSubscription::class);
        Http::assertNothingSent();

        $event = new WebhookEvent(
            WebhookEvent::TYPE_PAYMENT_SUCCEEDED,
            'plan-session',
            null,
            $invoice->total_cents,
            $invoice->currency,
            ['verified' => true],
        );
        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('name')->andReturn('lahza');
        $gateway->shouldReceive('verifyWebhook')->once()->andReturn($event);
        $manager = Mockery::mock(PaymentManager::class);
        $manager->shouldReceive('gateway')->once()->andReturn($gateway);
        $this->app->instance(PaymentManager::class, $manager);

        $this->postJson(route('payment.webhook', ['gateway' => 'lahza']), [], [
            'x-lahza-signature' => 'verified-by-fake',
        ])->assertStatus(202);

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertSame(Subscription::PROVISIONING_PENDING, $subscription->fresh()->provisioning_status);
        Queue::assertPushed(
            ProvisionSubscription::class,
            fn (ProvisionSubscription $job) => $job->subscriptionId === $subscription->id,
        );
        Queue::assertPushed(ProvisionSubscription::class, 1);
        Http::assertNothingSent();
    }

    public function test_failed_non_template_plan_payment_never_dispatches_provisioning(): void
    {
        Queue::fake();
        Http::fake();
        $client = $this->makeClient();
        $plan = $this->makePlan();
        $this->fakePaymentManager(
            fn () => new PaymentSession('failed-plan-session', 'https://pay.test/failed-plan-session'),
            1,
        );

        $this->actingAs($client, 'client')->postJson(
            route('checkout.process', ['template_id' => 0, 'plan_id' => $plan->id]),
            [
                'domain' => 'failed-payment.example.test',
                'domain_option' => 'subdomain',
            ],
        )->assertOk();

        $invoice = Invoice::query()->sole();
        $attempt = PaymentAttempt::query()->sole();
        $event = new WebhookEvent(
            WebhookEvent::TYPE_PAYMENT_FAILED,
            'failed-plan-session',
            null,
            $invoice->total_cents,
            $invoice->currency,
            ['verified' => true],
        );
        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('name')->andReturn('lahza');
        $gateway->shouldReceive('verifyWebhook')->once()->andReturn($event);
        $manager = Mockery::mock(PaymentManager::class);
        $manager->shouldReceive('gateway')->once()->andReturn($gateway);
        $this->app->instance(PaymentManager::class, $manager);

        $this->postJson(route('payment.webhook', ['gateway' => 'lahza']), [], [
            'x-lahza-signature' => 'verified-by-fake',
        ])->assertStatus(202);

        $this->assertSame(PaymentAttempt::STATUS_FAILED, $attempt->fresh()->status);
        $this->assertSame('draft', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_PENDING, Order::query()->sole()->status);
        $this->assertSame('pending', Subscription::query()->sole()->status);
        $this->assertSame(
            Subscription::PROVISIONING_PENDING,
            Subscription::query()->sole()->provisioning_status,
        );
        Queue::assertNotPushed(ProvisionSubscription::class);
        Http::assertNothingSent();
    }

    public function test_ready_session_is_reused_without_another_attempt_or_gateway_call(): void
    {
        [$client, $invoice] = $this->makeInvoice();
        $attempt = $this->makeAttempt($invoice, PaymentAttempt::STATUS_INITIATED, 'existing-session', [
            'checkout_url' => 'https://pay.test/existing-session',
        ]);
        $invoice->update([
            'payment_session_status' => Invoice::PAYMENT_SESSION_READY,
            'payment_session_attempt_id' => $attempt->id,
        ]);
        $manager = $this->fakePaymentManager(null, 0);

        $result = (new PaymentSessionStarter($manager))->start(
            $invoice,
            $client->id,
            'https://app.test/return',
            'https://app.test/cancel',
        );

        $this->assertSame('ready', $result['status']);
        $this->assertSame('https://pay.test/existing-session', $result['checkout_url']);
        $this->assertSame(1, PaymentAttempt::query()->count());
    }

    public function test_creating_session_returns_processing_without_second_session(): void
    {
        [$client, $invoice] = $this->makeInvoice();
        $attempt = $this->makeAttempt($invoice, PaymentAttempt::STATUS_PENDING);
        $invoice->update([
            'payment_session_status' => Invoice::PAYMENT_SESSION_CREATING,
            'payment_session_attempt_id' => $attempt->id,
        ]);
        $manager = $this->fakePaymentManager(null, 0);

        $result = (new PaymentSessionStarter($manager))->start(
            $invoice,
            $client->id,
            'https://app.test/return',
            'https://app.test/cancel',
        );

        $this->assertFalse($result['ok']);
        $this->assertSame('creating', $result['status']);
        $this->assertSame(409, $result['http_status']);
        $this->assertSame(1, PaymentAttempt::query()->count());
    }

    public function test_indeterminate_session_does_not_pay_or_activate_order(): void
    {
        [$client, $invoice, $order] = $this->makeInvoice(withOrder: true);
        $manager = $this->fakePaymentManager(fn () => throw new \RuntimeException('connection reset'), 1);

        $result = (new PaymentSessionStarter($manager))->start(
            $invoice,
            $client->id,
            'https://app.test/return',
            'https://app.test/cancel',
        );

        $this->assertSame('indeterminate', $result['status']);
        $this->assertSame('draft', $invoice->fresh()->status);
        $this->assertSame(Invoice::PAYMENT_SESSION_CREATING, $invoice->fresh()->payment_session_status);
        $this->assertSame(PaymentAttempt::STATUS_PENDING, PaymentAttempt::query()->sole()->status);
        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    public function test_payment_webhook_verified_event_remains_the_path_that_pays_invoice(): void
    {
        [, $invoice] = $this->makeInvoice();
        $attempt = $this->makeAttempt($invoice, PaymentAttempt::STATUS_INITIATED, 'webhook-session', [
            'checkout_url' => 'https://pay.test/webhook-session',
        ]);
        $invoice->update([
            'payment_session_status' => Invoice::PAYMENT_SESSION_READY,
            'payment_session_attempt_id' => $attempt->id,
        ]);

        $event = new WebhookEvent(
            WebhookEvent::TYPE_PAYMENT_SUCCEEDED,
            'webhook-session',
            null,
            $invoice->total_cents,
            $invoice->currency,
            ['verified' => true],
        );
        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('name')->andReturn('lahza');
        $gateway->shouldReceive('verifyWebhook')->once()->andReturn($event);
        $manager = Mockery::mock(PaymentManager::class);
        $manager->shouldReceive('gateway')->once()->andReturn($gateway);
        $this->app->instance(PaymentManager::class, $manager);

        $this->postJson(route('payment.webhook', ['gateway' => 'lahza']), [], [
            'x-lahza-signature' => 'verified-by-fake',
        ])->assertStatus(202);

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(PaymentAttempt::STATUS_SUCCEEDED, $attempt->fresh()->status);
    }

    public function test_checkout_javascript_redirects_only_safe_checkout_urls_before_local_success(): void
    {
        $source = file_get_contents(resource_path('views/front/pages/checkout.blade.php'));
        $checkoutController = file_get_contents(app_path('Http/Controllers/Front/CheckoutController.php'));
        $invoiceCheckoutController = file_get_contents(app_path('Http/Controllers/Client/InvoiceCheckoutController.php'));

        $this->assertIsString($source);
        $this->assertStringContainsString("typeof response.checkout_url === 'string'", $source);
        $this->assertStringContainsString("target.protocol === 'https:' || target.protocol === 'http:'", $source);
        $this->assertStringContainsString('window.location.assign(target.href)', $source);
        $this->assertLessThan(
            strpos($source, 'showSuccess();'),
            strpos($source, 'window.location.assign(target.href)'),
        );
        $this->assertStringNotContainsString('->markPaid(', $checkoutController);
        $this->assertStringNotContainsString('ProvisionSubscription::dispatch', $checkoutController);
        $this->assertStringNotContainsString('TenantProvisioningService', $checkoutController);
        $this->assertStringNotContainsString('SubscriptionSyncService', $checkoutController);
        $this->assertStringContainsString('PaymentSessionStarter::class', $invoiceCheckoutController);
        $this->assertStringNotContainsString('claimPaymentSession(', $invoiceCheckoutController);
    }

    private function makeClient(): Client
    {
        return Client::query()->create([
            'first_name' => 'Checkout',
            'last_name' => 'Payment',
            'email' => uniqid('checkout_payment_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Checkout Payment Test',
            'can_login' => true,
        ]);
    }

    private function makeTemplate(): Template
    {
        $categoryId = DB::table('category_templates')->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Template::query()->create([
            'category_template_id' => $categoryId,
            'price_cents' => 2500,
            'image' => 'template-test.jpg',
            'rating' => 0,
        ]);
    }

    private function makePlan(): Plan
    {
        return Plan::query()->create([
            'name' => 'Non-template Hosting',
            'slug' => 'non-template-hosting-' . Str::lower(Str::random(8)),
            'plan_type' => Plan::TYPE_HOSTING,
            'monthly_price_cents' => 2500,
            'annual_price_cents' => 25000,
            'is_active' => true,
            'requires_domain' => true,
        ]);
    }

    private function makeInvoice(bool $withOrder = false): array
    {
        $client = $this->makeClient();
        $order = $withOrder
            ? Order::query()->create([
                'client_id' => $client->id,
                'status' => Order::STATUS_PENDING,
                'type' => 'subscription',
            ])
            : null;
        $invoice = Invoice::query()->create([
            'client_id' => $client->id,
            'order_id' => $order?->id,
            'number' => 'INV-' . uniqid(),
            'status' => 'draft',
            'subtotal_cents' => 2500,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 2500,
            'currency' => 'USD',
        ]);

        return [$client, $invoice, $order];
    }

    private function makeAttempt(
        Invoice $invoice,
        string $status,
        ?string $sessionId = null,
        ?array $gatewayResponse = null,
    ): PaymentAttempt {
        return PaymentAttempt::query()->create([
            'invoice_id' => $invoice->id,
            'order_id' => $invoice->order_id,
            'client_id' => $invoice->client_id,
            'gateway' => 'lahza',
            'idempotency_key' => (string) Str::uuid(),
            'gateway_session_id' => $sessionId,
            'gateway_amount_cents' => $invoice->total_cents,
            'currency' => $invoice->currency,
            'status' => $status,
            'gateway_response' => $gatewayResponse,
        ]);
    }

    private function fakePaymentManager(?callable $createSession, int $expectedCalls): PaymentManager
    {
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
        $manager->shouldReceive('isEnabled')->andReturnTrue();
        $manager->shouldReceive('gateway')->andReturn($gateway);
        $this->app->instance(PaymentManager::class, $manager);

        return $manager;
    }
}
