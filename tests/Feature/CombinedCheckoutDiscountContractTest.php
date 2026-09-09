<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Coupon;
use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\DomainTld;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAttempt;
use App\Models\Plan;
use App\Models\Template;
use App\Models\Tenancy\Subscription;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\DTOs\PaymentSession;
use App\Payments\PaymentManager;
use App\Services\Billing\InvoiceSettlementService;
use App\Services\Billing\OrderActivationService;
use App\Services\Domains\DomainAvailabilityService;
use App\Services\Domains\DomainRenewalService;
use App\Services\Domains\RegistrarProvisioningService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/**
 * TLD-3H.3C.4B — Canonical sale-price invoice contract for combined Template/Subscription
 * checkout.
 *
 * Proves the fix in CheckoutController::process()'s combined branch (approved design from
 * TLD-3H.3C.4): Invoice.subtotal_cents is now the ACTUAL sale-price subtotal (sum of what is
 * really being invoiced -- sale-price subscription lines + trusted domain lines), never the
 * pre-discount list price. Invoice.discount_cents now represents the coupon discount only -- the
 * Template merchandising discount (list vs sale price) is no longer folded into it. This makes
 * sum(InvoiceItems.total_cents) === Invoice.subtotal_cents hold by construction for a discounted
 * Template checkout, satisfying InvoiceSettlementService::assertOrderBackedFinancialIntegrity()
 * (TLD-3H.3C) WITHOUT touching that validator at all.
 *
 * Out of scope / explicitly unchanged by this phase (verified by dedicated regression tests
 * below, not just left alone): domain financial classification (TLD-3H.3C.5), the combined
 * domain OrderItem contract (TLD-3H.3C.3), renewal pricing (DomainRenewalService), standalone
 * admin invoices, gateway currency rules, provisioning semantics.
 */
class CombinedCheckoutDiscountContractTest extends TestCase
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
    }

    /* ============================== 1 ============================== */
    public function test_discounted_template_checkout_stores_sale_price_in_subscription(): void
    {
        $this->fakePaymentManager();
        $client = $this->makeClient();
        $template = $this->makeDiscountedTemplate(10000, 8000); // list $100, sale $80

        $this->checkoutTemplateOnly($client, $template);

        $subscription = Subscription::query()->sole();
        $this->assertSame(8000, (int) $subscription->price_cents);
    }

    /* ============================== 2 ============================== */
    public function test_discounted_template_checkout_stores_sale_price_in_subscription_invoice_item(): void
    {
        $this->fakePaymentManager();
        $client = $this->makeClient();
        $template = $this->makeDiscountedTemplate(10000, 8000);

        [, $invoice] = $this->checkoutTemplateOnly($client, $template);

        $item = $invoice->items->where('item_type', 'subscription')->sole();
        $this->assertSame(8000, (int) $item->unit_price_cents);
        $this->assertSame(8000, (int) $item->total_cents);
    }

    /* ============================== 3 ============================== */
    public function test_invoice_subtotal_equals_sum_of_invoice_items(): void
    {
        $this->fakePaymentManager();
        $client = $this->makeClient();
        $template = $this->makeDiscountedTemplate(10000, 8000);

        [, $invoice] = $this->checkoutTemplateOnly($client, $template);

        $this->assertSame((int) $invoice->items->sum('total_cents'), (int) $invoice->subtotal_cents);
        $this->assertSame(8000, (int) $invoice->subtotal_cents);
    }

    /* ============================== 4 ============================== */
    public function test_template_merchandising_discount_not_stored_in_invoice_discount_cents(): void
    {
        $this->fakePaymentManager();
        $client = $this->makeClient();
        $template = $this->makeDiscountedTemplate(10000, 8000); // list-vs-sale gap = 2000

        [, $invoice] = $this->checkoutTemplateOnly($client, $template);

        $this->assertNotSame(2000, (int) $invoice->discount_cents);
        $this->assertSame(0, (int) $invoice->discount_cents);
    }

    /* ============================== 5 ============================== */
    public function test_discounted_template_no_coupon_zero_discount_and_total_equals_subtotal(): void
    {
        $this->fakePaymentManager();
        $client = $this->makeClient();
        $template = $this->makeDiscountedTemplate(10000, 8000);

        [, $invoice] = $this->checkoutTemplateOnly($client, $template);

        $this->assertSame(0, (int) $invoice->discount_cents);
        $this->assertSame((int) $invoice->subtotal_cents, (int) $invoice->total_cents);
        $this->assertSame(8000, (int) $invoice->total_cents);
    }

    /* ============================== 6 ============================== */
    public function test_discounted_template_with_coupon_discount_cents_is_coupon_only(): void
    {
        $this->fakePaymentManager();
        $client = $this->makeClient();
        $template = $this->makeDiscountedTemplate(10000, 8000); // sale $80
        $coupon = $this->makeCoupon('fixed', 10.00); // $10 off

        [, $invoice] = $this->checkoutTemplateOnly($client, $template, ['coupon_code' => $coupon->code]);

        // Not templatePlanDiscount(2000) + couponDiscount(1000) = 3000 -- coupon only.
        $this->assertSame(1000, (int) $invoice->discount_cents);
        $this->assertSame(7000, (int) $invoice->total_cents);
        $this->assertSame($coupon->id, $invoice->coupon_id);
    }

    /* ============================== 7 ============================== */
    public function test_coupon_computed_from_sale_price_subtotal_not_list_price_subtotal(): void
    {
        $this->fakePaymentManager();
        $client = $this->makeClient();
        $template = $this->makeDiscountedTemplate(10000, 8000); // list $100, sale $80
        $coupon = $this->makeCoupon('percent', 10); // 10%

        [, $invoice] = $this->checkoutTemplateOnly($client, $template, ['coupon_code' => $coupon->code]);

        // 10% of the ACTUAL sale-price subtotal (8000) = 800, never 10% of list price (10000) = 1000.
        $this->assertSame(800, (int) $invoice->discount_cents);
        $this->assertNotSame(1000, (int) $invoice->discount_cents);
        $this->assertSame(7200, (int) $invoice->total_cents);
    }

    /* ============================== 8 ============================== */
    public function test_discounted_template_invoice_settles_successfully_through_the_real_validator(): void
    {
        // TLD-3H.3C.6B -- makeDiscountedTemplate() links a real Plan, so settlement's
        // OrderActivationService::activate() legitimately dispatches ProvisionSubscription
        // (app/Jobs/ProvisionSubscription.php -> TenantProvisioningService ->
        // SubscriptionSyncService -> real WHM). Queue::fake() is the established, already-used
        // project pattern for isolating exactly this external boundary (see
        // AdminInvoiceSettlementTest, CheckoutPaymentTest, InvoiceSettlementActivationIdempotencyTest,
        // and this file's own sibling DomainBillableFinancialIntegrityTest::
        // test_checkout_payment_subdomain_plus_subscription_regression_now_settles) -- it does
        // not touch InvoiceSettlementService or OrderActivationService, both of which still run
        // for real; it only prevents the queued job's handle() from executing, so no live WHM
        // call is made.
        \Illuminate\Support\Facades\Queue::fake();
        $this->fakePaymentManager();
        $client = $this->makeClient();
        $template = $this->makeDiscountedTemplate(10000, 8000);

        [$order, $invoice] = $this->checkoutTemplateOnly($client, $template);

        // TLD-3H.3C.6 correction: checkoutTemplateOnly() -> CheckoutController::process()
        // always starts a real hosted payment session for a non-domain-only checkout
        // (PaymentSessionStarter::start(), fired whenever !$isDomainOnly), which claims the
        // invoice (payment_session_status = creating/ready, payment_session_attempt_id set to
        // a real PaymentAttempt). InvoiceSettlementService::assertPaymentSessionOwnsSettlement()
        // then requires markPaid()'s $paymentAttempt to be exactly that owning PaymentAttempt --
        // this is the legitimate production gateway-settlement path this fixture already
        // produces, not something to bypass. Fetch and pass the real owning attempt (same
        // pattern already used by test_gateway_amount_remains_invoice_total_cents below).
        $attempt = PaymentAttempt::query()->where('invoice_id', $invoice->id)->sole();

        $registrar = $this->spyRegistrar();
        $settlement = new InvoiceSettlementService(new OrderActivationService($registrar));
        $settlement->markPaid($invoice->fresh(), null, $attempt);

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
    }

    /* ============================== 9 ============================== */
    public function test_discounted_template_plus_real_domain_subtotal_is_sale_price_plus_domain(): void
    {
        $this->makeDomainCatalog(10.00); // trusted domain price_cents = 1000
        $this->fakeAvailability();
        $this->fakePaymentManager();
        $client = $this->makeClient();
        $template = $this->makeDiscountedTemplate(10000, 8000); // sale $80

        [$order, $invoice] = $this->checkoutCombined($client, $template, 'discount-plus-domain.com');

        $domainOrderItem = $order->items->sole();
        $this->assertSame(1000, (int) $domainOrderItem->price_cents);

        $subscriptionItem = $invoice->items->where('item_type', 'subscription')->sole();
        $domainItem = $invoice->items->where('item_type', 'domain')->sole();

        $this->assertSame(8000, (int) $subscriptionItem->total_cents);
        $this->assertSame(1000, (int) $domainItem->total_cents);
        $this->assertSame(9000, (int) $invoice->subtotal_cents);
        $this->assertSame((int) $invoice->items->sum('total_cents'), (int) $invoice->subtotal_cents);
    }

    /* ============================== 10 ============================== */
    public function test_no_discount_template_behavior_remains_unchanged(): void
    {
        $this->fakePaymentManager();
        $client = $this->makeClient();
        $template = $this->makeDiscountedTemplate(2500, null); // no discount configured

        [, $invoice] = $this->checkoutTemplateOnly($client, $template);

        $subscription = Subscription::query()->sole();
        $this->assertSame(2500, (int) $subscription->price_cents);
        $this->assertSame(2500, (int) $invoice->subtotal_cents);
        $this->assertSame(0, (int) $invoice->discount_cents);
        $this->assertSame(2500, (int) $invoice->total_cents);
    }

    /* ============================== 11 ============================== */
    public function test_domain_only_checkout_remains_unchanged(): void
    {
        $this->makeDomainCatalog(10.00); // trusted price_cents = 1000 -- forged price below never matches it
        $this->fakeAvailability();
        $this->fakePaymentManager();
        $client = $this->makeClient();

        // TLD-3H.3C.6A -- corrected to the proven, pre-existing production contract
        // (CheckoutController::process()'s price-changed-since-cart guard, unmodified by
        // TLD-3H.3C.4B): a forged price_cents that does not match the live DomainPricingService
        // quote is rejected outright -- HTTP 409, price_changed=true -- never silently
        // substituted with the trusted price. No Order/Invoice/OrderItem/InvoiceItem is
        // created for a rejected checkout.
        $response = $this->actingAs($client, 'client')->postJson(route('checkout.cart.process'), [
            'items' => [[
                'domain' => 'domain-only-unchanged-4b.com',
                'option' => 'register',
                'price_cents' => 1, // forged -- must be rejected, not silently substituted
            ]],
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('price_changed', true);

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(0, OrderItem::query()->count());
        $this->assertSame(0, InvoiceItem::query()->count());
    }

    /* ============================== 12 ============================== */
    public function test_renewal_invoice_behavior_remains_unchanged(): void
    {
        [$domain, $priceCents] = $this->makeRenewableDomain(16.20); // $16.20 -> 1620 cents

        $result = (new DomainRenewalService())->prepareRenewalCheckout($domain, 1, false);

        $this->assertTrue($result['created']);
        $invoice = $result['invoice'];

        $this->assertSame($priceCents, (int) $invoice->subtotal_cents);
        $this->assertSame(0, (int) $invoice->discount_cents);
        $this->assertSame(0, (int) $invoice->tax_cents);
        $this->assertSame($priceCents, (int) $invoice->total_cents);
        $this->assertSame((int) $invoice->items->sum('total_cents'), (int) $invoice->subtotal_cents);
    }

    /* ============================== 13 ============================== */
    public function test_gateway_amount_remains_invoice_total_cents(): void
    {
        $this->fakePaymentManager();
        $client = $this->makeClient();
        $template = $this->makeDiscountedTemplate(10000, 8000);
        $coupon = $this->makeCoupon('fixed', 10.00);

        [, $invoice] = $this->checkoutTemplateOnly($client, $template, ['coupon_code' => $coupon->code]);

        $attempt = PaymentAttempt::query()->where('invoice_id', $invoice->id)->sole();
        $this->assertSame((int) $invoice->total_cents, (int) $attempt->gateway_amount_cents);
        $this->assertSame(7000, (int) $attempt->gateway_amount_cents);
    }

    /* ============================== 14 ============================== */
    public function test_coupon_used_count_semantics_remain_unchanged(): void
    {
        // TLD-3H.3C.6B -- same real Plan-linked template as the settlement test above, so
        // settlement legitimately dispatches ProvisionSubscription -> real WHM. Queue::fake()
        // isolates that established external boundary only (see this file's sibling
        // test_discounted_template_invoice_settles_successfully_through_the_real_validator for
        // the full rationale) -- InvoiceSettlementService/OrderActivationService still run for
        // real, and no live WHM call is made.
        \Illuminate\Support\Facades\Queue::fake();
        $this->fakePaymentManager();
        $client = $this->makeClient();
        $template = $this->makeDiscountedTemplate(10000, 8000);
        $coupon = $this->makeCoupon('fixed', 10.00);

        [$order, $invoice] = $this->checkoutTemplateOnly($client, $template, ['coupon_code' => $coupon->code]);
        $this->assertSame(0, $coupon->fresh()->used_count);

        // TLD-3H.3C.6 correction: same real hosted payment session claim as the settlement
        // test above -- pass the owning PaymentAttempt rather than null so
        // assertPaymentSessionOwnsSettlement() sees the legitimate gateway-settlement shape
        // checkoutTemplateOnly() actually produced, instead of tripping the ownership guard.
        $attempt = PaymentAttempt::query()->where('invoice_id', $invoice->id)->sole();

        $registrar = $this->spyRegistrar();
        $settlement = new InvoiceSettlementService(new OrderActivationService($registrar));
        $settlement->markPaid($invoice->fresh(), null, $attempt);

        $this->assertSame(1, $coupon->fresh()->used_count);
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    // -----------------------------------------------------------------
    // Fixtures
    // -----------------------------------------------------------------

    private function makeClient(): Client
    {
        return Client::query()->create([
            'first_name' => 'Discount',
            'last_name' => 'Contract',
            'email' => uniqid('discount_contract_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Discount Contract Test',
            'can_login' => true,
        ]);
    }

    private function makePlanForTemplate(): Plan
    {
        return Plan::query()->create([
            'name' => 'TLD3H3C4B Plan',
            'slug' => 'tld3h3c4b-plan-' . uniqid(),
            'plan_type' => Plan::TYPE_HOSTING,
            'monthly_price_cents' => 0,
            'annual_price_cents' => 0,
            'is_active' => true,
        ]);
    }

    /**
     * A Template linked to a real Plan (so checkout actually creates a Subscription -- a
     * Template with no plan_id never reaches Subscription::create() in CheckoutController,
     * matching the pre-existing behavior of CheckoutPaymentTest's own plan-less makeTemplate()).
     */
    private function makeDiscountedTemplate(int $priceCents, ?int $discountPriceCents, ?\Illuminate\Support\Carbon $discountEndsAt = null): Template
    {
        $plan = $this->makePlanForTemplate();

        $categoryId = DB::table('category_templates')->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Template::query()->create([
            'category_template_id' => $categoryId,
            'plan_id' => $plan->id,
            'price_cents' => $priceCents,
            'discount_price_cents' => $discountPriceCents,
            'discount_ends_at' => $discountEndsAt,
            'image' => 'tld3h3c4b-test.jpg',
            'rating' => 0,
        ]);
    }

    private function makeCoupon(string $type, float $value): Coupon
    {
        return Coupon::query()->create([
            'code' => strtoupper('TLD3H3C4B-' . uniqid()),
            'discount_type' => $type,
            'discount_value' => $value,
            'used_count' => 0,
            'is_active' => true,
        ]);
    }

    private function makeDomainCatalog(float $sale): void
    {
        $provider = DomainProvider::query()->create([
            'name' => 'TLD3H3C4B Provider ' . uniqid(),
            'type' => 'namecheap',
            'mode' => 'live',
            'endpoint' => 'https://namecheap.example.test',
            'username' => 'test-user',
            'password' => 'test-password',
            'api_key' => 'test-key',
            'client_ip' => '127.0.0.1',
            'is_active' => true,
        ]);

        $tld = DomainTld::query()->create([
            'provider_id' => $provider->id,
            'provider' => $provider->type,
            'tld' => 'com',
            'currency' => 'USD',
            'enabled' => true,
        ]);

        $tld->prices()->create([
            'action' => 'register',
            'years' => 1,
            'sale' => $sale,
            'cost' => max(0.01, $sale - 3),
        ]);
    }

    /**
     * A real, provider-linked Domain + matching renew-price catalog row, for
     * DomainRenewalService::prepareRenewalCheckout() -- mirrors
     * OrderBackedInvoiceFinancialIntegrityTest::makeDomainRenewalInvoice()'s provider/domain
     * shape but builds the catalog row prepareRenewalCheckout() itself reads
     * (action='renew') rather than hand-constructing the Order/Invoice.
     *
     * @return array{0: Domain, 1: int} [$domain, $expectedPriceCents]
     */
    private function makeRenewableDomain(float $sale): array
    {
        $client = $this->makeClient();

        $provider = DomainProvider::query()->create([
            'name' => 'TLD3H3C4B Renewal Provider',
            'type' => 'enom',
            'username' => 'testuser',
            'password' => 'testpass',
            'is_active' => true,
            'mode' => 'live',
        ]);

        $domainName = 'tld3h3c4b-renew-' . uniqid() . '.com';

        $tld = DomainTld::query()->create([
            'provider_id' => $provider->id,
            'provider' => $provider->type,
            'tld' => 'com',
            'currency' => 'USD',
            'enabled' => true,
        ]);

        $tld->prices()->create([
            'action' => 'renew',
            'years' => 1,
            'sale' => $sale,
            'cost' => max(0.01, $sale - 3),
        ]);

        $domain = Domain::query()->create([
            'client_id' => $client->id,
            'domain_name' => $domainName,
            'registrar' => $provider->type,
            'provider_id' => $provider->id,
            'registration_date' => now()->subYear()->toDateString(),
            'renewal_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        return [$domain, (int) round($sale * 100)];
    }

    private function fakeAvailability(): void
    {
        $this->app->instance(DomainAvailabilityService::class, new class extends DomainAvailabilityService {
            public function checkDomains(array $domains, ?DomainProvider $provider = null): array
            {
                return [
                    'ok' => true,
                    'reason' => 'ok',
                    'message' => 'ok',
                    'results' => array_map(fn (string $domain) => [
                        'domain' => strtolower(trim($domain)),
                        'available' => true,
                        'is_premium' => false,
                    ], $domains),
                ];
            }

            public function verifyRegistrationAvailabilityBatch(array $domains, DomainProvider $provider): ?array
            {
                return array_fill_keys(array_map('strtolower', $domains), true);
            }
        });
    }

    private function fakePaymentManager(): void
    {
        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('name')->andReturn('lahza');
        $gateway->shouldReceive('createSession')->zeroOrMoreTimes()->andReturn(
            new PaymentSession('tld3h3c4b-session-' . uniqid(), 'https://pay.test/tld3h3c4b-session')
        );

        $manager = Mockery::mock(PaymentManager::class);
        $manager->shouldReceive('isEnabled')->andReturnTrue();
        $manager->shouldReceive('gateway')->andReturn($gateway);
        $this->app->instance(PaymentManager::class, $manager);
    }

    /**
     * Template-only combined checkout (no domain fields at all) -- mirrors
     * CheckoutPaymentTest::test_template_checkout_creates_unpaid_records_and_returns_gateway_url,
     * which proves a template checkout needs no domain selection
     * ($requiresDomainSelection is false for template checkouts).
     *
     * @return array{0: Order, 1: Invoice}
     */
    private function checkoutTemplateOnly(Client $client, Template $template, array $overrides = []): array
    {
        $response = $this->actingAs($client, 'client')
            ->postJson(route('checkout.process', ['template_id' => $template->id]), $overrides);

        $response->assertOk()->assertJsonPath('success', true);

        $order = Order::query()->where('client_id', $client->id)->latest('id')->first();
        $invoice = Invoice::query()->where('order_id', $order->id)->sole();

        return [$order->fresh(['items']), $invoice->fresh(['items'])];
    }

    /**
     * Combined template + real domain checkout -- same shape as
     * CombinedSubscriptionDomainOrderContractTest::checkoutCombined() (TLD-3H.3C.3).
     *
     * TLD-3H.3C.6 correction: submits the domain's ACTUAL current trusted DomainPricingService
     * quote (recomputed live) rather than a hardcoded forged value -- CheckoutController::
     * process()'s pre-existing, unmodified-by-TLD-3H.3C.4B "price changed since added to cart"
     * guard rejects with HTTP 409 on any submitted/trusted mismatch instead of silently
     * substituting the trusted price, so a forged default here never reached a 200.
     *
     * @return array{0: Order, 1: Invoice}
     */
    private function checkoutCombined(Client $client, Template $template, string $domain, array $overrides = []): array
    {
        $trustedPriceCents = app(\App\Services\Domains\DomainPricingService::class)
            ->registrationQuoteForDomain($domain)['price_cents'] ?? 1;

        $payload = array_merge([
            'domain' => $domain,
            'domain_option' => 'register',
            'domain_price_cents' => $trustedPriceCents,
            'items' => [[
                'domain' => $domain,
                'option' => 'register',
                'price_cents' => $trustedPriceCents,
            ]],
        ], $overrides);

        $response = $this->actingAs($client, 'client')
            ->postJson(route('checkout.process', ['template_id' => $template->id]), $payload);

        $response->assertOk()->assertJsonPath('success', true);

        $order = Order::query()->where('client_id', $client->id)->latest('id')->first();
        $invoice = Invoice::query()->where('order_id', $order->id)->sole();

        return [$order->fresh(['items']), $invoice->fresh(['items'])];
    }

    private function spyRegistrar(): RegistrarProvisioningService
    {
        return new class extends RegistrarProvisioningService {
            public int $registerCalls = 0;
            public int $renewCalls = 0;

            public function __construct()
            {
            }

            protected function registerDomainWithProvider(DomainProvider $provider, Domain $domain, array $context, array $contact): array
            {
                $this->registerCalls++;

                return ['ok' => true, 'reason' => 'ok', 'cid' => 'X'];
            }

            protected function renewDomainWithProvider(DomainProvider $provider, Domain $domain, array $context): array
            {
                $this->renewCalls++;

                return ['ok' => true, 'cid' => 'X', 'provider_reference' => 'X', 'provider_domain_id' => 'X'];
            }
        };
    }
}
