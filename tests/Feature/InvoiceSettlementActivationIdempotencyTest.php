<?php

namespace Tests\Feature;

use App\Jobs\ProvisionSubscription;
use App\Models\Client;
use App\Models\Coupon;
use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\Plan;
use App\Models\Server;
use App\Models\Tenancy\Subscription;
use App\Services\Billing\InvoiceSettlementService;
use App\Services\Billing\OrderActivationService;
use App\Services\Domains\RegistrarProvisioningService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * TLD-3H.3A — Invoice Settlement -> Order Activation Idempotency.
 *
 * Closes Stop Gate #6 from the TLD-3H.3 audit: InvoiceSettlementService::markPaid() used to call
 * OrderActivationService::activate() unconditionally whenever any not-yet-paid invoice on a given
 * Order settled, even if that Order was already active — risking a duplicate subscription-cycle
 * extension / re-dispatched provisioning job / re-run registrar Extend, none of which the
 * OrderController-level guard (TLD-3H.2C) or the OrderItem-level renewal claim guard (TLD-3H.2A)
 * protect against, because markPaid() never goes through OrderController at all.
 *
 * The fix captures whether the Order was already ACTIVE *before* this settlement mutates
 * anything, and skips the activate() call entirely when it was — while the invoice itself always
 * still settles (status/paid_date are unconditionally written beforehand). This mirrors the
 * already-accepted active -> active guard pattern from AdminOrderActivationIdempotencyTest
 * (TLD-3H.2C), applied at the one boundary that actually owns this call: markPaid() itself.
 *
 * No real registrar/Enom network call is made anywhere in this file: domain_renewal scenarios use
 * the same renewDomainWithProvider()-only override pattern already established in
 * RenewalProvisioningIdempotencyTest (TLD-3H.2A), so a live Extend is structurally impossible here
 * regardless of outcome.
 */
class InvoiceSettlementActivationIdempotencyTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    /* ============================== 1 ============================== */
    // unpaid invoice + pending order -> activate() runs exactly once.
    public function test_unpaid_pending_order_activates_exactly_once(): void
    {
        [$order, $invoice, $subscription] = $this->makeSubscriptionOrderWithInvoice(Order::STATUS_PENDING, 'pending');
        Queue::fake();

        $this->app->make(InvoiceSettlementService::class)->markPaid($invoice);

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
        $this->assertSame('active', $subscription->fresh()->status);
        Queue::assertPushed(ProvisionSubscription::class, 1);
    }

    /* ============================== 2 ============================== */
    // unpaid invoice + already-active order -> zero activate() calls, invoice still settles.
    public function test_unpaid_invoice_on_already_active_order_does_not_reactivate(): void
    {
        [$order, $invoice, $subscription] = $this->makeSubscriptionOrderWithInvoice(Order::STATUS_ACTIVE, 'pending');
        Queue::fake();

        $this->app->make(InvoiceSettlementService::class)->markPaid($invoice);

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->paid_date);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
        // The subscription was never activated because activate() was skipped entirely.
        $this->assertSame('pending', $subscription->fresh()->status);
        Queue::assertNothingPushed();
    }

    /* ============================== 3 ============================== */
    // Settling the same invoice twice remains a no-op (pre-existing guard, still intact).
    public function test_settling_the_same_invoice_twice_is_a_no_op(): void
    {
        [$order, $invoice, $subscription] = $this->makeSubscriptionOrderWithInvoice(Order::STATUS_PENDING, 'pending');
        Queue::fake();
        $settlement = $this->app->make(InvoiceSettlementService::class);

        $settlement->markPaid($invoice);
        Queue::assertPushed(ProvisionSubscription::class, 1);

        $settlement->markPaid($invoice);

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
        Queue::assertPushed(ProvisionSubscription::class, 1);
    }

    /* ============================== 4 ============================== */
    // Two invoices on the same order: the first activates, the second does not re-activate.
    public function test_two_invoices_on_same_order_first_activates_second_does_not_reactivate(): void
    {
        [$order, $invoiceA, $invoiceB, $subscription] = $this->makeOrderWithTwoInvoicesOneSubscriptionItem();
        Queue::fake();
        $settlement = $this->app->make(InvoiceSettlementService::class);

        $settlement->markPaid($invoiceA);

        $this->assertSame('paid', $invoiceA->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
        $this->assertSame('active', $subscription->fresh()->status);
        // activate()'s own draft-opening side effect already opened invoiceB on this first call.
        $this->assertSame('unpaid', $invoiceB->fresh()->status);
        Queue::assertPushed(ProvisionSubscription::class, 1);

        $settlement->markPaid($invoiceB);

        $this->assertSame('paid', $invoiceB->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
        // Still exactly one dispatch total: the second invoice did not re-activate.
        Queue::assertPushed(ProvisionSubscription::class, 1);
    }

    /* ============================== 5 (task's #7) ============================== */
    // A second invoice settlement on the same order cannot extend the subscription dates again.
    public function test_second_invoice_settlement_cannot_extend_subscription_dates_again(): void
    {
        [$order, $invoiceA, $invoiceB, $subscription] = $this->makeOrderWithTwoInvoicesOneSubscriptionItem();
        Queue::fake();
        $settlement = $this->app->make(InvoiceSettlementService::class);

        $settlement->markPaid($invoiceA);

        $subscription = $subscription->fresh();
        $lifecycle = [
            'starts_at' => $subscription->getRawOriginal('starts_at'),
            'ends_at' => $subscription->getRawOriginal('ends_at'),
            'next_due_date' => $subscription->getRawOriginal('next_due_date'),
        ];

        $settlement->markPaid($invoiceB);

        $this->assertSame('paid', $invoiceB->fresh()->status);
        $this->assertSame($lifecycle, [
            'starts_at' => $subscription->fresh()->getRawOriginal('starts_at'),
            'ends_at' => $subscription->fresh()->getRawOriginal('ends_at'),
            'next_due_date' => $subscription->fresh()->getRawOriginal('next_due_date'),
        ]);
        Queue::assertPushed(ProvisionSubscription::class, 1);
    }

    /* ============================== 6 ============================== */
    // domain_renewal order, pending -> paid -> activate() (and the registrar renew) runs exactly once.
    public function test_domain_renewal_pending_order_activates_exactly_once(): void
    {
        [$order, , , , $invoice] = $this->makeRenewalOrderWithInvoice(Order::STATUS_PENDING);
        $registrar = $this->fakeRenewalService(true);
        $settlement = new InvoiceSettlementService(new OrderActivationService($registrar));

        $settlement->markPaid($invoice);

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
        $this->assertSame(1, $registrar->renewCalls);
        $this->assertSame(0, $registrar->registerCalls);
    }

    /* ============================== 7 (task's #6) ============================== */
    // domain_renewal order already active + another unpaid invoice -> zero second provisioning/Extend.
    public function test_domain_renewal_already_active_order_sends_zero_second_extend(): void
    {
        [$order, , , , $firstInvoice, $secondInvoice] = $this->makeRenewalOrderWithInvoice(
            Order::STATUS_ACTIVE,
            withSecondUnpaidInvoice: true,
        );
        // Simulate that the first invoice's settlement already happened in the past.
        $firstInvoice->update(['status' => 'paid', 'paid_date' => now()->subDay()]);

        $registrar = $this->fakeRenewalService(true);
        $settlement = new InvoiceSettlementService(new OrderActivationService($registrar));

        $settlement->markPaid($secondInvoice);

        $this->assertSame('paid', $secondInvoice->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
        // activate() was never invoked at all, so the registrar boundary was never reached.
        $this->assertSame(0, $registrar->renewCalls);
        $this->assertSame(0, $registrar->registerCalls);
    }

    /* ============================== 8 ============================== */
    // markPaid()'s lockForUpdate()/early-return idempotency guard remains intact and unmodified.
    public function test_lock_for_update_guard_remains_intact(): void
    {
        $source = file_get_contents(app_path('Services/Billing/InvoiceSettlementService.php'));
        $this->assertStringContainsString('lockForUpdate()', $source);
        $this->assertStringContainsString("\$lockedInvoice->status === 'paid'", $source);
        $this->assertStringContainsString('DB::transaction(function', $source);

        [$order, $invoice, $subscription] = $this->makeSubscriptionOrderWithInvoice(Order::STATUS_PENDING, 'pending', withCoupon: true);
        Queue::fake();
        $settlement = $this->app->make(InvoiceSettlementService::class);
        $coupon = $invoice->fresh()->coupon;

        $settlement->markPaid($invoice);
        $settlement->markPaid($invoice);
        $settlement->markPaid($invoice);

        $this->assertSame(1, $coupon->fresh()->used_count);
        Queue::assertPushed(ProvisionSubscription::class, 1);
    }

    /* ============================== 9 ============================== */
    // Gateway (real PaymentAttempt) and admin_manual (null) paths obey the identical activation rule.
    public function test_gateway_and_admin_manual_paths_obey_identical_activation_rule(): void
    {
        [$adminOrder, $adminInvoice, $adminSubscription] = $this->makeSubscriptionOrderWithInvoice(Order::STATUS_ACTIVE, 'pending');
        [$gatewayOrder, $gatewayInvoice, $gatewaySubscription] = $this->makeSubscriptionOrderWithInvoice(Order::STATUS_ACTIVE, 'pending');
        $attempt = $this->claimHostedSession($gatewayInvoice, Invoice::PAYMENT_SESSION_READY);
        Queue::fake();
        $settlement = $this->app->make(InvoiceSettlementService::class);

        $settlement->markPaid($adminInvoice, 'admin_manual', null);
        $settlement->markPaid($gatewayInvoice, 'stripe', $attempt);

        $this->assertSame('paid', $adminInvoice->fresh()->status);
        $this->assertSame('paid', $gatewayInvoice->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $adminOrder->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $gatewayOrder->fresh()->status);
        $this->assertSame('pending', $adminSubscription->fresh()->status);
        $this->assertSame('pending', $gatewaySubscription->fresh()->status);
        Queue::assertNothingPushed();
    }

    // -----------------------------------------------------------------
    // Fixtures
    // -----------------------------------------------------------------

    private function makeSubscriptionOrderWithInvoice(
        string $orderStatus,
        string $subscriptionStatus,
        bool $withCoupon = false,
    ): array {
        $client = $this->makeClient();
        $subscription = $this->makeSubscription($client, $subscriptionStatus);

        $order = Order::query()->create([
            'client_id' => $client->id,
            'status' => $orderStatus,
            'type' => 'subscription',
        ]);

        $coupon = $withCoupon ? Coupon::query()->create([
            'code' => uniqid('TLD3H3A-', false),
            'discount_type' => 'fixed',
            'discount_value' => 1,
            'used_count' => 0,
            'is_active' => true,
        ]) : null;

        $invoice = Invoice::query()->create([
            'client_id' => $client->id,
            'order_id' => $order->id,
            'coupon_id' => $coupon?->id,
            'number' => 'INV-' . strtoupper(uniqid()),
            'status' => 'unpaid',
            'subtotal_cents' => 1000,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 1000,
            'currency' => 'USD',
            'payment_session_status' => Invoice::PAYMENT_SESSION_IDLE,
        ]);

        InvoiceItem::query()->create([
            'invoice_id' => $invoice->id,
            'item_type' => 'subscription',
            'reference_id' => $subscription->id,
            'description' => 'TLD-3H.3A settlement subscription',
            'qty' => 1,
            'unit_price_cents' => 1000,
            'total_cents' => 1000,
        ]);

        return [$order, $invoice, $subscription];
    }

    private function makeOrderWithTwoInvoicesOneSubscriptionItem(): array
    {
        [$order, $invoiceA, $subscription] = $this->makeSubscriptionOrderWithInvoice(Order::STATUS_PENDING, 'pending');

        // TLD-3H.3C.1 -- invoiceB is settled by some of this fixture's tests, so (per the
        // TLD-3H.3C invariant) it must independently be a valid financial projection: its
        // subscription InvoiceItem's unit_price_cents must equal the referenced
        // Subscription's price_cents (1000, per makeSubscription() below) exactly, and the
        // invoice's own subtotal_cents/total_cents must reconcile with that item. Both
        // invoices reference the SAME $subscription -- a second invoice tied to the same
        // subscription on the same order is the scenario these tests are proving idempotency
        // for, so this is not a new/different item shape, only a corrected amount.
        $invoiceB = Invoice::query()->create([
            'client_id' => $order->client_id,
            'order_id' => $order->id,
            'number' => 'INV-B-' . strtoupper(uniqid()),
            'status' => 'draft',
            'subtotal_cents' => 1000,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 1000,
            'currency' => 'USD',
            'payment_session_status' => Invoice::PAYMENT_SESSION_IDLE,
        ]);

        InvoiceItem::query()->create([
            'invoice_id' => $invoiceB->id,
            'item_type' => 'subscription',
            'reference_id' => $subscription->id,
            'description' => 'TLD-3H.3A settlement subscription (second invoice)',
            'qty' => 1,
            'unit_price_cents' => 1000,
            'total_cents' => 1000,
        ]);

        return [$order, $invoiceA, $invoiceB->fresh(['items']), $subscription];
    }

    private function makeRenewalOrderWithInvoice(string $orderStatus, bool $withSecondUnpaidInvoice = false): array
    {
        $client = Client::query()->create([
            'first_name' => 'Renewal',
            'last_name' => 'Settlement',
            'email' => uniqid('renewal_settlement_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Renewal Settlement Test',
        ]);

        $provider = DomainProvider::query()->create([
            'name' => 'TLD-3H.3A Renewal Test Provider',
            'type' => 'enom',
            'username' => 'testuser',
            'password' => 'testpass',
            'is_active' => true,
            'mode' => 'live',
        ]);

        $domainName = 'tld3h3a-' . uniqid() . '.com';

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
            'status' => $orderStatus,
            'type' => 'domain_renewal',
        ]);

        $orderItem = $order->items()->create([
            'domain' => $domainName,
            'item_option' => 'renew',
            'price_cents' => 2000,
            'meta' => [
                'domain_id' => $domain->id,
                'current_renewal_date' => now()->toDateString(),
                'renewal_date' => now()->addYear()->toDateString(),
                'term_years' => 1,
                'provider_id' => $provider->id,
                'provider_type' => $provider->type,
                'provider_mode' => $provider->mode,
            ],
        ]);

        $invoice = Invoice::query()->create([
            'client_id' => $client->id,
            'order_id' => $order->id,
            'number' => 'INV-REN-' . strtoupper(uniqid()),
            'status' => 'unpaid',
            'subtotal_cents' => 2000,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 2000,
            'currency' => 'USD',
            'payment_session_status' => Invoice::PAYMENT_SESSION_IDLE,
        ]);

        // TLD-3H.3C.1 -- matches the real production contract in DomainRenewalService::
        // requestRenewal() exactly: item_type 'domain', reference_id the renewed Domain's
        // id (NOT null -- unlike DomainInvoiceItemBuilder's registration-only contract,
        // renewals always know the Domain row already), qty 1, unit_price_cents ==
        // total_cents == the OrderItem's price_cents (2000).
        $invoice->items()->create([
            'item_type' => 'domain',
            'reference_id' => $domain->id,
            'description' => 'Domain Renewal: ' . $domainName,
            'qty' => 1,
            'unit_price_cents' => 2000,
            'total_cents' => 2000,
        ]);

        $secondInvoice = null;
        if ($withSecondUnpaidInvoice) {
            $secondInvoice = Invoice::query()->create([
                'client_id' => $client->id,
                'order_id' => $order->id,
                'number' => 'INV-REN2-' . strtoupper(uniqid()),
                'status' => 'unpaid',
                'subtotal_cents' => 2000,
                'discount_cents' => 0,
                'tax_cents' => 0,
                'total_cents' => 2000,
                'currency' => 'USD',
                'payment_session_status' => Invoice::PAYMENT_SESSION_IDLE,
            ]);

            // Same reasoning as $invoice above: this second invoice is settled by
            // test_domain_renewal_already_active_order_sends_zero_second_extend, so it must
            // also independently be a valid financial projection of the same domain renewal.
            $secondInvoice->items()->create([
                'item_type' => 'domain',
                'reference_id' => $domain->id,
                'description' => 'Domain Renewal: ' . $domainName,
                'qty' => 1,
                'unit_price_cents' => 2000,
                'total_cents' => 2000,
            ]);
        }

        return [$order->fresh(), $orderItem, $domain, $provider, $invoice->fresh(['items']), $secondInvoice?->fresh(['items'])];
    }

    /**
     * Anonymous subclass overriding only the renewDomainWithProvider()/registerDomainWithProvider()
     * external-registrar boundary — identical pattern to RenewalProvisioningIdempotencyTest
     * (TLD-3H.2A). No real network connection is made.
     */
    private function fakeRenewalService(bool $ok): RegistrarProvisioningService
    {
        return new class($ok) extends RegistrarProvisioningService {
            public int $renewCalls = 0;
            public int $registerCalls = 0;

            public function __construct(protected bool $ok)
            {
            }

            protected function renewDomainWithProvider(DomainProvider $provider, Domain $domain, array $context): array
            {
                $this->renewCalls++;

                if ($this->ok) {
                    return [
                        'ok' => true,
                        'cid' => 'TLD3H3A-FAKE-RENEW-CID',
                        'provider_reference' => 'TLD3H3A-FAKE-RENEW-REF',
                        'provider_domain_id' => 'TLD3H3A-FAKE-RENEW-DOMAIN',
                    ];
                }

                return ['ok' => false, 'reason' => 'provider_error', 'message' => 'Simulated renewal failure.'];
            }

            protected function registerDomainWithProvider(DomainProvider $provider, Domain $domain, array $context, array $contact): array
            {
                $this->registerCalls++;

                return ['ok' => true, 'reason' => 'ok', 'cid' => 'TLD3H3A-UNEXPECTED-REGISTER-CALL'];
            }
        };
    }

    private function claimHostedSession(Invoice $invoice, string $status): PaymentAttempt
    {
        $attempt = PaymentAttempt::query()->create([
            'invoice_id' => $invoice->id,
            'order_id' => $invoice->order_id,
            'client_id' => $invoice->client_id,
            'gateway' => 'stripe',
            'idempotency_key' => uniqid('tld3h3a-attempt-', true),
            'gateway_session_id' => uniqid('tld3h3a-session-', true),
            'gateway_amount_cents' => $invoice->total_cents,
            'currency' => $invoice->currency,
            'status' => PaymentAttempt::STATUS_INITIATED,
        ]);

        $invoice->update([
            'payment_session_status' => $status,
            'payment_session_attempt_id' => $attempt->id,
        ]);

        return $attempt;
    }

    private function makeClient(): Client
    {
        return Client::query()->create([
            'first_name' => 'TLD3H3A',
            'last_name' => 'Settlement',
            'email' => uniqid('tld3h3a_settlement_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'TLD-3H.3A Settlement Test',
        ]);
    }

    private function makeSubscription(Client $client, string $status): Subscription
    {
        $server = Server::query()->create([
            'name' => 'TLD3H3A Settlement WHM',
            'type' => 'cpanel',
            'hostname' => uniqid('whm-', false) . '.example.test',
            'username' => 'root',
            'api_token' => 'test-token',
            'is_active' => true,
        ]);
        $plan = Plan::query()->create([
            'name' => 'TLD3H3A Settlement Plan',
            'slug' => uniqid('tld3h3a-settlement-plan-', false),
            'plan_type' => Plan::TYPE_HOSTING,
            'server_id' => $server->id,
            'server_package' => 'tld3h3a_settlement_package',
            'is_active' => true,
        ]);

        return Subscription::query()->create([
            'client_id' => $client->id,
            'plan_id' => $plan->id,
            'status' => $status,
            'provisioning_status' => Subscription::PROVISIONING_PENDING,
            'price_cents' => 1000,
            'billing_cycle' => 'monthly',
            'username' => uniqid('tld3h3a', false),
            'server_id' => $server->id,
            'server_package' => 'tld3h3a_settlement_package',
            'domain_option' => 'subdomain',
            'domain_name' => uniqid('tld3h3a-', false) . '.example.test',
            'subdomain' => uniqid('tld3h3a-', false),
        ]);
    }
}
