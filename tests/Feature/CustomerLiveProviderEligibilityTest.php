<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\DomainProvisioningAttempt;
use App\Models\DomainTld;
use App\Models\DomainTldPrice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\DTOs\PaymentSession;
use App\Payments\PaymentManager;
use App\Services\Domains\DomainAvailabilityService;
use App\Services\Domains\DomainPricingService;
use App\Services\Domains\RegistrarProvisioningService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Mockery;
use Tests\TestCase;

/**
 * TLD-3F.1 — Live Provider Eligibility Guard.
 *
 * A test/sandbox-mode DomainProvider must never participate in the real customer-facing
 * domain-purchase flow. Production eligibility contract for customer search/pricing/cart/
 * checkout/purchase:
 *
 *   is_active = true AND mode = 'live' AND type IN ('enom', 'namecheap')
 *
 * This file is the explicit home for that contract. It intentionally builds mode='test'
 * providers as adversarial/competing fixtures (unlike the other regression suites this phase
 * touched, where 'test' was only ever an incidental default) to prove they are excluded at
 * every layer: pricing selection, availability-only fallback, public search, cart, checkout,
 * authenticated client purchase, and provisioning.
 */
class CustomerLiveProviderEligibilityTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    /* ====================== A/B — cheaper Test provider never wins ====================== */

    public function test_enom_live_wins_over_cheaper_namecheap_test_for_the_same_tld(): void
    {
        $enomLive = $this->makeProvider('enom', 'live');
        $namecheapTest = $this->makeProvider('namecheap', 'test');
        $this->makePrice($this->makeTld($enomLive, 'com'), sale: 15.00, cost: 10.00);
        $this->makePrice($this->makeTld($namecheapTest, 'com'), sale: 10.00, cost: 6.00);

        $quote = app(DomainPricingService::class)->registrationQuoteForDomain('cheaper-test.com');

        $this->assertNotNull($quote);
        $this->assertSame($enomLive->id, $quote['provider_id']);
        $this->assertSame('enom', $quote['provider_type']);
        $this->assertSame('live', $quote['provider_mode']);
        $this->assertSame(1500, $quote['price_cents']);
    }

    public function test_namecheap_live_wins_over_cheaper_enom_test_for_the_same_tld(): void
    {
        $namecheapLive = $this->makeProvider('namecheap', 'live');
        $enomTest = $this->makeProvider('enom', 'test');
        $this->makePrice($this->makeTld($namecheapLive, 'net'), sale: 14.00, cost: 9.00);
        $this->makePrice($this->makeTld($enomTest, 'net'), sale: 5.00, cost: 3.00);

        $quote = app(DomainPricingService::class)->registrationQuoteForDomain('cheaper-test.net');

        $this->assertNotNull($quote);
        $this->assertSame($namecheapLive->id, $quote['provider_id']);
        $this->assertSame('namecheap', $quote['provider_type']);
        $this->assertSame('live', $quote['provider_mode']);
        $this->assertSame(1400, $quote['price_cents']);
    }

    /* ====================== C/D/E — sale-only interaction with mode ====================== */

    public function test_only_test_provider_having_a_valid_sale_produces_no_customer_quote(): void
    {
        $namecheapTest = $this->makeProvider('namecheap', 'test');
        $this->makePrice($this->makeTld($namecheapTest, 'com'), sale: 9.99, cost: 5.00);

        $quote = app(DomainPricingService::class)->registrationQuoteForDomain('only-test.com');

        $this->assertNull($quote);
    }

    public function test_live_provider_with_sale_is_selected_normally_when_test_provider_has_no_sale(): void
    {
        $enomLive = $this->makeProvider('enom', 'live');
        $namecheapTest = $this->makeProvider('namecheap', 'test');
        $this->makePrice($this->makeTld($enomLive, 'com'), sale: 12.00, cost: 8.00);
        $this->makePrice($this->makeTld($namecheapTest, 'com'), sale: null, cost: 4.00);

        $quote = app(DomainPricingService::class)->registrationQuoteForDomain('live-normal.com');

        $this->assertNotNull($quote);
        $this->assertSame($enomLive->id, $quote['provider_id']);
        $this->assertSame(1200, $quote['price_cents']);
    }

    public function test_test_provider_sale_never_surfaces_when_live_provider_has_no_sale(): void
    {
        $enomLive = $this->makeProvider('enom', 'live');
        $namecheapTest = $this->makeProvider('namecheap', 'test');
        $this->makePrice($this->makeTld($enomLive, 'com'), sale: null, cost: 8.00);
        $this->makePrice($this->makeTld($namecheapTest, 'com'), sale: 9.99, cost: 4.00);

        $quote = app(DomainPricingService::class)->registrationQuoteForDomain('test-only-sale.com');

        $this->assertNull($quote);
    }

    /* ====================== F — availability-only fallback ignores Test ====================== */

    public function test_availability_only_fallback_ignores_test_providers(): void
    {
        $namecheapTest = $this->makeProvider('namecheap', 'test');
        $this->makeTld($namecheapTest, 'xyz');

        $result = app(DomainPricingService::class)->providersForTlds(['xyz']);

        $this->assertArrayNotHasKey('xyz', $result);
    }

    public function test_availability_only_fallback_still_resolves_a_live_provider(): void
    {
        $enomLive = $this->makeProvider('enom', 'live');
        $namecheapTest = $this->makeProvider('namecheap', 'test');
        $this->makeTld($enomLive, 'xyz');
        $this->makeTld($namecheapTest, 'xyz');

        $result = app(DomainPricingService::class)->providersForTlds(['xyz']);

        $this->assertArrayHasKey('xyz', $result);
        $this->assertSame($enomLive->id, $result['xyz']['provider_id']);
        $this->assertSame('live', $result['xyz']['provider_mode']);
    }

    /* ====================== G/H — public search: no identity leak, no Test call ====================== */

    public function test_public_search_response_never_exposes_provider_identity(): void
    {
        $enomLive = $this->makeProvider('enom', 'live');
        $namecheapTest = $this->makeProvider('namecheap', 'test');
        $this->makePrice($this->makeTld($enomLive, 'com'), sale: 15.00, cost: 10.00);
        $this->makePrice($this->makeTld($namecheapTest, 'com'), sale: 10.00, cost: 6.00);
        $this->app->instance(DomainAvailabilityService::class, $this->fakeAlwaysAvailable());

        $response = $this->getJson(route('domains.check', ['q' => 'no-leak', 'tlds' => 'com']));
        $response->assertOk();

        $raw = $response->getContent();
        $this->assertStringNotContainsString('provider_id', $raw);
        $this->assertStringNotContainsString('"provider"', $raw);
        $this->assertStringNotContainsString('namecheap', $raw);
        $this->assertStringNotContainsString('enom', $raw);
    }

    public function test_public_search_never_calls_a_test_mode_provider_and_domain_with_only_a_test_provider_stays_unknown(): void
    {
        $namecheapTest = $this->makeProvider('namecheap', 'test');
        $this->makePrice($this->makeTld($namecheapTest, 'zzz'), sale: 9.99, cost: 4.00);
        $spy = $this->spyAvailability();
        $this->app->instance(DomainAvailabilityService::class, $spy);

        $response = $this->getJson(route('domains.check', ['q' => 'only-test-provider', 'tlds' => 'zzz']));
        $response->assertOk();

        // No provider was eligible at all for this TLD, so the controller never had a
        // provider_id > 0 to group this domain under — checkDomains() must not be called.
        $this->assertSame([], $spy->calledProviderIds);
        $this->assertNotSame($namecheapTest->id, $spy->calledProviderIds[0] ?? null);
        $result = $response->json('results.0');
        $this->assertSame('unknown', $result['status']);
    }

    public function test_public_search_calls_only_the_live_provider_when_a_cheaper_test_provider_also_exists(): void
    {
        $enomLive = $this->makeProvider('enom', 'live');
        $namecheapTest = $this->makeProvider('namecheap', 'test');
        $this->makePrice($this->makeTld($enomLive, 'com'), sale: 15.00, cost: 10.00);
        $this->makePrice($this->makeTld($namecheapTest, 'com'), sale: 10.00, cost: 6.00);
        $spy = $this->spyAvailability();
        $this->app->instance(DomainAvailabilityService::class, $spy);

        $response = $this->getJson(route('domains.check', ['q' => 'live-only-call', 'tlds' => 'com']));
        $response->assertOk();

        $this->assertSame([$enomLive->id], $spy->calledProviderIds);
    }

    /* ====================== I/J/K — cart/checkout/client purchase cannot snapshot Test ====================== */

    public function test_cart_rejects_a_domain_priced_only_by_a_test_provider(): void
    {
        $namecheapTest = $this->makeProvider('namecheap', 'test');
        $this->makePrice($this->makeTld($namecheapTest, 'com'), sale: 9.99, cost: 4.00);

        $response = $this->postJson(route('cart.store'), [
            'items' => [[
                'domain' => 'cart-test-only.com',
                'item_option' => 'register',
                'price_cents' => 999,
                'provider_id' => $namecheapTest->id,
                'provider_type' => 'namecheap',
                'provider_mode' => 'test',
            ]],
        ]);

        $response->assertStatus(422)->assertJsonPath('ok', false);
        $this->assertEmpty(session('palgoals_cart_domains', []));
    }

    public function test_checkout_rejects_a_domain_priced_only_by_a_test_provider(): void
    {
        $namecheapTest = $this->makeProvider('namecheap', 'test');
        $this->makePrice($this->makeTld($namecheapTest, 'com'), sale: 9.99, cost: 4.00);
        $client = $this->makeClient();
        $this->fakePaymentManager();

        $response = $this->actingAs($client, 'client')->postJson(route('checkout.cart.process'), [
            'items' => [[
                'domain' => 'checkout-test-only.com',
                'option' => 'register',
                'price_cents' => 999,
            ]],
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, OrderItem::query()->count());
    }

    public function test_authenticated_client_purchase_cannot_snapshot_a_test_mode_provider(): void
    {
        $namecheapTest = $this->makeProvider('namecheap', 'test');
        $this->makePrice($this->makeTld($namecheapTest, 'com'), sale: 9.99, cost: 4.00);
        $client = $this->makeClient();
        $this->app->instance(DomainAvailabilityService::class, $this->fakeAlwaysAvailable());

        $response = $this->actingAs($client, 'client')->post(route('client.domains.purchase'), [
            'client_id' => $client->id,
            'domain_name' => 'client-purchase-test-only.com',
            // Forged: attempts to point purchase directly at the test-mode provider.
            'provider_id' => $namecheapTest->id,
            'provider_type' => 'namecheap',
            'provider_mode' => 'test',
        ]);

        $response->assertRedirect();
        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, OrderItem::query()->count());
    }

    /* ====================== L — forged legacy provisioning snapshot ====================== */

    public function test_provisioning_rejects_a_forged_legacy_snapshot_pointing_at_a_test_mode_provider(): void
    {
        $namecheapTest = $this->makeProvider('namecheap', 'test');
        $client = $this->makeClient();
        $order = Order::query()->create([
            'client_id' => $client->id,
            'status' => Order::STATUS_PENDING,
            'type' => 'domains',
        ]);
        $item = $order->items()->create([
            'domain' => 'legacy-forged-' . uniqid() . '.com',
            'item_option' => 'register',
            'price_cents' => 1000,
            'meta' => [
                'provider_id' => $namecheapTest->id,
                'provider_type' => 'namecheap',
                'provider_mode' => 'test',
                'registration_date' => now()->toDateString(),
                'renewal_date' => now()->addYear()->toDateString(),
            ],
            'provisioning_status' => OrderItem::PROVISIONING_NOT_STARTED,
        ]);
        $service = $this->fakeRegistrar();

        $result = $service->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));

        $this->assertFalse($result['ok']);
        $this->assertSame([], $service->providerCalls);
        $this->assertSame(OrderItem::PROVISIONING_NOT_STARTED, $item->fresh()->provisioning_status);
        $this->assertSame(0, DomainProvisioningAttempt::query()->count());
    }

    /* ====================== M — inactive Live provider still excluded ====================== */

    public function test_inactive_live_provider_is_still_excluded_from_a_customer_quote(): void
    {
        $enomLive = $this->makeProvider('enom', 'live');
        $enomLive->update(['is_active' => false]);
        $this->makePrice($this->makeTld($enomLive, 'com'), sale: 15.00, cost: 10.00);

        $quote = app(DomainPricingService::class)->registrationQuoteForDomain('inactive-live.com');

        $this->assertNull($quote);
    }

    /* ====================== N — two Live providers: lowest-sale policy unchanged ====================== */

    public function test_two_live_providers_same_currency_still_use_lowest_sale_policy(): void
    {
        $namecheapLive = $this->makeProvider('namecheap', 'live');
        $enomLive = $this->makeProvider('enom', 'live');
        $this->makePrice($this->makeTld($namecheapLive, 'com'), sale: 11.00, cost: 7.00);
        $this->makePrice($this->makeTld($enomLive, 'com'), sale: 9.50, cost: 6.00);

        $quote = app(DomainPricingService::class)->registrationQuoteForDomain('two-live.com');

        $this->assertNotNull($quote);
        $this->assertSame($enomLive->id, $quote['provider_id']);
        $this->assertSame(950, $quote['price_cents']);
    }

    /* ====================== Helpers ====================== */

    private function makeProvider(string $type, string $mode): DomainProvider
    {
        return DomainProvider::query()->create([
            'name' => strtoupper($type) . ' ' . uniqid(),
            'type' => $type,
            'mode' => $mode,
            'endpoint' => 'https://' . $type . '.example.test',
            'username' => 'test-user',
            'password' => 'test-password',
            'api_key' => 'test-key',
            'client_ip' => '127.0.0.1',
            'is_active' => true,
        ]);
    }

    private function makeTld(DomainProvider $provider, string $tld, bool $inCatalog = false): DomainTld
    {
        return DomainTld::query()->create([
            'provider_id' => $provider->id,
            'provider' => $provider->type,
            'tld' => $tld,
            'currency' => 'USD',
            'enabled' => true,
            'in_catalog' => $inCatalog,
        ]);
    }

    private function makePrice(DomainTld $tld, ?float $sale, ?float $cost, string $action = 'register', int $years = 1): DomainTldPrice
    {
        return $tld->prices()->create([
            'action' => $action,
            'years' => $years,
            'sale' => $sale,
            'cost' => $cost,
        ]);
    }

    private function makeClient(): Client
    {
        return Client::query()->create([
            'first_name' => 'Live',
            'last_name' => 'Eligibility',
            'email' => uniqid('live_eligibility_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Live Eligibility Test',
            'can_login' => true,
        ]);
    }

    private function fakeAlwaysAvailable(): DomainAvailabilityService
    {
        return new class extends DomainAvailabilityService {
            public function checkDomains(array $domains, ?DomainProvider $provider = null): array
            {
                return [
                    'ok' => true,
                    'reason' => 'ok',
                    'message' => 'ok',
                    'results' => array_map(fn (string $domain) => [
                        'domain' => strtolower(trim($domain)),
                        'available' => true,
                        'is_premium' => false,
                    ], $domains),
                ];
            }

            public function verifyRegistrationAvailabilityBatch(array $domains, DomainProvider $provider): ?array
            {
                return array_fill_keys(array_map('strtolower', $domains), true);
            }
        };
    }

    private function spyAvailability(): DomainAvailabilityService
    {
        return new class extends DomainAvailabilityService {
            public array $calledProviderIds = [];

            public function checkDomains(array $domains, ?DomainProvider $provider = null): array
            {
                if ($provider instanceof DomainProvider) {
                    $this->calledProviderIds[] = $provider->id;
                }

                return [
                    'ok' => true,
                    'reason' => 'ok',
                    'message' => 'ok',
                    'results' => array_map(fn (string $domain) => [
                        'domain' => strtolower(trim($domain)),
                        'available' => true,
                        'is_premium' => false,
                    ], $domains),
                ];
            }
        };
    }

    private function fakeRegistrar(): RegistrarProvisioningService
    {
        return new class extends RegistrarProvisioningService {
            public array $providerCalls = [];

            protected function registerDomainWithProvider(
                DomainProvider $provider,
                Domain $domain,
                array $context,
                array $contact
            ): array {
                $this->providerCalls[$domain->domain_name] = $provider->id;

                return [
                    'ok' => true,
                    'cid' => 'live-eligibility-' . $provider->id,
                    'provider_reference' => 'reference-' . $provider->id,
                ];
            }
        };
    }

    private function fakePaymentManager(): void
    {
        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('name')->andReturn('lahza');
        $gateway->shouldReceive('createSession')
            ->zeroOrMoreTimes()
            ->andReturn(new PaymentSession('live-eligibility-session', 'https://pay.test/live-eligibility-session'));

        $manager = Mockery::mock(PaymentManager::class);
        $manager->shouldReceive('isEnabled')->andReturnTrue();
        $manager->shouldReceive('gateway')->andReturn($gateway);
        $this->app->instance(PaymentManager::class, $manager);
    }
}
