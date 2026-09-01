<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\DomainProvider;
use App\Models\DomainTld;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAttempt;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\DTOs\PaymentSession;
use App\Payments\DTOs\WebhookEvent;
use App\Payments\PaymentManager;
use App\Services\Domains\DomainAvailabilityService;
use App\Services\Domains\DomainPricingService;
use App\Services\Billing\InvoiceSettlementService;
use App\Services\Billing\OrderActivationService;
use App\Services\Payments\PaymentSessionStarter;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class CurrencySourceOfTruthTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    public function test_ils_quote_reaches_cart_order_item_invoice_attempt_and_gateway_unchanged(): void
    {
        $provider = $this->makeProvider();
        $this->makeTldPrice($provider, 'ps', 12, 'ils');
        $client = $this->makeClient();
        $this->fakeAvailability();

        $this->postJson(route('cart.store'), [
            'items' => [[
                'domain' => 'currency.ps',
                'item_option' => 'register',
                'price_cents' => 1,
                'currency' => 'USD',
                'meta' => ['currency' => 'JOD'],
            ]],
        ])->assertOk();

        $cartItem = session('palgoals_cart_domains')[0];
        $this->assertSame('ILS', $cartItem['currency']);
        $this->assertSame('ILS', $cartItem['meta']['currency']);

        $this->fakePaymentManager(function (Invoice $invoice) {
            $this->assertSame('ILS', $invoice->currency);
            $this->assertSame(1200, (int) $invoice->total_cents);

            return new PaymentSession('ils-session', 'https://pay.test/ils-session');
        }, 1);

        $this->actingAs($client, 'client')->postJson(route('checkout.cart.process'), [
            'items' => [[
                'domain' => 'currency.ps',
                'option' => 'register',
                'price_cents' => 1200,
                'currency' => 'USD',
            ]],
        ])->assertOk()->assertJsonPath('success', true);

        $item = OrderItem::query()->sole();
        $invoice = Invoice::query()->sole();
        $this->actingAs($client, 'client')
            ->post(route('client.invoices.checkout.process', $invoice))
            ->assertRedirect('https://pay.test/ils-session');
        $attempt = PaymentAttempt::query()->sole();
        $this->assertSame('ILS', $item->meta['currency']);
        $this->assertSame('ILS', $invoice->currency);
        $this->assertSame('ILS', $attempt->currency);
        $this->assertSame(1200, $attempt->gateway_amount_cents);
    }

    public function test_mixed_currency_cart_is_rejected_before_any_persistent_write(): void
    {
        $provider = $this->makeProvider();
        $this->makeTldPrice($provider, 'com', 10, 'USD');
        $this->makeTldPrice($provider, 'ps', 12, 'ILS');
        $client = $this->makeClient();
        $this->fakePaymentManager(null, 0);

        $this->actingAs($client, 'client')->postJson(route('checkout.cart.process'), [
            'items' => [
                ['domain' => 'mixed.com', 'option' => 'register', 'price_cents' => 1000],
                ['domain' => 'mixed.ps', 'option' => 'register', 'price_cents' => 1200],
            ],
        ])->assertStatus(422);

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(0, PaymentAttempt::query()->count());
    }

    public function test_usd_quote_reaches_invoice_attempt_and_gateway_unchanged(): void
    {
        $provider = $this->makeProvider();
        $this->makeTldPrice($provider, 'com', 10, 'USD');
        $client = $this->makeClient();
        $this->fakeAvailability();
        $this->fakePaymentManager(function (Invoice $invoice) {
            $this->assertSame('USD', $invoice->currency);

            return new PaymentSession('usd-session', 'https://pay.test/usd-session');
        }, 1);

        $this->actingAs($client, 'client')->postJson(route('checkout.cart.process'), [
            'items' => [[
                'domain' => 'currency.com',
                'option' => 'register',
                'price_cents' => 1000,
                'currency' => 'ILS',
            ]],
        ])->assertOk();

        $invoice = Invoice::query()->sole();
        $this->assertSame('USD', $invoice->currency);
        $this->actingAs($client, 'client')
            ->post(route('client.invoices.checkout.process', $invoice))
            ->assertRedirect('https://pay.test/usd-session');
        $this->assertSame('USD', PaymentAttempt::query()->sole()->currency);
    }

    public function test_pricing_never_compares_same_tld_prices_across_currencies(): void
    {
        $first = $this->makeProvider('namecheap');
        $second = $this->makeProvider('enom');
        $this->makeTldPrice($first, 'com', 10, 'USD');
        $this->makeTldPrice($second, 'com', 9, 'ILS');

        $this->assertNull(app(DomainPricingService::class)->registrationQuoteForDomain('ambiguous.com'));
    }

    public function test_pricing_rejects_non_iso_currency_codes(): void
    {
        $provider = $this->makeProvider();
        $this->makeTldPrice($provider, 'bad', 10, 'US1');

        $this->assertNull(app(DomainPricingService::class)->registrationQuoteForDomain('invalid.bad'));
    }

    public function test_ready_attempt_with_different_currency_is_blocked_without_gateway_call(): void
    {
        [$client, $invoice] = $this->makeInvoice('ILS');
        $attempt = $this->makeAttempt($invoice, 'USD');
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

        $this->assertFalse($result['ok']);
        $this->assertSame('failed', $result['status']);
        $this->assertSame(422, $result['http_status']);
        $this->assertSame(1, PaymentAttempt::query()->count());
    }

    public function test_unsupported_invoice_currency_is_rejected_before_gateway_call(): void
    {
        [$client, $invoice] = $this->makeInvoice('SAR');
        $manager = $this->fakePaymentManager(null, 0);

        $result = (new PaymentSessionStarter($manager))->start(
            $invoice,
            $client->id,
            'https://app.test/return',
            'https://app.test/cancel',
        );

        $this->assertFalse($result['ok']);
        $this->assertSame(422, $result['http_status']);
        $this->assertSame(0, PaymentAttempt::query()->count());
    }

    public function test_settlement_rejects_attempt_currency_that_differs_from_invoice(): void
    {
        [, $invoice] = $this->makeInvoice('ILS');
        $attempt = $this->makeAttempt($invoice, 'USD');
        $invoice->update([
            'payment_session_status' => Invoice::PAYMENT_SESSION_READY,
            'payment_session_attempt_id' => $attempt->id,
        ]);
        $service = new InvoiceSettlementService(Mockery::mock(OrderActivationService::class));

        try {
            $service->markPaid($invoice, 'lahza', $attempt);
            $this->fail('A currency-mismatched PaymentAttempt settled the invoice.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('does not match invoice', $exception->getMessage());
        }

        $this->assertSame('draft', $invoice->fresh()->status);
        $this->assertNotSame(PaymentAttempt::STATUS_SUCCEEDED, $attempt->fresh()->status);
    }

    public function test_webhook_currency_or_amount_mismatch_never_settles_or_succeeds(): void
    {
        foreach ([
            ['amount' => 2499, 'currency' => 'ILS'],
            ['amount' => 2500, 'currency' => 'USD'],
        ] as $case) {
            [, $invoice] = $this->makeInvoice('ILS');
            $attempt = $this->makeAttempt($invoice, 'ILS');
            $invoice->update([
                'payment_session_status' => Invoice::PAYMENT_SESSION_READY,
                'payment_session_attempt_id' => $attempt->id,
            ]);
            $event = new WebhookEvent(
                WebhookEvent::TYPE_PAYMENT_SUCCEEDED,
                $attempt->gateway_session_id,
                null,
                $case['amount'],
                $case['currency'],
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
            ])->assertStatus(401);

            $this->assertNotSame('paid', $invoice->fresh()->status);
            $this->assertNotSame(PaymentAttempt::STATUS_SUCCEEDED, $attempt->fresh()->status);
            $this->assertSame('ILS', $attempt->fresh()->currency);
            $this->assertSame(2500, $attempt->fresh()->gateway_amount_cents);
        }
    }

    public function test_client_domain_purchase_uses_quote_currency_for_item_and_invoice(): void
    {
        $provider = $this->makeProvider();
        $this->makeTldPrice($provider, 'ps', 12, 'ILS');
        $client = $this->makeClient();
        $this->fakeAvailability();

        $this->actingAs($client, 'client')->post(route('client.domains.purchase'), [
            'client_id' => $client->id,
            'domain_name' => 'direct.ps',
            'currency' => 'USD',
        ])->assertRedirect();

        $this->assertSame('ILS', OrderItem::query()->sole()->meta['currency']);
        $this->assertSame('ILS', Invoice::query()->sole()->currency);
    }

    private function makeProvider(string $type = 'namecheap'): DomainProvider
    {
        return DomainProvider::query()->create([
            'name' => strtoupper($type) . ' ' . uniqid(),
            'type' => $type,
            'mode' => 'test',
            'endpoint' => 'https://' . $type . '.example.test',
            'username' => 'test-user',
            'password' => 'test-password',
            'api_key' => 'test-key',
            'client_ip' => '127.0.0.1',
            'is_active' => true,
        ]);
    }

    private function makeTldPrice(DomainProvider $provider, string $tld, float $price, string $currency): void
    {
        $model = DomainTld::query()->create([
            'provider_id' => $provider->id,
            'provider' => $provider->type,
            'tld' => $tld,
            'currency' => $currency,
            'enabled' => true,
        ]);
        $model->prices()->create([
            'action' => 'register',
            'years' => 1,
            'cost' => $price,
            'sale' => $price,
        ]);
    }

    private function makeClient(): Client
    {
        return Client::query()->create([
            'first_name' => 'Currency',
            'last_name' => 'Truth',
            'email' => uniqid('currency_truth_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Currency Truth Test',
            'can_login' => true,
        ]);
    }

    private function makeInvoice(string $currency): array
    {
        $client = $this->makeClient();
        $invoice = Invoice::query()->create([
            'client_id' => $client->id,
            'number' => 'INV-' . uniqid(),
            'status' => 'draft',
            'subtotal_cents' => 2500,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 2500,
            'currency' => $currency,
        ]);

        return [$client, $invoice];
    }

    private function makeAttempt(Invoice $invoice, string $currency): PaymentAttempt
    {
        return PaymentAttempt::query()->create([
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'gateway' => 'lahza',
            'idempotency_key' => (string) Str::uuid(),
            'gateway_session_id' => (string) Str::uuid(),
            'gateway_amount_cents' => $invoice->total_cents,
            'currency' => $currency,
            'status' => PaymentAttempt::STATUS_INITIATED,
            'gateway_response' => ['checkout_url' => 'https://pay.test/existing'],
        ]);
    }

    private function fakeAvailability(): void
    {
        $this->app->instance(DomainAvailabilityService::class, new class extends DomainAvailabilityService
        {
            public function verifyRegistrationAvailabilityBatch(array $domains, DomainProvider $provider): ?array
            {
                return array_fill_keys(array_map('strtolower', $domains), true);
            }

            public function checkDomains(array $domains, ?DomainProvider $provider = null): array
            {
                return [
                    'ok' => true,
                    'reason' => 'ok',
                    'message' => 'ok',
                    'results' => array_map(fn (string $domain) => [
                        'domain' => $domain,
                        'available' => true,
                        'is_premium' => false,
                        'price' => null,
                        'currency' => null,
                    ], $domains),
                ];
            }
        });
    }

    private function fakePaymentManager(?callable $createSession, int $expectedCalls): PaymentManager
    {
        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('name')->andReturn('lahza');
        if ($expectedCalls === 0) {
            $gateway->shouldNotReceive('createSession');
        } else {
            $gateway->shouldReceive('createSession')->times($expectedCalls)->andReturnUsing($createSession);
        }

        $manager = Mockery::mock(PaymentManager::class);
        $manager->shouldReceive('isEnabled')->andReturnTrue();
        $manager->shouldReceive('gateway')->andReturn($gateway);
        $this->app->instance(PaymentManager::class, $manager);

        return $manager;
    }
}
