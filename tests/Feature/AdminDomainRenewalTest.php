<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\DomainTld;
use App\Models\DomainTldPrice;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAttempt;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

/**
 * TLD-3E.3A — Replace Admin Renew Placeholder with Trusted Renewal Invoice Flow.
 *
 * Locks in that DomainController::updateRenew() no longer mutates Domain.renewal_date /
 * Domain.status / Domain.payment_method directly, and instead invokes the SAME trusted,
 * exact-provider-identity, sale-only pipeline the client renewal flow already uses
 * (DomainRenewalService::prepareRenewalCheckout()) — inheriting its Order/OrderItem/
 * Invoice/InvoiceItem creation, its pending-invoice reuse/idempotency, and its complete
 * rejection of untrusted provider identity or missing/invalid pricing before any financial
 * write. On success, Admin is redirected to the existing dashboard.invoices.show route for
 * the resulting/reused Invoice — never to the client checkout route, and no registrar API
 * call happens merely from creating/reusing the invoice (that only happens later, through
 * OrderActivationService -> RegistrarProvisioningService, after payment settlement, which
 * this phase does not touch).
 *
 * These tests hit the real DomainRenewalService (no fake/override) since the whole point of
 * this phase is that Admin inherits its actual, already-tested contract rather than a
 * duplicated one. Pricing-edge-case exhaustiveness (currency validation, hardcoded-fallback
 * removal, etc.) is already covered by DomainRenewalSaleOnlyPricingTest and is not repeated
 * here beyond what's needed to prove the Admin route wiring is correct.
 */
class AdminDomainRenewalTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    /* ================================== A / C / D ==================================== */

    public function test_successful_admin_renewal_creates_the_trusted_renewal_order_and_invoice(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('namecheap');
        $tld = $this->makeTld($provider, 'test');
        $this->makePrice($tld, action: 'renew', sale: 15.75, cost: 9.00);
        $this->makePrice($tld, action: 'register', sale: 40.00, cost: 20.00);
        $domain = $this->makeDomain($this->makeClient(), 'admin-renew.test', $provider);

        $response = $this->actingAs($admin)->put(route('dashboard.domains.renew.update', $domain));

        $response->assertRedirect();
        $this->assertSame(1, Order::query()->count());
        $this->assertSame(1, Invoice::query()->count());

        $invoice = Invoice::query()->sole();
        // D — trusted renew.sale used, never register.sale (2000) or cost (900).
        $this->assertSame(1575, $invoice->total_cents);
        $this->assertSame(1575, $invoice->subtotal_cents);
        $this->assertSame('USD', $invoice->currency);

        // C — OrderItem is a renewal snapshot carrying the exact trusted provider identity.
        $orderItem = OrderItem::query()->sole();
        $this->assertSame('renew', $orderItem->item_option);
        $this->assertSame($domain->domain_name, $orderItem->domain);
        $this->assertSame(1575, $orderItem->price_cents);
        $this->assertSame($provider->id, $orderItem->meta['provider_id']);
        $this->assertSame('namecheap', $orderItem->meta['provider_type']);
        $this->assertSame($domain->id, $orderItem->meta['domain_id']);
    }

    /* ==================================== B ===================================== */

    public function test_successful_admin_renewal_does_not_directly_mutate_the_domain_record(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('namecheap');
        $tld = $this->makeTld($provider, 'test');
        $this->makePrice($tld, action: 'renew', sale: 12.00, cost: 5.00);
        $domain = $this->makeDomain($this->makeClient(), 'no-domain-mutation.test', $provider);
        $originalRenewalDate = $domain->renewal_date;
        $originalStatus = $domain->status;
        $originalPaymentMethod = $domain->payment_method;

        $this->actingAs($admin)->put(route('dashboard.domains.renew.update', $domain));

        $fresh = $domain->fresh();
        $this->assertSame((string) $originalRenewalDate, (string) $fresh->renewal_date);
        $this->assertSame($originalStatus, $fresh->status);
        $this->assertSame($originalPaymentMethod, $fresh->payment_method);
    }

    /* ==================================== E ===================================== */

    public function test_missing_renew_sale_rejects_with_zero_new_financial_records(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('namecheap');
        $tld = $this->makeTld($provider, 'test');
        $this->makePrice($tld, action: 'renew', sale: null, cost: 9.00);
        $domain = $this->makeDomain($this->makeClient(), 'missing-sale.test', $provider);
        $originalStatus = $domain->status;

        $response = $this->actingAs($admin)->put(route('dashboard.domains.renew.update', $domain));

        $response->assertRedirect();
        $response->assertSessionHasErrors('renewal');
        $this->assertNoFinancialWrites();
        $this->assertSame($originalStatus, $domain->fresh()->status);
    }

    /* ==================================== F ===================================== */

    public function test_provider_id_null_rejects_with_zero_financial_writes_and_zero_domain_mutation(): void
    {
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $domain = Domain::query()->create([
            'client_id' => $client->id,
            'domain_name' => 'admin-unmanaged.test',
            'registrar' => 'namecheap',
            'provider_id' => null,
            'registration_date' => now()->subYear()->toDateString(),
            'renewal_date' => now()->addDays(10)->toDateString(),
            'auto_renew' => false,
            'status' => 'active',
            'payment_method' => null,
        ]);

        $response = $this->actingAs($admin)->put(route('dashboard.domains.renew.update', $domain));

        $response->assertRedirect();
        $response->assertSessionHasErrors(['renewal' => 'لا يمكن تجديد هذا النطاق عبر المنصة لأنه غير مرتبط بمزوّد مُدار.']);
        $this->assertNoFinancialWrites();

        $fresh = $domain->fresh();
        $this->assertSame('active', $fresh->status);
        $this->assertNull($fresh->payment_method);
    }

    /* ==================================== G ===================================== */

    public function test_inactive_trusted_provider_rejects_safely(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('namecheap', active: false);
        $tld = $this->makeTld($provider, 'test');
        $this->makePrice($tld, action: 'renew', sale: 15.00, cost: 9.00);
        $domain = $this->makeDomain($this->makeClient(), 'inactive-provider.test', $provider);

        $response = $this->actingAs($admin)->put(route('dashboard.domains.renew.update', $domain));

        $response->assertRedirect();
        $response->assertSessionHasErrors('renewal');
        $this->assertNoFinancialWrites();
    }

    /* ==================================== H ===================================== */

    public function test_same_type_competing_provider_is_never_substituted_for_the_domains_trusted_provider(): void
    {
        $admin = $this->makeAdmin();
        $trusted = $this->makeProvider('namecheap');
        $competitor = $this->makeProvider('namecheap');
        $this->makePrice($this->makeTld($trusted, 'test'), action: 'renew', sale: 18.00, cost: 9.00);
        $this->makePrice($this->makeTld($competitor, 'test'), action: 'renew', sale: 99.00, cost: 50.00);
        $domain = $this->makeDomain($this->makeClient(), 'same-type-competitor.test', $trusted);

        $response = $this->actingAs($admin)->put(route('dashboard.domains.renew.update', $domain));

        $response->assertRedirect();
        $invoice = Invoice::query()->sole();
        // 1800, never 9900 — the competitor's price/identity is never substituted.
        $this->assertSame(1800, $invoice->total_cents);
        $this->assertSame($trusted->id, OrderItem::query()->sole()->meta['provider_id']);
    }

    /* ==================================== I ===================================== */

    public function test_successful_admin_renewal_redirects_to_the_admin_invoice_show_route(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('namecheap');
        $tld = $this->makeTld($provider, 'test');
        $this->makePrice($tld, action: 'renew', sale: 10.00, cost: 5.00);
        $domain = $this->makeDomain($this->makeClient(), 'redirect-check.test', $provider);

        $response = $this->actingAs($admin)->put(route('dashboard.domains.renew.update', $domain));

        $invoice = Invoice::query()->sole();
        $response->assertRedirect(route('dashboard.invoices.show', $invoice));
    }

    /* ==================================== J ===================================== */

    public function test_repeated_admin_renewal_reuses_the_existing_pending_renewal_invoice(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('namecheap');
        $tld = $this->makeTld($provider, 'test');
        $this->makePrice($tld, action: 'renew', sale: 10.00, cost: 5.00);
        $domain = $this->makeDomain($this->makeClient(), 'idempotent-renew.test', $provider);

        $first = $this->actingAs($admin)->put(route('dashboard.domains.renew.update', $domain));
        $first->assertRedirect();
        $this->assertSame(1, Order::query()->count());
        $this->assertSame(1, Invoice::query()->count());
        $firstInvoiceId = Invoice::query()->sole()->id;

        $second = $this->actingAs($admin)->put(route('dashboard.domains.renew.update', $domain));
        $second->assertRedirect();

        // No duplicate Order/Invoice — the pending one is reused.
        $this->assertSame(1, Order::query()->count());
        $this->assertSame(1, Invoice::query()->count());
        $this->assertSame($firstInvoiceId, Invoice::query()->sole()->id);
        $second->assertRedirect(route('dashboard.invoices.show', Invoice::query()->sole()));
    }

    /* ==================================== K ===================================== */

    public function test_creating_or_reusing_the_renewal_invoice_never_triggers_registrar_provisioning(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('namecheap');
        $tld = $this->makeTld($provider, 'test');
        $this->makePrice($tld, action: 'renew', sale: 10.00, cost: 5.00);
        $domain = $this->makeDomain($this->makeClient(), 'no-provisioning-yet.test', $provider);

        $this->actingAs($admin)->put(route('dashboard.domains.renew.update', $domain));

        // Only OrderActivationService (triggered by payment settlement) would ever call
        // RegistrarProvisioningService — this request never reaches that far. The order stays
        // pending and the order item carries no provisioning attempt.
        $order = Order::query()->sole();
        $this->assertSame(Order::STATUS_PENDING, $order->status);
        $orderItem = OrderItem::query()->sole();
        $this->assertNull($orderItem->provisioning_started_at);
        $this->assertNull($orderItem->provisioning_completed_at);
    }

    /* ==================================== L ===================================== */

    public function test_forged_legacy_renewal_fields_in_the_request_are_ignored(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('namecheap');
        $tld = $this->makeTld($provider, 'test');
        $this->makePrice($tld, action: 'renew', sale: 10.00, cost: 5.00);
        $domain = $this->makeDomain($this->makeClient(), 'forged-fields.test', $provider);
        $originalRenewalDate = (string) $domain->renewal_date;
        $originalStatus = $domain->status;
        $originalPaymentMethod = $domain->payment_method;

        $response = $this->actingAs($admin)->put(route('dashboard.domains.renew.update', $domain), [
            'renewal_date' => now()->addYears(5)->toDateString(),
            'status' => 'pending',
            'payment_method' => 'forged-cash',
            'notes' => 'this should never be persisted',
        ]);

        $response->assertRedirect();

        $fresh = $domain->fresh();
        $this->assertSame($originalRenewalDate, (string) $fresh->renewal_date);
        $this->assertSame($originalStatus, $fresh->status);
        $this->assertSame($originalPaymentMethod, $fresh->payment_method);
        $this->assertNotSame('forged-cash', $fresh->payment_method);
    }

    /* ================================ View contract ================================ */

    public function test_renew_view_no_longer_exposes_editable_domain_record_fields(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeProvider('namecheap');
        $domain = $this->makeDomain($this->makeClient(), 'view-contract.test', $provider);

        $response = $this->actingAs($admin)->get(route('dashboard.domains.renew.edit', $domain));

        $response->assertOk();
        foreach (['renewal_date', 'status', 'payment_method', 'notes'] as $field) {
            $response->assertDontSee('name="' . $field . '"', false);
        }
        // The submit action is still the same Admin renew update route.
        $response->assertSee(route('dashboard.domains.renew.update', $domain), false);
    }

    /* ================================ Helpers ================================ */

    private function assertNoFinancialWrites(): void
    {
        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, OrderItem::query()->count());
        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(0, InvoiceItem::query()->count());
        $this->assertSame(0, PaymentAttempt::query()->count());
    }

    private function makeAdmin(): User
    {
        return User::factory()->create(['super_admin' => true]);
    }

    private function makeProvider(string $type, bool $active = true): DomainProvider
    {
        return DomainProvider::query()->create([
            'name' => strtoupper($type) . ' ' . uniqid(),
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

    private function makeTld(DomainProvider $provider, string $tld, string $currency = 'USD'): DomainTld
    {
        return DomainTld::query()->create([
            'provider_id' => $provider->id,
            'provider' => $provider->type,
            'tld' => $tld,
            'currency' => $currency,
            'enabled' => true,
        ]);
    }

    private function makePrice(DomainTld $tld, ?float $sale, ?float $cost, string $action = 'renew', int $years = 1): DomainTldPrice
    {
        return $tld->prices()->create([
            'action' => $action,
            'years' => $years,
            'sale' => $sale,
            'cost' => $cost,
        ]);
    }

    private function makeClient(): Client
    {
        return Client::query()->create([
            'first_name' => 'Admin',
            'last_name' => 'Renewal',
            'email' => uniqid('admin_renewal_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Admin Renewal Test',
            'can_login' => true,
        ]);
    }

    private function makeDomain(Client $client, string $domainName, DomainProvider $provider): Domain
    {
        return Domain::query()->create([
            'client_id' => $client->id,
            'domain_name' => $domainName,
            'registrar' => $provider->type,
            'provider_id' => $provider->id,
            'registration_date' => now()->subYear()->toDateString(),
            'renewal_date' => now()->addDays(10)->toDateString(),
            'auto_renew' => false,
            'status' => 'active',
            'payment_method' => null,
        ]);
    }
}
