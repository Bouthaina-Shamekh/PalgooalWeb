<?php

namespace Tests\Feature;

use App\Models\Client;
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
use App\Services\Domains\RegistrarProvisioningService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

/**
 * TLD-3H.3C.3 — Combined subscription/template + domain Order/Invoice contract.
 *
 * Proves the fix in CheckoutController::process(): a real, provisionable domain bundled with a
 * subscription/template checkout now produces ONE priced OrderItem sourced from the same
 * trusted, server-repriced domain quote (DomainPricingService) that the domain InvoiceItem is
 * built from — never a separate zero-price "informational" row (see TLD-3H.3C.2 audit for the
 * bug this closes). Client-submitted domain_price_cents is never trusted for either row.
 *
 * Strictly out of scope here (per TLD-3H.3C.3 task spec): the separate discounted Template/Plan
 * subtotal_cents mismatch (reserved for a future phase) — no test in this file settles a
 * discounted Template/Plan invoice, and InvoiceSettlementService/its validator are not modified.
 */
class CombinedSubscriptionDomainOrderContractTest extends TestCase
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
    public function test_combined_checkout_creates_matching_priced_order_item(): void
    {
        $this->makeCatalog(10.00); // sale=10.00 -> trusted price_cents = 1000
        $this->fakeAvailability();
        $this->fakePaymentManager();
        $client = $this->makeClient();
        $template = $this->makeTemplate();

        [$order] = $this->checkoutCombined($client, $template, 'combined-one.com');

        $domainItem = $order->items->sole();
        $this->assertSame('combined-one.com', $domainItem->domain);
        $this->assertSame('register', $domainItem->item_option);
        $this->assertSame(1000, $domainItem->price_cents);
        $this->assertIsArray($domainItem->meta);
        $this->assertArrayHasKey('provider_id', $domainItem->meta);
        $this->assertArrayHasKey('domain_tld_id', $domainItem->meta);
    }

    /* ============================== 2 ============================== */
    public function test_domain_invoice_item_has_the_same_trusted_price_as_the_order_item(): void
    {
        $this->makeCatalog(10.00);
        $this->fakeAvailability();
        $this->fakePaymentManager();
        $client = $this->makeClient();
        $template = $this->makeTemplate();

        [$order, $invoice] = $this->checkoutCombined($client, $template, 'combined-two.com');

        $orderItem = $order->items->sole();
        $domainInvoiceItem = $invoice->items->where('item_type', 'domain')->sole();

        $this->assertSame((int) $orderItem->price_cents, (int) $domainInvoiceItem->total_cents);
        $this->assertSame(1000, (int) $domainInvoiceItem->total_cents);
        $this->assertSame(1000, (int) $domainInvoiceItem->unit_price_cents);
    }

    /* ============================== 3 ============================== */
    public function test_client_submitted_domain_price_cents_cannot_control_order_item_price(): void
    {
        $this->makeCatalog(10.00); // trusted price_cents = 1000 -- forged price below never matches it
        $this->fakeAvailability();
        $this->fakePaymentManager();
        $client = $this->makeClient();
        $template = $this->makeTemplate();

        // TLD-3H.3C.6A -- corrected to the proven, pre-existing production contract
        // (CheckoutController::process()'s price-changed-since-cart guard, unmodified by any
        // TLD-3H.3C phase): a client-submitted domain price that does not match the live
        // DomainPricingService quote is rejected outright -- HTTP 409, price_changed=true --
        // BEFORE any Order/Invoice/OrderItem/InvoiceItem is ever created. It is never silently
        // replaced with the trusted price and allowed through. This is the stronger, actually
        // correct proof that a client-controlled price can never determine the purchased
        // price: a forged submission cannot even produce a checkout, let alone a wrong one.
        // checkoutCombined() is not used here because it asserts a 200 success internally.
        $response = $this->actingAs($client, 'client')->postJson(
            route('checkout.process', ['template_id' => $template->id]),
            [
                'domain' => 'forged-price.com',
                'domain_option' => 'register',
                'domain_price_cents' => 999999,
                'items' => [[
                    'domain' => 'forged-price.com',
                    'option' => 'register',
                    'price_cents' => 999999,
                ]],
            ],
        );

        $response->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('price_changed', true);

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(0, OrderItem::query()->count());
        $this->assertSame(0, InvoiceItem::query()->count());
    }

    /* ============================== 4 ============================== */
    public function test_scalar_domain_fields_plus_items_do_not_create_duplicate_order_items(): void
    {
        $this->makeCatalog(10.00);
        $this->fakeAvailability();
        $this->fakePaymentManager();
        $client = $this->makeClient();
        $template = $this->makeTemplate();

        [$order] = $this->checkoutCombined($client, $template, 'no-duplicate.com');

        $this->assertSame(1, OrderItem::query()->count());
        $this->assertSame(1, $order->items->count());
    }

    /* ============================== 5 ============================== */
    public function test_real_domain_remains_provisionable_after_settlement(): void
    {
        // TLD-3H.3C.7 -- makeTemplate() now links a real Plan, so settlement legitimately
        // creates and provisions a Subscription (ProvisionSubscription -> TenantProvisioningService
        // -> real WHM). Queue::fake() isolates that established external boundary only (same
        // pattern already proven in TLD-3H.3C.6B) -- InvoiceSettlementService/OrderActivationService
        // still run for real, and no live WHM call is made.
        \Illuminate\Support\Facades\Queue::fake();
        $this->makeCatalog(10.00);
        $this->fakeAvailability();
        $this->fakePaymentManager();
        $client = $this->makeClient();
        $template = $this->makeTemplate(); // no discount configured -- keeps this test inside
        // TLD-3H.3C.3's scope (Section 10 explicitly excludes discounted Template/Plan settlement)

        [$order, $invoice] = $this->checkoutCombined($client, $template, 'provisionable.com');

        // TLD-3H.3C.6B correction: checkoutCombined() -> CheckoutController::process() always
        // starts a real hosted payment session for a non-domain-only checkout
        // (PaymentSessionStarter::start()), which claims the invoice (payment_session_status =
        // creating/ready, payment_session_attempt_id set to a real PaymentAttempt). Unlike
        // OrderBackedInvoiceFinancialIntegrityTest/InvoiceSettlementActivationIdempotencyTest's
        // hand-built invoices (which never claim a session and so legitimately settle with a
        // null PaymentAttempt), this fixture's invoice DOES have an active claimed session --
        // markPaid() must be given the real owning PaymentAttempt to satisfy
        // assertPaymentSessionOwnsSettlement(), exactly as production's gateway-settlement path
        // does (same correction already proven in TLD-3H.3C.6/6A). No live/Enom/Namecheap call:
        // registerDomainWithProvider() is overridden below.
        $attempt = PaymentAttempt::query()->where('invoice_id', $invoice->id)->sole();

        $registrar = $this->spyRegistrar();
        $settlement = new InvoiceSettlementService(new OrderActivationService($registrar));
        $settlement->markPaid($invoice->fresh(), null, $attempt);

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
        $this->assertSame(1, $registrar->registerCalls);
        $this->assertSame(0, $registrar->renewCalls);
        $this->assertSame('provisionable.com', $registrar->lastDomain);
    }

    /* ============================== 6 ============================== */
    public function test_subdomain_selection_remains_zero_price_and_non_provisionable(): void
    {
        $this->fakePaymentManager();
        $client = $this->makeClient();
        $template = $this->makeTemplate();

        // Real JS behaviour for a subdomain pick (checkout.blade.php): domain/domain_option are
        // set, but NO items[] payload is ever built for a non-'register' option (items[] is
        // register-only -- process() hard-rejects any other item_option in items[]). No domain
        // catalog/availability faking needed: this path never calls DomainPricingService.
        $response = $this->actingAs($client, 'client')->postJson(
            route('checkout.process', ['template_id' => $template->id]),
            [
                'domain' => 'my-subdomain-pick',
                'domain_option' => 'subdomain',
            ],
        );

        $response->assertOk()->assertJsonPath('success', true);

        $order = Order::query()->sole();
        $domainItem = $order->items()->whereNotNull('domain')->sole();

        $this->assertSame('subdomain', $domainItem->item_option);
        $this->assertSame(0, (int) $domainItem->price_cents);

        // Never turned into a registrar registration item -- OrderActivationService's
        // provisionable gate only fires for item_option in ['register', 'renew'].
        $this->assertNotContains(strtolower((string) $domainItem->item_option), ['register', 'renew']);

        // No domain InvoiceItem is created for a subdomain -- this test intentionally stops
        // before settlement (see file docblock: a separate, pre-existing validator gap around
        // non-provisionable domain-filled OrderItems is out of scope for TLD-3H.3C.3 and is
        // reported separately, not exercised or fixed here).
        $invoice = Invoice::query()->sole();
        $this->assertSame(0, $invoice->items()->where('item_type', 'domain')->count());
    }

    /* ============================== 7 ============================== */
    public function test_multiple_billed_domains_all_get_matching_order_items(): void
    {
        $this->makeCatalog(10.00);
        $this->fakeAvailability();
        $this->fakePaymentManager();
        $client = $this->makeClient();
        $template = $this->makeTemplate();

        // TLD-3H.3C.6 correction: submit each domain's ACTUAL current trusted quote (not a
        // hardcoded forged value) -- see checkoutCombined()'s docblock above for why a mismatch
        // is rejected with 409 rather than silently substituted.
        $pricing = app(\App\Services\Domains\DomainPricingService::class);
        [$order, $invoice] = $this->checkoutCombined($client, $template, 'multi-one.com', [
            'items' => [
                ['domain' => 'multi-one.com', 'option' => 'register', 'price_cents' => $pricing->registrationQuoteForDomain('multi-one.com')['price_cents']],
                ['domain' => 'multi-two.com', 'option' => 'register', 'price_cents' => $pricing->registrationQuoteForDomain('multi-two.com')['price_cents']],
            ],
        ]);

        $domainOrderItems = $order->items->filter(fn ($i) => filled($i->domain))->values();
        $domainInvoiceItems = $invoice->items->where('item_type', 'domain')->values();

        // Section 5 decision (Option A): every accepted billable domain gets its own matching,
        // trusted-priced, provisionable OrderItem -- no billed domain is ever left without one.
        $this->assertSame(2, $domainOrderItems->count());
        $this->assertSame(2, $domainInvoiceItems->count());
        $this->assertSame(
            (int) $domainOrderItems->sum('price_cents'),
            (int) $domainInvoiceItems->sum('total_cents'),
        );
        foreach ($domainOrderItems as $item) {
            $this->assertSame(1000, (int) $item->price_cents);
            $this->assertSame('register', $item->item_option);
        }
    }

    /* ============================== 8 ============================== */
    public function test_combined_non_discounted_checkout_satisfies_domain_financial_integrity(): void
    {
        // TLD-3H.3C.7 -- same real-Plan settlement boundary as
        // test_real_domain_remains_provisionable_after_settlement above.
        \Illuminate\Support\Facades\Queue::fake();
        $this->makeCatalog(10.00);
        $this->fakeAvailability();
        $this->fakePaymentManager();
        $client = $this->makeClient();
        $template = $this->makeTemplate(2500); // no discount_price_cents set -> $showDiscount = false

        [$order, $invoice] = $this->checkoutCombined($client, $template, 'financial-check.com');

        $domainOrderItem = $order->items->sole();
        $domainInvoiceItem = $invoice->items->where('item_type', 'domain')->sole();
        $subscriptionInvoiceItem = $invoice->items->where('item_type', 'subscription')->sole();

        // Domain portion reconciles exactly (this is what TLD-3H.3C.3 fixes).
        $this->assertSame((int) $domainOrderItem->price_cents, (int) $domainInvoiceItem->total_cents);

        // With no discount active, subtotal_cents also equals sum(items) -- proving this
        // specific non-discounted invoice already satisfies the FULL TLD-3H.3C.3 contract
        // (not just the domain portion) without touching the validator itself.
        $expectedSubtotal = (int) $subscriptionInvoiceItem->total_cents + (int) $domainInvoiceItem->total_cents;
        $this->assertSame($expectedSubtotal, (int) $invoice->subtotal_cents);
        $this->assertSame($expectedSubtotal, (int) $invoice->total_cents);

        // TLD-3H.3C.7 -- prove the valid Template+Plan checkout contract in full: a real
        // Subscription was created (by CheckoutController itself, not this fixture), and the
        // subscription InvoiceItem's reference_id points at it -- the exact link
        // InvoiceSettlementService::assertOrderBackedFinancialIntegrity() requires.
        $subscription = Subscription::query()->sole();
        $this->assertSame($subscription->id, (int) $subscriptionInvoiceItem->reference_id);
        $this->assertSame((int) $subscriptionInvoiceItem->unit_price_cents, (int) $subscription->price_cents);

        // Settling it end-to-end proves the validator (untouched by this phase) actually
        // accepts this contract -- not just that the arithmetic happens to line up.
        //
        // TLD-3H.3C.6B correction: this combined checkout also starts a real hosted payment
        // session (see test_real_domain_remains_provisionable_after_settlement above for the
        // full rationale) -- pass the real owning PaymentAttempt rather than null.
        $attempt = PaymentAttempt::query()->where('invoice_id', $invoice->id)->sole();

        $registrar = $this->spyRegistrar();
        $settlement = new InvoiceSettlementService(new OrderActivationService($registrar));
        $settlement->markPaid($invoice->fresh(), null, $attempt);

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
    }

    /* ============================== 9 ============================== */
    public function test_combined_checkout_leaves_fingerprint_semantics_untouched(): void
    {
        $this->makeCatalog(10.00);
        $this->fakeAvailability();
        $this->fakePaymentManager();
        $client = $this->makeClient();
        $template = $this->makeTemplate();

        // buildCheckoutFingerprint() is only ever invoked for the domain-only branch
        // ($isDomainOnly ? buildCheckoutFingerprint(...) : null) -- confirmed unchanged by this
        // phase's diff. A combined subscription/template + domain order must still get a null
        // fingerprint, exactly as before TLD-3H.3C.3, so idempotency there is unaffected.
        [$order] = $this->checkoutCombined($client, $template, 'fingerprint-check.com');

        $this->assertNull($order->checkout_fingerprint);

        // A second, independent combined checkout for a different domain must not collide on
        // any fingerprint-based uniqueness constraint (since none applies here).
        [$order2] = $this->checkoutCombined($client, $this->makeTemplate(), 'fingerprint-check-2.com');
        $this->assertNull($order2->checkout_fingerprint);
        $this->assertNotSame($order->id, $order2->id);
    }

    /* ============================== 10 ============================== */
    public function test_domain_only_checkout_order_item_pricing_remains_unchanged(): void
    {
        $this->makeCatalog(10.00);
        $this->fakeAvailability();
        $this->fakePaymentManager();
        $client = $this->makeClient();

        // isDomainOnly branch (checkout.cart.process -> process() with no template/plan) was
        // not touched by this phase's diff -- proves it still behaves exactly as before.
        //
        // TLD-3H.3C.6A -- corrected to the proven, pre-existing production contract: a forged
        // price_cents that does not match the live DomainPricingService quote is rejected
        // outright (HTTP 409, price_changed=true), never silently substituted. No Order,
        // Invoice, OrderItem, or InvoiceItem is created for a rejected checkout.
        $response = $this->actingAs($client, 'client')->postJson(route('checkout.cart.process'), [
            'items' => [[
                'domain' => 'domain-only-unchanged.com',
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

    /* ============================== 11 ============================== */
    /**
     * TLD-3H.3C.7 regression -- A: combined Template + real domain checkout, plan-less Template.
     * CheckoutController::process()'s new fail-closed guard must reject this before any
     * financial record is created, even though a real, correctly-priced domain is attached.
     */
    public function test_combined_checkout_with_plan_less_template_is_rejected_before_any_financial_record(): void
    {
        $this->makeCatalog(10.00);
        $this->fakeAvailability();
        $this->fakePaymentManager();
        $client = $this->makeClient();
        $template = $this->makeTemplateWithoutPlan();
        $domain = 'plan-less-combined.com';
        $trustedPriceCents = app(\App\Services\Domains\DomainPricingService::class)
            ->registrationQuoteForDomain($domain)['price_cents'] ?? 1;

        $response = $this->actingAs($client, 'client')->postJson(
            route('checkout.process', ['template_id' => $template->id]),
            [
                'domain' => $domain,
                'domain_option' => 'register',
                'domain_price_cents' => $trustedPriceCents,
                'items' => [[
                    'domain' => $domain,
                    'option' => 'register',
                    'price_cents' => $trustedPriceCents,
                ]],
            ],
        );

        $response->assertStatus(422)->assertJsonPath('success', false);

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, OrderItem::query()->count());
        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(0, InvoiceItem::query()->count());
        $this->assertSame(0, Subscription::query()->count());
        $this->assertSame(0, PaymentAttempt::query()->count());
    }

    /* ============================== 12 ============================== */
    /**
     * TLD-3H.3C.7 regression -- B: template/subscription-only checkout (no domain at all,
     * mirroring CombinedCheckoutDiscountContractTest::checkoutTemplateOnly()'s shape), plan-less
     * Template. Same fail-closed guard, same zero-side-effect requirement -- proves the guard is
     * not accidentally scoped only to the combined-with-domain branch.
     */
    public function test_template_only_checkout_with_plan_less_template_is_rejected_before_any_financial_record(): void
    {
        $this->fakePaymentManager();
        $client = $this->makeClient();
        $template = $this->makeTemplateWithoutPlan();

        $response = $this->actingAs($client, 'client')->postJson(
            route('checkout.process', ['template_id' => $template->id]),
            [],
        );

        $response->assertStatus(422)->assertJsonPath('success', false);

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, OrderItem::query()->count());
        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(0, InvoiceItem::query()->count());
        $this->assertSame(0, Subscription::query()->count());
        $this->assertSame(0, PaymentAttempt::query()->count());
    }

    // -----------------------------------------------------------------
    // Fixtures
    // -----------------------------------------------------------------

    private function makeClient(): Client
    {
        return Client::query()->create([
            'first_name' => 'Combined',
            'last_name' => 'DomainOrder',
            'email' => uniqid('combined_domain_order_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Combined Domain Order Test',
            'can_login' => true,
        ]);
    }

    private function makeCatalog(float $sale): array
    {
        $provider = DomainProvider::query()->create([
            'name' => 'TLD3H3C3 Provider ' . uniqid(),
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

        $price = $tld->prices()->create([
            'action' => 'register',
            'years' => 1,
            'sale' => $sale,
            'cost' => max(0.01, $sale - 3),
        ]);

        return [$provider, $tld, $price];
    }

    /**
     * TLD-3H.3C.7 -- every real Template checkout in production now requires that Template to
     * resolve to a real Plan (CheckoutController::process()'s fail-closed guard, added this
     * phase) before any Order/Invoice/Subscription record is created. This helper is used by
     * every checkoutCombined()/direct-template-checkout call in this file, so it links a real
     * Plan by default, mirroring production's actual Template->plan() relationship exactly --
     * the same working pattern already proven by CombinedCheckoutDiscountContractTest::
     * makePlanForTemplate(). CheckoutController itself creates the resulting Subscription; nothing
     * here hand-builds one. Tests that specifically need to prove the plan-less-template
     * rejection path use makeTemplateWithoutPlan() below instead.
     */
    private function makeTemplate(int $priceCents = 2500): Template
    {
        $plan = Plan::query()->create([
            'name' => 'TLD3H3C3 Plan ' . uniqid(),
            'slug' => 'tld3h3c3-plan-' . uniqid(),
            'plan_type' => Plan::TYPE_HOSTING,
            'monthly_price_cents' => 0,
            'annual_price_cents' => 0,
            'is_active' => true,
        ]);

        $categoryId = DB::table('category_templates')->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Template::query()->create([
            'category_template_id' => $categoryId,
            'plan_id' => $plan->id,
            'price_cents' => $priceCents,
            // discount_price_cents intentionally left unset -- TLD-3H.3C.3 does not touch the
            // discounted Template/Plan blocker, so every test in this file stays non-discounted.
            'image' => 'combined-test.jpg',
            'rating' => 0,
        ]);
    }

    /**
     * TLD-3H.3C.7 -- deliberately WITHOUT a linked Plan (plan_id left null), reproducing the
     * confirmed-architecturally-reachable production shape (templates.plan_id is nullable;
     * the column was added to an already-existing table via migration, so any Template row
     * that predates it -- or one whose linked Plan was deleted, nullOnDelete() -- legitimately
     * has plan_id = NULL; the admin Template create/update form requires a Plan going forward,
     * but does nothing for a row that already exists). Used only by the plan-less-rejection
     * regression tests below -- CheckoutController::process()'s new fail-closed guard must
     * reject any checkout that treats this Template as a subscription/template product, before
     * any financial record is created.
     */
    private function makeTemplateWithoutPlan(int $priceCents = 2500): Template
    {
        $categoryId = DB::table('category_templates')->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Template::query()->create([
            'category_template_id' => $categoryId,
            'price_cents' => $priceCents,
            'image' => 'combined-test.jpg',
            'rating' => 0,
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

    private function fakePaymentManager(): void
    {
        // TLD-3H.3C.6D -- new PaymentSession(...) was previously constructed ONCE, at mock-setup
        // time, and handed to a single ->andReturn(...) -- so every createSession() call across
        // the whole test (this file's fingerprint test makes two, for two separate checkouts)
        // returned the exact same PaymentSession object, including the same gateway_session_id.
        // PaymentSessionStarter persists that id onto a real PaymentAttempt row, and
        // payment_attempts has a real UNIQUE(gateway, gateway_session_id) constraint (matching
        // the real gateway's contract: a session id must be unique per gateway) -- so a second,
        // legitimate, independent checkout collided on it. andReturnUsing() defers evaluation to
        // each actual invocation, so every createSession() call builds its own PaymentSession
        // with a fresh uniqid() -- one per real, distinct hosted session, exactly matching what
        // a real gateway does. Established pattern already used the same way in
        // CheckoutPaymentTest/CurrencySourceOfTruthTest/InvoiceCheckoutPaymentAttemptTest.
        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('name')->andReturn('lahza');
        $gateway->shouldReceive('createSession')->zeroOrMoreTimes()->andReturnUsing(
            fn () => new PaymentSession('combined-session-' . uniqid('', true), 'https://pay.test/combined-session')
        );

        $manager = Mockery::mock(PaymentManager::class);
        $manager->shouldReceive('isEnabled')->andReturnTrue();
        $manager->shouldReceive('gateway')->andReturn($gateway);
        $this->app->instance(PaymentManager::class, $manager);
    }

    /**
     * Mirrors the exact request shape checkout.blade.php's updateDomainFieldsFromSelection()
     * builds for a 'register' domain pick during a template/subscription checkout: scalar
     * domain/domain_option/domain_price_cents fields AND items[0][domain/option/price_cents],
     * both submitted together (see TLD-3H.3C.2 audit, line ~1464 "// items[0] للباك إند").
     *
     * TLD-3H.3C.6 correction: CheckoutController::process()'s pre-existing "price changed since
     * added to cart" guard (unrelated to and unmodified by TLD-3H.3C.3/3H.3C.5/3H.3C.4B --
     * confirmed by fresh source read and git diff) rejects with HTTP 409 whenever submitted
     * price_cents !== the freshly-recomputed DomainPricingService quote; it never silently
     * substitutes the trusted price and continues. The default payload here must therefore
     * submit the domain's ACTUAL current trusted quote (recomputed live, the same way the
     * controller itself will a moment later) so the default combined-checkout path succeeds
     * exactly like a real client re-confirming an unchanged price. This still proves
     * price_cents is never taken on faith: it is independently recomputed server-side and
     * compared, and every assertion in this file checks the STORED OrderItem/InvoiceItem price
     * against the trusted quote value, never against whatever the client submitted. A caller
     * that wants to prove a mismatched/forged submission is rejected should override 'items'/
     * 'domain_price_cents' explicitly and assert the resulting 409, not call this default.
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

    /**
     * Anonymous subclass overriding only the external-network boundary methods
     * (registerDomainWithProvider/renewDomainWithProvider) -- mirrors the exact convention
     * already established in OrderBackedInvoiceFinancialIntegrityTest/
     * InvoiceSettlementActivationIdempotencyTest. No live registrar/Enom/Namecheap call is ever
     * made. provisionOrderItem() defers the real call to DB::afterCommit(), which fires
     * synchronously once markPaid()'s own transaction commits (no queue/job involved), so
     * asserting call counts immediately after markPaid() returns is reliable.
     */
    private function spyRegistrar(): RegistrarProvisioningService
    {
        return new class extends RegistrarProvisioningService {
            public int $registerCalls = 0;
            public int $renewCalls = 0;
            public ?string $lastDomain = null;
            public ?string $lastItemOption = null;

            public function __construct()
            {
            }

            protected function registerDomainWithProvider(\App\Models\DomainProvider $provider, \App\Models\Domain $domain, array $context, array $contact): array
            {
                $this->registerCalls++;
                $this->lastDomain = $domain->domain_name;
                $this->lastItemOption = 'register';

                return ['ok' => true, 'reason' => 'ok', 'cid' => 'X'];
            }

            protected function renewDomainWithProvider(\App\Models\DomainProvider $provider, \App\Models\Domain $domain, array $context): array
            {
                $this->renewCalls++;
                $this->lastDomain = $domain->domain_name;
                $this->lastItemOption = 'renew';

                return ['ok' => true, 'cid' => 'X', 'provider_reference' => 'X', 'provider_domain_id' => 'X'];
            }
        };
    }
}
