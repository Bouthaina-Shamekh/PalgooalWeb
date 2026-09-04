<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

/**
 * TLD-3E.4D — External Domain Auto-Renew Guard.
 *
 * Domain.provider_id is the Source of Truth for Managed vs External (TLD-3D / TLD-3E.4).
 * Client\DomainController::toggleAutoRenew() must reject enabling auto-renew on an External
 * domain (provider_id === null, or a linked-but-inactive provider), while always allowing
 * disabling — including from the invalid legacy state (provider_id null, auto_renew true) —
 * so a client can always self-recover. No Order/Invoice is ever created by this action, and
 * Domain.provider_id is never written by it.
 *
 * This file also covers the TLD-3E.4D prerequisite fix: Client\DomainController::ownedDomain()
 * was previously undefined (edit()/update()/destroy()/toggleAutoRenew() all called it without
 * this class ever defining it), so every request to any of those four methods fatal-errored.
 * Test J proves the now-added ownedDomain() enforces the same cross-client 404 the sibling
 * DomainDnsController::ownedDomain() already enforces for DNS.
 */
class ClientDomainAutoRenewTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    /* ================================ A ================================ */

    public function test_managed_domain_can_enable_auto_renew(): void
    {
        $provider = $this->makeProvider('namecheap');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, 'managed-enable.test', $provider, autoRenew: false);

        $response = $this->actingAs($client, 'client')
            ->patch(route('client.domains.auto-renew', $domain->id));

        $response->assertRedirect(route('client.domains.index'));
        $response->assertSessionHas('ok');
        $response->assertSessionMissing('error');
        $this->assertTrue($domain->fresh()->auto_renew);
    }

    /* =============================== B–F ================================ */

    public function test_external_domain_rejects_enabling_with_no_state_change_and_no_financial_writes(): void
    {
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, 'external-reject.test', null, autoRenew: false);

        $response = $this->actingAs($client, 'client')
            ->patch(route('client.domains.auto-renew', $domain->id));

        $response->assertRedirect(route('client.domains.index'));
        $response->assertSessionHas(
            'error',
            'لا يمكن تفعيل التجديد التلقائي لهذا النطاق لأنه غير مرتبط بمزوّد مُدار.'
        );

        $fresh = $domain->fresh();
        $this->assertFalse($fresh->auto_renew);   // C
        $this->assertNull($fresh->provider_id);   // F — never silently attached
        $this->assertSame(0, Order::query()->count());   // D
        $this->assertSame(0, Invoice::query()->count());  // E
    }

    /* ================================ G ================================ */

    public function test_legacy_invalid_state_can_always_be_disabled(): void
    {
        $client = $this->makeClient();
        // Directly construct the invalid legacy state the guard must still allow recovering from:
        // provider_id null (External) with auto_renew already true (pre-existing bad data).
        $domain = $this->makeDomain($client, 'legacy-invalid.test', null, autoRenew: true);

        $response = $this->actingAs($client, 'client')
            ->patch(route('client.domains.auto-renew', $domain->id));

        $response->assertRedirect(route('client.domains.index'));
        $response->assertSessionHas('ok');
        $response->assertSessionMissing('error');
        $this->assertFalse($domain->fresh()->auto_renew);
    }

    /* ================================ H ================================ */

    public function test_managed_domain_can_disable_auto_renew(): void
    {
        $provider = $this->makeProvider('namecheap');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, 'managed-disable.test', $provider, autoRenew: true);

        $response = $this->actingAs($client, 'client')
            ->patch(route('client.domains.auto-renew', $domain->id));

        $response->assertRedirect(route('client.domains.index'));
        $response->assertSessionHas('ok');
        $this->assertFalse($domain->fresh()->auto_renew);
    }

    /* ================================ I ================================ */

    public function test_forged_registrar_text_does_not_bypass_the_provider_id_check(): void
    {
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, 'forged-registrar.test', null, autoRenew: false, registrar: 'enom');

        $response = $this->actingAs($client, 'client')
            ->patch(route('client.domains.auto-renew', $domain->id));

        $response->assertSessionHas('error');
        $this->assertFalse($domain->fresh()->auto_renew);
    }

    /* ================================ J ================================ */

    public function test_another_clients_domain_remains_inaccessible(): void
    {
        $owner = $this->makeClient();
        $intruder = $this->makeClient();
        $provider = $this->makeProvider('namecheap');
        $domain = $this->makeDomain($owner, 'not-yours.test', $provider, autoRenew: false);

        $response = $this->actingAs($intruder, 'client')
            ->patch(route('client.domains.auto-renew', $domain->id));

        $response->assertNotFound();
        $this->assertFalse($domain->fresh()->auto_renew);
    }

    /* ================================ K ================================ */

    public function test_inactive_linked_provider_cannot_enable_auto_renew(): void
    {
        $provider = $this->makeProvider('namecheap', active: false);
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, 'inactive-provider.test', $provider, autoRenew: false);

        $response = $this->actingAs($client, 'client')
            ->patch(route('client.domains.auto-renew', $domain->id));

        $response->assertSessionHas('error');
        $this->assertFalse($domain->fresh()->auto_renew);
    }

    /* ============================ Helpers ============================ */

    private function makeProvider(string $type, bool $active = true): DomainProvider
    {
        return DomainProvider::query()->create([
            'name' => ucfirst($type) . ' Auto-Renew Test ' . uniqid('', true),
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
            'first_name' => 'Auto',
            'last_name' => 'Renew',
            'email' => uniqid('client_auto_renew_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Auto Renew Test',
            'can_login' => true,
        ]);
    }

    private function makeDomain(
        Client $client,
        string $domainName,
        ?DomainProvider $provider,
        bool $autoRenew = false,
        string $registrar = 'namecheap',
    ): Domain {
        return Domain::query()->create([
            'client_id' => $client->id,
            'domain_name' => $domainName,
            'registrar' => $registrar,
            'provider_id' => $provider?->id,
            'registration_date' => now()->subYear()->toDateString(),
            'renewal_date' => now()->addDays(30)->toDateString(),
            'auto_renew' => $autoRenew,
            'status' => 'active',
        ]);
    }
}
