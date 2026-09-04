<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * TLD-3E.4B — Admin Edit Invariants.
 *
 * Domain.provider_id is the Source of Truth for Managed vs External (TLD-3D/TLD-3E.4). Ordinary
 * Admin Edit must never change provider_id and must never let registrar drift away from
 * provider.type on a Managed domain (provider_id !== null) — the exact drift the TLD-3E.4 audit
 * flagged as reachable (state D: provider_id=Enom, registrar=namecheap). External domains
 * (provider_id === null) keep registrar as freely editable metadata, unchanged from before.
 *
 * No provider transfer, no reassignment, no detach flow, and no client_id/status/date/auto_renew
 * behavior is touched here — those are separate, already-covered or explicitly out-of-scope
 * concerns. Admin Register/DNS/Renew/Create contract tests live in their own files and are not
 * duplicated here.
 */
class AdminDomainEditProviderInvariantTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    /* ================================ A ================================ */

    public function test_normal_edit_of_a_managed_domain_preserves_provider_id(): void
    {
        Http::fake();
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $provider = $this->makeProvider('enom');
        $domain = $this->makeDomain($client, 'managed-normal-edit.test', $provider, registrar: 'enom');

        $this->actingAs($admin)->put(route('dashboard.domains.update', $domain->id), $this->payloadFor($domain))
            ->assertRedirect(route('dashboard.domains.index'));

        $this->assertSame($provider->id, $domain->fresh()->provider_id);
    }

    /* ================================ B ================================ */

    public function test_forged_provider_id_in_request_cannot_change_provider_id(): void
    {
        Http::fake();
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $provider = $this->makeProvider('enom');
        $competitor = $this->makeProvider('namecheap');
        $domain = $this->makeDomain($client, 'forged-provider-id.test', $provider, registrar: 'enom');

        $this->actingAs($admin)->put(
            route('dashboard.domains.update', $domain->id),
            array_merge($this->payloadFor($domain), ['provider_id' => $competitor->id]),
        );

        $this->assertSame($provider->id, $domain->fresh()->provider_id);
    }

    /* ================================ C ================================ */

    public function test_managed_domain_forged_registrar_is_overridden_by_the_trusted_provider_type(): void
    {
        Http::fake();
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $provider = $this->makeProvider('enom');
        $domain = $this->makeDomain($client, 'forged-registrar.test', $provider, registrar: 'enom');

        $this->actingAs($admin)->put(
            route('dashboard.domains.update', $domain->id),
            array_merge($this->payloadFor($domain), ['registrar' => 'namecheap']),
        );

        $fresh = $domain->fresh();
        $this->assertSame('enom', $fresh->registrar);
        $this->assertSame($provider->id, $fresh->provider_id);
    }

    /* ================================ D ================================ */

    public function test_same_type_competing_provider_is_never_switched_in_via_edit(): void
    {
        Http::fake();
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $trusted = $this->makeProvider('enom');
        $competitor = $this->makeProvider('enom'); // same type, different row
        $domain = $this->makeDomain($client, 'same-type-competitor.test', $trusted, registrar: 'enom');

        $this->actingAs($admin)->put(
            route('dashboard.domains.update', $domain->id),
            array_merge($this->payloadFor($domain), ['provider_id' => $competitor->id]),
        );

        $this->assertSame($trusted->id, $domain->fresh()->provider_id);
    }

    /* ================================ E ================================ */

    public function test_inactive_linked_provider_still_allows_metadata_edit_and_keeps_derived_registrar(): void
    {
        Http::fake();
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $provider = $this->makeProvider('enom', active: false);
        $domain = $this->makeDomain($client, 'inactive-provider-edit.test', $provider, registrar: 'enom');

        $payload = $this->payloadFor($domain);
        $payload['renewal_date'] = now()->addYears(2)->toDateString();

        $response = $this->actingAs($admin)->put(route('dashboard.domains.update', $domain->id), $payload);

        $response->assertRedirect(route('dashboard.domains.index'));
        $fresh = $domain->fresh();
        $this->assertSame($provider->id, $fresh->provider_id);
        $this->assertSame('enom', $fresh->registrar);
        $this->assertSame($payload['renewal_date'], \Carbon\Carbon::parse($fresh->renewal_date)->toDateString());
    }

    /* ================================ F ================================ */

    public function test_external_domain_registrar_remains_editable_metadata(): void
    {
        Http::fake();
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, 'external-editable.test', null, registrar: 'enom');

        $this->actingAs($admin)->put(
            route('dashboard.domains.update', $domain->id),
            array_merge($this->payloadFor($domain), ['registrar' => 'namcheap']),
        );

        $this->assertSame('namcheap', $domain->fresh()->registrar);
    }

    /* ================================ G ================================ */

    public function test_external_domain_registrar_enom_does_not_attach_a_provider(): void
    {
        Http::fake();
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $this->makeProvider('enom'); // an active matching-type provider exists
        $domain = $this->makeDomain($client, 'external-no-attach.test', null, registrar: 'namcheap');

        $this->actingAs($admin)->put(
            route('dashboard.domains.update', $domain->id),
            array_merge($this->payloadFor($domain), ['registrar' => 'enom']),
        );

        $fresh = $domain->fresh();
        $this->assertSame('enom', $fresh->registrar);
        $this->assertNull($fresh->provider_id);
    }

    /* ================================ H ================================ */

    public function test_external_domain_forged_provider_id_remains_null(): void
    {
        Http::fake();
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $provider = $this->makeProvider('enom');
        $domain = $this->makeDomain($client, 'external-forged-provider.test', null, registrar: 'enom');

        $this->actingAs($admin)->put(
            route('dashboard.domains.update', $domain->id),
            array_merge($this->payloadFor($domain), ['provider_id' => $provider->id]),
        );

        $this->assertNull($domain->fresh()->provider_id);
    }

    /* ============================== I / J ================================ */

    public function test_managed_edit_view_exposes_no_usable_provider_selector_and_shows_read_only_identity(): void
    {
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $provider = $this->makeProvider('enom');
        $domain = $this->makeDomain($client, 'managed-view.test', $provider, registrar: 'enom');

        $response = $this->actingAs($admin)->get(route('dashboard.domains.edit', $domain));

        $response->assertOk();
        // No provider_id form control of any kind, and no editable registrar <select>.
        $response->assertDontSee('name="provider_id"', false);
        $response->assertDontSee('<select id="registrar"', false);
        // The trusted provider identity is still shown to the admin (read-only) — the disabled
        // display input plus the hidden field that carries the value through validation.
        $response->assertSee('disabled', false);
        $response->assertSee('name="registrar" value="enom"', false);
    }

    /* ================================ K ================================ */

    public function test_external_edit_view_still_shows_an_editable_registrar_select(): void
    {
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, 'external-view.test', null, registrar: 'enom');

        $response = $this->actingAs($admin)->get(route('dashboard.domains.edit', $domain));

        $response->assertOk();
        $response->assertSee('<select id="registrar"', false);
    }

    /* ================================ L ================================ */

    // Not implemented: Domain.provider_id is a foreign key with restrictOnDelete(), and the test
    // database runs with foreign key constraints enabled (config/database.php:
    // 'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true), no .env.testing override) — a
    // Domain row pointing at a non-existent/removed DomainProvider row cannot be constructed
    // through normal Eloquent/DB operations in this test database. The defensive branch in
    // update() (return back()->withErrors(...) when $domain->provider is null after
    // loadMissing()) is implemented per Section 5 but is not exercised by an automated test here.

    /* ================================ M ================================ */

    public function test_non_admin_is_denied_access_to_edit_and_update(): void
    {
        $regularUser = User::factory()->create(['super_admin' => false]);
        $client = $this->makeClient();
        $provider = $this->makeProvider('enom');
        $domain = $this->makeDomain($client, 'authz-unchanged.test', $provider, registrar: 'enom');

        $this->actingAs($regularUser)->get(route('dashboard.domains.edit', $domain))->assertForbidden();
        $this->actingAs($regularUser)->put(route('dashboard.domains.update', $domain->id), $this->payloadFor($domain))
            ->assertForbidden();

        $this->assertSame($provider->id, $domain->fresh()->provider_id);
    }

    /* ============================ Helpers ============================ */

    private function payloadFor(Domain $domain): array
    {
        return [
            'client_id' => $domain->client_id,
            'domain_name' => $domain->domain_name,
            'registrar' => $domain->registrar,
            'registration_date' => $domain->registration_date instanceof \Carbon\Carbon
                ? $domain->registration_date->toDateString()
                : $domain->registration_date,
            'renewal_date' => $domain->renewal_date instanceof \Carbon\Carbon
                ? $domain->renewal_date->toDateString()
                : $domain->renewal_date,
            'status' => $domain->status,
        ];
    }

    private function makeAdmin(): User
    {
        return User::factory()->create(['super_admin' => true]);
    }

    private function makeProvider(string $type, bool $active = true): DomainProvider
    {
        return DomainProvider::query()->create([
            'name' => strtoupper($type) . ' ' . uniqid('', true),
            'type' => $type,
            'mode' => 'test',
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
            'last_name' => 'Edit',
            'email' => uniqid('admin_edit_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Admin Edit Test',
            'can_login' => true,
        ]);
    }

    private function makeDomain(
        Client $client,
        string $domainName,
        ?DomainProvider $provider,
        string $registrar = 'enom',
    ): Domain {
        return Domain::query()->create([
            'client_id' => $client->id,
            'domain_name' => $domainName,
            'registrar' => $registrar,
            'provider_id' => $provider?->id,
            'registration_date' => now()->subYear()->toDateString(),
            'renewal_date' => now()->addDays(30)->toDateString(),
            'auto_renew' => false,
            'status' => 'active',
        ]);
    }
}
