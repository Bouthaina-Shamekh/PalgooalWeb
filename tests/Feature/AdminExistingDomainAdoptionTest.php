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
 * TLD-3G.1B — Admin Adopt Existing Domain.
 *
 * Every scenario that could reach eNom uses Http::fake() — no real Enom HTTP request is ever
 * made from this file. Provider-eligibility rejections are additionally asserted with
 * Http::assertNothingSent() to prove they happen strictly before any registrar call, exactly as
 * already proven for ExistingDomainVerificationService itself in TLD-3G.1A.
 */
class AdminExistingDomainAdoptionTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    /* ====================== A — form view ====================== */

    public function test_admin_can_view_adopt_form(): void
    {
        $admin = $this->makeAdmin();
        $this->makeProvider('enom', true, 'live');

        $response = $this->actingAs($admin)->get(route('dashboard.domains.adopt.create'));

        $response->assertOk();
        $response->assertSee('name="domain_name"', false);
        $response->assertSee('name="client_id"', false);
        $response->assertSee('name="provider_id"', false);
    }

    /* ====================== B — provider selector filtering ====================== */

    public function test_provider_selector_shows_only_active_live_enom_providers(): void
    {
        $admin = $this->makeAdmin();
        $eligible = $this->makeProvider('enom', true, 'live');
        $inactiveEnom = $this->makeProvider('enom', false, 'live');
        $testEnom = $this->makeProvider('enom', true, 'test');
        $namecheapLive = $this->makeProvider('namecheap', true, 'live');

        $response = $this->actingAs($admin)->get(route('dashboard.domains.adopt.create'));

        $response->assertOk();
        $response->assertViewHas('providers', function ($providers) use ($eligible, $inactiveEnom, $testEnom, $namecheapLive) {
            $ids = $providers->pluck('id')->all();

            return in_array($eligible->id, $ids, true)
                && !in_array($inactiveEnom->id, $ids, true)
                && !in_array($testEnom->id, $ids, true)
                && !in_array($namecheapLive->id, $ids, true);
        });
    }

    /* ====================== C — successful adoption creates Managed Domain ====================== */

    public function test_successful_verified_domain_creates_managed_domain_with_expected_fields(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response($this->fakeGetDomainInfoXml(), 200, ['Content-Type' => 'application/xml']),
        ]);

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();

        $response = $this->actingAs($admin)->post(route('dashboard.domains.adopt.store'), [
            'provider_id' => $provider->id,
            'domain_name' => 'Example.COM.',
            'client_id' => $client->id,
        ]);

        $response->assertRedirect(route('dashboard.domains.index'));

        $domain = Domain::where('domain_name', 'example.com')->first();
        $this->assertNotNull($domain);
        $this->assertSame($provider->id, $domain->provider_id);
        $this->assertSame('enom', $domain->registrar);
        $this->assertSame($client->id, $domain->client_id);
        $this->assertSame('active', $domain->status);
        $this->assertNotNull($domain->registration_date);
        $this->assertNotNull($domain->renewal_date);
        $this->assertFalse((bool) $domain->auto_renew);
    }

    /* ====================== D — zero financial/provisioning side effects ====================== */

    public function test_successful_adoption_creates_zero_financial_or_provisioning_rows(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response($this->fakeGetDomainInfoXml(), 200, ['Content-Type' => 'application/xml']),
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

    /* ====================== E — verification failure creates no Domain ====================== */

    public function test_verification_failure_creates_no_domain(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response(
                '<?xml version="1.0"?><interface-response><ErrCount>1</ErrCount><errors><Err1>Domain not found</Err1></errors></interface-response>',
                200,
                ['Content-Type' => 'application/xml']
            ),
        ]);

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();

        $response = $this->actingAs($admin)->post(route('dashboard.domains.adopt.store'), [
            'provider_id' => $provider->id,
            'domain_name' => 'example.com',
            'client_id' => $client->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('domain_name');
        $this->assertSame(0, Domain::count());
    }

    /* ====================== F/G/H — provider eligibility guard ====================== */

    public function test_inactive_enom_provider_is_rejected_before_verification_api_call(): void
    {
        Http::fake();
        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', false, 'live');
        $client = $this->makeClient();

        $response = $this->actingAs($admin)->post(route('dashboard.domains.adopt.store'), [
            'provider_id' => $provider->id,
            'domain_name' => 'example.com',
            'client_id' => $client->id,
        ]);

        $response->assertSessionHasErrors('provider_id');
        Http::assertNothingSent();
        $this->assertSame(0, Domain::count());
    }

    public function test_test_mode_enom_provider_is_rejected_before_verification_api_call(): void
    {
        Http::fake();
        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'test');
        $client = $this->makeClient();

        $response = $this->actingAs($admin)->post(route('dashboard.domains.adopt.store'), [
            'provider_id' => $provider->id,
            'domain_name' => 'example.com',
            'client_id' => $client->id,
        ]);

        $response->assertSessionHasErrors('provider_id');
        Http::assertNothingSent();
        $this->assertSame(0, Domain::count());
    }

    public function test_namecheap_provider_is_rejected_before_verification_api_call(): void
    {
        Http::fake();
        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('namecheap', true, 'live');
        $client = $this->makeClient();

        $response = $this->actingAs($admin)->post(route('dashboard.domains.adopt.store'), [
            'provider_id' => $provider->id,
            'domain_name' => 'example.com',
            'client_id' => $client->id,
        ]);

        $response->assertSessionHasErrors('provider_id');
        Http::assertNothingSent();
        $this->assertSame(0, Domain::count());
    }

    /* ====================== I/J/R — same-client External upgrade ====================== */

    public function test_existing_external_domain_belonging_to_same_client_upgrades_in_place(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response($this->fakeGetDomainInfoXml(), 200, ['Content-Type' => 'application/xml']),
        ]);

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();
        $existing = $this->makeExternalDomain($client, 'example.com');

        $response = $this->actingAs($admin)->post(route('dashboard.domains.adopt.store'), [
            'provider_id' => $provider->id,
            'domain_name' => 'example.com',
            'client_id' => $client->id,
        ]);

        $response->assertRedirect(route('dashboard.domains.index'));

        $fresh = $existing->fresh();
        $this->assertSame($provider->id, $fresh->provider_id);
        $this->assertSame('enom', $fresh->registrar);
        $this->assertSame('active', $fresh->status);
        $this->assertSame(1, Domain::count(), 'upgrade must not create a second Domain row');
    }

    public function test_upgrade_preserves_the_same_domain_primary_key(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response($this->fakeGetDomainInfoXml(), 200, ['Content-Type' => 'application/xml']),
        ]);

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();
        $existing = $this->makeExternalDomain($client, 'example.com');
        $originalId = $existing->id;

        $this->actingAs($admin)->post(route('dashboard.domains.adopt.store'), [
            'provider_id' => $provider->id,
            'domain_name' => 'example.com',
            'client_id' => $client->id,
        ])->assertRedirect(route('dashboard.domains.index'));

        $this->assertSame($originalId, $existing->fresh()->id);
    }

    public function test_no_client_reassignment_occurs_during_same_client_external_upgrade(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response($this->fakeGetDomainInfoXml(), 200, ['Content-Type' => 'application/xml']),
        ]);

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();
        $existing = $this->makeExternalDomain($client, 'example.com');

        $this->actingAs($admin)->post(route('dashboard.domains.adopt.store'), [
            'provider_id' => $provider->id,
            'domain_name' => 'example.com',
            'client_id' => $client->id,
        ])->assertRedirect(route('dashboard.domains.index'));

        $this->assertSame($client->id, $existing->fresh()->client_id);
    }

    /* ====================== K — cross-client collision ====================== */

    public function test_external_domain_belonging_to_another_client_is_rejected(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response($this->fakeGetDomainInfoXml(), 200, ['Content-Type' => 'application/xml']),
        ]);

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $ownerClient = $this->makeClient();
        $otherClient = $this->makeClient();
        $existing = $this->makeExternalDomain($ownerClient, 'example.com');

        $response = $this->actingAs($admin)->post(route('dashboard.domains.adopt.store'), [
            'provider_id' => $provider->id,
            'domain_name' => 'example.com',
            'client_id' => $otherClient->id,
        ]);

        $response->assertSessionHasErrors('client_id');

        $fresh = $existing->fresh();
        $this->assertSame($ownerClient->id, $fresh->client_id);
        $this->assertNull($fresh->provider_id);
        $this->assertSame(1, Domain::count());
    }

    /* ====================== L — already-Managed collision ====================== */

    public function test_already_managed_domain_is_rejected_even_with_same_provider_and_client(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response($this->fakeGetDomainInfoXml(), 200, ['Content-Type' => 'application/xml']),
        ]);

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();
        $existing = Domain::query()->create([
            'client_id' => $client->id,
            'domain_name' => 'example.com',
            'registrar' => 'enom',
            'provider_id' => $provider->id,
            'registration_date' => now()->subYear()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post(route('dashboard.domains.adopt.store'), [
            'provider_id' => $provider->id,
            'domain_name' => 'example.com',
            'client_id' => $client->id,
        ]);

        $response->assertSessionHasErrors('domain_name');

        $fresh = $existing->fresh();
        $this->assertSame($provider->id, $fresh->provider_id);
        $this->assertSame(1, Domain::count());
        // Already-managed collision must not even need to reach eNom to be rejected safely;
        // regardless, no mutating command may ever be sent.
        Http::assertNotSent(function ($request) {
            $url = (string) $request->url();
            foreach (['Purchase', 'Extend', 'ModifyNS', 'RegisterNameServer', 'UpdateNameServer'] as $mutating) {
                if (str_contains($url, 'command=' . $mutating)) {
                    return true;
                }
            }
            return false;
        });
    }

    /* ====================== M — forged provider_id cannot bypass eligibility ====================== */

    public function test_forged_provider_id_cannot_bypass_exact_provider_eligibility(): void
    {
        Http::fake();
        $admin = $this->makeAdmin();
        // A provider_id that legitimately "exists" (passes FormRequest validation) but is
        // ineligible — proving eligibility is enforced by the controller/service, not merely by
        // whether the id resolves to a row.
        $ineligible = $this->makeProvider('enom', false, 'live');
        $client = $this->makeClient();

        $response = $this->actingAs($admin)->post(route('dashboard.domains.adopt.store'), [
            'provider_id' => $ineligible->id,
            'domain_name' => 'example.com',
            'client_id' => $client->id,
        ]);

        $response->assertSessionHasErrors('provider_id');
        Http::assertNothingSent();
        $this->assertSame(0, Domain::count());
    }

    /* ====================== N — missing registered_at fails safely ====================== */

    public function test_missing_registered_at_fails_safely_and_creates_no_domain(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response(
                $this->fakeGetDomainInfoXml(registeredAt: null),
                200,
                ['Content-Type' => 'application/xml']
            ),
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
    }

    /* ====================== O — no mutating Enom command ====================== */

    public function test_no_mutating_enom_command_is_ever_sent(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response($this->fakeGetDomainInfoXml(), 200, ['Content-Type' => 'application/xml']),
        ]);

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();

        $this->actingAs($admin)->post(route('dashboard.domains.adopt.store'), [
            'provider_id' => $provider->id,
            'domain_name' => 'example.com',
            'client_id' => $client->id,
        ]);

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $url = (string) $request->url();
            $this->assertStringContainsString('command=GetDomainInfo', $url);
            foreach (['Purchase', 'Extend', 'ModifyNS', 'RegisterNameServer', 'UpdateNameServer', 'CheckNSStatus'] as $mutating) {
                $this->assertStringNotContainsString('command=' . $mutating, $url);
            }
            return true;
        });
    }

    /* ====================== P — non-admin access ====================== */

    public function test_guest_cannot_access_adopt_create_or_store(): void
    {
        Http::fake();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();

        $getResponse = $this->get(route('dashboard.domains.adopt.create'));
        $getResponse->assertRedirect();
        $getResponse->assertDontSee('name="domain_name"', false);

        $postResponse = $this->post(route('dashboard.domains.adopt.store'), [
            'provider_id' => $provider->id,
            'domain_name' => 'example.com',
            'client_id' => $client->id,
        ]);
        $postResponse->assertRedirect();

        $this->assertSame(0, Domain::count());
        Http::assertNothingSent();
    }

    public function test_non_admin_user_cannot_access_adopt_create_or_store(): void
    {
        Http::fake();
        $ordinaryUser = User::factory()->create(['super_admin' => false]);
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();

        $getResponse = $this->actingAs($ordinaryUser)->get(route('dashboard.domains.adopt.create'));
        $getResponse->assertForbidden();

        $postResponse = $this->actingAs($ordinaryUser)->post(route('dashboard.domains.adopt.store'), [
            'provider_id' => $provider->id,
            'domain_name' => 'example.com',
            'client_id' => $client->id,
        ]);
        $postResponse->assertForbidden();

        $this->assertSame(0, Domain::count());
        Http::assertNothingSent();
    }

    /* ====================== Q — no registration/renewal invoice ====================== */

    public function test_no_registration_or_renewal_invoice_is_created(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response($this->fakeGetDomainInfoXml(), 200, ['Content-Type' => 'application/xml']),
        ]);

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();

        $this->actingAs($admin)->post(route('dashboard.domains.adopt.store'), [
            'provider_id' => $provider->id,
            'domain_name' => 'example.com',
            'client_id' => $client->id,
        ])->assertRedirect(route('dashboard.domains.index'));

        $this->assertSame(0, Invoice::count());
        $this->assertSame(0, InvoiceItem::count());
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
            'first_name' => 'Adopt',
            'last_name' => 'Client',
            'email' => uniqid('adopt_client_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Adopt Test',
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
}
