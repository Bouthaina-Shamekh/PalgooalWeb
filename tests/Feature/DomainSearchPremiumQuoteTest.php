<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\Management\DomainSearchController;
use App\Models\DomainProvider;
use App\Models\DomainTld;
use App\Services\Domains\DomainAvailabilityService;
use App\Services\Domains\DomainPricingService;
use Closure;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Premium Trusted Quote Lifecycle — Step 2 only.
 *
 * Scope: DomainSearchController::check() (the public `domains.check` endpoint)
 * and DomainPricingService::providersForTlds(). This file does NOT re-test
 * PremiumQuoteManager's own validation rules (already covered exhaustively by
 * tests/Feature/PremiumQuoteManagerTest.php, 35 tests / 47 assertions,
 * untouched by Step 2) — it only proves that Step 2's integration wiring
 * (provider-identity preservation, conditional issuance, fail-closed
 * behavior, and public-response shape) is correct.
 *
 * Registrar availability is always faked (fakeCheckDomains()); no real
 * Namecheap/Enom API is ever called.
 */
class DomainSearchPremiumQuoteTest extends TestCase
{
    use DatabaseMigrations;

    private const SESSION_KEY = 'palgoals_premium_quotes';

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    /* ====================== 1. providersForTlds() exposes domain_tld_id ====================== */

    public function test_providers_for_tlds_returns_domain_tld_id(): void
    {
        $provider = $this->makeProvider('namecheap', 'live');
        $tld = $this->makeTld($provider, 'com');

        $result = app(DomainPricingService::class)->providersForTlds(['com']);

        $this->assertArrayHasKey('com', $result);
        $this->assertSame($provider->id, $result['com']['provider_id']);
        $this->assertSame('namecheap', $result['com']['provider_type']);
        $this->assertSame('live', $result['com']['provider_mode']);
        $this->assertSame($tld->id, $result['com']['domain_tld_id']);
        $this->assertIsInt($result['com']['domain_tld_id']);
    }

    /* ====================== 2. Available normal domain — unchanged, no quote_token ====================== */

    public function test_available_normal_domain_price_unchanged_and_quote_token_null(): void
    {
        $provider = $this->makeProvider('namecheap', 'live');
        $tld = $this->makeTld($provider, 'com');
        $this->makeTldPrice($tld, 12.99);

        $this->fakeCheckDomains(function (array $domains) {
            return [
                'ok' => true, 'reason' => 'ok', 'message' => 'تم.',
                'results' => array_map(fn (string $d) => [
                    'domain' => $d, 'available' => true, 'is_premium' => false,
                ], $domains),
            ];
        });

        $response = $this->getJson(route('domains.check', ['q' => 'plainword', 'tlds' => 'com']));

        $response->assertOk();
        $result = $response->json('results.0');

        $this->assertSame('available', $result['status']);
        $this->assertFalse($result['is_premium']);
        $this->assertSame(12.99, $result['price']);
        $this->assertSame('USD', $result['currency']);
        $this->assertTrue($result['sellable']);
        $this->assertSame('ok', $result['pricing_status']);
        $this->assertNull($result['quote_token']);
    }

    /* ====================== 3. Available trusted Namecheap premium domain ====================== */

    public function test_available_trusted_namecheap_premium_domain_gets_quote_token(): void
    {
        $provider = $this->makeProvider('namecheap', 'live');
        $tld = $this->makeTld($provider, 'xyz', currency: 'USD', supportsPremium: true);
        // Deliberately no DomainTldPrice row: the premium price must come
        // from the provider's availability response, never the catalog, and
        // provider routing must still work via providersForTlds()'s
        // availability-only fallback (no Trusted Quote required for that).

        $this->fakeCheckDomains(function (array $domains) {
            return [
                'ok' => true, 'reason' => 'ok', 'message' => 'تم.',
                'results' => array_map(fn (string $d) => [
                    'domain' => $d, 'available' => true, 'is_premium' => true,
                    'price' => 3250.00, 'currency' => 'USD',
                ], $domains),
            ];
        });

        $response = $this->getJson(route('domains.check', ['q' => 'us', 'tlds' => 'xyz']));

        $response->assertOk();
        $result = $response->json('results.0');

        $this->assertTrue($result['is_premium']);
        $this->assertEquals(3250.0, $result['price']);
        $this->assertSame('USD', $result['currency']);
        $this->assertTrue($result['sellable']);
        $this->assertSame('ok', $result['pricing_status']);
        $this->assertIsString($result['quote_token']);
        $this->assertTrue(Str::isUuid($result['quote_token']));

        $stored = session(self::SESSION_KEY.'.'.$result['quote_token']);
        $this->assertIsArray($stored);
        $this->assertSame('us.xyz', $stored['domain']);
        $this->assertTrue($stored['is_premium']);
        $this->assertSame($provider->id, $stored['provider_id']);
        $this->assertSame('namecheap', $stored['provider_type']);
        $this->assertSame('live', $stored['provider_mode']);
        $this->assertSame($tld->id, $stored['domain_tld_id']);
        $this->assertSame(3250.0, $stored['price']);
        $this->assertSame(325000, $stored['price_cents']);
        $this->assertSame('USD', $stored['currency']);
        $this->assertSame(1, $stored['years']);
    }

    /* ====================== 4. Provider identity comes from the provider group, not the request ====================== */

    public function test_actual_provider_identity_is_never_taken_from_request_input(): void
    {
        $ownerProvider = $this->makeProvider('namecheap', 'live');
        $tld = $this->makeTld($ownerProvider, 'xyz', supportsPremium: true);

        // A second, unrelated live Namecheap provider that owns no TLD row
        // at all. If provider identity were ever taken from (or influenced
        // by) request input instead of the actual provider group, a crafted
        // request could point the quote at this provider instead.
        $decoyProvider = $this->makeProvider('namecheap', 'live');

        $this->fakeCheckDomains(function (array $domains) {
            return [
                'ok' => true, 'reason' => 'ok', 'message' => 'تم.',
                'results' => array_map(fn (string $d) => [
                    'domain' => $d, 'available' => true, 'is_premium' => true,
                    'price' => 500.00, 'currency' => 'USD',
                ], $domains),
            ];
        });

        $response = $this->getJson(route('domains.check', [
            'q' => 'us',
            'tlds' => 'xyz',
            // Unrecognized fields that an attacker might hope influence
            // provider/TLD resolution. normalizeDomains()/queryTlds() only
            // ever read q/tlds/domains, so these must have zero effect.
            'provider_id' => $decoyProvider->id,
            'provider_type' => 'enom',
            'provider_mode' => 'test',
            'domain_tld_id' => 999999,
        ]));

        $response->assertOk();
        $token = $response->json('results.0.quote_token');
        $this->assertIsString($token);

        $stored = session(self::SESSION_KEY.'.'.$token);
        $this->assertSame($ownerProvider->id, $stored['provider_id']);
        $this->assertNotSame($decoyProvider->id, $stored['provider_id']);
        $this->assertSame($tld->id, $stored['domain_tld_id']);
        $this->assertSame('namecheap', $stored['provider_type']);
        $this->assertSame('live', $stored['provider_mode']);
    }

    /* ====================== 5. Invalid/missing trusted context — fail closed ====================== */

    public function test_premium_result_with_untrusted_context_has_no_token_and_is_not_sellable(): void
    {
        $provider = $this->makeProvider('namecheap', 'live');
        // supports_premium=false: PremiumQuoteManager::issue() must reject
        // this at its own internal cross-check (Requirement 6 — this is a
        // local eligibility flag only, enforced server-side, never bypassed).
        $tld = $this->makeTld($provider, 'xyz', supportsPremium: false);

        $this->fakeCheckDomains(function (array $domains) {
            return [
                'ok' => true, 'reason' => 'ok', 'message' => 'تم.',
                'results' => array_map(fn (string $d) => [
                    'domain' => $d, 'available' => true, 'is_premium' => true,
                    'price' => 3250.00, 'currency' => 'USD',
                ], $domains),
            ];
        });

        $response = $this->getJson(route('domains.check', ['q' => 'us', 'tlds' => 'xyz']));

        $response->assertOk();
        $result = $response->json('results.0');

        // Display data may remain (it is UI-only, not a purchase input) —
        // but the domain must not be represented as purchasable, and there
        // must be no fallback to a normal catalog price.
        $this->assertTrue($result['is_premium']);
        $this->assertEquals(3250.0, $result['price']);
        $this->assertFalse($result['sellable']);
        $this->assertNull($result['quote_token']);

        $this->assertSame([], (array) session(self::SESSION_KEY, []));
    }

    /* ====================== 6. Non-live / non-Namecheap context cannot yield a trusted token ====================== */

    public function test_premium_result_from_enom_provider_cannot_produce_a_trusted_token(): void
    {
        // Enom is an otherwise-eligible provider type for availability/catalog
        // routing, but only a live Namecheap context may ever produce a
        // premium trusted quote (PremiumQuoteManager::PROVIDER_TYPE).
        $provider = $this->makeProvider('enom', 'live');
        $this->makeTld($provider, 'xyz', supportsPremium: true);

        $this->fakeCheckDomains(function (array $domains) {
            return [
                'ok' => true, 'reason' => 'ok', 'message' => 'تم.',
                'results' => array_map(fn (string $d) => [
                    'domain' => $d, 'available' => true, 'is_premium' => true,
                    'price' => 3250.00, 'currency' => 'USD',
                ], $domains),
            ];
        });

        $response = $this->getJson(route('domains.check', ['q' => 'us', 'tlds' => 'xyz']));

        $response->assertOk();
        $result = $response->json('results.0');

        $this->assertTrue($result['is_premium']);
        $this->assertNull($result['quote_token']);
        $this->assertFalse($result['sellable']);
        $this->assertSame([], (array) session(self::SESSION_KEY, []));
    }

    public function test_issue_premium_quote_token_rejects_non_live_provider_context_directly(): void
    {
        // Belt-and-suspenders unit-level proof for the "non-live" half of
        // Requirement 6: a non-live provider can never even enter a
        // provider group in the real check() flow (DomainPricingService
        // filters provider.mode='live' at the query level), so this
        // exercises DomainSearchController::issuePremiumQuoteToken()'s own
        // internal guard directly, independent of that DB-level exclusion.
        $provider = $this->makeProvider('namecheap', 'live');
        $tld = $this->makeTld($provider, 'xyz', supportsPremium: true);

        $controller = app(DomainSearchController::class);
        $method = new ReflectionMethod($controller, 'issuePremiumQuoteToken');
        $method->setAccessible(true);

        $token = $method->invoke($controller, 'us.xyz', 3250.00, 'USD', [
            'provider_id'   => $provider->id,
            'provider_type' => 'namecheap',
            'provider_mode' => 'test',
            'domain_tld_id' => $tld->id,
        ]);

        $this->assertNull($token);
    }

    public function test_issue_premium_quote_token_rejects_non_namecheap_provider_type_directly(): void
    {
        $provider = $this->makeProvider('enom', 'live');
        $tld = $this->makeTld($provider, 'xyz', supportsPremium: true);

        $controller = app(DomainSearchController::class);
        $method = new ReflectionMethod($controller, 'issuePremiumQuoteToken');
        $method->setAccessible(true);

        $token = $method->invoke($controller, 'us.xyz', 3250.00, 'USD', [
            'provider_id'   => $provider->id,
            'provider_type' => 'enom',
            'provider_mode' => 'live',
            'domain_tld_id' => $tld->id,
        ]);

        $this->assertNull($token);
    }

    /* ====================== 7. Provider identity fields never exposed in public JSON ====================== */

    public function test_provider_identity_fields_are_not_exposed_in_public_response(): void
    {
        $provider = $this->makeProvider('namecheap', 'live');
        $this->makeTld($provider, 'xyz', supportsPremium: true);

        $this->fakeCheckDomains(function (array $domains) {
            return [
                'ok' => true, 'reason' => 'ok', 'message' => 'تم.',
                'results' => array_map(fn (string $d) => [
                    'domain' => $d, 'available' => true, 'is_premium' => true,
                    'price' => 3250.00, 'currency' => 'USD',
                ], $domains),
            ];
        });

        $response = $this->getJson(route('domains.check', ['q' => 'us', 'tlds' => 'xyz']));

        $response->assertOk();
        $result = $response->json('results.0');

        $this->assertIsString($result['quote_token']);
        $this->assertEqualsCanonicalizing([
            'domain', 'available', 'status', 'is_premium', 'price', 'currency',
            'sellable', 'pricing_status', 'quote_token',
        ], array_keys($result));

        foreach (['provider_id', 'provider_type', 'provider_mode', 'domain_tld_id', 'source', 'expires_at'] as $leaked) {
            $this->assertArrayNotHasKey($leaked, $result);
        }
    }

    /* ====================== Helpers ====================== */

    private function fakeCheckDomains(Closure $resultsForDomains): void
    {
        $fake = new class($resultsForDomains) extends DomainAvailabilityService {
            private Closure $resultsForDomains;

            public function __construct(Closure $resultsForDomains)
            {
                $this->resultsForDomains = $resultsForDomains;
            }

            public function checkDomains(array $domains, ?DomainProvider $provider = null): array
            {
                return ($this->resultsForDomains)($domains);
            }
        };

        $this->app->instance(DomainAvailabilityService::class, $fake);
    }

    private function makeProvider(string $type, string $mode): DomainProvider
    {
        return DomainProvider::query()->create([
            'name' => strtoupper($type).' '.uniqid(),
            'type' => $type,
            'mode' => $mode,
            'endpoint' => 'https://'.$type.'.example.test',
            'username' => 'test-user',
            'password' => 'test-password',
            'api_key' => 'test-key',
            'client_ip' => '127.0.0.1',
            'is_active' => true,
        ]);
    }

    private function makeTld(
        DomainProvider $provider,
        string $tld,
        string $currency = 'USD',
        bool $enabled = true,
        bool $supportsPremium = true,
    ): DomainTld {
        return DomainTld::query()->create([
            'provider_id' => $provider->id,
            'provider' => $provider->type,
            'tld' => $tld,
            'currency' => $currency,
            'enabled' => $enabled,
            'supports_premium' => $supportsPremium,
            'in_catalog' => true,
        ]);
    }

    private function makeTldPrice(DomainTld $tld, float $price): void
    {
        $tld->prices()->create([
            'action' => 'register',
            'years' => 1,
            'cost' => $price,
            'sale' => $price,
        ]);
    }
}
