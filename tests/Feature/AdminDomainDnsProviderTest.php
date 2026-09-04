<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\Management\DomainController;
use App\Models\Client;
use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

/**
 * TLD-3E.2 — Admin DNS Exact Provider Selection.
 *
 * Locks in that Admin DNS (editDns()/updateDns()) resolves the provider ONLY via the trusted
 * Domain.provider_id — never Domain.registrar (display/compatibility metadata only) and never
 * DomainProvider::query()->active()->ofType($type)->first(). A provider_id === null or an
 * inactive/nonexistent linked provider fails safely with no provider API call. A
 * registrar/provider.type mismatch is logged as a drift signal (same non-blocking pattern as
 * TLD-3D's renewal source-of-truth) but never used to re-resolve or override the provider.
 *
 * updateDns()'s registrar API call is verified without ever hitting a real Namecheap/Enom
 * endpoint by rebinding DomainController to an anonymous subclass that overrides the protected
 * pushNameserversToProvider() hook (same technique as AdminDomainRegisterTest's
 * registerDomainWithProvider() override) — the call is recorded (by provider_id) onto a
 * $this-bound closure, never a cross-class static property (TLD-3E.1A).
 *
 * editDns()'s read-only registrar DNS *snapshot* fetch is exercised as-is (it was already
 * wrapped in try/catch before TLD-3E.2 and remains so): these tests only assert on the
 * provider-resolution view data ($remoteDns['provider'] / ['provider_label']), which is set
 * before that try/catch runs, so a failed/unreachable live fetch against the fake test provider
 * endpoints does not affect what is being proven here.
 *
 * Out of scope here (per TLD-3E.2): Admin Renew, Admin Create/Edit, Client quick-add,
 * DomainProvider delete, transfer/import semantics.
 */
class AdminDomainDnsProviderTest extends TestCase
{
    use DatabaseMigrations;

    /** @var int[] provider_id values passed to the faked pushNameserversToProvider() call(s). */
    protected array $dnsCalls = [];

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->dnsCalls = [];
    }

    /* ==================================== H ===================================== */

    public function test_dns_update_pushes_through_the_exact_linked_provider_even_with_a_same_type_competitor(): void
    {
        $admin = $this->makeAdmin();
        $primary = $this->makeProvider('namecheap');
        $linked = $this->makeProvider('namecheap');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, $linked, 'namecheap');
        $this->bindFakeDnsPusher(true);

        $response = $this->actingAs($admin)->put(route('dashboard.domains.dns.update', $domain), [
            'nameservers' => ['ns1.example.com', 'ns2.example.com'],
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();

        // Exactly the domain's linked provider is used — never the same-type sibling.
        $this->assertSame([$linked->id], $this->dnsCalls);
        $this->assertNotContains($primary->id, $this->dnsCalls);

        $fresh = $domain->fresh();
        $this->assertNotNull($fresh->dns_last_synced_at);
        $this->assertSame(['ns1.example.com', 'ns2.example.com'], $fresh->nameservers);
    }

    public function test_dns_view_shows_the_exact_linked_provider_identity_not_a_same_type_competitor(): void
    {
        $admin = $this->makeAdmin();
        $primary = $this->makeProvider('namecheap');
        $linked = $this->makeProvider('namecheap');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, $linked, 'namecheap');

        $response = $this->actingAs($admin)->get(route('dashboard.domains.dns.edit', $domain));

        $response->assertOk();
        $response->assertViewHas('remoteDns', function ($remoteDns) use ($linked, $primary) {
            return ($remoteDns['provider'] ?? null) === 'namecheap'
                && str_contains((string) ($remoteDns['provider_label'] ?? ''), (string) $linked->name)
                && !str_contains((string) ($remoteDns['provider_label'] ?? ''), (string) $primary->name);
        });
    }

    /* ==================================== I ===================================== */

    public function test_dns_update_ignores_a_misleading_registrar_string_and_uses_the_trusted_provider_id(): void
    {
        $admin = $this->makeAdmin();
        // Linked provider is enom, but Domain.registrar is left as the misleading "namecheap".
        $linkedEnom = $this->makeProvider('enom');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, $linkedEnom, 'namecheap');
        $this->assertSame('namecheap', $domain->registrar);
        $this->assertSame($linkedEnom->id, $domain->provider_id);
        $this->bindFakeDnsPusher(true);

        $response = $this->actingAs($admin)->put(route('dashboard.domains.dns.update', $domain), [
            'nameservers' => ['ns1.example.com', 'ns2.example.com'],
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        // The push happened through the provider_id-linked provider (enom), never a provider
        // resolved from the misleading "namecheap" registrar string.
        $this->assertSame([$linkedEnom->id], $this->dnsCalls);
    }

    /* ==================================== J ===================================== */

    public function test_dns_update_with_null_provider_id_fails_safely_with_no_provider_api_call(): void
    {
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, null, 'unassigned');
        $this->assertNull($domain->provider_id);
        $this->bindFakeDnsPusher(true);

        $response = $this->actingAs($admin)->put(route('dashboard.domains.dns.update', $domain), [
            'nameservers' => ['ns1.example.com', 'ns2.example.com'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['nameservers' => 'لا يوجد مزود مُدار مرتبط بهذا النطاق.']);
        $this->assertSame([], $this->dnsCalls, 'no provider API call must be made when provider_id is null');
        $this->assertNull($domain->fresh()->dns_last_synced_at);
    }

    /* ==================================== K ===================================== */

    public function test_dns_update_with_inactive_linked_provider_fails_safely_with_no_provider_api_call(): void
    {
        $admin = $this->makeAdmin();
        $inactive = $this->makeProvider('namecheap', false);
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, $inactive, 'namecheap');
        $this->bindFakeDnsPusher(true);

        $response = $this->actingAs($admin)->put(route('dashboard.domains.dns.update', $domain), [
            'nameservers' => ['ns1.example.com', 'ns2.example.com'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('nameservers');
        $this->assertSame([], $this->dnsCalls, 'no provider API call must be made when the linked provider is inactive');
        $this->assertNull($domain->fresh()->dns_last_synced_at);
    }

    /* ==================================== L ===================================== */

    public function test_no_ofType_first_resolution_remains_in_domain_controller(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Admin/Management/DomainController.php'));
        $this->assertIsString($source);
        $this->assertStringNotContainsString('->ofType(', $source, 'DomainController must not resolve any provider via ofType()->first() ambiguity anymore');
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
            'last_name' => 'Dns',
            'email' => uniqid('admin_dns_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Admin DNS Test',
        ]);
    }

    private function makeDomain(Client $client, ?DomainProvider $provider, string $registrar): Domain
    {
        return Domain::query()->create([
            'client_id' => $client->id,
            'domain_name' => uniqid('admin-dns-', false) . '.test',
            'registrar' => $registrar,
            'provider_id' => $provider?->id,
            'registration_date' => now()->subYear()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);
    }

    /**
     * Rebinds DomainController in the container to an anonymous subclass that overrides the
     * protected pushNameserversToProvider() hook, so no real Namecheap/Enom API call is ever
     * made. Every call is recorded (by provider_id) onto $this->dnsCalls via a $this-bound
     * closure passed into the subclass constructor as a callable (same pattern as
     * AdminDomainRegisterTest::bindFakeRegistrar() — TLD-3E.1A).
     */
    private function bindFakeDnsPusher(bool $ok, ?string $message = null): void
    {
        $recordCall = function (int $providerId): void {
            $this->dnsCalls[] = $providerId;
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

                protected function pushNameserversToProvider(
                    DomainProvider $provider,
                    Domain $domain,
                    array $nameservers
                ): array {
                    ($this->recordCall)($provider->getKey());

                    if (!$this->ok) {
                        return [
                            'ok' => false,
                            'message' => $this->message ?? 'Registrar declined the request.',
                        ];
                    }

                    return ['ok' => true, 'cid' => 'test-cid-' . $provider->getKey()];
                }
            };
        });
    }
}
