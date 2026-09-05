<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Services\Domains\RdapDomainRegistrationDateService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * TLD-3G.1C-C — RdapDomainRegistrationDateService.
 *
 * Every scenario uses Http::fake() — no real IANA bootstrap or registry RDAP request is ever
 * made from this file. Covers the RDAP mechanics in isolation: trusted-endpoint resolution via
 * the IANA bootstrap document, the "registration" eventAction contract, exact domain-identity
 * matching, and fail-closed behavior on any ambiguity or malformed/oversized/unreachable
 * response. Controller-level Enom-first/RDAP-second ordering and adoption-transaction
 * side-effect guarantees are covered separately in
 * AdminExistingDomainAdoptionRdapFallbackTest.
 */
class RdapDomainRegistrationDateServiceTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    /* ====================== C — single trustworthy registration event ====================== */

    public function test_single_registration_event_is_accepted(): void
    {
        Http::fake([
            'data.iana.org/*' => Http::response($this->bootstrapJson(), 200, ['Content-Type' => 'application/json']),
            'rdap.verisign.com/*' => Http::response($this->rdapJson('wpgoals.com', [
                ['eventAction' => 'registration', 'eventDate' => '2018-05-01T00:00:00Z'],
                ['eventAction' => 'expiration', 'eventDate' => '2026-09-29T09:31:00Z'],
            ]), 200, ['Content-Type' => 'application/rdap+json']),
        ]);

        $result = (new RdapDomainRegistrationDateService())->resolveRegistrationDate('wpgoals.com');

        $this->assertTrue($result['ok']);
        $this->assertSame('wpgoals.com', $result['domain_name']);
        $this->assertSame('2018-05-01T00:00:00+00:00', $result['registered_at']);
        Http::assertSentCount(2);
        $this->assertSame(0, Domain::count());
    }

    /* ====================== D — no registration event present ====================== */

    public function test_no_registration_event_fails_closed(): void
    {
        Http::fake([
            'data.iana.org/*' => Http::response($this->bootstrapJson(), 200, ['Content-Type' => 'application/json']),
            'rdap.verisign.com/*' => Http::response($this->rdapJson('wpgoals.com', [
                ['eventAction' => 'expiration', 'eventDate' => '2026-09-29T09:31:00Z'],
                ['eventAction' => 'last changed', 'eventDate' => '2026-01-01T00:00:00Z'],
            ]), 200, ['Content-Type' => 'application/rdap+json']),
        ]);

        $result = (new RdapDomainRegistrationDateService())->resolveRegistrationDate('wpgoals.com');

        $this->assertFalse($result['ok']);
        $this->assertSame('rdap_no_registration_event', $result['reason']);
        $this->assertNull($result['registered_at']);
    }

    /* ====================== E — conflicting registration events ====================== */

    public function test_conflicting_registration_events_fail_closed(): void
    {
        Http::fake([
            'data.iana.org/*' => Http::response($this->bootstrapJson(), 200, ['Content-Type' => 'application/json']),
            'rdap.verisign.com/*' => Http::response($this->rdapJson('wpgoals.com', [
                ['eventAction' => 'registration', 'eventDate' => '2018-05-01T00:00:00Z'],
                ['eventAction' => 'registration', 'eventDate' => '2019-06-02T00:00:00Z'],
            ]), 200, ['Content-Type' => 'application/rdap+json']),
        ]);

        $result = (new RdapDomainRegistrationDateService())->resolveRegistrationDate('wpgoals.com');

        $this->assertFalse($result['ok']);
        $this->assertSame('rdap_conflicting_registration_events', $result['reason']);
    }

    /* ====================== duplicate identical registration events deduplicate deterministically ====================== */

    public function test_duplicate_identical_registration_events_deduplicate(): void
    {
        Http::fake([
            'data.iana.org/*' => Http::response($this->bootstrapJson(), 200, ['Content-Type' => 'application/json']),
            'rdap.verisign.com/*' => Http::response($this->rdapJson('wpgoals.com', [
                ['eventAction' => 'registration', 'eventDate' => '2018-05-01T00:00:00Z'],
                ['eventAction' => 'registration', 'eventDate' => '2018-05-01T00:00:00Z'],
            ]), 200, ['Content-Type' => 'application/rdap+json']),
        ]);

        $result = (new RdapDomainRegistrationDateService())->resolveRegistrationDate('wpgoals.com');

        $this->assertTrue($result['ok']);
        $this->assertSame('2018-05-01T00:00:00+00:00', $result['registered_at']);
    }

    /* ====================== domain-identity mismatch ====================== */

    public function test_domain_identity_mismatch_fails_closed(): void
    {
        Http::fake([
            'data.iana.org/*' => Http::response($this->bootstrapJson(), 200, ['Content-Type' => 'application/json']),
            'rdap.verisign.com/*' => Http::response($this->rdapJson('someone-else.com', [
                ['eventAction' => 'registration', 'eventDate' => '2018-05-01T00:00:00Z'],
            ]), 200, ['Content-Type' => 'application/rdap+json']),
        ]);

        $result = (new RdapDomainRegistrationDateService())->resolveRegistrationDate('wpgoals.com');

        $this->assertFalse($result['ok']);
        $this->assertSame('rdap_domain_mismatch', $result['reason']);
    }

    /* ====================== malformed JSON from the registry ====================== */

    public function test_malformed_json_fails_closed(): void
    {
        Http::fake([
            'data.iana.org/*' => Http::response($this->bootstrapJson(), 200, ['Content-Type' => 'application/json']),
            'rdap.verisign.com/*' => Http::response('{not-json', 200, ['Content-Type' => 'application/rdap+json']),
        ]);

        $result = (new RdapDomainRegistrationDateService())->resolveRegistrationDate('wpgoals.com');

        $this->assertFalse($result['ok']);
        $this->assertSame('rdap_lookup_failed', $result['reason']);
    }

    /* ====================== HTTP failure from the registry ====================== */

    public function test_http_failure_from_registry_fails_closed(): void
    {
        Http::fake([
            'data.iana.org/*' => Http::response($this->bootstrapJson(), 200, ['Content-Type' => 'application/json']),
            'rdap.verisign.com/*' => Http::response('Internal Server Error', 500),
        ]);

        $result = (new RdapDomainRegistrationDateService())->resolveRegistrationDate('wpgoals.com');

        $this->assertFalse($result['ok']);
        $this->assertSame('rdap_lookup_failed', $result['reason']);
    }

    /* ====================== future-dated registration event ====================== */

    public function test_future_registration_date_fails_closed(): void
    {
        Http::fake([
            'data.iana.org/*' => Http::response($this->bootstrapJson(), 200, ['Content-Type' => 'application/json']),
            'rdap.verisign.com/*' => Http::response($this->rdapJson('wpgoals.com', [
                ['eventAction' => 'registration', 'eventDate' => '2099-01-01T00:00:00Z'],
            ]), 200, ['Content-Type' => 'application/rdap+json']),
        ]);

        $result = (new RdapDomainRegistrationDateService())->resolveRegistrationDate('wpgoals.com');

        $this->assertFalse($result['ok']);
        $this->assertSame('rdap_future_registration_date', $result['reason']);
    }

    /* ====================== bootstrap has no HTTPS URL for the TLD ====================== */

    public function test_bootstrap_without_https_url_fails_closed(): void
    {
        Http::fake([
            'data.iana.org/*' => Http::response(json_encode([
                'services' => [
                    [['com'], ['http://insecure.example.test/']],
                ],
            ]), 200, ['Content-Type' => 'application/json']),
        ]);

        $result = (new RdapDomainRegistrationDateService())->resolveRegistrationDate('wpgoals.com');

        $this->assertFalse($result['ok']);
        $this->assertSame('rdap_endpoint_unresolved', $result['reason']);
        Http::assertSentCount(1);
    }

    /* ====================== bootstrap does not list the TLD at all ====================== */

    public function test_bootstrap_missing_tld_fails_closed(): void
    {
        Http::fake([
            'data.iana.org/*' => Http::response(json_encode([
                'services' => [
                    [['net'], ['https://rdap.example-net-registry.test/']],
                ],
            ]), 200, ['Content-Type' => 'application/json']),
        ]);

        $result = (new RdapDomainRegistrationDateService())->resolveRegistrationDate('wpgoals.com');

        $this->assertFalse($result['ok']);
        $this->assertSame('rdap_endpoint_unresolved', $result['reason']);
    }

    /* ====================== oversized bootstrap response ====================== */

    public function test_oversized_bootstrap_response_fails_closed(): void
    {
        Http::fake([
            'data.iana.org/*' => Http::response(str_repeat('a', 300000), 200, ['Content-Type' => 'application/json']),
        ]);

        $result = (new RdapDomainRegistrationDateService())->resolveRegistrationDate('wpgoals.com');

        $this->assertFalse($result['ok']);
        $this->assertSame('rdap_endpoint_unresolved', $result['reason']);
    }

    /* ====================== oversized registry response ====================== */

    public function test_oversized_registry_response_fails_closed(): void
    {
        Http::fake([
            'data.iana.org/*' => Http::response($this->bootstrapJson(), 200, ['Content-Type' => 'application/json']),
            'rdap.verisign.com/*' => Http::response(str_repeat('a', 300000), 200, ['Content-Type' => 'application/rdap+json']),
        ]);

        $result = (new RdapDomainRegistrationDateService())->resolveRegistrationDate('wpgoals.com');

        $this->assertFalse($result['ok']);
        $this->assertSame('rdap_lookup_failed', $result['reason']);
    }

    /* ====================== zero DB writes across a failing scenario ====================== */

    public function test_failing_lookup_creates_zero_database_rows(): void
    {
        Http::fake([
            'data.iana.org/*' => Http::response($this->bootstrapJson(), 200, ['Content-Type' => 'application/json']),
            'rdap.verisign.com/*' => Http::response($this->rdapJson('wpgoals.com', []), 200, ['Content-Type' => 'application/rdap+json']),
        ]);

        (new RdapDomainRegistrationDateService())->resolveRegistrationDate('wpgoals.com');

        $this->assertSame(0, Domain::count());
    }

    /* ====================== Helpers ====================== */

    private function bootstrapJson(): string
    {
        return json_encode([
            'services' => [
                [['com'], ['https://rdap.verisign.com/com/v1/']],
            ],
        ]);
    }

    private function rdapJson(string $ldhName, array $events): string
    {
        return json_encode([
            'ldhName' => $ldhName,
            'events' => $events,
        ]);
    }
}
