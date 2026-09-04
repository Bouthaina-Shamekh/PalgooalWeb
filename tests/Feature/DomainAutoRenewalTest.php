<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\DomainTld;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAttempt;
use App\Services\Billing\InvoiceSettlementService;
use App\Services\Billing\OrderActivationService;
use App\Services\Domains\DomainRenewalService;
use App\Services\Domains\RegistrarProvisioningService;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class DomainAutoRenewalTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-05 10:00:00');
        Http::fake();
        $this->createRenewalCatalog();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_due_auto_renew_creates_only_one_unpaid_invoice_and_never_settles_or_calls_provider(): void
    {
        $domain = $this->makeDomain([
            'payment_method' => 'mock_gateway',
        ]);
        $originalRenewalDate = $domain->renewal_date;

        $settlement = Mockery::mock(InvoiceSettlementService::class);
        $settlement->shouldNotReceive('markPaid');
        $this->app->instance(InvoiceSettlementService::class, $settlement);

        $summary = app(DomainRenewalService::class)->processDueAutoRenewals();

        $order = Order::query()->sole();
        $orderItem = OrderItem::query()->sole();
        $invoice = Invoice::query()->sole();
        $invoiceItem = InvoiceItem::query()->sole();

        $this->assertSame(Order::STATUS_PENDING, $order->status);
        $this->assertSame('renew', $orderItem->item_option);
        $this->assertSame(OrderItem::PROVISIONING_NOT_STARTED, $orderItem->provisioning_status);
        $this->assertSame('unpaid', $invoice->status);
        $this->assertSame($domain->id, $invoiceItem->reference_id);
        $this->assertSame('domain', $invoiceItem->item_type);
        $this->assertSame(0, PaymentAttempt::query()->count());
        $this->assertSame($originalRenewalDate, $domain->fresh()->renewal_date);
        $this->assertStringContainsString($invoice->number, (string) $domain->fresh()->dns_last_note);
        $this->assertSame(1, $summary['created']);
        $this->assertSame(1, $summary['awaiting_payment']);
        $this->assertSame(0, $summary['renewed']);
        Http::assertNothingSent();
    }

    public function test_pending_renewal_invoice_is_reused_across_repeated_runs(): void
    {
        $domain = $this->makeDomain();
        $service = app(DomainRenewalService::class);

        $first = $service->processDueAutoRenewals();
        $second = $service->processDueAutoRenewals();

        $this->assertSame(1, Order::query()->count());
        $this->assertSame(1, OrderItem::query()->count());
        $this->assertSame(1, Invoice::query()->count());
        $this->assertSame(1, InvoiceItem::query()->count());
        $this->assertSame(1, $first['created']);
        $this->assertSame(1, $second['existing']);
        $this->assertSame('unpaid', Invoice::query()->sole()->status);
        $this->assertSame($domain->renewal_date, $domain->fresh()->renewal_date);
        Http::assertNothingSent();
    }

    public function test_dry_run_reports_the_plan_without_any_database_writes_or_provider_call(): void
    {
        $domain = $this->makeDomain([
            'dns_last_note' => 'unchanged',
            'payment_method' => 'mock_gateway',
        ]);
        $domainUpdatedAt = $domain->updated_at;
        $translationCount = \App\Models\TranslationValue::query()->count();
        $writeQueries = [];
        DB::listen(function ($query) use (&$writeQueries): void {
            if (preg_match('/^\s*(insert|update|delete|replace|alter|create|drop|truncate)\b/i', $query->sql)) {
                $writeQueries[] = $query->sql;
            }
        });

        $summary = app(DomainRenewalService::class)->processDueAutoRenewals(true);

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, OrderItem::query()->count());
        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(0, InvoiceItem::query()->count());
        $this->assertSame(0, PaymentAttempt::query()->count());
        $this->assertSame($translationCount, \App\Models\TranslationValue::query()->count());
        $this->assertSame([], $writeQueries);
        $this->assertSame('unchanged', $domain->fresh()->dns_last_note);
        $this->assertTrue($domainUpdatedAt->equalTo($domain->fresh()->updated_at));
        $this->assertSame('create_unpaid_invoice', $summary['details'][0]['action']);
        $this->assertFalse($summary['details'][0]['pending_invoice']);
        $this->assertSame($domain->domain_name, $summary['details'][0]['domain']);
        $this->assertSame(1250, $summary['details'][0]['estimated_price_cents']);
        Http::assertNothingSent();
    }

    public function test_disabled_or_not_due_domains_create_no_renewal_records(): void
    {
        $this->makeDomain([
            'domain_name' => 'disabled-auto-renew.test',
            'auto_renew' => false,
        ]);
        $this->makeDomain([
            'domain_name' => 'not-due-auto-renew.test',
            'renewal_date' => now()->addMonths(2)->toDateString(),
        ]);

        $summary = app(DomainRenewalService::class)->processDueAutoRenewals();

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(0, $summary['due']);
        Http::assertNothingSent();
    }

    public function test_auto_renew_eligibility_is_rechecked_after_the_domain_lock(): void
    {
        $this->makeDomain();

        $service = new class extends DomainRenewalService
        {
            public int $eligibilityChecks = 0;

            public function isDueForAutoRenewal(Domain $domain, ?Carbon $today = null): bool
            {
                $this->eligibilityChecks++;

                return $this->eligibilityChecks === 1;
            }
        };

        $service->processDueAutoRenewals();

        $this->assertSame(2, $service->eligibilityChecks);
        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, Invoice::query()->count());
        Http::assertNothingSent();
    }

    /**
     * TLD-3B — Strict Sale-Only Renewal Pricing (item I of the test plan).
     *
     * A domain whose TLD has no trusted renew.sale must be counted as failed and must never
     * produce an Invoice, never call the registrar, and must not stop processing of the next
     * due domain in the batch.
     */
    public function test_auto_renew_with_missing_trusted_sale_is_marked_failed_and_does_not_block_other_domains(): void
    {
        $provider = DomainProvider::query()->where('type', 'namecheap')->first();

        $tld = DomainTld::query()->create([
            'provider_id' => $provider->id,
            'provider' => 'namecheap',
            'tld' => 'nosale',
            'currency' => 'USD',
            'enabled' => true,
        ]);

        $tld->prices()->create([
            'action' => 'renew',
            'years' => 1,
            'cost' => 10,
            'sale' => null,
        ]);

        $failingDomain = $this->makeDomain([
            'domain_name' => 'auto-renew-nosale.nosale',
        ]);
        $healthyDomain = $this->makeDomain([
            'domain_name' => 'auto-renew-healthy.test',
        ]);

        $summary = app(DomainRenewalService::class)->processDueAutoRenewals();

        $this->assertSame(1, $summary['failed']);
        $this->assertSame(1, $summary['created']);
        $this->assertSame(0, Invoice::query()->where('client_id', $failingDomain->client_id)->count());
        $this->assertSame(1, Invoice::query()->where('client_id', $healthyDomain->client_id)->count());
        $this->assertStringContainsString('Auto-renew skipped', (string) $failingDomain->fresh()->dns_last_note);
        Http::assertNothingSent();
    }

    public function test_DomainRenewal_verified_payment_settlement_can_still_activate_the_order(): void
    {
        $domain = $this->makeDomain();
        $checkout = app(DomainRenewalService::class)->prepareRenewalCheckout($domain, 1, true);
        $invoice = $checkout['invoice'];

        $attempt = PaymentAttempt::query()->create([
            'invoice_id' => $invoice->id,
            'order_id' => $invoice->order_id,
            'client_id' => $invoice->client_id,
            'gateway' => 'lahza',
            'idempotency_key' => 'renewal-payment-' . $invoice->id,
            'gateway_session_id' => 'renewal-session-' . $invoice->id,
            'gateway_transaction_id' => 'renewal-transaction-' . $invoice->id,
            'gateway_amount_cents' => $invoice->total_cents,
            'currency' => $invoice->currency,
            'status' => PaymentAttempt::STATUS_PENDING,
            'webhook_verified_at' => now(),
        ]);
        $invoice->update([
            'payment_session_status' => Invoice::PAYMENT_SESSION_READY,
            'payment_session_attempt_id' => $attempt->id,
        ]);

        $registrar = Mockery::mock(RegistrarProvisioningService::class);
        $registrar->shouldReceive('provisionOrderDomain')
            ->once()
            ->with(Mockery::type(Order::class), 'lahza')
            ->andReturn(['ok' => true, 'message' => 'renewed']);

        $settlement = new InvoiceSettlementService(new OrderActivationService($registrar));
        $settlement->markPaid($invoice, 'lahza', $attempt);

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $invoice->order->fresh()->status);
        $this->assertSame(PaymentAttempt::STATUS_SUCCEEDED, $attempt->fresh()->status);
    }

    private function makeDomain(array $overrides = []): Domain
    {
        $client = Client::query()->create([
            'first_name' => 'Auto',
            'last_name' => 'Renewal',
            'email' => uniqid('auto_renew_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Auto Renewal Test',
        ]);

        // TLD-3D: Domain.provider_id is now the source of truth for renewal
        // pricing/provisioning. Resolve the trusted provider by the same
        // registrar type this helper defaults domains to (or an override's
        // registrar type), so every domain created here has a valid,
        // matching provider_id unless a test explicitly overrides it.
        $registrarType = $overrides['registrar'] ?? 'namecheap';
        $provider = DomainProvider::query()->where('type', $registrarType)->first();

        return Domain::query()->create(array_merge([
            'client_id' => $client->id,
            'domain_name' => uniqid('auto-renew-', false) . '.test',
            'registrar' => 'namecheap',
            'provider_id' => $provider?->id,
            'registration_date' => now()->subYear()->toDateString(),
            'renewal_date' => now()->addDays(3)->toDateString(),
            'auto_renew' => true,
            'status' => 'active',
            'payment_method' => 'lahza',
        ], $overrides));
    }

    private function createRenewalCatalog(): void
    {
        $provider = DomainProvider::query()->create([
            'name' => 'Auto Renewal Namecheap',
            'type' => 'namecheap',
            'endpoint' => 'https://provider.example.test',
            'is_active' => true,
            'mode' => 'test',
        ]);

        $tld = DomainTld::query()->create([
            'provider_id' => $provider->id,
            'provider' => 'namecheap',
            'tld' => 'test',
            'currency' => 'USD',
            'enabled' => true,
        ]);

        $tld->prices()->create([
            'action' => 'renew',
            'years' => 1,
            'cost' => 10,
            'sale' => 12.50,
        ]);
    }
}
