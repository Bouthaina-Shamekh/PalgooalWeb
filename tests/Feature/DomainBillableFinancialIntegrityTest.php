<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\Plan;
use App\Models\Server;
use App\Models\Template;
use App\Models\Tenancy\Subscription;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\DTOs\PaymentSession;
use App\Payments\DTOs\WebhookEvent;
use App\Payments\PaymentManager;
use App\Services\Billing\InvoiceSettlementService;
use App\Services\Billing\OrderActivationService;
use App\Services\Domains\DomainAvailabilityService;
use App\Services\Domains\RegistrarProvisioningService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/**
 * TLD-3H.3C.5 — Billable vs non-billable domain integrity classification.
 *
 * Proves the fix in InvoiceSettlementService::assertOrderBackedFinancialIntegrity(): the domain
 * aggregate check now only requires a matching domain InvoiceItem for OrderItems whose
 * item_option is 'register' or 'renew' (the same $hasProvisionableDomain predicate
 * OrderActivationService already used) — never for a merely filled(domain) OrderItem. A
 * legitimate $0 subdomain/own/transfer OrderItem can no longer trip a false count mismatch and
 * block settlement of an otherwise-valid invoice (the bug flagged, not fixed, in the TLD-3H.3C.3
 * report). Register/renew remain exactly as strictly enforced as before this phase.
 *
 * Strictly out of scope here (per TLD-3H.3C.5 task spec): Template/Plan discount/subtotal
 * semantics — no test in this file settles a discounted invoice, and nothing about discount
 * calculation was touched.
 */
class DomainBillableFinancialIntegrityTest extends TestCase
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
    public function test_subdomain_order_item_with_zero_domain_invoice_items_settles_successfully(): void
    {
        [$order, $invoice] = $this->makeSubscriptionOrderWithExtraDomainItem('subdomain', 0);

        $settlement = new InvoiceSettlementService(new OrderActivationService($this->spyRegistrar()));
        $settlement->markPaid($invoice->fresh());

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
        // No domain InvoiceItem was ever required for the non-billable subdomain OrderItem.
        $this->assertSame(0, $invoice->fresh(['items'])->items->where('item_type', 'domain')->count());
    }

    /* ============================== 2 ============================== */
    public function test_register_order_item_without_matching_domain_invoice_item_fails_closed(): void
    {
        [$order, $invoice] = $this->makeDomainOnlyOrderAndInvoice('register', 1200, withInvoiceItem: false);

        try {
            app(InvoiceSettlementService::class)->markPaid($invoice->fresh());
            $this->fail('Expected settlement to fail closed: a billable register OrderItem has no matching domain InvoiceItem.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('domain item count', $e->getMessage());
        }

        $this->assertSame('unpaid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    /* ============================== 3 ============================== */
    public function test_register_invoice_item_without_matching_billable_order_item_fails_closed(): void
    {
        [$order, $invoice] = $this->makeDomainOnlyOrderAndInvoice(null, 0, withInvoiceItem: true, invoiceItemCents: 1200);

        try {
            app(InvoiceSettlementService::class)->markPaid($invoice->fresh());
            $this->fail('Expected settlement to fail closed: a billed domain InvoiceItem has no backing billable OrderItem.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('domain item count', $e->getMessage());
        }

        $this->assertSame('unpaid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    /* ============================== 4 ============================== */
    public function test_register_amount_mismatch_fails_closed(): void
    {
        [$order, $invoice, $item] = $this->makeDomainOnlyOrderAndInvoice('register', 1200, withInvoiceItem: true, invoiceItemCents: 1200, returnItem: true);

        DB::table('invoice_items')->where('id', $item->id)->update([
            'unit_price_cents' => 1,
            'total_cents' => 1,
        ]);

        try {
            app(InvoiceSettlementService::class)->markPaid($invoice->fresh());
            $this->fail('Expected settlement to fail closed on a corrupted register amount.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('financial total', $e->getMessage());
        }

        $this->assertSame('unpaid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    /* ============================== 5 ============================== */
    public function test_renew_order_item_without_matching_renewal_invoice_item_fails_closed(): void
    {
        [$order, $invoice] = $this->makeDomainOnlyOrderAndInvoice('renew', 1620, withInvoiceItem: false, orderType: 'domain_renewal');

        try {
            app(InvoiceSettlementService::class)->markPaid($invoice->fresh());
            $this->fail('Expected settlement to fail closed: a billable renew OrderItem has no matching domain InvoiceItem.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('domain item count', $e->getMessage());
        }

        $this->assertSame('unpaid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    /* ============================== 6 ============================== */
    public function test_valid_renewal_still_settles(): void
    {
        [$order, $invoice] = $this->makeValidRenewalOrderAndInvoice();

        $registrar = $this->spyRegistrar();
        $settlement = new InvoiceSettlementService(new OrderActivationService($registrar));
        $settlement->markPaid($invoice->fresh());

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
        $this->assertSame(1, $registrar->renewCalls);
        $this->assertSame(0, $registrar->registerCalls);
    }

    /* ============================== 7 ============================== */
    public function test_combined_checkout_real_domain_order_item_remains_financially_enforced(): void
    {
        // TLD-3H.3C.7A -- makeTemplate() now links a real Plan, so activate() legitimately
        // finds a real subscription InvoiceItem and dispatches ProvisionSubscription. Fake the
        // queue so that dispatch is intercepted rather than hitting real WHM (no credentials in
        // this environment), matching the established pattern already used elsewhere in this
        // file (see test_subscription_validation_remains_unchanged) and in
        // CombinedCheckoutDiscountContractTest.php (TLD-3H.3C.6B).
        \Illuminate\Support\Facades\Queue::fake();

        $this->makeDomainCatalog(10.00); // trusted price_cents = 1000
        $this->fakeAvailability();
        $this->fakePaymentManager();

        // TLD-3H.3C.6B correction: checkoutCombined() -> CheckoutController::process() always
        // starts a real hosted payment session for a non-domain-only checkout
        // (PaymentSessionStarter::start()), which claims each invoice (payment_session_status =
        // creating/ready, payment_session_attempt_id set to a real PaymentAttempt).
        // InvoiceSettlementService::assertPaymentSessionOwnsSettlement() requires markPaid()'s
        // $paymentAttempt to be exactly that owning PaymentAttempt for both the happy-path and
        // corrupted-path invoices below -- the same correction already proven in
        // TLD-3H.3C.6/6A. This does not touch the financial-integrity check itself: the
        // corrupted path still reaches and fails assertOrderBackedFinancialIntegrity() exactly
        // as intended, now that the unrelated session-ownership guard is satisfied first.

        // Happy path: a real TLD-3H.3C.3 combined-checkout domain OrderItem (created by the
        // actual controller code, not a hand-built fixture) still settles cleanly.
        [$orderOk, $invoiceOk] = $this->checkoutCombined($this->makeClient(), $this->makeTemplate(), 'enforced-ok.com');
        $attemptOk = PaymentAttempt::query()->where('invoice_id', $invoiceOk->id)->sole();
        $registrar = $this->spyRegistrar();
        (new InvoiceSettlementService(new OrderActivationService($registrar)))->markPaid($invoiceOk->fresh(), null, $attemptOk);
        $this->assertSame('paid', $invoiceOk->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $orderOk->fresh()->status);
        $this->assertSame(1, $registrar->registerCalls);

        // Corrupted path: the same real combined-checkout shape, but the domain InvoiceItem's
        // amount is tampered with after creation -- TLD-3H.3C.5's billable-only filter must NOT
        // have weakened this. register OrderItems remain exactly as strictly enforced as before.
        [$orderBad, $invoiceBad] = $this->checkoutCombined($this->makeClient(), $this->makeTemplate(), 'enforced-bad.com');
        $attemptBad = PaymentAttempt::query()->where('invoice_id', $invoiceBad->id)->sole();
        $domainItem = $invoiceBad->items->where('item_type', 'domain')->sole();
        DB::table('invoice_items')->where('id', $domainItem->id)->update([
            'unit_price_cents' => 1,
            'total_cents' => 1,
        ]);

        try {
            app(InvoiceSettlementService::class)->markPaid($invoiceBad->fresh(), null, $attemptBad);
            $this->fail('Expected the corrupted combined-checkout domain invoice item to fail settlement.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('financial total', $e->getMessage());
        }

        // TLD-3H.3C.7D -- checkoutCombined() -> CheckoutController::process() creates this
        // invoice as 'draft' (both the combined-with-domain and plan-only Invoice::create()
        // calls do). PaymentSessionStarter::start() only ever mutates payment_session_status,
        // never Invoice.status -- and explicitly treats 'draft' and 'unpaid' as equally
        // legitimate pre-settlement states (see its own `!in_array($invoice->status,
        // ['draft', 'unpaid'])` guard). The only place that bulk-transitions an Order's 'draft'
        // invoices to 'unpaid' is OrderActivationService::activate() (invoked by
        // InvoiceSettlementService::markPaid() only AFTER assertOrderBackedFinancialIntegrity()
        // passes and the invoice is marked 'paid'), which is never reached here: the corrupted
        // domain-total mismatch throws from assertOrderBackedFinancialIntegrity() -- a pure,
        // non-mutating check -- and DB::transaction() rolls back the whole markPaid() call. So
        // 'draft' is this invoice's correct, unmutated pre-settlement state, not 'unpaid'. The
        // previous 'unpaid' expectation predates TLD-3H.3C.7A, when this fixture used a plan-less
        // Template and did not exercise the real checkout HTTP flow; every OTHER test in this
        // suite asserting 'unpaid' after a failed settlement (e.g. test_subscription_validation_
        // remains_unchanged, and every OrderBackedInvoiceFinancialIntegrityTest.php case) hand-
        // builds its Invoice fixture directly with 'status' => 'unpaid' rather than deriving it
        // from a real checkout -- not a comparable starting state. No Invoice::STATUS_* constant
        // exists in this codebase (status is a plain string everywhere, including the admin
        // InvoiceController's `Rule::in(['draft', 'unpaid', 'paid', 'cancelled'])`), so this
        // matches the project's own established convention rather than introducing a new one.
        $this->assertSame('draft', $invoiceBad->fresh()->status);
        $this->assertSame(Order::STATUS_PENDING, $orderBad->fresh()->status);
    }

    /* ============================== 8 ============================== */
    public function test_non_billable_domain_option_cannot_smuggle_a_real_billed_invoice_item_past_validation(): void
    {
        // A subdomain (non-billable) OrderItem paired with a real, billed domain InvoiceItem --
        // e.g. a corrupted/tampered row, or a future bug that mis-tags a real charge under a
        // non-provisionable option. Must still fail closed: filled(domain) is not the predicate,
        // item_option in ['register','renew'] is, so this subdomain OrderItem never legitimizes
        // that InvoiceItem's charge.
        [$order, $invoice] = $this->makeDomainOnlyOrderAndInvoice('subdomain', 0, withInvoiceItem: true, invoiceItemCents: 1200);

        try {
            app(InvoiceSettlementService::class)->markPaid($invoice->fresh());
            $this->fail('Expected settlement to fail closed: a non-billable subdomain OrderItem cannot back a real billed domain InvoiceItem.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('domain item count', $e->getMessage());
        }

        $this->assertSame('unpaid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    /* ============================== 9 ============================== */
    public function test_subscription_validation_remains_unchanged(): void
    {
        [$order, $invoice, $item] = $this->makeSubscriptionOrderBackedInvoice();

        DB::table('invoice_items')->where('id', $item->id)->update([
            'unit_price_cents' => 1,
            'total_cents' => 1,
        ]);

        try {
            app(InvoiceSettlementService::class)->markPaid($invoice->fresh());
            $this->fail('Expected the corrupted subscription invoice item to fail settlement.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Subscription contract', $e->getMessage());
        }

        $this->assertSame('unpaid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    /* ============================== 10 ============================== */
    public function test_gateway_currency_validation_remains_unchanged(): void
    {
        [$order, $invoice] = $this->makeSubscriptionOrderBackedInvoice();
        $attempt = $this->claimHostedSession($invoice->fresh(), 'EUR');

        $this->expectException(\RuntimeException::class);
        app(InvoiceSettlementService::class)->markPaid($invoice->fresh(), 'stripe', $attempt);
    }

    /* ======================== Regression (CheckoutPaymentTest shape) ======================== */
    public function test_checkout_payment_subdomain_plus_subscription_regression_now_settles(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        \Illuminate\Support\Facades\Http::fake();
        $client = $this->makeClient();
        $plan = $this->makePlan();
        $this->fakePaymentManager('tld3h3c5-regression-session');

        // Exact real-world shape from CheckoutPaymentTest::
        // test_non_template_plan_checkout_waits_for_verified_payment_before_provisioning --
        // a non-template plan checkout with a subdomain selection, submitted the same way the
        // real checkout.blade.php path can submit it (scalar domain/domain_option fields, no
        // items[] key). Before TLD-3H.3C.5 this settlement threw inside
        // assertOrderBackedFinancialIntegrity() (the subdomain OrderItem's filled(domain) was
        // wrongly treated as requiring a domain InvoiceItem that legitimately doesn't exist).
        $response = $this->actingAs($client, 'client')->postJson(
            route('checkout.process', ['template_id' => 0, 'plan_id' => $plan->id]),
            [
                'domain' => 'regression-subdomain.example.test',
                'domain_option' => 'subdomain',
            ],
        );

        $response->assertOk()->assertJsonPath('success', true);

        $order = Order::query()->sole();
        $invoice = Invoice::query()->sole();
        $subscription = Subscription::query()->sole();

        $event = new WebhookEvent(
            WebhookEvent::TYPE_PAYMENT_SUCCEEDED,
            'tld3h3c5-regression-session',
            null,
            $invoice->total_cents,
            $invoice->currency,
            ['verified' => true],
        );
        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('name')->andReturn('lahza');
        $gateway->shouldReceive('verifyWebhook')->once()->andReturn($event);
        $manager = Mockery::mock(PaymentManager::class);
        $manager->shouldReceive('gateway')->once()->andReturn($gateway);
        $this->app->instance(PaymentManager::class, $manager);

        $this->postJson(route('payment.webhook', ['gateway' => 'lahza']), [], [
            'x-lahza-signature' => 'verified-by-fake',
        ])->assertStatus(202);

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
        $this->assertSame('active', $subscription->fresh()->status);
    }

    // -----------------------------------------------------------------
    // Fixtures
    // -----------------------------------------------------------------

    private function makeClient(): Client
    {
        return Client::query()->create([
            'first_name' => 'Billable',
            'last_name' => 'Integrity',
            'email' => uniqid('billable_integrity_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Billable Integrity Test',
            'can_login' => true,
        ]);
    }

    private function makeTemplate(int $priceCents = 2500): Template
    {
        $categoryId = DB::table('category_templates')->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // TLD-3H.3C.7A -- CheckoutController::process() (TLD-3H.3C.7) now fail-closed rejects
        // any template checkout whose Template does not resolve to a real Plan. This helper is
        // used only by test_combined_checkout_real_domain_order_item_remains_financially_enforced,
        // whose intent is a successful combined checkout, so link a real Plan here (mirroring the
        // proven CombinedCheckoutDiscountContractTest::makePlanForTemplate() field set, and the
        // same fix already applied in CombinedSubscriptionDomainOrderContractTest.php and
        // CheckoutPaymentTest.php) so this fixture represents a production-valid template.
        $plan = $this->makePlanForTemplate();

        return Template::query()->create([
            'category_template_id' => $categoryId,
            'plan_id' => $plan->id,
            'price_cents' => $priceCents,
            'image' => 'tld3h3c5-test.jpg',
            'rating' => 0,
        ]);
    }

    private function makePlanForTemplate(): Plan
    {
        return Plan::query()->create([
            'name' => 'TLD3H3C7A Template Plan',
            'slug' => 'tld3h3c7a-template-plan-' . uniqid(),
            'plan_type' => Plan::TYPE_HOSTING,
            'monthly_price_cents' => 0,
            'annual_price_cents' => 0,
            'is_active' => true,
        ]);
    }

    private function makePlan(): Plan
    {
        return Plan::query()->create([
            'name' => 'TLD3H3C5 Non-template Hosting',
            'slug' => 'tld3h3c5-non-template-hosting-' . uniqid(),
            'plan_type' => Plan::TYPE_HOSTING,
            'monthly_price_cents' => 2500,
            'annual_price_cents' => 25000,
            'is_active' => true,
            'requires_domain' => true,
        ]);
    }

    private function makeDomainCatalog(float $sale): void
    {
        $provider = DomainProvider::query()->create([
            'name' => 'TLD3H3C5 Provider ' . uniqid(),
            'type' => 'namecheap',
            'mode' => 'live',
            'endpoint' => 'https://namecheap.example.test',
            'username' => 'test-user',
            'password' => 'test-password',
            'api_key' => 'test-key',
            'client_ip' => '127.0.0.1',
            'is_active' => true,
        ]);

        $tld = \App\Models\DomainTld::query()->create([
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

    private function fakePaymentManager(?string $sessionId = null): void
    {
        // TLD-3H.3C.7C -- new PaymentSession(...) was previously constructed ONCE, at mock-setup
        // time, and handed to a single ->andReturn(...) -- so every createSession() call across
        // the whole test (test 7's happy-path + corrupted-path checkouts both call
        // fakePaymentManager() once, then checkout twice) returned the exact same PaymentSession
        // object, including the same gateway_session_id. PaymentSessionStarter persists that id
        // onto a real PaymentAttempt row, and payment_attempts has a real
        // UNIQUE(gateway, gateway_session_id) constraint -- so the second, legitimate, independent
        // checkout collided on it (confirmed via TLD-3H.3C.7B/7B.1 diagnostic: SQLSTATE 23000 on
        // the exact reused session id). andReturnUsing() defers evaluation to each actual
        // invocation, so every createSession() call generates its own fresh id -- one per real,
        // distinct hosted session, exactly matching what a real gateway does. Established pattern
        // already used the same way in CombinedSubscriptionDomainOrderContractTest.php
        // (TLD-3H.3C.6D) and CheckoutPaymentTest/CurrencySourceOfTruthTest/
        // InvoiceCheckoutPaymentAttemptTest.
        //
        // The optional explicit $sessionId parameter is preserved exactly: when a caller passes
        // one (e.g. test_checkout_payment_subdomain_plus_subscription_regression_now_settles,
        // which correlates that fixed id with a webhook event referencing the same string), every
        // createSession() call continues to return that exact requested id. Only the null-default
        // case (multi-checkout tests like test 7) now generates a fresh unique id per invocation.
        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('name')->andReturn('lahza');
        $gateway->shouldReceive('createSession')->zeroOrMoreTimes()->andReturnUsing(
            fn () => new PaymentSession($sessionId ?? ('tld3h3c5-session-' . uniqid('', true)), 'https://pay.test/tld3h3c5-session')
        );

        $manager = Mockery::mock(PaymentManager::class);
        $manager->shouldReceive('isEnabled')->andReturnTrue();
        $manager->shouldReceive('gateway')->andReturn($gateway);
        $this->app->instance(PaymentManager::class, $manager);
    }

    /**
     * Real HTTP combined template + domain checkout (same shape/helper convention as
     * TLD-3H.3C.3's CombinedSubscriptionDomainOrderContractTest::checkoutCombined()) -- used so
     * test 7 proves the register-classification enforcement against a domain OrderItem the real
     * controller code produced, not a hand-built fixture.
     *
     * TLD-3H.3C.6 correction: submits the domain's ACTUAL current trusted DomainPricingService
     * quote (recomputed live) rather than a hardcoded forged value -- CheckoutController::
     * process()'s pre-existing, unmodified-by-any-of-these-phases "price changed since added to
     * cart" guard rejects with HTTP 409 on any submitted/trusted mismatch instead of silently
     * substituting the trusted price, so a forged submission never reaches a 200 here.
     */
    private function checkoutCombined(Client $client, Template $template, string $domain): array
    {
        $trustedPriceCents = app(\App\Services\Domains\DomainPricingService::class)
            ->registrationQuoteForDomain($domain)['price_cents'] ?? 1;

        $payload = [
            'domain' => $domain,
            'domain_option' => 'register',
            'domain_price_cents' => $trustedPriceCents,
            'items' => [[
                'domain' => $domain,
                'option' => 'register',
                'price_cents' => $trustedPriceCents,
            ]],
        ];

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

    /**
     * Order with a real subscription (settleable, per the established convention in
     * OrderBackedInvoiceFinancialIntegrityTest::makeSubscriptionOrderBackedInvoice()) PLUS one
     * extra domain OrderItem of the given item_option/price attached to the same order, with NO
     * matching domain InvoiceItem -- proves a legitimate non-billable domain selection bundled
     * alongside a subscription no longer blocks settlement.
     */
    private function makeSubscriptionOrderWithExtraDomainItem(string $domainItemOption, int $domainPriceCents): array
    {
        [$order, $invoice] = $this->makeSubscriptionOrderBackedInvoice();

        $order->items()->create([
            'domain' => 'extra-' . $domainItemOption . '-' . uniqid() . '.example.test',
            'item_option' => $domainItemOption,
            'price_cents' => $domainPriceCents,
            'meta' => null,
        ]);

        return [$order->fresh(['items']), $invoice->fresh(['items'])];
    }

    private function makeSubscriptionOrderBackedInvoice(): array
    {
        $client = $this->makeClient();
        $subscription = $this->makeSubscription($client, 1500);

        $order = Order::query()->create([
            'client_id' => $client->id,
            'status' => Order::STATUS_PENDING,
            'type' => 'subscription',
        ]);

        $invoice = Invoice::query()->create([
            'client_id' => $client->id,
            'order_id' => $order->id,
            'number' => 'INV-TLD3H3C5-SUB-' . strtoupper(uniqid()),
            'status' => 'unpaid',
            'subtotal_cents' => 1500,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 1500,
            'currency' => 'USD',
            'due_date' => now()->addDays(7),
        ]);

        $item = $invoice->items()->create([
            'item_type' => 'subscription',
            'reference_id' => $subscription->id,
            'description' => 'Subscription #' . $subscription->id,
            'qty' => 1,
            'unit_price_cents' => 1500,
            'total_cents' => 1500,
        ]);

        return [$order->fresh(['items']), $invoice->fresh(['items']), $item, $subscription];
    }

    private function makeSubscription(Client $client, int $priceCents): Subscription
    {
        $server = Server::query()->create([
            'name' => 'TLD3H3C5 WHM',
            'type' => 'cpanel',
            'hostname' => uniqid('whm-', false) . '.example.test',
            'username' => 'root',
            'api_token' => 'test-token',
            'is_active' => true,
        ]);
        $plan = Plan::query()->create([
            'name' => 'TLD3H3C5 Plan',
            'slug' => uniqid('tld3h3c5-plan-', false),
            'plan_type' => Plan::TYPE_HOSTING,
            'server_id' => $server->id,
            'server_package' => 'tld3h3c5_package',
            'is_active' => true,
        ]);

        return Subscription::query()->create([
            'client_id' => $client->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'provisioning_status' => Subscription::PROVISIONING_PENDING,
            'price_cents' => $priceCents,
            'billing_cycle' => 'monthly',
            'username' => uniqid('tld3h3c5', false),
            'server_id' => $server->id,
            'server_package' => 'tld3h3c5_package',
            'domain_option' => 'subdomain',
            'domain_name' => uniqid('tld3h3c5-', false) . '.example.test',
            'subdomain' => uniqid('tld3h3c5-', false),
        ]);
    }

    /**
     * Minimal, direct Order(+optional OrderItem)/Invoice(+optional InvoiceItem) construction for
     * the domain-only fail-closed/settle cases (tests 2, 3, 4, 5, 8) -- mirrors the exact fixture
     * shape established in OrderBackedInvoiceFinancialIntegrityTest::makeDomainRegistrationInvoice()
     * / makeDomainRenewalInvoice(), generalized over item_option so the same helper covers
     * register/renew/subdomain/no-item combinations. None of these cases reach
     * OrderActivationService::activate()'s registrar call (they all fail closed inside
     * assertOrderBackedFinancialIntegrity() before it), so no Domain/DomainProvider row is
     * required here.
     */
    private function makeDomainOnlyOrderAndInvoice(
        ?string $itemOption,
        int $orderItemPriceCents,
        bool $withInvoiceItem,
        int $invoiceItemCents = 0,
        string $orderType = 'domains',
        bool $returnItem = false
    ): array {
        $client = $this->makeClient();
        $domainName = 'tld3h3c5-' . uniqid() . '.com';

        $order = Order::query()->create([
            'client_id' => $client->id,
            'status' => Order::STATUS_PENDING,
            'type' => $orderType,
        ]);

        if ($itemOption !== null) {
            $order->items()->create([
                'domain' => $domainName,
                'item_option' => $itemOption,
                'price_cents' => $orderItemPriceCents,
                'meta' => ['currency' => 'USD', 'years' => 1],
            ]);
        }

        $invoice = Invoice::query()->create([
            'client_id' => $client->id,
            'order_id' => $order->id,
            'number' => 'INV-TLD3H3C5-' . strtoupper(uniqid()),
            'status' => 'unpaid',
            'subtotal_cents' => $withInvoiceItem ? $invoiceItemCents : 0,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => $withInvoiceItem ? $invoiceItemCents : 0,
            'currency' => 'USD',
            'due_date' => now()->addDays(7),
        ]);

        $item = null;
        if ($withInvoiceItem) {
            $item = $invoice->items()->create([
                'item_type' => 'domain',
                'reference_id' => null,
                'description' => 'Domain: ' . $domainName,
                'qty' => 1,
                'unit_price_cents' => $invoiceItemCents,
                'total_cents' => $invoiceItemCents,
            ]);
        }

        $result = [$order->fresh(['items']), $invoice->fresh(['items'])];
        if ($returnItem) {
            $result[] = $item;
        }

        return $result;
    }

    /**
     * Full register/renew-capable fixture (Domain + DomainProvider + meta the registrar
     * provisioning layer actually reads) -- mirrors
     * OrderBackedInvoiceFinancialIntegrityTest::makeDomainRenewalInvoice() exactly. Used only by
     * test 6, the one case that must actually reach a (spied) registrar call successfully.
     */
    private function makeValidRenewalOrderAndInvoice(): array
    {
        $client = $this->makeClient();

        $provider = DomainProvider::query()->create([
            'name' => 'TLD3H3C5 Renewal Provider',
            'type' => 'enom',
            'username' => 'testuser',
            'password' => 'testpass',
            'is_active' => true,
            'mode' => 'live',
        ]);

        $domainName = 'tld3h3c5-renew-' . uniqid() . '.com';

        $domain = Domain::query()->create([
            'client_id' => $client->id,
            'domain_name' => $domainName,
            'registrar' => $provider->type,
            'provider_id' => $provider->id,
            'registration_date' => now()->subYear()->toDateString(),
            'renewal_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $order = Order::query()->create([
            'client_id' => $client->id,
            'status' => Order::STATUS_PENDING,
            'type' => 'domain_renewal',
        ]);

        $order->items()->create([
            'domain' => $domainName,
            'item_option' => 'renew',
            'price_cents' => 1620,
            'meta' => [
                'domain_id' => $domain->id,
                'currency' => 'USD',
                'provider_id' => $provider->id,
                'provider_type' => $provider->type,
                'provider_mode' => $provider->mode,
                'term_years' => 1,
            ],
        ]);

        $invoice = Invoice::query()->create([
            'client_id' => $client->id,
            'order_id' => $order->id,
            'number' => 'INV-TLD3H3C5-REN-' . strtoupper(uniqid()),
            'status' => 'unpaid',
            'subtotal_cents' => 1620,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 1620,
            'currency' => 'USD',
            'due_date' => now()->addDays(7),
        ]);

        $invoice->items()->create([
            'item_type' => 'domain',
            'reference_id' => $domain->id,
            'description' => 'Domain Renewal: ' . $domainName,
            'qty' => 1,
            'unit_price_cents' => 1620,
            'total_cents' => 1620,
        ]);

        return [$order->fresh(['items']), $invoice->fresh(['items'])];
    }

    private function claimHostedSession(Invoice $invoice, string $currency = 'USD'): PaymentAttempt
    {
        $attempt = PaymentAttempt::query()->create([
            'invoice_id' => $invoice->id,
            'order_id' => $invoice->order_id,
            'client_id' => $invoice->client_id,
            'gateway' => 'stripe',
            'idempotency_key' => uniqid('tld3h3c5-attempt-', true),
            'gateway_session_id' => uniqid('tld3h3c5-session-', true),
            'gateway_amount_cents' => $invoice->total_cents,
            'currency' => $currency,
            'status' => PaymentAttempt::STATUS_INITIATED,
        ]);

        $invoice->update([
            'payment_session_status' => Invoice::PAYMENT_SESSION_READY,
            'payment_session_attempt_id' => $attempt->id,
        ]);

        return $attempt;
    }
}
