<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\DomainTld;
use App\Models\DomainTldPrice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Domains\DomainRenewalService;
use App\Services\Domains\RegistrarProvisioningService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use ReflectionClass;
use Tests\TestCase;

/**
 * TLD-3D — Hybrid Provider Identity: Renewal Provider Source-of-Truth.
 *
 * Covers the renewal-specific slice of the TLD-3D test plan not already exercised elsewhere:
 *   - D: renewal pricing selects the exact Domain.provider_id even with a cheaper valid
 *        same-type competitor (extends TLD-3B's sale-only contract with provider-exactness).
 *   - E: OrderItem.meta snapshots the exact provider_id/provider_type/provider_mode.
 *   - F/M: renewal provisioning resolves ONLY the exact snapshot provider_id and never
 *          substitutes another active, same-type provider.
 *   - G: snapshot provider inactive → fails safely, no registrar call, no fallback.
 *   - H/L: snapshot fields missing, or Domain.provider_id null → fail safely (defense in depth
 *          at the provisioning layer; the pricing-layer guarantee for L is covered in
 *          DomainRenewalSaleOnlyPricingTest::test_manual_renewal_rejects_when_domain_has_no_trusted_provider_identity).
 *   - I/J: provider_type / provider_mode snapshot mismatch → fail safely.
 *   - K: Domain.provider_id snapshot mismatch → fails safely.
 *   - N: provider state changes AFTER the invoice/OrderItem already exist → provisioning still
 *        fails safely against the original snapshot, with no substitution.
 *   - O: renewal (success or failure) never mutates Domain.provider_id.
 *   - P: renewal never silently overwrites Domain.registrar.
 *
 * Q/R (existing TLD-3B sale-only and ProviderSourceOfTruth registration regressions staying
 * PASS) are verified by running those existing test files unchanged, not duplicated here.
 *
 * Note on the "provider row no longer exists" sub-case of `renewal_provider_snapshot_missing`:
 * it is defensively coded in trustedRenewalProvider() but is not exercised by a dedicated test
 * here, because it is unreachable through any normal write path — domains.provider_id carries a
 * `restrictOnDelete()` foreign key to domain_providers, and this project's sqlite test
 * connection runs with foreign_key_constraints enabled (config/database.php), so a Domain can
 * never legitimately reference a provider_id that does not exist in domain_providers. Test H
 * below instead exercises the other, realistic way the same reason code fires: a renewal
 * OrderItem whose meta snapshot never carries provider_id/provider_type/provider_mode at all
 * (e.g. stale data predating this snapshot contract).
 */
class RenewalProviderSourceOfTruthTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    /* ================================ D ================================ */

    public function test_renewal_pricing_selects_the_exact_domain_provider_even_with_a_cheaper_same_type_competitor(): void
    {
        $primary = $this->makeProvider('namecheap');
        $competitor = $this->makeProvider('namecheap');
        $this->makePrice($this->makeTld($primary, 'test'), sale: 20.00, cost: 9.00);
        $this->makePrice($this->makeTld($competitor, 'test'), sale: 5.00, cost: 2.00);

        $client = $this->makeClient();
        $domain = $this->makeDomain($client, 'exact-provider-pricing.test', $primary);

        $checkout = app(DomainRenewalService::class)->prepareRenewalCheckout($domain);

        $this->assertTrue($checkout['created']);
        $this->assertSame(2000, $checkout['invoice']->total_cents);
        $orderItem = OrderItem::query()->sole();
        $this->assertSame($primary->id, $orderItem->meta['provider_id']);
        $this->assertNotSame($competitor->id, $orderItem->meta['provider_id']);
    }

    /* ================================ E ================================ */

    public function test_renewal_order_item_meta_snapshots_the_exact_provider_identity(): void
    {
        $provider = $this->makeProvider('enom', mode: 'live');
        $this->makePrice($this->makeTld($provider, 'test'), sale: 12.00, cost: 5.00);
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, 'meta-snapshot.test', $provider);

        app(DomainRenewalService::class)->prepareRenewalCheckout($domain);

        $meta = OrderItem::query()->sole()->meta;
        $this->assertSame($provider->id, $meta['provider_id']);
        $this->assertSame('enom', $meta['provider_type']);
        $this->assertSame('live', $meta['provider_mode']);
        $this->assertSame($domain->id, $meta['domain_id']);
    }

    /* ============================== F / M =============================== */

    public function test_renewal_provisioning_uses_the_exact_snapshot_provider_id_and_ignores_other_active_same_type_providers(): void
    {
        $provider = $this->makeProvider('namecheap');
        $otherActiveSameType = $this->makeProvider('namecheap');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, 'provisioning-exact.test', $provider);
        $order = $this->makeRenewOrder($client, $domain);
        $service = $this->fakeRenewalRegistrar();

        $result = $service->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));

        $this->assertTrue($result['ok']);
        $this->assertSame([$provider->id], array_values($service->renewalProviderCalls));
        $this->assertNotContains($otherActiveSameType->id, $service->renewalProviderCalls);
    }

    /* ================================ G ================================ */

    public function test_renewal_provisioning_rejects_when_the_snapshot_provider_is_inactive(): void
    {
        $provider = $this->makeProvider('namecheap');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, 'inactive-snapshot.test', $provider);
        $order = $this->makeRenewOrder($client, $domain);
        $provider->update(['is_active' => false]);
        $service = $this->fakeRenewalRegistrar();

        $result = $service->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));

        $this->assertFalse($result['ok']);
        $this->assertSame('renewal_provider_inactive', $result['reason']);
        $this->assertSame([], $service->renewalProviderCalls);
        $this->assertSame($provider->id, $domain->fresh()->provider_id);
    }

    /* ============================== H / L =============================== */

    public function test_renewal_provisioning_rejects_when_the_order_item_snapshot_is_missing(): void
    {
        $provider = $this->makeProvider('namecheap');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, 'missing-snapshot.test', $provider);
        // A stale/legacy renewal OrderItem whose meta never carried the provider identity
        // snapshot at all — domain_id only, no provider_id/provider_type/provider_mode.
        $order = $this->makeRenewOrder($client, $domain, [
            'provider_id' => null,
            'provider_type' => null,
            'provider_mode' => null,
        ]);
        $service = $this->fakeRenewalRegistrar();

        $result = $service->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));

        $this->assertFalse($result['ok']);
        $this->assertSame('renewal_provider_snapshot_missing', $result['reason']);
        $this->assertSame([], $service->renewalProviderCalls);
    }

    public function test_renewal_provisioning_rejects_when_the_domain_has_no_trusted_provider_identity(): void
    {
        $provider = $this->makeProvider('namecheap');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, 'provisioning-no-provider.test', $provider);
        $order = $this->makeRenewOrder($client, $domain);
        // The domain loses its trusted provider identity AFTER the OrderItem snapshot was taken
        // (defense in depth — pricing already rejects this before any OrderItem/Invoice exists;
        // see DomainRenewalSaleOnlyPricingTest::test_manual_renewal_rejects_when_domain_has_no_trusted_provider_identity).
        $domain->forceFill(['provider_id' => null])->save();
        $service = $this->fakeRenewalRegistrar();

        $result = $service->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));

        $this->assertFalse($result['ok']);
        $this->assertSame('domain_provider_missing', $result['reason']);
        $this->assertSame([], $service->renewalProviderCalls);
    }

    /* ================================ I ================================ */

    public function test_renewal_provisioning_rejects_a_provider_type_snapshot_mismatch(): void
    {
        $provider = $this->makeProvider('namecheap');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, 'type-mismatch.test', $provider);
        $order = $this->makeRenewOrder($client, $domain, [
            'provider_type' => 'enom',
        ]);
        $service = $this->fakeRenewalRegistrar();

        $result = $service->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));

        $this->assertFalse($result['ok']);
        $this->assertSame('renewal_provider_type_mismatch', $result['reason']);
        $this->assertSame([], $service->renewalProviderCalls);
    }

    /* ================================ J ================================ */

    public function test_renewal_provisioning_rejects_a_provider_mode_snapshot_mismatch(): void
    {
        $provider = $this->makeProvider('namecheap', mode: 'test');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, 'mode-mismatch.test', $provider);
        $order = $this->makeRenewOrder($client, $domain, [
            'provider_mode' => 'live',
        ]);
        $service = $this->fakeRenewalRegistrar();

        $result = $service->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));

        $this->assertFalse($result['ok']);
        $this->assertSame('renewal_provider_mode_mismatch', $result['reason']);
        $this->assertSame([], $service->renewalProviderCalls);
    }

    /* ================================ K ================================ */

    public function test_renewal_provisioning_rejects_when_the_snapshot_provider_does_not_match_the_domain(): void
    {
        $ownProvider = $this->makeProvider('namecheap');
        $otherProvider = $this->makeProvider('namecheap');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, 'domain-mismatch.test', $ownProvider);
        $order = $this->makeRenewOrder($client, $domain, [
            'provider_id' => $otherProvider->id,
            'provider_type' => $otherProvider->type,
            'provider_mode' => $otherProvider->mode,
        ]);
        $service = $this->fakeRenewalRegistrar();

        $result = $service->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));

        $this->assertFalse($result['ok']);
        $this->assertSame('renewal_provider_domain_mismatch', $result['reason']);
        $this->assertSame([], $service->renewalProviderCalls);
    }

    /* ================================ N ================================ */

    public function test_renewal_provisioning_fails_safely_when_the_provider_becomes_inactive_after_the_invoice_already_exists(): void
    {
        $provider = $this->makeProvider('namecheap');
        $this->makePrice($this->makeTld($provider, 'test'), sale: 18.00, cost: 9.00);
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, 'state-change-after-invoice.test', $provider);

        $checkout = app(DomainRenewalService::class)->prepareRenewalCheckout($domain);
        $this->assertTrue($checkout['created']);
        $invoice = $checkout['invoice'];
        $order = $invoice->order;

        // Provider state changes AFTER the invoice/OrderItem snapshot already exists.
        $provider->update(['is_active' => false]);

        $service = $this->fakeRenewalRegistrar();
        $result = $service->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));

        $this->assertFalse($result['ok']);
        $this->assertSame('renewal_provider_inactive', $result['reason']);
        $this->assertSame([], $service->renewalProviderCalls);
        $this->assertSame('unpaid', $invoice->fresh()->status);
        $this->assertSame($provider->id, $domain->fresh()->provider_id);
        $this->assertSame('namecheap', $domain->fresh()->registrar);
    }

    /* ================================ O ================================ */

    public function test_successful_renewal_never_mutates_domain_provider_id(): void
    {
        $provider = $this->makeProvider('namecheap');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, 'no-mutation-success.test', $provider);
        $order = $this->makeRenewOrder($client, $domain);
        $service = $this->fakeRenewalRegistrar();

        $result = $service->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));

        $this->assertTrue($result['ok']);
        $this->assertSame($provider->id, $domain->fresh()->provider_id);
    }

    public function test_failed_renewal_never_mutates_domain_provider_id(): void
    {
        $provider = $this->makeProvider('namecheap');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, 'no-mutation-failure.test', $provider);
        $order = $this->makeRenewOrder($client, $domain);
        $service = $this->fakeRenewalRegistrarDecline();

        $result = $service->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));

        $this->assertFalse($result['ok']);
        $this->assertSame($provider->id, $domain->fresh()->provider_id);
    }

    /* ================================ P ================================ */

    public function test_renewal_never_overwrites_domain_registrar(): void
    {
        $provider = $this->makeProvider('namecheap');
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, 'registrar-untouched.test', $provider);
        $order = $this->makeRenewOrder($client, $domain);
        $service = $this->fakeRenewalRegistrar();

        $result = $service->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));

        $this->assertTrue($result['ok']);
        $this->assertSame('namecheap', $domain->fresh()->registrar);

        // Direct source proof: renewOrderDomain() must never write the 'registrar' key on
        // Domain at all — the strongest guarantee against silently "correcting" it to a
        // different provider type on either the success or the failure path.
        $source = file_get_contents((new ReflectionClass(RegistrarProvisioningService::class))->getFileName());
        $this->assertMatchesRegularExpression('/protected function renewOrderDomain\(/', $source);
        $matched = preg_match(
            '/protected function renewOrderDomain\(.*?\n    \}\n/s',
            $source,
            $m
        );
        $this->assertSame(1, $matched, 'Could not isolate renewOrderDomain() source for the registrar-write assertion.');
        $this->assertStringNotContainsString("'registrar' =>", $m[0]);
    }

    /* ================================ Helpers ================================ */

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
            'first_name' => 'Renewal',
            'last_name' => 'ProviderTruth',
            'email' => uniqid('renewal_provider_truth_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Renewal Provider Truth Test',
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
            'payment_method' => 'lahza',
        ]);
    }

    /**
     * Builds a pending "renew" Order/OrderItem for $domain. By default the meta snapshot exactly
     * mirrors $domain's own current provider (id/type/mode) — the normal, non-drifted case.
     * Pass $metaOverrides to construct a specific drift/mismatch/missing-snapshot scenario.
     */
    private function makeRenewOrder(Client $client, Domain $domain, array $metaOverrides = []): Order
    {
        $order = Order::query()->create([
            'client_id' => $client->id,
            'status' => Order::STATUS_PENDING,
            'type' => 'domain_renewal',
        ]);

        $provider = $domain->provider_id
            ? DomainProvider::query()->find($domain->provider_id)
            : null;

        $defaultMeta = [
            'domain_id' => $domain->id,
            'current_renewal_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'term_years' => 1,
        ];

        if ($provider instanceof DomainProvider) {
            $defaultMeta['provider_id'] = $provider->id;
            $defaultMeta['provider_type'] = $provider->type;
            $defaultMeta['provider_mode'] = $provider->mode;
        }

        $order->items()->create([
            'domain' => $domain->domain_name,
            'item_option' => 'renew',
            'price_cents' => 2000,
            'meta' => array_merge($defaultMeta, $metaOverrides),
            'provisioning_status' => OrderItem::PROVISIONING_NOT_STARTED,
        ]);

        return $order;
    }

    private function fakeRenewalRegistrar(): RegistrarProvisioningService
    {
        return new class extends RegistrarProvisioningService
        {
            public array $renewalProviderCalls = [];

            protected function renewDomainWithProvider(DomainProvider $provider, Domain $domain, array $context): array
            {
                $this->renewalProviderCalls[] = $provider->id;

                return [
                    'ok' => true,
                    'cid' => 'renewal-source-' . $provider->id,
                ];
            }
        };
    }

    private function fakeRenewalRegistrarDecline(): RegistrarProvisioningService
    {
        return new class extends RegistrarProvisioningService
        {
            public array $renewalProviderCalls = [];

            protected function renewDomainWithProvider(DomainProvider $provider, Domain $domain, array $context): array
            {
                $this->renewalProviderCalls[] = $provider->id;

                return [
                    'ok' => false,
                    'message' => 'Registrar declined the renewal.',
                ];
            }
        };
    }
}
