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
