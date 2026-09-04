<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\DomainProvisioningAttempt;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * TLD-3E.4A — Formalize External-Only Admin Create Contract.
 *
 * Admin Domain Create produces External/Unmanaged domains ONLY: Domain.provider_id MUST be
 * null and Domain.auto_renew MUST be false on every created record, regardless of what a
 * (forged or future) request body contains. StoreDomainRequest has no rule for either key
 * today, so this was already the accidental effective behavior (per the TLD-3E.4 audit); this
 * phase makes it an explicit, defense-in-depth contract inside
 * DomainController::store() itself. registrar remains display/legacy metadata only and is never
 * used to resolve or imply a provider_id. The only route from External to Managed is the
 * existing Admin Register success path (TLD-3E.1/TLD-3E.2) — not covered here.
 *
 * Admin Register/DNS/Renew contract tests live in AdminDomainRegisterTest,
 * AdminDomainDnsProviderTest, and AdminDomainRenewalTest respectively — not duplicated here.
 */
class AdminDomainCreateExternalContractTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    /* ================================ A ================================ */

    public function test_normal_create_produces_an_external_unmanaged_domain(): void
    {
        Http::fake();
        $admin = $this->makeAdmin();
        $client = $this->makeClient();

        $response = $this->actingAs($admin)->post(route('dashboard.domains.store'), $this->basePayload($client));

        $response->assertRedirect(route('dashboard.domains.index'));
        $domain = Domain::query()->sole();
        $this->assertNull($domain->provider_id);
        $this->assertFalse($domain->auto_renew);
        Http::assertNothingSent();
    }

    /* ================================ B ================================ */

    public function test_forged_provider_id_in_raw_request_is_ignored(): void
    {
        Http::fake();
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $provider = $this->makeProvider('namecheap');

        $this->actingAs($admin)->post(route('dashboard.domains.store'), array_merge(
            $this->basePayload($client),
            ['provider_id' => $provider->id],
        ));

        $domain = Domain::query()->sole();
        $this->assertNull($domain->provider_id);
    }

    /* ================================ C ================================ */

    public function test_forged_auto_renew_in_raw_request_is_ignored(): void
    {
        Http::fake();
        $admin = $this->makeAdmin();
        $client = $this->makeClient();

        $this->actingAs($admin)->post(route('dashboard.domains.store'), array_merge(
            $this->basePayload($client),
            ['auto_renew' => 1],
        ));

        $domain = Domain::query()->sole();
        $this->assertFalse($domain->auto_renew);
    }

    /* ================================ D ================================ */

    public function test_forged_provider_id_and_auto_renew_together_still_yield_external_domain(): void
    {
        Http::fake();
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $provider = $this->makeProvider('namecheap');

        $this->actingAs($admin)->post(route('dashboard.domains.store'), array_merge(
            $this->basePayload($client),
            ['provider_id' => $provider->id, 'auto_renew' => 1],
        ));

        $domain = Domain::query()->sole();
        $this->assertNull($domain->provider_id);
        $this->assertFalse($domain->auto_renew);
    }

    /* =============================== E/F ================================ */

    public function test_registrar_enom_does_not_implicitly_bind_a_provider_id(): void
    {
        Http::fake();
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $this->makeProvider('enom'); // an active matching-type provider exists, but must not be auto-linked

        $this->actingAs($admin)->post(route('dashboard.domains.store'), array_merge(
            $this->basePayload($client),
            ['registrar' => 'enom'],
        ));

        $domain = Domain::query()->sole();
        $this->assertSame('enom', $domain->registrar);
        $this->assertNull($domain->provider_id);
    }

    public function test_registrar_namecheap_does_not_implicitly_bind_a_provider_id(): void
    {
        Http::fake();
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $this->makeProvider('namecheap');

        $this->actingAs($admin)->post(route('dashboard.domains.store'), array_merge(
            $this->basePayload($client),
            ['registrar' => 'namecheap'],
        ));

        $domain = Domain::query()->sole();
        $this->assertSame('namecheap', $domain->registrar);
        $this->assertNull($domain->provider_id);
    }

    /* =============================== G/H ================================ */

    public function test_create_never_calls_a_registrar_api_or_creates_a_provisioning_attempt(): void
    {
        Http::fake();
        $admin = $this->makeAdmin();
        $client = $this->makeClient();

        $this->actingAs($admin)->post(route('dashboard.domains.store'), $this->basePayload($client));

        Http::assertNothingSent();
        $this->assertSame(0, DomainProvisioningAttempt::query()->count());
    }

    /* ================================ I ================================ */

    public function test_create_writes_only_the_existing_zero_value_bookkeeping_invoice_never_a_renewal_order(): void
    {
        Http::fake();
        $admin = $this->makeAdmin();
        $client = $this->makeClient();

        $this->actingAs($admin)->post(route('dashboard.domains.store'), $this->basePayload($client));

        $domain = Domain::query()->sole();

        // Pre-existing behavior (not touched/reinterpreted by this phase): a single $0
        // bookkeeping Invoice + InvoiceItem is created for the new domain record.
        $invoice = Invoice::query()->sole();
        $this->assertSame(0, $invoice->total_cents);
        $this->assertSame('unpaid', $invoice->status);
        $item = InvoiceItem::query()->sole();
        $this->assertSame('domain', $item->item_type);
        $this->assertSame($domain->id, $item->reference_id);
        $this->assertStringStartsWith('تسجيل النطاق:', $item->description);

        // No renewal financial trail is ever produced by Create.
        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, PaymentAttempt::query()->count());
    }

    /* ================================ J ================================ */

    public function test_created_external_domain_is_reachable_by_the_admin_register_screen(): void
    {
        Http::fake();
        $admin = $this->makeAdmin();
        $client = $this->makeClient();

        $this->actingAs($admin)->post(route('dashboard.domains.store'), $this->basePayload($client));
        $domain = Domain::query()->sole();

        // Light touchpoint only — proves the created record is a valid input to the existing
        // Register screen; the full Register flow itself is covered by AdminDomainRegisterTest.
        $this->actingAs($admin)->get(route('dashboard.domains.register.edit', $domain))
            ->assertOk();
    }

    /* ================================ K ================================ */

    public function test_non_admin_is_denied_access_to_create_and_store(): void
    {
        $regularUser = User::factory()->create(['super_admin' => false]);
        $client = $this->makeClient();

        $this->actingAs($regularUser)->get(route('dashboard.domains.create'))->assertForbidden();
        $this->actingAs($regularUser)->post(route('dashboard.domains.store'), $this->basePayload($client))
            ->assertForbidden();

        $this->assertSame(0, Domain::query()->count());
    }

    /* ============================ Helpers ============================ */

    private function basePayload(Client $client): array
    {
        return [
            'client_id' => $client->id,
            'domain_name' => 'create-contract-' . uniqid('', false) . '.test',
            'registrar' => 'enom',
            'registration_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'active',
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
            'last_name' => 'Create',
            'email' => uniqid('admin_create_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Admin Create Test',
            'can_login' => true,
        ]);
    }
}
