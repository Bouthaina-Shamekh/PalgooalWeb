<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\DomainProvider;
use App\Models\DomainTld;
use App\Models\DomainTldPrice;
use App\Services\Domains\DomainAvailabilityService;
use App\Services\Domains\DomainPricingService;
use App\Support\Sections\SectionQueryResolver;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use ReflectionMethod;
use Tests\TestCase;

/**
 * TLD-3A — Require Explicit Sale Price for Domain Quotes.
 *
 * Locks in the new business rule: a registration quote is only ever derived from an explicit,
 * valid `sale` (numeric, > 0). `cost` (provider cost from sync) must never surface as a
 * customer-facing price, whether directly via DomainPricingService::pickValidPrice(), or via the
 * two independent display-only fallbacks identified during the TLD-3A audit gate
 * (Client\DomainController::buildSearchCatalog() and SectionQueryResolver::searchDomain()).
 *
 * Cart/Checkout/Client-purchase guards are NOT duplicated here beyond what's needed to prove the
 * contract holds end-to-end — all three already re-derive price exclusively through
 * DomainPricingService::registrationQuoteForDomain() (see ADR comments in each controller), so a
 * sale-only pickValidPrice() is expected to make them reject unsellable items automatically.
 *
 * Out of scope (explicitly, per TLD-3A decision): DomainRenewalService — it keeps an independent
 * cost fallback (renew sale -> renew cost -> register sale/cost -> hardcoded 10.0 * years),
 * deferred to TLD-3B — Renewal Pricing Contract Audit. Not touched, not tested here.
 *
 * TLD-3F.1 — fixture note: every provider in this file is now created with mode='live'. This
 * file tests sale-only pricing rules exclusively; provider mode was never the subject of any
 * assertion here, but a mode='test' provider is no longer eligible at all under the live-only
 * customer eligibility contract, so the generic fixtures were updated to keep these tests
 * exercising the sale-only rule they were written for.
 */
class DomainSaleOnlyPricingTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    /* ====================== DomainPricingService — sale-only contract ====================== */

    public function test_registration_quote_uses_sale_when_valid(): void
    {
        $provider = $this->makeProvider('namecheap', 'live');
        $this->makePrice($this->makeTld($provider, 'com'), sale: 12.99, cost: 8.00);

        $quote = app(DomainPricingService::class)->registrationQuoteForDomain('example.com');

        $this->assertNotNull($quote);
        $this->assertSame(12.99, $quote['price']);
        $this->assertSame(1299, $quote['price_cents']);
    }

    public function test_registration_quote_is_null_when_sale_is_null_even_with_a_cost_value(): void
    {
        $provider = $this->makeProvider('namecheap', 'live');
        $this->makePrice($this->makeTld($provider, 'com'), sale: null, cost: 13.00);

        $this->assertNull(app(DomainPricingService::class)->registrationQuoteForDomain('nosale.com'));
    }

    public function test_registration_quote_is_null_when_sale_is_zero(): void
    {
        $provider = $this->makeProvider('namecheap', 'live');
        $this->makePrice($this->makeTld($provider, 'com'), sale: 0, cost: 13.00);

        $this->assertNull(app(DomainPricingService::class)->registrationQuoteForDomain('zero-sale.com'));
    }

    public function test_pick_valid_price_rejects_a_non_numeric_sale(): void
    {
        // domain_tld_prices.sale is a nullable decimal(10,2) column, so a non-numeric value can
        // never actually reach the database through the application — this exercises the guard
        // directly (defense in depth), per TLD-3A section 14 item 4's own "if schema/test setup
        // allows it" qualifier.
        $method = new ReflectionMethod(DomainPricingService::class, 'pickValidPrice');
        $method->setAccessible(true);
        $service = app(DomainPricingService::class);

        $this->assertNull($method->invoke($service, 'not-a-number'));
        $this->assertNull($method->invoke($service, ''));
        $this->assertNull($method->invoke($service, null));
        $this->assertNull($method->invoke($service, -5));
        $this->assertSame(5.5, $method->invoke($service, '5.50'));
    }

    public function test_multi_provider_quote_prefers_the_provider_with_a_valid_sale_over_a_cheaper_cost_only_row(): void
    {
        $namecheap = $this->makeProvider('namecheap', 'live');
        $enom = $this->makeProvider('enom', 'live');
        // TLD-3A spec section 4's worked example: Namecheap cost=13/sale=null must not win just
        // because its cost is cheaper than Enom's sale=18.
        $this->makePrice($this->makeTld($namecheap, 'com'), sale: null, cost: 13.00);
        $this->makePrice($this->makeTld($enom, 'com'), sale: 18.00, cost: 14.00);

        $quote = app(DomainPricingService::class)->registrationQuoteForDomain('contested.com');

        $this->assertNotNull($quote);
        $this->assertSame('enom', $quote['provider_type']);
        $this->assertSame($enom->id, $quote['provider_id']);
        $this->assertSame(1800, $quote['price_cents']);
    }

    public function test_registration_quote_is_null_when_every_provider_has_no_sale(): void
    {
        $namecheap = $this->makeProvider('namecheap', 'live');
        $enom = $this->makeProvider('enom', 'live');
        $this->makePrice($this->makeTld($namecheap, 'com'), sale: null, cost: 13.00);
        $this->makePrice($this->makeTld($enom, 'com'), sale: null, cost: 14.00);

        $this->assertNull(app(DomainPricingService::class)->registrationQuoteForDomain('unsellable.com'));
    }

    /* ====================== Front search: availability vs sellability ====================== */

    public function test_search_reports_available_and_sellable_when_a_trusted_sale_quote_exists(): void
    {
        $provider = $this->makeProvider('namecheap', 'live');
        $this->makePrice($this->makeTld($provider, 'com'), sale: 12.99, cost: 8.00);
        $this->app->instance(DomainAvailabilityService::class, $this->fakeAlwaysAvailable());

        $response = $this->getJson(route('domains.check', ['q' => 'sellable', 'tlds' => 'com']));
        $result = $response->assertOk()->json('results.0');

        $this->assertSame('available', $result['status']);
        $this->assertTrue($result['sellable']);
        $this->assertSame('ok', $result['pricing_status']);
        $this->assertSame(12.99, $result['price']);
    }

    public function test_search_reports_available_but_unsellable_when_provider_has_it_but_sale_is_missing(): void
    {
        $provider = $this->makeProvider('namecheap', 'live');
        $this->makePrice($this->makeTld($provider, 'com'), sale: null, cost: 13.00);
        $this->app->instance(DomainAvailabilityService::class, $this->fakeAlwaysAvailable());

        $response = $this->getJson(route('domains.check', ['q' => 'unsellable', 'tlds' => 'com']));
        $result = $response->assertOk()->json('results.0');

        // Still "available" — the provider confirmed it. Never downgraded to unavailable/unknown.
        $this->assertSame('available', $result['status']);
        $this->assertTrue($result['available']);
        $this->assertFalse($result['sellable']);
        $this->assertSame('missing_sale', $result['pricing_status']);
        $this->assertNull($result['price']);
        $this->assertNull($result['currency']);
    }

    /* ====================== Cart / Checkout / Client purchase guards ====================== */

    public function test_cart_rejects_a_domain_with_no_trusted_sale_quote_and_persists_nothing(): void
    {
        $provider = $this->makeProvider('namecheap', 'live');
        $this->makePrice($this->makeTld($provider, 'com'), sale: null, cost: 13.00);

        $response = $this->postJson(route('cart.store'), [
            'items' => [[
                'domain' => 'no-sale-cart.com',
                'item_option' => 'register',
                'price_cents' => 1300,
            ]],
        ]);

        $response->assertStatus(422)->assertJsonPath('ok', false);
        $this->assertEmpty(session('palgoals_cart_domains', []));
    }

    public function test_checkout_rejects_a_previously_priced_cart_item_after_its_sale_is_removed(): void
    {
        // TLD-3A.1 — audited against the ACTUAL route contract before writing this assertion:
        // checkout.cart.process -> CheckoutController::processCart() requires 'items' in the
        // request body itself (it does NOT fall back to the session cart), then delegates to
        // process(), whose pricing loop is what returns {success:false, message} on a missing
        // trusted quote (confirmed identical to CurrencySourceOfTruthTest's usage of the same
        // route). Posting no body at all instead hits processCart()'s own field-validation
        // ('items' => 'required|array|min:1'), which is a Laravel ValidationException response
        // ({message, errors} — no 'success' key) and is a different contract entirely; that
        // mismatch, not a CheckoutController regression, was the TLD-3A runtime failure's cause.
        $provider = $this->makeProvider('namecheap', 'live');
        $tld = $this->makeTld($provider, 'com');
        $price = $this->makePrice($tld, sale: 10.00, cost: 7.00);
        $client = $this->makeClient();

        // Sale is withdrawn after the item was priced (e.g. an admin unpublishes it) — simulates
        // a stale/forged quote reaching checkout.
        $price->update(['sale' => null]);

        $this->fakePaymentManager();
        $response = $this->actingAs($client, 'client')->postJson(route('checkout.cart.process'), [
            'items' => [[
                'domain' => 'stale-quote.com',
                'option' => 'register',
                'price_cents' => 1000,
            ]],
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
        $this->assertSame(0, \App\Models\Order::query()->count());
        $this->assertSame(0, \App\Models\OrderItem::query()->count());
        $this->assertSame(0, \App\Models\Invoice::query()->count());
        $this->assertSame(0, \App\Models\InvoiceItem::query()->count());
        $this->assertSame(0, \App\Models\PaymentAttempt::query()->count());
    }

    public function test_client_domain_purchase_rejects_a_domain_with_no_trusted_sale_quote(): void
    {
        $provider = $this->makeProvider('namecheap', 'live');
        $this->makePrice($this->makeTld($provider, 'com'), sale: null, cost: 13.00);
        $client = $this->makeClient();
        $this->app->instance(DomainAvailabilityService::class, $this->fakeAlwaysAvailable());

        $response = $this->actingAs($client, 'client')->post(route('client.domains.purchase'), [
            'client_id' => $client->id,
            'domain_name' => 'no-sale-direct.com',
        ]);

        $response->assertRedirect();
        $this->assertSame(0, \App\Models\Order::query()->count());
        $this->assertSame(0, \App\Models\Invoice::query()->count());
    }

    /* ====================== Independent display-only fallbacks removed ====================== */

    public function test_client_domain_search_catalog_excludes_cost_as_a_fallback_price(): void
    {
        $provider = $this->makeProvider('namecheap', 'live');
        $this->makePrice($this->makeTld($provider, 'com', inCatalog: true), sale: 9.99, cost: 5.00);
        $this->makePrice($this->makeTld($provider, 'net', inCatalog: true), sale: null, cost: 13.00);
        $client = $this->makeClient();

        $response = $this->actingAs($client, 'client')->get(route('client.domains.search'));

        $response->assertOk();
        $fallbackPrices = $response->viewData('fallback_prices');
        $this->assertSame(9.99, $fallbackPrices['com'] ?? null);
        $this->assertArrayNotHasKey('net', $fallbackPrices);
    }

    public function test_section_query_resolver_search_domain_excludes_cost_as_a_fallback_price(): void
    {
        $provider = $this->makeProvider('namecheap', 'live');
        $this->makePrice($this->makeTld($provider, 'com', inCatalog: true), sale: 9.99, cost: 5.00);
        $this->makePrice($this->makeTld($provider, 'net', inCatalog: true), sale: null, cost: 13.00);

        $data = SectionQueryResolver::resolve('search-domain', []);

        $this->assertSame(9.99, $data['fallback_prices']['com'] ?? null);
        $this->assertArrayNotHasKey('net', $data['fallback_prices']);
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
            'first_name' => 'SaleOnly',
            'last_name' => 'Pricing',
            'email' => uniqid('sale_only_pricing_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Sale Only Pricing Test',
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

    private function fakePaymentManager(): void
    {
        $gateway = \Mockery::mock(\App\Payments\Contracts\PaymentGatewayInterface::class);
        $gateway->shouldReceive('name')->andReturn('lahza');
        $gateway->shouldReceive('createSession')->zeroOrMoreTimes()->andReturn(
            new \App\Payments\DTOs\PaymentSession('sale-only-session', 'https://pay.test/sale-only-session')
        );

        $manager = \Mockery::mock(\App\Payments\PaymentManager::class);
        $manager->shouldReceive('isEnabled')->andReturnTrue();
        $manager->shouldReceive('gateway')->andReturn($gateway);
        $this->app->instance(\App\Payments\PaymentManager::class, $manager);
    }
}
