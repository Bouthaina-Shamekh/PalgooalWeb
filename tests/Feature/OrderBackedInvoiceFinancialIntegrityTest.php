<?php

namespace Tests\Feature;

use App\Jobs\ProvisionSubscription;
use App\Models\Client;
use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\Plan;
use App\Models\Server;
use App\Models\Tenancy\Subscription;
use App\Models\User;
use App\Services\Billing\InvoiceSettlementService;
use App\Services\Billing\OrderActivationService;
use App\Services\Domains\RegistrarProvisioningService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

/**
 * TLD-3H.3C — Enforce Order-Backed Invoice Financial Integrity.
 *
 * Proves the invariant approved in TLD-3H.3B: an Invoice with order_id !== null is a billing
 * PROJECTION of its Order/OrderItems and its financial line items (description/qty/unit_price/
 * totals) must never be independently editable after creation — enforced in defense-in-depth:
 * the admin UI never renders editable fields for it (_form.blade.php), the controller ignores
 * any submitted 'items' payload for it regardless of what the request contains
 * (InvoiceController::update()), and InvoiceSettlementService::markPaid() fail-closed validates
 * the stored projection against the frozen Order/OrderItem (and, for subscription lines,
 * Subscription) contract before any financial mutation — so even a row corrupted by bypassing
 * both UI and controller (direct DB write) cannot settle.
 *
 * Currency is deliberately NOT compared anywhere in the new checks: TLD-3H.3B/3H.3C confirmed
 * neither `orders` nor `subscriptions` has ever had a currency column, so there is no reliable
 * generic Order-level currency invariant to validate against. This is a tracked, reported gap,
 * not a silently weakened check — see the TLD-3H.3C report. The pre-existing gateway-path
 * currency protection (assertPaymentSessionOwnsSettlement() comparing PaymentAttempt.currency to
 * Invoice.currency) is untouched by this phase and is covered here by a regression test proving
 * it still fires.
 */
class OrderBackedInvoiceFinancialIntegrityTest extends TestCase
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

        $this->actingAs(User::factory()->create(['super_admin' => true]));
    }

    /* ============================== 1 ============================== */
    // Standalone unpaid invoice remains fully editable (unchanged behavior).
    public function test_standalone_unpaid_invoice_remains_editable(): void
    {
        [, $invoice, $subscription] = $this->makeStandaloneSubscriptionInvoice();

        $this->put(route('dashboard.invoices.update', $invoice), $this->standalonePayload($subscription, [
            'description' => 'Corrected description',
            'qty' => 2,
            'unit_price_cents' => 2500,
        ]))->assertRedirect(route('dashboard.invoices.index'))->assertSessionHas('ok');

        $fresh = $invoice->fresh(['items']);
        $this->assertSame('Corrected description', $fresh->items->first()->description);
        $this->assertSame(2, $fresh->items->first()->qty);
        $this->assertSame(2500, $fresh->items->first()->unit_price_cents);
        $this->assertSame(5000, $fresh->total_cents);
    }

    /* ============================== 2-6 ============================== */
    // Order-backed domain_renewal invoice: description/qty/unit price cannot be altered, and
    // items cannot be added or removed, via the normal update() endpoint.
    public function test_domain_renewal_invoice_item_description_cannot_be_altered(): void
    {
        [$order, $invoice, $originalItem] = $this->makeDomainRenewalInvoice();

        $this->put(route('dashboard.invoices.update', $invoice), $this->orderBackedForgedPayload($originalItem, [
            'description' => 'FORGED DESCRIPTION',
        ]))->assertRedirect(route('dashboard.invoices.index'));

        $this->assertSame($originalItem->description, $invoice->fresh(['items'])->items->first()->description);
    }

    public function test_domain_renewal_invoice_item_qty_cannot_be_altered(): void
    {
        [$order, $invoice, $originalItem] = $this->makeDomainRenewalInvoice();

        $this->put(route('dashboard.invoices.update', $invoice), $this->orderBackedForgedPayload($originalItem, [
            'qty' => 99,
        ]))->assertRedirect(route('dashboard.invoices.index'));

        $this->assertSame(1, $invoice->fresh(['items'])->items->first()->qty);
    }

    public function test_domain_renewal_invoice_item_unit_price_cannot_be_altered(): void
    {
        [$order, $invoice, $originalItem] = $this->makeDomainRenewalInvoice();

        $this->put(route('dashboard.invoices.update', $invoice), $this->orderBackedForgedPayload($originalItem, [
            'unit_price_cents' => 1,
        ]))->assertRedirect(route('dashboard.invoices.index'));

        $fresh = $invoice->fresh(['items']);
        $this->assertSame($originalItem->unit_price_cents, $fresh->items->first()->unit_price_cents);
        $this->assertSame($originalItem->unit_price_cents, $fresh->total_cents);
    }

    public function test_domain_renewal_invoice_items_cannot_be_added_or_removed(): void
    {
        [$order, $invoice, $originalItem] = $this->makeDomainRenewalInvoice();

        $payload = $this->orderBackedForgedPayload($originalItem);
        $payload['items'][] = [
            'item_type' => 'domain',
            'reference_id' => $originalItem->reference_id,
            'description' => 'A second, forged line item',
            'qty' => 1,
            'unit_price_cents' => 500,
        ];

        $this->put(route('dashboard.invoices.update', $invoice), $payload)
            ->assertRedirect(route('dashboard.invoices.index'));

        $this->assertSame(1, $invoice->fresh(['items'])->items->count());
    }

    /* ============================== 6b ============================== */
    public function test_forged_patch_without_items_key_cannot_bypass_restriction(): void
    {
        [$order, $invoice, $originalItem] = $this->makeDomainRenewalInvoice();

        $this->put(route('dashboard.invoices.update', $invoice), [
            'status' => 'unpaid',
            'due_date' => now()->addDays(3)->toDateString(),
        ])->assertRedirect(route('dashboard.invoices.index'));

        $fresh = $invoice->fresh(['items']);
        $this->assertSame(1, $fresh->items->count());
        $this->assertSame($originalItem->description, $fresh->items->first()->description);
        $this->assertSame($originalItem->unit_price_cents, $fresh->items->first()->unit_price_cents);
    }

    /* ============================== 7 ============================== */
    public function test_due_date_on_order_backed_invoice_remains_editable(): void
    {
        [$order, $invoice, $originalItem] = $this->makeDomainRenewalInvoice();
        $newDueDate = now()->addDays(10)->toDateString();

        $this->put(route('dashboard.invoices.update', $invoice), $this->orderBackedForgedPayload($originalItem, [], [
            'due_date' => $newDueDate,
        ]))->assertRedirect(route('dashboard.invoices.index'));

        $this->assertSame($newDueDate, $invoice->fresh()->due_date->toDateString());
    }

    /* ============================== 8 ============================== */
    public function test_domain_renewal_status_paid_still_uses_settlement_service(): void
    {
        [$order, $invoice] = $this->makeDomainRenewalInvoice();

        // TLD-3H.3C test hygiene: InvoiceController resolves InvoiceSettlementService from the
        // container, so we cannot inject a fake registrar manually here. Bind a Mockery double
        // for the external-network boundary instead, matching the established convention in
        // AdminInvoiceSettlementTest::test_admin_settlement_keeps_domain_activation_delegated_to_order_activation_service.
        // No live/Enom call is made.
        $registrar = Mockery::mock(RegistrarProvisioningService::class);
        $registrar->shouldReceive('provisionOrderDomain')
            ->once()
            ->withArgs(fn (Order $activatedOrder, ?string $method) => $activatedOrder->id === $order->id)
            ->andReturn(['ok' => true]);
        $this->app->instance(RegistrarProvisioningService::class, $registrar);

        $this->put(route('dashboard.invoices.update', $invoice), array_merge(
            $this->orderBackedForgedPayload($invoice->items->first()),
            ['status' => 'paid'],
        ))->assertRedirect(route('dashboard.invoices.index'))->assertSessionHas('ok');

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->paid_date);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
    }

    /* ============================== 9 ============================== */
    public function test_paid_order_backed_invoice_remains_immutable(): void
    {
        // TLD-3H.3C test hygiene: use the spy-registrar fixture + manual construction (same
        // pattern as test_settlement_succeeds_when_domain_renewal_contract_matches) instead of
        // the container-resolved service, so this test never reaches a live registrar call.
        [$order, $invoice, , $registrar] = $this->makeDomainRenewalInvoiceWithSpyRegistrar();
        $settlement = new InvoiceSettlementService(new OrderActivationService($registrar));
        $settlement->markPaid($invoice->fresh());
        $this->assertSame('paid', $invoice->fresh()->status);

        $this->put(route('dashboard.invoices.update', $invoice), [
            'status' => 'unpaid',
            'due_date' => now()->addDay()->toDateString(),
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame('paid', $invoice->fresh()->status);
    }

    /* ============================== 10 (domain) ============================== */
    public function test_settlement_succeeds_when_domain_renewal_contract_matches(): void
    {
        [$order, $invoice, , $registrar] = $this->makeDomainRenewalInvoiceWithSpyRegistrar();
        $settlement = new InvoiceSettlementService(new OrderActivationService($registrar));

        $settlement->markPaid($invoice->fresh());

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
        $this->assertSame(1, $registrar->renewCalls);
    }

    /* ============================== 11 (domain) ============================== */
    public function test_settlement_rejects_corrupted_domain_renewal_amount(): void
    {
        [$order, $invoice, $item] = $this->makeDomainRenewalInvoice();

        DB::table('invoice_items')->where('id', $item->id)->update([
            'unit_price_cents' => 1,
            'total_cents' => 1,
        ]);

        try {
            app(InvoiceSettlementService::class)->markPaid($invoice->fresh());
            $this->fail('Expected corrupted domain renewal invoice to fail settlement.');
        } catch (\RuntimeException) {
        }

        $this->assertSame('unpaid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    /* ============================== 12/13/14 (domain) ============================== */
    public function test_failed_domain_integrity_leaves_invoice_unpaid_order_non_active_and_sends_no_renew_call(): void
    {
        [$order, $invoice, $item, $registrar] = $this->makeDomainRenewalInvoiceWithSpyRegistrar();

        DB::table('invoice_items')->where('id', $item->id)->update(['total_cents' => 999999]);

        $settlement = new InvoiceSettlementService(new \App\Services\Billing\OrderActivationService($registrar));

        try {
            $settlement->markPaid($invoice->fresh());
            $this->fail('Expected the corrupted invoice to fail settlement.');
        } catch (\RuntimeException) {
        }

        $this->assertSame('unpaid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
        $this->assertSame(0, $registrar->renewCalls);
    }

    /* ============================== 15 (domain_registration) ============================== */
    public function test_domain_registration_gets_the_same_protection(): void
    {
        [$order, $invoice, $item] = $this->makeDomainRegistrationInvoice();

        $this->put(route('dashboard.invoices.update', $invoice), $this->orderBackedForgedPayload($item, [
            'unit_price_cents' => 1,
        ]))->assertRedirect(route('dashboard.invoices.index'));
        $this->assertSame($item->unit_price_cents, $invoice->fresh(['items'])->items->first()->unit_price_cents);

        // TLD-3H.3C test hygiene: the successful-settlement path for a matching contract is
        // already covered independently by test_settlement_succeeds_when_domain_renewal_contract_matches()
        // and test_settlement_succeeds_when_subscription_contract_matches(), both of which use a
        // fake/spy registrar so no live provider call is ever made. This test keeps only the
        // assertions that never require the registrar to be reached: the forged-edit-ignored
        // assertion above, and the corrupted-invoice-fails-closed assertion below (which throws
        // inside assertOrderBackedFinancialIntegrity() before activate()/the registrar is ever
        // invoked, regardless of provider setup) -- this fixture deliberately creates no
        // DomainProvider, so reaching the registrar here would be a live-call risk, not a
        // realistic scenario.
        [$order2, $invoice2, $item2] = $this->makeDomainRegistrationInvoice();
        DB::table('invoice_items')->where('id', $item2->id)->update(['unit_price_cents' => 1, 'total_cents' => 1]);

        try {
            app(InvoiceSettlementService::class)->markPaid($invoice2->fresh());
            $this->fail('Expected corrupted domain registration invoice to fail settlement.');
        } catch (\RuntimeException) {
        }

        $this->assertSame('unpaid', $invoice2->fresh()->status);
        $this->assertSame(Order::STATUS_PENDING, $order2->fresh()->status);
    }

    /* ============================== 16 (subscription) ============================== */
    public function test_order_backed_subscription_invoice_gets_the_same_protection(): void
    {
        [$order, $invoice, $item, $subscription] = $this->makeSubscriptionOrderBackedInvoice();

        $this->put(route('dashboard.invoices.update', $invoice), $this->orderBackedForgedPayload($item, [
            'unit_price_cents' => 1,
        ]))->assertRedirect(route('dashboard.invoices.index'));
        $this->assertSame($item->unit_price_cents, $invoice->fresh(['items'])->items->first()->unit_price_cents);

        app(InvoiceSettlementService::class)->markPaid($invoice->fresh());
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
    }

    public function test_settlement_succeeds_when_subscription_contract_matches(): void
    {
        [$order, $invoice] = $this->makeSubscriptionOrderBackedInvoice();

        app(InvoiceSettlementService::class)->markPaid($invoice);

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
    }

    public function test_settlement_rejects_corrupted_subscription_amount(): void
    {
        [$order, $invoice, $item, $subscription] = $this->makeSubscriptionOrderBackedInvoice();

        DB::table('invoice_items')->where('id', $item->id)->update([
            'unit_price_cents' => 1,
            'total_cents' => 1,
        ]);

        try {
            app(InvoiceSettlementService::class)->markPaid($invoice->fresh());
            $this->fail('Expected corrupted subscription invoice to fail settlement.');
        } catch (\RuntimeException) {
        }

        $this->assertSame('unpaid', $invoice->fresh()->status);
        $this->assertSame('pending', $subscription->fresh()->status);
    }

    /* ============================== mixed/unsupported ============================== */
    public function test_unsupported_item_type_on_order_backed_invoice_fails_closed(): void
    {
        [$order, $invoice] = $this->makeDomainRenewalInvoice();

        InvoiceItem::query()->create([
            'invoice_id' => $invoice->id,
            'item_type' => 'service',
            'reference_id' => null,
            'description' => 'Unsupported line item type',
            'qty' => 1,
            'unit_price_cents' => 0,
            'total_cents' => 0,
        ]);

        try {
            app(InvoiceSettlementService::class)->markPaid($invoice->fresh());
            $this->fail('Expected an unsupported item_type to fail settlement, not be silently skipped.');
        } catch (\RuntimeException) {
        }

        $this->assertSame('unpaid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    /* ============================== duplicate ============================== */
    public function test_duplicate_of_order_backed_invoice_remains_standalone_and_editable_and_cannot_activate_original(): void
    {
        [$order, $invoice] = $this->makeDomainRenewalInvoice();

        $this->post(route('dashboard.invoices.bulk'), [
            'ids' => [$invoice->id],
            'action' => 'duplicate',
        ])->assertSessionHas('error'); // domain items are blocked from duplication (pre-existing)

        [$subOrder, $subInvoice] = $this->makeSubscriptionOrderBackedInvoice();

        $this->post(route('dashboard.invoices.bulk'), [
            'ids' => [$subInvoice->id],
            'action' => 'duplicate',
        ])->assertSessionHas('ok');

        $clone = Invoice::query()->where('id', '!=', $subInvoice->id)->where('client_id', $subInvoice->client_id)->sole();
        $this->assertNull($clone->order_id);

        $cloneSubscription = Subscription::find($clone->fresh(['items'])->items->first()->reference_id);

        $this->put(route('dashboard.invoices.update', $clone), $this->standalonePayload(
            $cloneSubscription,
            ['description' => 'Freely edited clone', 'unit_price_cents' => 42],
        ))->assertRedirect(route('dashboard.invoices.index'))->assertSessionHas('ok');

        $this->assertSame('Freely edited clone', $clone->fresh(['items'])->items->first()->description);
        $this->assertSame(Order::STATUS_PENDING, $subOrder->fresh()->status);
    }

    /* ============================== 19 — TLD-3H.3A intact ============================== */
    public function test_activation_idempotency_from_3h3a_remains_intact_alongside_the_new_integrity_check(): void
    {
        Queue::fake();
        [$order, $invoiceA, $invoiceB, $subscription] = $this->makeOrderWithTwoMatchingInvoices();
        $settlement = app(InvoiceSettlementService::class);

        $settlement->markPaid($invoiceA);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
        Queue::assertPushed(ProvisionSubscription::class, 1);

        $settlement->markPaid($invoiceB->fresh());

        $this->assertSame('paid', $invoiceB->fresh()->status);
        Queue::assertPushed(ProvisionSubscription::class, 1);
    }

    /* ============================== 20 — gateway vs admin_manual ============================== */
    public function test_gateway_and_admin_manual_settlement_both_pass_through_the_same_integrity_check(): void
    {
        [$adminOrder, $adminInvoice, $adminItem] = $this->makeSubscriptionOrderBackedInvoice();
        [$gwOrder, $gwInvoice, $gwItem] = $this->makeSubscriptionOrderBackedInvoice();

        DB::table('invoice_items')->where('id', $adminItem->id)->update(['unit_price_cents' => 1, 'total_cents' => 1]);
        DB::table('invoice_items')->where('id', $gwItem->id)->update(['unit_price_cents' => 1, 'total_cents' => 1]);

        $settlement = app(InvoiceSettlementService::class);

        try {
            $settlement->markPaid($adminInvoice->fresh(), 'admin_manual', null);
            $this->fail('Expected admin_manual settlement to reject the corrupted invoice.');
        } catch (\RuntimeException) {
        }

        $attempt = $this->claimHostedSession($gwInvoice->fresh());
        try {
            $settlement->markPaid($gwInvoice->fresh(), 'stripe', $attempt);
            $this->fail('Expected gateway settlement to reject the corrupted invoice.');
        } catch (\RuntimeException) {
        }

        $this->assertSame('unpaid', $adminInvoice->fresh()->status);
        $this->assertSame('unpaid', $gwInvoice->fresh()->status);
    }

    /* ============================== existing gateway currency check ============================== */
    public function test_existing_gateway_currency_validation_remains_unchanged(): void
    {
        [$order, $invoice] = $this->makeSubscriptionOrderBackedInvoice();
        $attempt = $this->claimHostedSession($invoice->fresh(), currency: 'EUR');

        $this->expectException(\RuntimeException::class);
        app(InvoiceSettlementService::class)->markPaid($invoice->fresh(), 'stripe', $attempt);
    }

    // -----------------------------------------------------------------
    // Fixtures
    // -----------------------------------------------------------------

    private function makeDomainRenewalInvoice(string $orderStatus = Order::STATUS_PENDING): array
    {
        $client = Client::query()->create([
            'first_name' => 'Renewal',
            'last_name' => 'Integrity',
            'email' => uniqid('renewal_integrity_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Renewal Integrity Test',
        ]);

        $provider = DomainProvider::query()->create([
            'name' => 'TLD-3H.3C Renewal Provider',
            'type' => 'enom',
            'username' => 'testuser',
            'password' => 'testpass',
            'is_active' => true,
            'mode' => 'live',
        ]);

        $domainName = 'tld3h3c-renew-' . uniqid() . '.com';

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
            'number' => 'INV-REN-' . strtoupper(uniqid()),
            'status' => 'unpaid',
            'subtotal_cents' => 1620,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 1620,
            'currency' => 'USD',
            'due_date' => now()->addDays(7),
        ]);

        $item = $invoice->items()->create([
            'item_type' => 'domain',
            'reference_id' => $domain->id,
            'description' => 'Domain Renewal: ' . $domainName,
            'qty' => 1,
            'unit_price_cents' => 1620,
            'total_cents' => 1620,
        ]);

        return [$order->fresh(), $invoice->fresh(['items']), $item];
    }

    private function makeDomainRenewalInvoiceWithSpyRegistrar(): array
    {
        [$order, $invoice, $item] = $this->makeDomainRenewalInvoice();

        $registrar = new class extends \App\Services\Domains\RegistrarProvisioningService {
            public int $renewCalls = 0;
            public int $registerCalls = 0;

            public function __construct()
            {
            }

            protected function renewDomainWithProvider(DomainProvider $provider, Domain $domain, array $context): array
            {
                $this->renewCalls++;

                return ['ok' => true, 'cid' => 'X', 'provider_reference' => 'X', 'provider_domain_id' => 'X'];
            }

            protected function registerDomainWithProvider(DomainProvider $provider, Domain $domain, array $context, array $contact): array
            {
                $this->registerCalls++;

                return ['ok' => true, 'reason' => 'ok', 'cid' => 'X'];
            }
        };

        return [$order, $invoice, $item, $registrar];
    }

    private function makeDomainRegistrationInvoice(): array
    {
        $client = Client::query()->create([
            'first_name' => 'Registration',
            'last_name' => 'Integrity',
            'email' => uniqid('registration_integrity_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Registration Integrity Test',
        ]);

        $domainName = 'tld3h3c-register-' . uniqid() . '.com';

        $order = Order::query()->create([
            'client_id' => $client->id,
            'status' => Order::STATUS_PENDING,
            'type' => 'domains',
        ]);

        $order->items()->create([
            'domain' => $domainName,
            'item_option' => 'register',
            'price_cents' => 1200,
            'meta' => [
                'currency' => 'USD',
                'years' => 1,
            ],
        ]);

        $invoice = Invoice::query()->create([
            'client_id' => $client->id,
            'order_id' => $order->id,
            'number' => 'INV-REG-' . strtoupper(uniqid()),
            'status' => 'unpaid',
            'subtotal_cents' => 1200,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 1200,
            'currency' => 'USD',
            'due_date' => now()->addDays(7),
        ]);

        // Mirrors DomainInvoiceItemBuilder's real output shape: reference_id is null until the
        // Domain row exists (registration hasn't happened yet at settlement-check time).
        $item = $invoice->items()->create([
            'item_type' => 'domain',
            'reference_id' => null,
            'description' => 'Domain Registration: ' . $domainName,
            'qty' => 1,
            'unit_price_cents' => 1200,
            'total_cents' => 1200,
        ]);

        return [$order->fresh(), $invoice->fresh(['items']), $item];
    }

    private function makeSubscriptionOrderBackedInvoice(): array
    {
        $client = Client::query()->create([
            'first_name' => 'Subscription',
            'last_name' => 'Integrity',
            'email' => uniqid('subscription_integrity_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Subscription Integrity Test',
        ]);

        $subscription = $this->makeSubscription($client, 1500);

        // Matches CheckoutController's real subscription-only flow: NO OrderItem row is ever
        // created for the subscription line — Subscription.price_cents is the only frozen
        // financial source for this item.
        $order = Order::query()->create([
            'client_id' => $client->id,
            'status' => Order::STATUS_PENDING,
            'type' => 'subscription',
        ]);

        $invoice = Invoice::query()->create([
            'client_id' => $client->id,
            'order_id' => $order->id,
            'number' => 'INV-SUB-' . strtoupper(uniqid()),
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

        return [$order->fresh(), $invoice->fresh(['items']), $item, $subscription];
    }

    private function makeOrderWithTwoMatchingInvoices(): array
    {
        $client = Client::query()->create([
            'first_name' => 'TwoInvoice',
            'last_name' => 'Integrity',
            'email' => uniqid('two_invoice_integrity_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Two Invoice Integrity Test',
        ]);

        $subscription = $this->makeSubscription($client, 1000);

        $order = Order::query()->create([
            'client_id' => $client->id,
            'status' => Order::STATUS_PENDING,
            'type' => 'subscription',
        ]);

        $invoiceA = Invoice::query()->create([
            'client_id' => $client->id,
            'order_id' => $order->id,
            'number' => 'INV-A-' . strtoupper(uniqid()),
            'status' => 'unpaid',
            'subtotal_cents' => 1000,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 1000,
            'currency' => 'USD',
        ]);
        $invoiceA->items()->create([
            'item_type' => 'subscription',
            'reference_id' => $subscription->id,
            'description' => 'Subscription #' . $subscription->id,
            'qty' => 1,
            'unit_price_cents' => 1000,
            'total_cents' => 1000,
        ]);

        // A second, empty draft invoice — matches OrderActivationServiceTest's own established
        // multi-invoice fixture shape (no items). Zero items trivially satisfies the integrity
        // check (0 == 0), so this isolates the activation-idempotency behavior being tested.
        $invoiceB = Invoice::query()->create([
            'client_id' => $client->id,
            'order_id' => $order->id,
            'number' => 'INV-B-' . strtoupper(uniqid()),
            'status' => 'draft',
            'subtotal_cents' => 0,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 0,
            'currency' => 'USD',
        ]);

        return [$order->fresh(), $invoiceA->fresh(['items']), $invoiceB->fresh(['items']), $subscription];
    }

    private function makeStandaloneSubscriptionInvoice(): array
    {
        $client = Client::query()->create([
            'first_name' => 'Standalone',
            'last_name' => 'Integrity',
            'email' => uniqid('standalone_integrity_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Standalone Integrity Test',
        ]);

        $subscription = $this->makeSubscription($client, 1000);

        $invoice = Invoice::query()->create([
            'client_id' => $client->id,
            'order_id' => null,
            'number' => 'INV-STD-' . strtoupper(uniqid()),
            'status' => 'unpaid',
            'subtotal_cents' => 1000,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 1000,
            'currency' => 'USD',
            'due_date' => now()->addDays(7),
        ]);

        $invoice->items()->create([
            'item_type' => 'subscription',
            'reference_id' => $subscription->id,
            'description' => 'Standalone subscription line',
            'qty' => 1,
            'unit_price_cents' => 1000,
            'total_cents' => 1000,
        ]);

        return [null, $invoice->fresh(['items']), $subscription];
    }

    private function makeSubscription(Client $client, int $priceCents): Subscription
    {
        $server = Server::query()->create([
            'name' => 'TLD3H3C WHM',
            'type' => 'cpanel',
            'hostname' => uniqid('whm-', false) . '.example.test',
            'username' => 'root',
            'api_token' => 'test-token',
            'is_active' => true,
        ]);
        $plan = Plan::query()->create([
            'name' => 'TLD3H3C Plan',
            'slug' => uniqid('tld3h3c-plan-', false),
            'plan_type' => Plan::TYPE_HOSTING,
            'server_id' => $server->id,
            'server_package' => 'tld3h3c_package',
            'is_active' => true,
        ]);

        return Subscription::query()->create([
            'client_id' => $client->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'provisioning_status' => Subscription::PROVISIONING_PENDING,
            'price_cents' => $priceCents,
            'billing_cycle' => 'monthly',
            'username' => uniqid('tld3h3c', false),
            'server_id' => $server->id,
            'server_package' => 'tld3h3c_package',
            'domain_option' => 'subdomain',
            'domain_name' => uniqid('tld3h3c-', false) . '.example.test',
            'subdomain' => uniqid('tld3h3c-', false),
        ]);
    }

    private function claimHostedSession(Invoice $invoice, string $currency = 'USD'): PaymentAttempt
    {
        $attempt = PaymentAttempt::query()->create([
            'invoice_id' => $invoice->id,
            'order_id' => $invoice->order_id,
            'client_id' => $invoice->client_id,
            'gateway' => 'stripe',
            'idempotency_key' => uniqid('tld3h3c-attempt-', true),
            'gateway_session_id' => uniqid('tld3h3c-session-', true),
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

    /**
     * Builds an update() payload for an Order-backed invoice that submits the ORIGINAL item's
     * values back merged with $itemOverrides (a forged/attempted change) — assertions in each
     * test prove these overrides never take effect for an Order-backed invoice.
     */
    private function orderBackedForgedPayload(InvoiceItem $originalItem, array $itemOverrides = [], array $topLevelOverrides = []): array
    {
        return array_merge([
            'status' => 'unpaid',
            'due_date' => now()->addDays(7)->toDateString(),
            'items' => [
                array_merge([
                    'item_type' => $originalItem->item_type,
                    'reference_id' => $originalItem->reference_id ?? 1,
                    'description' => $originalItem->description,
                    'qty' => $originalItem->qty,
                    'unit_price_cents' => $originalItem->unit_price_cents,
                ], $itemOverrides),
            ],
        ], $topLevelOverrides);
    }

    private function standalonePayload(Subscription $subscription, array $itemOverrides = []): array
    {
        return [
            'status' => 'unpaid',
            'due_date' => now()->addWeek()->toDateString(),
            'items' => [
                array_merge([
                    'item_type' => 'subscription',
                    'reference_id' => $subscription->id,
                    'description' => 'Standalone subscription line',
                    'qty' => 1,
                    'unit_price_cents' => 1000,
                ], $itemOverrides),
            ],
        ];
    }
}
