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
use App\Services\Domains\DomainRenewalService;
use App\Services\Domains\Exceptions\MissingRenewalPriceException;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use ReflectionClass;
use Tests\TestCase;

/**
 * TLD-3B — Strict Sale-Only Renewal Pricing.
 *
 * Locks in the new renewal-pricing contract: DomainRenewalService::buildRenewalQuote() accepts
 * ONLY an explicit, numeric, `> 0` `renew.sale` row, for the domain's own TLD and its own active
 * provider (matching $domain->registrar). Every fallback tier identified by the TLD-3B audit gate
 * — renew.cost, register.sale, register.cost, and the hard-coded `10.0 * years` — is removed with
 * no replacement. When no trusted price exists, the whole renewal operation must fail safely
 * *before* any financial write: no Order, no OrderItem, no Invoice, no InvoiceItem, no
 * PaymentAttempt. `cost` never surfaces as a customer-facing renewal price.
 *
 * Auto-renew's own failure-path behavior (failed++, no provider call, continues to next domain)
 * and the untouched happy-path / pending-invoice-reuse / settlement regressions are covered in
 * DomainAutoRenewalTest — not duplicated here.
 */
class DomainRenewalSaleOnlyPricingTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    /* ================================ A ================================ */

    public function test_manual_renewal_uses_the_explicit_renew_sale_price(): void
    {
        $provider = $this->makeProvider('namecheap');
        $tld = $this->makeTld($provider, 'test');
        $this->makePrice($tld, action: 'renew', sale: 15.75, cost: 9.00);
        $domain = $this->makeDomain($this->makeClient(), 'renews-fine.test', 'namecheap');

        $checkout = app(DomainRenewalService::class)->prepareRenewalCheckout($domain);

        $this->assertTrue($checkout['created']);
        $invoice = $checkout['invoice'];
        $this->assertSame(1575, $invoice->total_cents);
        $this->assertSame(1575, $invoice->subtotal_cents);
        $this->assertSame('USD', $invoice->currency);
        $this->assertSame(1, Order::query()->count());
        $this->assertSame(1575, OrderItem::query()->sole()->price_cents);
        $this->assertSame(1575, InvoiceItem::query()->sole()->unit_price_cents);
    }

    /* ================================ B ================================ */

    public function test_manual_renewal_rejects_null_renew_sale_even_with_a_renew_cost(): void
    {
        $provider = $this->makeProvider('namecheap');
        $tld = $this->makeTld($provider, 'test');
        $this->makePrice($tld, action: 'renew', sale: null, cost: 11.00);
        $domain = $this->makeDomain($this->makeClient(), 'no-sale.test', 'namecheap');

        // If renew.cost were still used as a fallback the checkout would succeed with
        // total_cents = 1100; asserting rejection + zero writes proves cost was never touched.
        $this->assertRenewalRejectedWithNoFinancialWrites($domain);
    }

    /* ================================ C ================================ */

    public function test_manual_renewal_rejects_a_zero_renew_sale(): void
    {
        $provider = $this->makeProvider('namecheap');
        $tld = $this->makeTld($provider, 'test');
        $this->makePrice($tld, action: 'renew', sale: 0, cost: 11.00);
        $domain = $this->makeDomain($this->makeClient(), 'zero-sale.test', 'namecheap');

        $this->assertRenewalRejectedWithNoFinancialWrites($domain);
    }

    /* ================================ D ================================ */

    public function test_manual_renewal_rejects_and_never_falls_back_to_register_sale(): void
    {
        $provider = $this->makeProvider('namecheap');
        $tld = $this->makeTld($provider, 'test');
        $this->makePrice($tld, action: 'renew', sale: null, cost: null);
        $this->makePrice($tld, action: 'register', sale: 20.00, cost: 12.00);
        $domain = $this->makeDomain($this->makeClient(), 'renew-row-empty.test', 'namecheap');

        // If register.sale were still used as a fallback the checkout would succeed with
        // total_cents = 2000; asserting rejection + zero writes proves it was never touched.
        $this->assertRenewalRejectedWithNoFinancialWrites($domain);
    }

    /* ================================ E ================================ */

    public function test_manual_renewal_rejects_with_no_hardcoded_fallback_when_everything_is_null(): void
    {
        $provider = $this->makeProvider('namecheap');
        $tld = $this->makeTld($provider, 'test');
        $this->makePrice($tld, action: 'renew', sale: null, cost: null);
        $this->makePrice($tld, action: 'register', sale: null, cost: null);
        $domain = $this->makeDomain($this->makeClient(), 'nothing-priced.test', 'namecheap');

        // If the old `10.0 * years` fallback were still present, this would succeed with
        // total_cents = 1000 instead of throwing.
        $this->assertRenewalRejectedWithNoFinancialWrites($domain);
    }

    /* ================================ F ================================ */

    public function test_manual_renewal_rejects_an_invalid_currency_before_any_financial_write(): void
    {
        $provider = $this->makeProvider('namecheap');
        $tld = $this->makeTld($provider, 'test', currency: '12');
        $this->makePrice($tld, action: 'renew', sale: 15.00, cost: 9.00);
        $domain = $this->makeDomain($this->makeClient(), 'bad-currency.test', 'namecheap');

        $this->assertRenewalRejectedWithNoFinancialWrites($domain);
    }

    /* ================================ G ================================ */

    public function test_manual_renewal_rejects_when_the_matching_provider_is_inactive(): void
    {
        $provider = $this->makeProvider('namecheap', active: false);
        $tld = $this->makeTld($provider, 'test');
        $this->makePrice($tld, action: 'renew', sale: 15.00, cost: 9.00);
        $domain = $this->makeDomain($this->makeClient(), 'inactive-provider.test', 'namecheap');

        $this->assertRenewalRejectedWithNoFinancialWrites($domain);
    }

    public function test_manual_renewal_does_not_fall_back_to_a_different_active_provider(): void
    {
        // Domain is held at namecheap, but only enom has a valid renew.sale for this TLD.
        // Pricing must not silently borrow another provider's price.
        $enom = $this->makeProvider('enom');
        $this->makePrice($this->makeTld($enom, 'test'), action: 'renew', sale: 15.00, cost: 9.00);
        $domain = $this->makeDomain($this->makeClient(), 'wrong-provider.test', 'namecheap');

        $this->assertRenewalRejectedWithNoFinancialWrites($domain);
    }

    /* ================================ H ================================ */

    public function test_manual_renewal_creates_no_financial_records_when_price_is_missing(): void
    {
        $provider = $this->makeProvider('namecheap');
        $tld = $this->makeTld($provider, 'test');
        $this->makePrice($tld, action: 'renew', sale: null, cost: null);
        $domain = $this->makeDomain($this->makeClient(), 'no-records.test', 'namecheap');

        $this->assertRenewalRejectedWithNoFinancialWrites($domain);
    }

    public function test_renewal_controller_redirects_with_a_clear_error_and_no_financial_records_when_price_is_missing(): void
    {
        $provider = $this->makeProvider('namecheap');
        $tld = $this->makeTld($provider, 'test');
        $this->makePrice($tld, action: 'renew', sale: null, cost: null);
        $client = $this->makeClient();
        $domain = $this->makeDomain($client, 'controller-no-price.test', 'namecheap');

        $response = $this->actingAs($client, 'client')->post(route('client.domains.renew', $domain));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, OrderItem::query()->count());
        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(0, InvoiceItem::query()->count());
        $this->assertSame(0, PaymentAttempt::query()->count());
    }

    /* ================================ M ================================ */

    public function test_hardcoded_ten_dollar_fallback_is_removed_from_the_renewal_pricing_path(): void
    {
        $this->assertFalse(
            method_exists(DomainRenewalService::class, 'fallbackRenewalPrice'),
            'fallbackRenewalPrice() must be removed once it becomes dead code under the strict sale-only contract.'
        );

        $source = file_get_contents((new ReflectionClass(DomainRenewalService::class))->getFileName());

        $this->assertStringNotContainsString('10.0 *', $source);
        $this->assertStringNotContainsString("'action', 'register'", $source);
    }

    /* ================================ Helpers ================================ */

    private function assertRenewalRejectedWithNoFinancialWrites(Domain $domain): MissingRenewalPriceException
    {
        $thrown = null;

        try {
            app(DomainRenewalService::class)->prepareRenewalCheckout($domain);
        } catch (MissingRenewalPriceException $e) {
            $thrown = $e;
        }

        $this->assertInstanceOf(
            MissingRenewalPriceException::class,
            $thrown,
            'Expected prepareRenewalCheckout() to throw MissingRenewalPriceException.'
        );
        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, OrderItem::query()->count());
        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(0, InvoiceItem::query()->count());
        $this->assertSame(0, PaymentAttempt::query()->count());

        return $thrown;
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
            'first_name' => 'Renewal',
            'last_name' => 'SaleOnly',
            'email' => uniqid('renewal_sale_only_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Renewal Sale Only Test',
            'can_login' => true,
        ]);
    }

    private function makeDomain(Client $client, string $domainName, string $registrar): Domain
    {
        return Domain::query()->create([
            'client_id' => $client->id,
            'domain_name' => $domainName,
            'registrar' => $registrar,
            'registration_date' => now()->subYear()->toDateString(),
            'renewal_date' => now()->addDays(10)->toDateString(),
            'auto_renew' => false,
            'status' => 'active',
            'payment_method' => 'lahza',
        ]);
    }
}
