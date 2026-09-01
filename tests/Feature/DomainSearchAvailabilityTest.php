<?php

namespace Tests\Feature;

use App\Models\DomainProvider;
use App\Models\DomainTld;
use App\Services\Domains\DomainAvailabilityService;
use Closure;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

/**
 * D-BUILDER-1 — regression coverage for the public domain-search endpoint
 * (`domains.check` -> App\Http\Controllers\Admin\Management\DomainSearchController::check()).
 *
 * Deliberately scoped to the search endpoint's response contract only. Cart
 * item_option validation, provider/currency source-of-truth, and purchase
 * idempotency are already covered by DomainItemOptionValidationTest,
 * ProviderSourceOfTruthTest, CurrencySourceOfTruthTest and
 * ClientDomainPurchaseIdempotencyTest — not duplicated here.
 *
 * The central regression this file exists to prove: a domain the provider
 * could not resolve (missing from its response, or explicitly returned with
 * available => null) must surface as status "unknown", never silently
 * downgraded to "unavailable". DomainAvailabilityService::
 * verifyRegistrationAvailabilityBatch() already enforces this for the
 * purchase-time re-verification path; DomainSearchController::check() did
 * not, before D-BUILDER-1 — this file locks the fix in place.
 */
class DomainSearchAvailabilityTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    public function test_available_domain_is_returned_with_status_and_trusted_price(): void
    {
        $provider = $this->makeProvider('namecheap', 'test');
        $this->makeTldPrice($provider, 'com', 12.99, 'USD');

        $fake = $this->fakeAvailability(function (array $domains) {
            return [
                'ok' => true,
                'reason' => 'ok',
                'message' => 'تم.',
                'results' => array_map(fn (string $d) => [
                    'domain' => $d,
                    'available' => true,
                    'is_premium' => false,
                ], $domains),
            ];
        });
        $this->app->instance(DomainAvailabilityService::class, $fake);

        $response = $this->getJson(route('domains.check', ['q' => 'palgoals', 'tlds' => 'com']));

        $response->assertOk()->assertJsonPath('ok', true);
        $result = $response->json('results.0');

        $this->assertSame('palgoals.com', $result['domain']);
        $this->assertSame('available', $result['status']);
        $this->assertTrue($result['available']);
        $this->assertSame(12.99, $result['price']);
        $this->assertSame('USD', $result['currency']);
    }

    public function test_unavailable_domain_has_no_price_and_no_purchase_signal(): void
    {
        $provider = $this->makeProvider('namecheap', 'test');
        $this->makeTldPrice($provider, 'com', 12.99, 'USD');

        $fake = $this->fakeAvailability(function (array $domains) {
            return [
                'ok' => true,
                'reason' => 'ok',
                'message' => 'تم.',
                'results' => array_map(fn (string $d) => [
                    'domain' => $d,
                    'available' => false,
                    'is_premium' => false,
                ], $domains),
            ];
        });
        $this->app->instance(DomainAvailabilityService::class, $fake);

        $response = $this->getJson(route('domains.check', ['q' => 'taken', 'tlds' => 'com']));

        $response->assertOk();
        $result = $response->json('results.0');

        $this->assertSame('unavailable', $result['status']);
        $this->assertFalse($result['available']);
        $this->assertNull($result['price']);
        $this->assertNull($result['currency']);
    }

    public function test_domain_missing_from_provider_response_is_unknown_not_unavailable(): void
    {
        $provider = $this->makeProvider('namecheap', 'test');
        $this->makeTldPrice($provider, 'com', 12.99, 'USD');
        $this->makeTldPrice($provider, 'net', 14.00, 'USD');

        // Provider only answers for .com; .net is silently dropped from its
        // response (partial/technical failure within an otherwise-ok batch).
        $fake = $this->fakeAvailability(function (array $domains) {
            $results = [];
            foreach ($domains as $d) {
                if (str_ends_with($d, '.net')) {
                    continue; // omitted on purpose
                }
                $results[] = ['domain' => $d, 'available' => true, 'is_premium' => false];
            }

            return ['ok' => true, 'reason' => 'ok', 'message' => 'تم.', 'results' => $results];
        });
        $this->app->instance(DomainAvailabilityService::class, $fake);

        $response = $this->getJson(route('domains.check', ['q' => 'partial', 'tlds' => 'com,net']));

        $response->assertOk();
        $byDomain = collect($response->json('results'))->keyBy('domain');

        $this->assertSame('available', $byDomain['partial.com']['status']);

        $unknown = $byDomain['partial.net'];
        $this->assertSame('unknown', $unknown['status']);
        $this->assertNull($unknown['available']);
        $this->assertNull($unknown['price']);
        $this->assertNull($unknown['currency']);
    }

    public function test_explicit_null_availability_from_provider_is_unknown_not_unavailable(): void
    {
        $provider = $this->makeProvider('namecheap', 'test');
        $this->makeTldPrice($provider, 'com', 12.99, 'USD');

        // Mirrors DomainAvailabilityService::enomCheck()'s fallback for an
        // unparseable domain: available explicitly null, not false.
        $fake = $this->fakeAvailability(function (array $domains) {
            return [
                'ok' => true,
                'reason' => 'ok',
                'message' => 'تم.',
                'results' => array_map(fn (string $d) => [
                    'domain' => $d,
                    'available' => null,
                ], $domains),
            ];
        });
        $this->app->instance(DomainAvailabilityService::class, $fake);

        $response = $this->getJson(route('domains.check', ['q' => 'ambiguous', 'tlds' => 'com']));

        $response->assertOk();
        $result = $response->json('results.0');

        $this->assertSame('unknown', $result['status']);
        $this->assertNull($result['available']);
        $this->assertNull($result['price']);
    }

    public function test_provider_check_failure_is_a_technical_error_with_no_silent_fallback(): void
    {
        $namecheap = $this->makeProvider('namecheap', 'test');
        $enom = $this->makeProvider('enom', 'live');
        $this->makeTldPrice($namecheap, 'com', 12.99, 'USD');

        $fake = new class extends DomainAvailabilityService {
            public array $calls = [];

            public function checkDomains(array $domains, ?DomainProvider $provider = null): array
            {
                $this->calls[] = ['provider_id' => $provider?->id, 'domains' => $domains];

                return ['ok' => false, 'reason' => 'http_error', 'message' => 'تعذّر الفحص.', 'results' => []];
            }
        };
        $this->app->instance(DomainAvailabilityService::class, $fake);

        $response = $this->getJson(route('domains.check', ['q' => 'failing', 'tlds' => 'com']));

        $response->assertStatus(422)->assertJsonPath('ok', false);
        $this->assertSame([], $response->json('results'));

        // Exactly one provider was ever asked — the one the trusted quote
        // named — never a fallback to the other active provider.
        $this->assertCount(1, $fake->calls);
        $this->assertSame($namecheap->id, $fake->calls[0]['provider_id']);
        $this->assertNotSame($enom->id, $fake->calls[0]['provider_id']);
    }

    public function test_empty_query_is_rejected_before_any_provider_call(): void
    {
        $response = $this->getJson(route('domains.check', ['q' => '']));

        $response->assertStatus(422)->assertJsonPath('ok', false);
    }

    /* ====================== Helpers ====================== */

    private function fakeAvailability(Closure $resultsForDomains): DomainAvailabilityService
    {
        return new class($resultsForDomains) extends DomainAvailabilityService {
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
    }

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

    private function makeTldPrice(
        DomainProvider $provider,
        string $tldName,
        float $price,
        string $currency = 'USD'
    ): void {
        $tld = DomainTld::query()->create([
            'provider_id' => $provider->id,
            'provider' => $provider->type,
            'tld' => $tldName,
            'currency' => $currency,
            'enabled' => true,
        ]);
        $tld->prices()->create([
            'action' => 'register',
            'years' => 1,
            'cost' => $price,
            'sale' => $price,
        ]);
    }
}
