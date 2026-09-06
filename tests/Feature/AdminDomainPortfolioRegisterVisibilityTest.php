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
 * TLD-3G.2B — Domain Portfolio Register Visibility.
 *
 * Covers the Admin Domain Portfolio index Blade's Register-eligibility condition (identical
 * rule to DomainController::isManagedEnomDomain(), reused for the GET guard in
 * AdminDomainRegisterTest) and confirms Renew/Change DNS remain untouched by this phase for the
 * same managed-Enom domain shape. Every scenario that could reach Enom uses Http::fake() — no
 * real registrar HTTP request is ever made from this file.
 */
class AdminDomainPortfolioRegisterVisibilityTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    /* ====================== A — managed Enom domain ====================== */

    public function test_register_hidden_renew_and_dns_shown_for_managed_enom_domain(): void
    {
        Http::fake();

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, $provider);

        $response = $this->actingAs($admin)->get(route('dashboard.domains.index'));

        $response->assertOk();
        $response->assertDontSee(route('dashboard.domains.register.edit', $domain), false);
        $response->assertSee(route('dashboard.domains.renew.edit', $domain), false);
        $response->assertSee(route('dashboard.domains.dns.edit', $domain), false);
    }

    /* ====================== D — external/unmanaged domain ====================== */

    public function test_register_shown_for_external_unmanaged_domain(): void
    {
        Http::fake();

        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $domain = $this->makeDomain($client);
        $this->assertNull($domain->provider_id);

        $response = $this->actingAs($admin)->get(route('dashboard.domains.index'));

        $response->assertOk();
        $response->assertSee(route('dashboard.domains.register.edit', $domain), false);
    }

    /* ====================== E/G — managed Namecheap domain (legitimate retry candidate) ====================== */

    public function test_register_shown_for_managed_namecheap_domain(): void
    {
        Http::fake();

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('namecheap', true, 'live');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, $provider);

        $response = $this->actingAs($admin)->get(route('dashboard.domains.index'));

        $response->assertOk();
        $response->assertSee(route('dashboard.domains.register.edit', $domain), false);
    }

    /* ====================== H — Renew route remains accessible for a managed Enom domain ====================== */

    public function test_renew_edit_route_remains_accessible_for_managed_enom_domain(): void
    {
        Http::fake();

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, $provider);

        $response = $this->actingAs($admin)->get(route('dashboard.domains.renew.edit', $domain));

        $response->assertOk();
    }

    /* ====================== I — Change DNS route remains accessible for a managed Enom domain ====================== */

    public function test_dns_edit_route_remains_accessible_for_managed_enom_domain(): void
    {
        // editDns() fetches a live-but-faked DNS snapshot for an enom provider (pre-existing,
        // unrelated behavior this phase does not touch) — faked here so the page still renders;
        // a fetch failure is handled gracefully by editDns() itself and the page still returns
        // 200 either way.
        Http::fake([
            'reseller.enom.com/*' => Http::response('Internal Server Error', 500),
        ]);

        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('enom', true, 'live');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, $provider);

        $response = $this->actingAs($admin)->get(route('dashboard.domains.dns.edit', $domain));

        $response->assertOk();
    }

    /* ================================ Helpers ================================ */

    private function makeAdmin(): User
    {
        return User::factory()->create(['super_admin' => true]);
    }

    private function makeProvider(string $type, bool $active = true, string $mode = 'live'): DomainProvider
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
            'first_name' => 'Portfolio',
            'last_name' => 'Visibility',
            'email' => uniqid('portfolio_visibility_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Portfolio Visibility Test',
        ]);
    }

    private function makeDomain(Client $client, ?DomainProvider $provider = null): Domain
    {
        return Domain::query()->create([
            'client_id' => $client->id,
            'domain_name' => uniqid('portfolio-visibility-', false) . '.test',
            'registrar' => $provider?->type ?? 'unassigned',
            'provider_id' => $provider?->id,
            'registration_date' => now()->subDay()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);
    }
}
