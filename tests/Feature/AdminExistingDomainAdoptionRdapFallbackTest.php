<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\DomainProvisioningAttempt;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAttempt;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * TLD-3G.1C-C — Admin Adopt Existing Domain: RDAP registration-date fallback integration.
 *
 * Covers ONLY the Enom-first/RDAP-second ordering and the adoption transaction's own
 * side-effect/ownership guarantees when the RDAP fallback path is exercised end-to-end through
 * DomainController::storeAdopt(). RDAP mechanics themselves (event filtering, domain-identity
 * matching, malformed/oversized responses, endpoint resolution) are covered in isolation by
 * RdapDomainRegistrationDateServiceTest. Every scenario uses Http::fake() — no real Enom, IANA
 * bootstrap, or registry RDAP request is ever made from this file.
 */
class AdminExistingDomainAdoptionRdapFallbackTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    /* ====================== A — Enom already has a date: RDAP must never be called ====================== */

    public function test_rdap_is_never_called_when_enom_already_returns_a_trustworthy_date(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response($this->fakeGetDomainInfoXml(registeredAt: '01/01/2020'), 200, ['Content-Type' => 'application/xml']),
            'data.iana.org/*' => Http::response('should not be called', 500),
            'rdap.verisign.com/*' => Http::response('should not be called', 500),
        ]);

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();

        $response = $this->actingAs($admin)->post(route('dashboard.domains.adopt.store'), [
            'provider_id' => $provider->id,
            'domain_name' => 'example.com',
            'client_id' => $client->id,
        ]);

        $response->assertRedirect(route('dashboard.domains.index'));
        Http::assertNotSent(fn ($request) => str_contains((string) $request->url(), 'data.iana.org') || str_contains((string) $request->url(), 'rdap.verisign.com'));

        $domain = Domain::where('domain_name', 'example.com')->first();
        $this->assertNotNull($domain);
        $this->assertSame('2020-01-01', \Illuminate\Support\Carbon::parse($domain->registration_date)->toDateString());
    }

    /* ====================== B — Enom has no date, RDAP supplies a valid one: adoption succeeds ====================== */

    public function test_adoption_succeeds_using_rdap_date_when_enom_date_is_missing(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response($this->fakeGetDomainInfoXml(registeredAt: null), 200, ['Content-Type' => 'application/xml']),
            'data.iana.org/*' => Http::response($this->bootstrapJson(), 200, ['Content-Type' => 'application/json']),
            'rdap.verisign.com/*' => Http::response($this->rdapJson('example.com', [
                ['eventAction' => 'registration', 'eventDate' => '2015-03-10T00:00:00Z'],
            ]), 200, ['Content-Type' => 'application/rdap+json']),
        ]);

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();

        $response = $this->actingAs($admin)->post(route('dashboard.domains.adopt.store'), [
            'provider_id' => $provider->id,
            'domain_name' => 'example.com',
            'client_id' => $client->id,
        ]);

        $response->assertRedirect(route('dashboard.domains.index'));

        $domain = Domain::where('domain_name', 'example.com')->first();
        $this->assertNotNull($domain);
        $this->assertSame('2015-03-10', \Illuminate\Support\Carbon::parse($domain->registration_date)->toDateString());
        $this->assertSame($provider->id, $domain->provider_id);
        $this->assertSame($client->id, $domain->client_id);
    }

    /* ====================== RDAP also fails: adoption fails closed, zero DB writes ====================== */

    public function test_adoption_fails_closed_when_both_enom_and_rdap_lack_a_date(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response($this->fakeGetDomainInfoXml(registeredAt: null), 200, ['Content-Type' => 'application/xml']),
            'data.iana.org/*' => Http::response($this->bootstrapJson(), 200, ['Content-Type' => 'application/json']),
            'rdap.verisign.com/*' => Http::response($this->rdapJson('example.com', []), 200, ['Content-Type' => 'application/rdap+json']),
        ]);

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();

        $response = $this->actingAs($admin)->post(route('dashboard.domains.adopt.store'), [
            'provider_id' => $provider->id,
            'domain_name' => 'example.com',
            'client_id' => $client->id,
        ]);

        $response->assertSessionHasErrors('domain_name');
        $this->assertSame(0, Domain::count());
        $this->assertSame(0, $this->financialSideEffectRows());
    }

    /* ====================== K — RDAP cannot alter provider_id/client_id/provider identity ====================== */

    public function test_rdap_fallback_cannot_alter_provider_or_client_identity(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response($this->fakeGetDomainInfoXml(registeredAt: null), 200, ['Content-Type' => 'application/xml']),
            'data.iana.org/*' => Http::response($this->bootstrapJson(), 200, ['Content-Type' => 'application/json']),
            'rdap.verisign.com/*' => Http::response($this->rdapJson('example.com', [
                ['eventAction' => 'registration', 'eventDate' => '2015-03-10T00:00:00Z'],
            ]), 200, ['Content-Type' => 'application/rdap+json']),
        ]);

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();

        $this->actingAs($admin)->post(route('dashboard.domains.adopt.store'), [
            'provider_id' => $provider->id,
            'domain_name' => 'example.com',
            'client_id' => $client->id,
        ])->assertRedirect(route('dashboard.domains.index'));

        $domain = Domain::where('domain_name', 'example.com')->first();
        $this->assertSame($provider->id, $domain->provider_id);
        $this->assertSame('enom', $domain->registrar);
        $this->assertSame($client->id, $domain->client_id);
    }

    /* ====================== L — zero financial/provisioning side effects on the RDAP path ====================== */

    public function test_rdap_fallback_adoption_creates_zero_financial_or_provisioning_rows(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response($this->fakeGetDomainInfoXml(registeredAt: null), 200, ['Content-Type' => 'application/xml']),
            'data.iana.org/*' => Http::response($this->bootstrapJson(), 200, ['Content-Type' => 'application/json']),
            'rdap.verisign.com/*' => Http::response($this->rdapJson('example.com', [
                ['eventAction' => 'registration', 'eventDate' => '2015-03-10T00:00:00Z'],
            ]), 200, ['Content-Type' => 'application/rdap+json']),
        ]);

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();

        $this->actingAs($admin)->post(route('dashboard.domains.adopt.store'), [
            'provider_id' => $provider->id,
            'domain_name' => 'example.com',
            'client_id' => $client->id,
        ])->assertRedirect(route('dashboard.domains.index'));

        $this->assertSame(0, $this->financialSideEffectRows());
    }

    /* ====================== M — no registrar mutating call anywhere on the RDAP path ====================== */

    public function test_rdap_fallback_path_never_sends_a_mutating_registrar_command(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response($this->fakeGetDomainInfoXml(registeredAt: null), 200, ['Content-Type' => 'application/xml']),
            'data.iana.org/*' => Http::response($this->bootstrapJson(), 200, ['Content-Type' => 'application/json']),
            'rdap.verisign.com/*' => Http::response($this->rdapJson('example.com', [
                ['eventAction' => 'registration', 'eventDate' => '2015-03-10T00:00:00Z'],
            ]), 200, ['Content-Type' => 'application/rdap+json']),
        ]);

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();

        $this->actingAs($admin)->post(route('dashboard.domains.adopt.store'), [
            'provider_id' => $provider->id,
            'domain_name' => 'example.com',
            'client_id' => $client->id,
        ])->assertRedirect(route('dashboard.domains.index'));

        Http::assertNotSent(function ($request) {
            $command = strtolower((string) ($request->data()['command'] ?? ''));

            return in_array($command, ['purchase', 'extend', 'registernameserver', 'updatenameserver', 'modifyns'], true);
        });
    }

    /* ====================== N — verification failure: RDAP must never be called ====================== */

    public function test_rdap_is_never_called_when_enom_verification_itself_fails(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response(
                '<?xml version="1.0"?><interface-response><ErrCount>1</ErrCount><errors><Err1>Domain not found</Err1></errors></interface-response>',
                200,
                ['Content-Type' => 'application/xml']
            ),
            'data.iana.org/*' => Http::response('should not be called', 500),
            'rdap.verisign.com/*' => Http::response('should not be called', 500),
        ]);

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();

        $response = $this->actingAs($admin)->post(route('dashboard.domains.adopt.store'), [
            'provider_id' => $provider->id,
            'domain_name' => 'example.com',
            'client_id' => $client->id,
        ]);

        $response->assertSessionHasErrors('domain_name');
        Http::assertNotSent(fn ($request) => str_contains((string) $request->url(), 'data.iana.org') || str_contains((string) $request->url(), 'rdap.verisign.com'));
        $this->assertSame(0, Domain::count());
    }

    /* ====================== O — external domain, same client: RDAP-assisted upgrade preserves the primary key ====================== */

    public function test_rdap_assisted_upgrade_of_external_domain_preserves_primary_key(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response($this->fakeGetDomainInfoXml(registeredAt: null), 200, ['Content-Type' => 'application/xml']),
            'data.iana.org/*' => Http::response($this->bootstrapJson(), 200, ['Content-Type' => 'application/json']),
            'rdap.verisign.com/*' => Http::response($this->rdapJson('example.com', [
                ['eventAction' => 'registration', 'eventDate' => '2015-03-10T00:00:00Z'],
            ]), 200, ['Content-Type' => 'application/rdap+json']),
        ]);

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();
        $external = $this->makeExternalDomain($client, 'example.com');

        $response = $this->actingAs($admin)->post(route('dashboard.domains.adopt.store'), [
            'provider_id' => $provider->id,
            'domain_name' => 'example.com',
            'client_id' => $client->id,
        ]);

        $response->assertRedirect(route('dashboard.domains.index'));
        $this->assertSame(1, Domain::count());

        $domain = Domain::where('domain_name', 'example.com')->first();
        $this->assertSame($external->id, $domain->id);
        $this->assertSame($client->id, $domain->client_id);
        $this->assertSame($provider->id, $domain->provider_id);
        $this->assertSame('2015-03-10', \Illuminate\Support\Carbon::parse($domain->registration_date)->toDateString());
    }

    /* ====================== P — external domain under a different client stays protected ====================== */

    public function test_rdap_fallback_never_bypasses_the_different_client_protection(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response($this->fakeGetDomainInfoXml(registeredAt: null), 200, ['Content-Type' => 'application/xml']),
            'data.iana.org/*' => Http::response($this->bootstrapJson(), 200, ['Content-Type' => 'application/json']),
            'rdap.verisign.com/*' => Http::response($this->rdapJson('example.com', [
                ['eventAction' => 'registration', 'eventDate' => '2015-03-10T00:00:00Z'],
            ]), 200, ['Content-Type' => 'application/rdap+json']),
        ]);

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $ownerClient = $this->makeClient();
        $otherClient = $this->makeClient();
        $external = $this->makeExternalDomain($ownerClient, 'example.com');

        $response = $this->actingAs($admin)->post(route('dashboard.domains.adopt.store'), [
            'provider_id' => $provider->id,
            'domain_name' => 'example.com',
            'client_id' => $otherClient->id,
        ]);

        $response->assertSessionHasErrors('client_id');

        $domain = Domain::find($external->id);
        $this->assertSame($ownerClient->id, $domain->client_id);
        $this->assertNull($domain->provider_id);
    }

    /* ====================== Helpers ====================== */

    private function financialSideEffectRows(): int
    {
        return Order::query()->count()
            + Invoice::query()->count()
            + InvoiceItem::query()->count()
            + OrderItem::query()->count()
            + PaymentAttempt::query()->count()
            + DomainProvisioningAttempt::query()->count();
    }

    private function makeAdmin(): User
    {
        return User::factory()->create(['super_admin' => true]);
    }

    private function makeProvider(string $type, bool $active, string $mode): DomainProvider
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
            'is_active' => $active,
        ]);
    }

    private function makeClient(): Client
    {
        return Client::query()->create([
            'first_name' => 'Rdap',
            'last_name' => 'Client',
            'email' => uniqid('rdap_client_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'RDAP Test',
        ]);
    }

    private function makeExternalDomain(Client $client, string $domainName): Domain
    {
        return Domain::query()->create([
            'client_id' => $client->id,
            'domain_name' => $domainName,
            'registrar' => 'unassigned',
            'provider_id' => null,
            'registration_date' => now()->subYears(2)->toDateString(),
            'renewal_date' => now()->subYear()->toDateString(),
            'status' => 'pending',
            'auto_renew' => false,
        ]);
    }

    private function fakeGetDomainInfoXml(
        ?string $providerDomainId = '12345',
        ?string $registrationStatus = 'Registered',
        ?string $purchaseStatus = 'Paid',
        ?string $belongsToPartyId = '98765',
        ?string $registeredAt = '01/01/2020',
        ?string $expiresAt = '12/31/2027'
    ): string {
        $domainNameAttr = $providerDomainId !== null ? ' domainnameid="' . $providerDomainId . '"' : '';
        $belongsTo = $belongsToPartyId !== null
            ? '<belongs-to party-id="' . $belongsToPartyId . '"/>'
            : '';
        $registrationStatusEl = $registrationStatus !== null
            ? '<registrationstatus>' . $registrationStatus . '</registrationstatus>'
            : '';
        $purchaseStatusEl = $purchaseStatus !== null
            ? '<purchase-status>' . $purchaseStatus . '</purchase-status>'
            : '';
        $expirationEl = $expiresAt !== null ? '<expiration>' . $expiresAt . '</expiration>' : '';
        $registryCreateDateEl = $registeredAt !== null
            ? '<RegistryCreateDate>' . $registeredAt . '</RegistryCreateDate>'
            : '';

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<interface-response>
    <ErrCount>0</ErrCount>
    <GetDomainInfo>
        <domainname{$domainNameAttr}>example.com</domainname>
        <status>
            {$registrationStatusEl}
            {$purchaseStatusEl}
            {$expirationEl}
            {$belongsTo}
        </status>
    </GetDomainInfo>
    {$registryCreateDateEl}
</interface-response>
XML;
    }

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
