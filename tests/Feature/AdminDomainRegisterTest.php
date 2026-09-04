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
 * TLD-3E.1 — Admin Register Exact Provider Identity.
 *
 * Locks in the single fix applied to DomainController::updateRegister()'s success branch: after
 * a successful registrar API call, the Domain is now updated with the EXACT $provider instance
 * already used for that call (provider_id + registrar derived from provider->type), instead of
 * only `registrar` derived from raw request input with no provider_id write at all. The failure
 * branch is unchanged — it already returned before ever touching the Domain, so an existing
 * Domain.provider_id (managed or not) is untouched by a failed registration attempt.
 *
 * These tests never hit a real registrar API: DomainController is rebound in the container to
 * an anonymous subclass that overrides the protected registerDomainWithProvider() hook (the same
 * technique already used for RegistrarProvisioningService in ProviderSourceOfTruthTest and
 * RenewalProviderSourceOfTruthTest), so the exact $provider passed to it can be captured and
 * the API outcome (ok/fail) controlled per test.
 *
 * Out of scope here (per TLD-3E.1): Admin Renew, Admin DNS, Admin Create/Edit, Client quick-add,
 * DomainProvider delete, the same-type provider PICKER's ambiguity itself — only whether the
 * write after a successful call is exactly consistent with whichever provider was actually used.
 */
class AdminDomainRegisterTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * provider_id values passed to the faked registerDomainWithProvider() call(s). A plain
     * instance property on the test case, reset automatically since PHPUnit/Pest construct a
     * fresh test-case instance per test method — no setUp() reset needed.
     *
     * TLD-3E.1A harness fix: this used to be a `protected static` property that the anonymous
     * DomainController subclass in bindFakeRegistrar() wrote to directly via
     * `AdminDomainRegisterTest::$registerCalls[] = ...`. That failed at runtime with "Cannot
     * access protected property" — PHP's "anonymous class declared inside a method gets that
     * method's scope" rule extends visibility for code written directly inside this class's own
     * methods, but it does NOT extend to the anonymous class's own method bodies referencing an
     * unrelated class by name: ordinary inheritance-based visibility still applies there, and the
     * anonymous class extends DomainController, not this test case. Fixed by recording through a
     * closure instead (see bindFakeRegistrar()) — closures keep the $this-binding of the scope
     * they were created in even when invoked from unrelated code, so no cross-class property
     * access is needed at all.
     *
     * @var int[]
     */
    protected array $registerCalls = [];

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    /* ============================== A / B / E ============================== */

    public function test_successful_admin_registration_writes_the_exact_provider_id_and_registrar_from_provider_type(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('namecheap');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client);
        $this->bindFakeRegistrar(true);

        $response = $this->actingAs($admin)->put(route('dashboard.domains.register.update', $domain), [
            'registrar' => 'namecheap',
            'registration_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertRedirect(route('dashboard.domains.index'));

        $fresh = $domain->fresh();
        // A — exact provider_id written.
        $this->assertSame($provider->id, $fresh->provider_id);
        // B — registrar comes from provider->type, not raw request text.
        $this->assertSame($provider->type, $fresh->registrar);
        // E — internally consistent: provider_id and registrar agree with the same provider row.
        $this->assertSame($fresh->provider_id, $provider->id);
        $this->assertSame($fresh->registrar, $provider->type);
        // The write matches exactly the provider that was actually used for the API call.
        $this->assertSame([$provider->id], $this->registerCalls);
    }

    /* ================================== C ==================================== */

    public function test_failed_admin_registration_does_not_write_provider_id(): void
    {
        $admin = $this->makeAdmin();
        $this->makeProvider('namecheap');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client);
        $this->assertNull($domain->provider_id);
        $this->bindFakeRegistrar(false, 'Registrar declined the request.');

        $response = $this->actingAs($admin)->put(route('dashboard.domains.register.update', $domain), [
            'registrar' => 'namecheap',
            'registration_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('registrar');

        $fresh = $domain->fresh();
        $this->assertNull($fresh->provider_id);
        // The pre-existing placeholder registrar/status/dates are untouched too — the whole
        // update() call is skipped on a failed API result, not merely the provider_id line.
        $this->assertSame($domain->registrar, $fresh->registrar);
        $this->assertSame($domain->status, $fresh->status);
    }

    /* ================================== D ==================================== */

    public function test_failed_admin_registration_does_not_overwrite_an_existing_provider_id(): void
    {
        $admin = $this->makeAdmin();
        $originalProvider = $this->makeProvider('namecheap');
        $otherProvider = $this->makeProvider('enom');
        $client = $this->makeClient();
        // An already-managed domain (e.g. from a prior successful registration).
        $domain = $this->makeDomain($client, $originalProvider);
        $this->assertSame($originalProvider->id, $domain->provider_id);
        // This attempt resolves a DIFFERENT provider and fails.
        $this->bindFakeRegistrar(false, 'Registrar declined the request.');

        $response = $this->actingAs($admin)->put(route('dashboard.domains.register.update', $domain), [
            'registrar' => 'enom',
            'registration_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('registrar');

        $fresh = $domain->fresh();
        $this->assertSame($originalProvider->id, $fresh->provider_id);
        $this->assertNotSame($otherProvider->id, $fresh->provider_id);
        $this->assertSame('namecheap', $fresh->registrar);
    }

    /* ============================ Item 6 — exactness ============================ */

    public function test_successful_registration_writes_the_exact_provider_instance_actually_called_even_with_a_same_type_competitor(): void
    {
        $admin = $this->makeAdmin();
        $primary = $this->makeProvider('namecheap');
        $competitor = $this->makeProvider('namecheap');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client);
        $this->bindFakeRegistrar(true);

        $response = $this->actingAs($admin)->put(route('dashboard.domains.register.update', $domain), [
            'registrar' => 'namecheap',
            'registration_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertRedirect(route('dashboard.domains.index'));

        // updateRegister() still resolves the provider internally via ofType()->first(), so
        // which of $primary/$competitor gets called is not something this test controls or
        // asserts (that ambiguity is documented as remaining TLD-3E.2 debt, see item 18 of the
        // TLD-3E.1 report). What this test proves is the exactness property the fix guarantees:
        // whichever provider was ACTUALLY passed to registerDomainWithProvider() is precisely
        // the one persisted onto the Domain afterward — no independent re-resolution occurs
        // between the API call and the write.
        $this->assertCount(1, $this->registerCalls);
        $calledProviderId = $this->registerCalls[0];
        $this->assertContains($calledProviderId, [$primary->id, $competitor->id]);

        $fresh = $domain->fresh();
        $this->assertSame($calledProviderId, $fresh->provider_id);
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
     * made. Every call is recorded (by provider_id) onto $this->registerCalls, and the outcome
     * is fixed to $ok/$message for the whole test.
     */
    private function bindFakeRegistrar(bool $ok, ?string $message = null): void
    {
        // A closure declared inside this (instance) method keeps its $this-binding to THIS test
        // case for the closure's whole lifetime, no matter who ends up calling it later — unlike
        // a cross-class static property reference, this needs no visibility relationship between
        // the anonymous DomainController subclass below and AdminDomainRegisterTest at all.
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
