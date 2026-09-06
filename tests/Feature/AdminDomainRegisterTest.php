<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\Management\DomainController;
use App\Models\Client;
use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * TLD-3E.1 + TLD-3E.2 — Admin Register Exact Provider Identity / Exact Provider Selection.
 *
 * TLD-3E.1 locked in that a successful registrar API call persists the EXACT $provider instance
 * already used for that call (provider_id + registrar derived from provider->type).
 *
 * TLD-3E.2 closes the remaining ambiguity: the admin now selects an exact DomainProvider row
 * (provider_id) instead of a bare registrar-type string, so two active providers sharing the
 * same type (e.g. two active "namecheap" rows) are never ambiguous. updateRegister() validates
 * provider_id (required|integer|exists:domain_providers,id), resolves it via
 * DomainProvider::query()->active()->find() only (never ofType()->first(), never
 * defaultProvider(), never a fallback), and rejects the request before any API call when the
 * provider is invalid/inactive, or when it differs from an already-managed domain's existing
 * provider_id (no silent provider switch / transfer from this screen).
 *
 * These tests never hit a real registrar API: DomainController is rebound in the container to
 * an anonymous subclass that overrides the protected registerDomainWithProvider() hook (the same
 * technique already used for RegistrarProvisioningService in ProviderSourceOfTruthTest and
 * RenewalProviderSourceOfTruthTest), so the exact $provider passed to it can be captured and
 * the API outcome (ok/fail) controlled per test. The recorder is a plain instance property
 * captured by a $this-bound closure (established in TLD-3E.1A) — NOT a cross-class static
 * property, which an anonymous subclass of DomainController cannot access by name.
 *
 * Out of scope here (per TLD-3E.1 / TLD-3E.2): Admin Renew, Admin Create/Edit, Client quick-add,
 * DomainProvider delete, transfer/import semantics, the _form.blade.php "namcheap" typo.
 */
class AdminDomainRegisterTest extends TestCase
{
    use DatabaseMigrations;

    /** @var int[] provider_id values passed to the faked registerDomainWithProvider() call(s). */
    protected array $registerCalls = [];

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->registerCalls = [];
    }

    /* ================================== A ==================================== */

    public function test_register_view_exposes_exact_provider_ids_not_registrar_type_values(): void
    {
        $admin = $this->makeAdmin();
        $namecheapA = $this->makeProvider('namecheap');
        $namecheapB = $this->makeProvider('namecheap');
        $enom = $this->makeProvider('enom');
        $inactive = $this->makeProvider('cloudflare', false);
        $client = $this->makeClient();
        $domain = $this->makeDomain($client);

        $response = $this->actingAs($admin)->get(route('dashboard.domains.register.edit', $domain));

        $response->assertOk();
        $response->assertViewHas('providers', function ($providers) use ($namecheapA, $namecheapB, $enom, $inactive) {
            $ids = $providers->pluck('id')->all();

            return in_array($namecheapA->id, $ids, true)
                && in_array($namecheapB->id, $ids, true)
                && in_array($enom->id, $ids, true)
                && !in_array($inactive->id, $ids, true);
        });
        // The old registrar-type-only contract must be gone from the view payload.
        $response->assertViewMissing('registrarOptions');

        // The rendered <select> offers exact provider_id option values, never a bare type string.
        $response->assertSee('name="provider_id"', false);
        $response->assertSee('value="' . $namecheapA->id . '"', false);
        $response->assertSee('value="' . $namecheapB->id . '"', false);
        $response->assertSee('value="' . $enom->id . '"', false);
        $response->assertDontSee('value="' . $inactive->id . '"', false);
    }

    /* ================================ B / F ==================================== */

    public function test_successful_admin_registration_writes_the_exact_provider_id_and_registrar_from_provider_type(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('namecheap');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client);
        $this->assertNull($domain->provider_id, 'domain starts external/unmanaged');
        $this->bindFakeRegistrar(true);

        $response = $this->actingAs($admin)->put(route('dashboard.domains.register.update', $domain), [
            'provider_id' => $provider->id,
            'registration_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertRedirect(route('dashboard.domains.index'));

        $fresh = $domain->fresh();
        // exact provider_id written.
        $this->assertSame($provider->id, $fresh->provider_id);
        // registrar comes from provider->type, not raw request text.
        $this->assertSame($provider->type, $fresh->registrar);
        // F — external -> managed transition happened only after API success.
        $this->assertSame('active', $fresh->status);
        // B — the write matches exactly the provider that was actually used for the API call.
        $this->assertSame([$provider->id], $this->registerCalls);
    }

    /* ==================================== C ===================================== */

    public function test_two_active_same_type_providers_submitted_provider_id_chooses_exact_requested_row(): void
    {
        $admin = $this->makeAdmin();
        $primary = $this->makeProvider('namecheap');
        $competitor = $this->makeProvider('namecheap');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client);
        $this->bindFakeRegistrar(true);

        $response = $this->actingAs($admin)->put(route('dashboard.domains.register.update', $domain), [
            'provider_id' => $competitor->id,
            'registration_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertRedirect(route('dashboard.domains.index'));

        // Deterministic: exactly the requested row is called, never the same-type sibling.
        $this->assertSame([$competitor->id], $this->registerCalls);

        $fresh = $domain->fresh();
        $this->assertSame($competitor->id, $fresh->provider_id);
        $this->assertNotSame($primary->id, $fresh->provider_id);
    }

    /* ==================================== D ===================================== */

    public function test_inactive_provider_id_rejected_before_api_call(): void
    {
        $admin = $this->makeAdmin();
        $inactive = $this->makeProvider('namecheap', false);
        $client = $this->makeClient();
        $domain = $this->makeDomain($client);
        $this->bindFakeRegistrar(true);

        $response = $this->actingAs($admin)->put(route('dashboard.domains.register.update', $domain), [
            'provider_id' => $inactive->id,
            'registration_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('provider_id');
        $this->assertSame([], $this->registerCalls, 'no API call must be made for an inactive provider');
        $this->assertNull($domain->fresh()->provider_id);
    }

    /* ==================================== E ===================================== */

    public function test_invalid_provider_id_rejected(): void
    {
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $domain = $this->makeDomain($client);
        $this->bindFakeRegistrar(true);

        $response = $this->actingAs($admin)->put(route('dashboard.domains.register.update', $domain), [
            'provider_id' => 999999,
            'registration_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('provider_id');
        $this->assertSame([], $this->registerCalls, 'no API call must be made for a nonexistent provider_id');
        $this->assertNull($domain->fresh()->provider_id);
    }

    /* ==================================== G ===================================== */

    public function test_managed_domain_cannot_register_through_a_different_provider_id(): void
    {
        $admin = $this->makeAdmin();
        $originalProvider = $this->makeProvider('namecheap');
        $otherProvider = $this->makeProvider('enom');
        $client = $this->makeClient();
        // Already-managed domain (e.g. from a prior successful registration).
        $domain = $this->makeDomain($client, $originalProvider);
        $this->bindFakeRegistrar(true);

        $response = $this->actingAs($admin)->put(route('dashboard.domains.register.update', $domain), [
            'provider_id' => $otherProvider->id,
            'registration_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('provider_id');
        $this->assertSame([], $this->registerCalls, 'no API call must be made when switching provider on a managed domain');

        $fresh = $domain->fresh();
        $this->assertSame($originalProvider->id, $fresh->provider_id);
        $this->assertSame($originalProvider->type, $fresh->registrar);
    }

    public function test_managed_domain_can_register_again_through_its_own_existing_provider(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('namecheap');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, $provider);
        $this->bindFakeRegistrar(true);

        $response = $this->actingAs($admin)->put(route('dashboard.domains.register.update', $domain), [
            'provider_id' => $provider->id,
            'registration_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertRedirect(route('dashboard.domains.index'));
        $response->assertSessionDoesntHaveErrors();
        $this->assertSame([$provider->id], $this->registerCalls);
        $this->assertSame($provider->id, $domain->fresh()->provider_id);
    }

    /* ============================ API-failure regression ============================ */

    public function test_failed_admin_registration_does_not_write_provider_id(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('namecheap');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client);
        $this->assertNull($domain->provider_id);
        $this->bindFakeRegistrar(false, 'Registrar declined the request.');

        $response = $this->actingAs($admin)->put(route('dashboard.domains.register.update', $domain), [
            'provider_id' => $provider->id,
            'registration_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertRedirect();
        // Deliberately preserved from TLD-3E.1: the real-API-failure branch still reports under
        // the 'registrar' error key (unchanged production behavior), not 'provider_id'.
        $response->assertSessionHasErrors('registrar');

        $fresh = $domain->fresh();
        $this->assertNull($fresh->provider_id);
        $this->assertSame($domain->registrar, $fresh->registrar);
        $this->assertSame($domain->status, $fresh->status);
    }

    public function test_failed_admin_registration_through_the_same_provider_does_not_change_provider_id(): void
    {
        $admin = $this->makeAdmin();
        $originalProvider = $this->makeProvider('namecheap');
        $client = $this->makeClient();
        // Already-managed domain re-registering through its OWN provider (passes the TLD-3E.2 G
        // guard, reaches the API call) which then fails.
        $domain = $this->makeDomain($client, $originalProvider);
        $this->bindFakeRegistrar(false, 'Registrar declined the request.');

        $response = $this->actingAs($admin)->put(route('dashboard.domains.register.update', $domain), [
            'provider_id' => $originalProvider->id,
            'registration_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('registrar');

        $fresh = $domain->fresh();
        $this->assertSame($originalProvider->id, $fresh->provider_id);
        $this->assertSame($originalProvider->type, $fresh->registrar);
        // The API call did happen (same-provider re-registration is allowed) — it just failed.
        $this->assertSame([$originalProvider->id], $this->registerCalls);
    }


    /* ====================== TLD-3G.2A — Enom Re-Registration Safety Guard ====================== */

    public function test_enom_managed_domain_confirmed_registered_blocks_purchase_and_preserves_domain_fields(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response($this->fakeGetDomainInfoXml(), 200, ['Content-Type' => 'application/xml']),
        ]);

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, $provider);
        $before = $domain->fresh();
        $this->bindFakeRegistrar(true);

        $response = $this->actingAs($admin)->put(route('dashboard.domains.register.update', $domain), [
            'provider_id' => $provider->id,
            'registration_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('provider_id');
        $this->assertSame([], $this->registerCalls, 'zero Purchase-equivalent calls when Enom confirms the domain is already registered and paid');

        // J — blocked scenario must not mutate any Domain field.
        $after = $domain->fresh();
        $this->assertSame($before->provider_id, $after->provider_id);
        $this->assertSame($before->registrar, $after->registrar);
        $this->assertSame($before->status, $after->status);
        $this->assertSame($before->registration_date, $after->registration_date);
        $this->assertSame($before->renewal_date, $after->renewal_date);
    }

    public function test_enom_reregistration_guard_enforced_without_ui_via_direct_put(): void
    {
        // B — proves this is a true backend gate, not merely a hidden/disabled UI control: the
        // request below never visits editRegister()'s GET form (no view is ever rendered), it
        // goes straight to a raw PUT — exactly what a forged/direct submission would look like.
        Http::fake([
            'reseller.enom.com/*' => Http::response($this->fakeGetDomainInfoXml(), 200, ['Content-Type' => 'application/xml']),
        ]);

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, $provider);
        $this->bindFakeRegistrar(true);

        $response = $this->actingAs($admin)->from('/some/unrelated/page')->put(route('dashboard.domains.register.update', $domain), [
            'provider_id' => $provider->id,
            'registration_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('provider_id');
        $this->assertSame([], $this->registerCalls);
        $this->assertSame($provider->id, $domain->fresh()->provider_id, 'domain remains exactly as it was — no partial mutation');
    }

    public function test_enom_verification_api_failure_fails_closed(): void
    {
        // C — a hard HTTP-level failure talking to Enom (not an ambiguous-but-successful
        // response — see the ambiguous test below for that distinct case).
        Http::fake([
            'reseller.enom.com/*' => Http::response('Internal Server Error', 500),
        ]);

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, $provider);
        $before = $domain->fresh();
        $this->bindFakeRegistrar(true);

        $response = $this->actingAs($admin)->put(route('dashboard.domains.register.update', $domain), [
            'provider_id' => $provider->id,
            'registration_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('provider_id');
        $this->assertSame([], $this->registerCalls, 'a verification API failure must never fall through to a live Purchase call');
        $this->assertSame($before->provider_id, $domain->fresh()->provider_id);
    }

    public function test_enom_verification_ambiguous_result_fails_closed(): void
    {
        // D — Enom responds successfully but does NOT confirm registered+paid (e.g. a domain
        // that is present in the account but only "pending") — this is the
        // 'registration_not_confirmed' branch of ExistingDomainVerificationService, distinct
        // from the hard API failure above. Ambiguous, therefore fails closed too.
        Http::fake([
            'reseller.enom.com/*' => Http::response(
                $this->fakeGetDomainInfoXml(registrationStatus: 'Pending', purchaseStatus: 'Paid'),
                200,
                ['Content-Type' => 'application/xml']
            ),
        ]);

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, $provider);
        $before = $domain->fresh();
        $this->bindFakeRegistrar(true);

        $response = $this->actingAs($admin)->put(route('dashboard.domains.register.update', $domain), [
            'provider_id' => $provider->id,
            'registration_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('provider_id');
        $this->assertSame([], $this->registerCalls, 'an ambiguous/non-definitive verification result must never fall through to a live Purchase call');
        $this->assertSame($before->provider_id, $domain->fresh()->provider_id);
    }

    public function test_enom_not_in_account_style_failure_still_fails_closed_under_current_contract(): void
    {
        // E — TLD-3G.2A report finding: ExistingDomainVerificationService::verify()'s reason
        // contract collapses EVERY EnomClient-level failure (including a "domain not found in
        // this account" response, which conceptually sounds like "safe to retry") into the
        // single generic 'enom_api_failure' reason. This test proves, empirically, that today's
        // contract does NOT let this guard distinguish that case from a truly ambiguous/unsafe
        // failure — so even this "sounds like a retry" scenario still fails closed. This is the
        // documented, intentional consequence of not weakening the service to manufacture a
        // distinction it cannot currently support safely (see the TLD-3G.2A final report).
        Http::fake([
            'reseller.enom.com/*' => Http::response(
                '<?xml version="1.0"?><interface-response><ErrCount>1</ErrCount><errors><Err1>Domain not found in your account</Err1></errors></interface-response>',
                200,
                ['Content-Type' => 'application/xml']
            ),
        ]);

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, $provider);
        $before = $domain->fresh();
        $this->bindFakeRegistrar(true);

        $response = $this->actingAs($admin)->put(route('dashboard.domains.register.update', $domain), [
            'provider_id' => $provider->id,
            'registration_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('provider_id');
        $this->assertSame([], $this->registerCalls, 'current contract cannot safely distinguish this from an ambiguous failure, so it fails closed too');
        $this->assertSame($before->provider_id, $domain->fresh()->provider_id);
    }

    public function test_two_enom_providers_different_provider_still_hard_rejected_with_no_fallback(): void
    {
        // F/I — even when BOTH providers are exactly type 'enom', switching to a different
        // provider_id on a managed domain is still hard-rejected by the pre-existing TLD-3E.2
        // guard, and the new TLD-3G.2A verification guard is never reached at all (proven by
        // Http::assertNothingSent() — zero Enom traffic of any kind). No provider-by-type
        // fallback/resolution ever happens.
        Http::fake();

        $admin = $this->makeAdmin();
        $originalEnom = $this->makeProvider('enom', true, 'live');
        $otherEnom = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, $originalEnom);
        $this->bindFakeRegistrar(true);

        $response = $this->actingAs($admin)->put(route('dashboard.domains.register.update', $domain), [
            'provider_id' => $otherEnom->id,
            'registration_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('provider_id');
        $this->assertSame([], $this->registerCalls);
        Http::assertNothingSent();

        $fresh = $domain->fresh();
        $this->assertSame($originalEnom->id, $fresh->provider_id);
    }

    public function test_external_domain_with_enom_provider_registers_normally_without_verification_gate(): void
    {
        // G — an external/unmanaged domain (provider_id null) selecting an Enom provider must
        // NOT trigger the new verification gate at all — it is not a re-registration. Proven via
        // Http::assertNothingSent(): zero Enom traffic occurs even though the provider is enom.
        Http::fake();

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client);
        $this->assertNull($domain->provider_id, 'domain starts external/unmanaged');
        $this->bindFakeRegistrar(true);

        $response = $this->actingAs($admin)->put(route('dashboard.domains.register.update', $domain), [
            'provider_id' => $provider->id,
            'registration_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertRedirect(route('dashboard.domains.index'));
        $response->assertSessionDoesntHaveErrors();
        Http::assertNothingSent();
        $this->assertSame([$provider->id], $this->registerCalls);
        $this->assertSame($provider->id, $domain->fresh()->provider_id);
    }

    public function test_namecheap_same_provider_retry_makes_zero_enom_calls(): void
    {
        // H — Namecheap protection is explicitly deferred scope for TLD-3G.2A. This test proves
        // the existing same-provider retry behavior for a NON-enom provider is completely
        // unaffected by the new guard, and that no Enom (or any) HTTP traffic is generated for
        // it at all — the type==='enom' condition correctly excludes Namecheap entirely.
        Http::fake();

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('namecheap', true, 'live');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, $provider);
        $this->bindFakeRegistrar(true);

        $response = $this->actingAs($admin)->put(route('dashboard.domains.register.update', $domain), [
            'provider_id' => $provider->id,
            'registration_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertRedirect(route('dashboard.domains.index'));
        $response->assertSessionDoesntHaveErrors();
        Http::assertNothingSent();
        $this->assertSame([$provider->id], $this->registerCalls);
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

    /* ================================ Helpers ================================ */

    private function makeAdmin(): User
    {
        return User::factory()->create(['super_admin' => true]);
    }

    private function makeProvider(string $type, bool $active = true, string $mode = 'test'): DomainProvider
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
            'first_name' => 'Admin',
            'last_name' => 'Register',
            'email' => uniqid('admin_register_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Admin Register Test',
        ]);
    }

    private function makeDomain(Client $client, ?DomainProvider $provider = null): Domain
    {
        return Domain::query()->create([
            'client_id' => $client->id,
            'domain_name' => uniqid('admin-register-', false) . '.test',
            'registrar' => $provider?->type ?? 'unassigned',
            'provider_id' => $provider?->id,
            'registration_date' => now()->subDay()->toDateString(),
            'renewal_date' => now()->toDateString(),
            'status' => 'pending',
        ]);
    }

    /**
     * Rebinds DomainController in the container to an anonymous subclass that overrides the
     * protected registerDomainWithProvider() hook, so no real Namecheap/Enom API call is ever
     * made. Every call is recorded (by provider_id) onto $this->registerCalls via a $this-bound
     * closure passed into the subclass constructor as a callable — an anonymous subclass of
     * DomainController cannot reach an unrelated test class's property by name (TLD-3E.1A), so a
     * plain static property is not used here.
     */
    private function bindFakeRegistrar(bool $ok, ?string $message = null): void
    {
        $recordCall = function (int $providerId): void {
            $this->registerCalls[] = $providerId;
        };

        $this->app->bind(DomainController::class, function () use ($ok, $message, $recordCall) {
            return new class($ok, $message, $recordCall) extends DomainController {
                private bool $ok;
                private ?string $message;
                /** @var callable */
                private $recordCall;

                public function __construct(bool $ok, ?string $message, callable $recordCall)
                {
                    $this->ok = $ok;
                    $this->message = $message;
                    $this->recordCall = $recordCall;
                }

                protected function registerDomainWithProvider(
                    DomainProvider $provider,
                    Domain $domain,
                    array $context,
                    array $contact
                ): array {
                    ($this->recordCall)($provider->getKey());

                    if (!$this->ok) {
                        return [
                            'ok' => false,
                            'message' => $this->message ?? 'Registrar declined the request.',
                        ];
                    }

                    return [
                        'ok' => true,
                        'cid' => 'test-cid-' . $provider->getKey(),
                    ];
                }
            };
        });
    }
}
