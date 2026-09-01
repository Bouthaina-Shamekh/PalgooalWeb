<?php

namespace Tests\Feature;

use App\Jobs\ProvisionSubscription;
use App\Models\Client;
use App\Models\Coupon;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\Plan;
use App\Models\Server;
use App\Models\Tenancy\Subscription;
use App\Models\User;
use App\Services\Billing\InvoiceSettlementService;
use App\Services\Domains\RegistrarProvisioningService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class AdminInvoiceSettlementTest extends TestCase
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
        Queue::fake();
    }

    public function test_admin_update_from_unpaid_to_paid_uses_central_settlement(): void
    {
        [$order, $invoice, $subscription] = $this->makeSubscriptionInvoice();

        $this->put(route('dashboard.invoices.update', $invoice), $this->invoicePayload($subscription))
            ->assertRedirect(route('dashboard.invoices.index'))
            ->assertSessionHas('ok');

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->paid_date);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertNull($invoice->fresh()->payment_attempt_id);
        $this->assertSame(0, PaymentAttempt::query()->count());
        Queue::assertPushed(
            ProvisionSubscription::class,
            fn (ProvisionSubscription $job) => $job->subscriptionId === $subscription->id,
        );
        Queue::assertPushed(ProvisionSubscription::class, 1);
    }

    public function test_admin_create_as_paid_builds_items_before_settlement_and_outside_controller_transaction(): void
    {
        $client = $this->makeClient();
        $subscription = $this->makeSubscription($client);
        $realSettlement = $this->app->make(InvoiceSettlementService::class);
        $settlement = Mockery::mock(InvoiceSettlementService::class);
        $settlement->shouldReceive('markPaid')
            ->once()
            ->withArgs(function (Invoice $invoice, ?string $method, $attempt) use ($realSettlement): bool {
                $this->assertSame(0, DB::transactionLevel());
                $this->assertSame('admin_manual', $method);
                $this->assertNull($attempt);
                $this->assertSame('unpaid', $invoice->fresh()->status);
                $this->assertSame(1, $invoice->items()->count());

                $realSettlement->markPaid($invoice, $method, $attempt);

                return true;
            });
        $this->app->instance(InvoiceSettlementService::class, $settlement);

        $this->post(route('dashboard.invoices.store'), array_merge(
            $this->invoicePayload($subscription),
            ['client_id' => $client->id],
        ))
            ->assertRedirect(route('dashboard.invoices.index'))
            ->assertSessionHas('ok');

        $invoice = Invoice::query()->sole();
        $this->assertSame('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_date);
        $this->assertSame(1, $invoice->items()->count());
        $this->assertNull($invoice->payment_attempt_id);
        $this->assertSame(0, PaymentAttempt::query()->count());
    }

    public function test_bulk_mark_paid_settles_each_invoice_once(): void
    {
        [$firstOrder, $firstInvoice, $firstSubscription] = $this->makeSubscriptionInvoice();
        [$secondOrder, $secondInvoice, $secondSubscription] = $this->makeSubscriptionInvoice();

        $this->post(route('dashboard.invoices.bulk'), [
            'ids' => [$firstInvoice->id, $secondInvoice->id],
            'action' => 'paid',
        ])
            ->assertRedirect()
            ->assertSessionHas('ok');

        $this->assertSame('paid', $firstInvoice->fresh()->status);
        $this->assertSame('paid', $secondInvoice->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $firstOrder->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $secondOrder->fresh()->status);
        $this->assertSame('active', $firstSubscription->fresh()->status);
        $this->assertSame('active', $secondSubscription->fresh()->status);
        Queue::assertPushed(ProvisionSubscription::class, 2);
        $this->assertSame(0, PaymentAttempt::query()->count());
    }

    public function test_bulk_mark_paid_continues_after_an_active_session_rejects_one_invoice(): void
    {
        [$blockedOrder, $blockedInvoice, $blockedSubscription] = $this->makeSubscriptionInvoice();
        $this->claimHostedSession($blockedInvoice, Invoice::PAYMENT_SESSION_READY);
        [$paidOrder, $paidInvoice, $paidSubscription] = $this->makeSubscriptionInvoice();

        $this->post(route('dashboard.invoices.bulk'), [
            'ids' => [$blockedInvoice->id, $paidInvoice->id],
            'action' => 'paid',
        ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('unpaid', $blockedInvoice->fresh()->status);
        $this->assertSame(Order::STATUS_PENDING, $blockedOrder->fresh()->status);
        $this->assertSame('pending', $blockedSubscription->fresh()->status);
        $this->assertSame('paid', $paidInvoice->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $paidOrder->fresh()->status);
        $this->assertSame('active', $paidSubscription->fresh()->status);
        Queue::assertPushed(ProvisionSubscription::class, 1);
    }

    public function test_paid_invoice_totals_items_and_side_effects_are_immutable(): void
    {
        [$order, $invoice, $subscription] = $this->makeSubscriptionInvoice(withCoupon: true);
        $payload = $this->invoicePayload($subscription);

        $this->put(route('dashboard.invoices.update', $invoice), $payload)->assertSessionHas('ok');
        $settledInvoice = $invoice->fresh();
        $paidDate = $settledInvoice->paid_date?->toDateString();
        $originalItem = $settledInvoice->items()->sole();

        $changedPayload = $payload;
        $changedPayload['items'][0]['description'] = 'Attempted mutation after payment';
        $changedPayload['items'][0]['unit_price_cents'] = 50000;

        $this->put(route('dashboard.invoices.update', $invoice), $changedPayload)
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(1000, $invoice->fresh()->subtotal_cents);
        $this->assertSame(1000, $invoice->fresh()->total_cents);
        $this->assertSame($paidDate, $invoice->fresh()->paid_date?->toDateString());
        $this->assertSame(1, $invoice->items()->count());
        $this->assertSame($originalItem->id, $invoice->items()->sole()->id);
        $this->assertSame($originalItem->description, $invoice->items()->sole()->description);
        $this->assertSame(1000, $invoice->items()->sole()->unit_price_cents);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertSame(1, $invoice->coupon->fresh()->used_count);
        $this->assertTrue($invoice->coupon->subscriptions()->whereKey($subscription->id)->exists());
        Queue::assertPushed(ProvisionSubscription::class, 1);
        $this->assertSame(0, PaymentAttempt::query()->count());
    }

    public function test_paid_invoice_cannot_be_reverted_to_unpaid_draft_or_cancelled(): void
    {
        foreach (['unpaid', 'draft', 'cancelled'] as $targetStatus) {
            [$order, $invoice, $subscription] = $this->makeSubscriptionInvoice();
            $this->put(route('dashboard.invoices.update', $invoice), $this->invoicePayload($subscription))
                ->assertSessionHas('ok');
            $originalItemId = $invoice->items()->sole()->id;

            $payload = $this->invoicePayload($subscription);
            $payload['status'] = $targetStatus;
            $payload['items'][0]['unit_price_cents'] = 50000;

            $this->put(route('dashboard.invoices.update', $invoice), $payload)
                ->assertRedirect()
                ->assertSessionHas('error');

            $this->assertSame('paid', $invoice->fresh()->status);
            $this->assertSame(1000, $invoice->fresh()->total_cents);
            $this->assertSame($originalItemId, $invoice->items()->sole()->id);
        }

        Queue::assertPushed(ProvisionSubscription::class, 3);
        $this->assertSame(0, PaymentAttempt::query()->count());
    }

    public function test_bulk_reversal_skips_paid_invoices_and_reports_affected_and_skipped_counts(): void
    {
        [, $paidInvoice, $paidSubscription] = $this->makeSubscriptionInvoice();
        $this->put(route('dashboard.invoices.update', $paidInvoice), $this->invoicePayload($paidSubscription))
            ->assertSessionHas('ok');
        [, $readyInvoice] = $this->makeSubscriptionInvoice();
        $this->claimHostedSession($readyInvoice, Invoice::PAYMENT_SESSION_READY);
        [, $unpaidInvoice] = $this->makeSubscriptionInvoice();

        $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post(route('dashboard.invoices.bulk'), [
                'ids' => [$paidInvoice->id, $readyInvoice->id, $unpaidInvoice->id],
                'action' => 'cancelled',
            ])
            ->assertOk()
            ->assertJson([
                'affected' => 1,
                'failed' => 0,
                'skipped' => 2,
            ]);

        $this->assertSame('paid', $paidInvoice->fresh()->status);
        $this->assertSame('unpaid', $readyInvoice->fresh()->status);
        $this->assertSame('cancelled', $unpaidInvoice->fresh()->status);
        Queue::assertPushed(ProvisionSubscription::class, 1);
    }

    public function test_bulk_paid_is_idempotent_for_an_already_paid_invoice(): void
    {
        [, $invoice, $subscription] = $this->makeSubscriptionInvoice(withCoupon: true);
        $this->put(route('dashboard.invoices.update', $invoice), $this->invoicePayload($subscription))
            ->assertSessionHas('ok');

        $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post(route('dashboard.invoices.bulk'), [
                'ids' => [$invoice->id],
                'action' => 'paid',
            ])
            ->assertOk()
            ->assertJson([
                'affected' => 0,
                'failed' => 0,
                'skipped' => 1,
            ]);

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(1, $invoice->coupon->fresh()->used_count);
        $this->assertSame(0, PaymentAttempt::query()->count());
        Queue::assertPushed(ProvisionSubscription::class, 1);
    }

    public function test_paid_invoice_cannot_be_deleted_individually_or_by_bulk_action(): void
    {
        [, $paidInvoice, $subscription] = $this->makeSubscriptionInvoice();
        $this->put(route('dashboard.invoices.update', $paidInvoice), $this->invoicePayload($subscription))
            ->assertSessionHas('ok');
        $originalItemId = $paidInvoice->items()->sole()->id;
        [, $readyInvoice] = $this->makeSubscriptionInvoice();
        $this->claimHostedSession($readyInvoice, Invoice::PAYMENT_SESSION_READY);
        $readyItemId = $readyInvoice->items()->sole()->id;
        [, $unpaidInvoice] = $this->makeSubscriptionInvoice();

        $this->delete(route('dashboard.invoices.destroy', $paidInvoice))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($paidInvoice);
        $this->assertDatabaseHas('invoice_items', ['id' => $originalItemId]);

        $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post(route('dashboard.invoices.bulk'), [
                'ids' => [$paidInvoice->id, $readyInvoice->id, $unpaidInvoice->id],
                'action' => 'delete',
            ])
            ->assertOk()
            ->assertJson([
                'affected' => 1,
                'failed' => 0,
                'skipped' => 2,
            ]);

        $this->assertNotSoftDeleted($paidInvoice);
        $this->assertNotSoftDeleted($readyInvoice);
        $this->assertSoftDeleted($unpaidInvoice);
        $this->assertDatabaseHas('invoice_items', ['id' => $originalItemId]);
        $this->assertDatabaseHas('invoice_items', ['id' => $readyItemId]);
    }

    public function test_active_hosted_sessions_block_totals_and_items_mutation(): void
    {
        foreach ([Invoice::PAYMENT_SESSION_CREATING, Invoice::PAYMENT_SESSION_READY] as $sessionStatus) {
            [, $invoice, $subscription] = $this->makeSubscriptionInvoice();
            $this->claimHostedSession($invoice, $sessionStatus);
            $originalItem = $invoice->items()->sole();
            $payload = $this->invoicePayload($subscription);
            $payload['status'] = 'unpaid';
            $payload['items'][0]['description'] = 'Blocked active-session mutation';
            $payload['items'][0]['unit_price_cents'] = 50000;

            $this->put(route('dashboard.invoices.update', $invoice), $payload)
                ->assertRedirect()
                ->assertSessionHas('error');

            $this->assertSame('unpaid', $invoice->fresh()->status);
            $this->assertSame(1000, $invoice->fresh()->total_cents);
            $this->assertSame($originalItem->id, $invoice->items()->sole()->id);
            $this->assertSame($originalItem->description, $invoice->items()->sole()->description);
            $this->assertSame(1000, $invoice->items()->sole()->unit_price_cents);
        }

        Queue::assertNothingPushed();
    }

    public function test_active_hosted_session_blocks_mark_paid_before_settlement_is_called(): void
    {
        $settlement = Mockery::mock(InvoiceSettlementService::class);
        $settlement->shouldNotReceive('markPaid');
        $this->app->instance(InvoiceSettlementService::class, $settlement);

        foreach ([Invoice::PAYMENT_SESSION_CREATING, Invoice::PAYMENT_SESSION_READY] as $sessionStatus) {
            [$order, $invoice, $subscription] = $this->makeSubscriptionInvoice();
            $attempt = $this->claimHostedSession($invoice, $sessionStatus);

            $this->put(route('dashboard.invoices.update', $invoice), $this->invoicePayload($subscription))
                ->assertRedirect()
                ->assertSessionHas('error');

            $this->assertSame('unpaid', $invoice->fresh()->status);
            $this->assertNull($invoice->fresh()->paid_date);
            $this->assertNull($invoice->fresh()->payment_attempt_id);
            $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
            $this->assertSame('pending', $subscription->fresh()->status);
            $this->assertSame(PaymentAttempt::STATUS_INITIATED, $attempt->fresh()->status);
        }

        Queue::assertNothingPushed();
    }

    public function test_active_hosted_sessions_block_individual_delete_and_preserve_items(): void
    {
        foreach ([Invoice::PAYMENT_SESSION_CREATING, Invoice::PAYMENT_SESSION_READY] as $sessionStatus) {
            [, $invoice] = $this->makeSubscriptionInvoice();
            $this->claimHostedSession($invoice, $sessionStatus);
            $itemId = $invoice->items()->sole()->id;

            $this->delete(route('dashboard.invoices.destroy', $invoice))
                ->assertRedirect()
                ->assertSessionHas('error');

            $this->assertNotSoftDeleted($invoice);
            $this->assertDatabaseHas('invoice_items', ['id' => $itemId]);
        }
    }

    public function test_bulk_paid_counts_an_active_session_as_failed_not_skipped(): void
    {
        [, $invoice] = $this->makeSubscriptionInvoice();
        $this->claimHostedSession($invoice, Invoice::PAYMENT_SESSION_READY);

        $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post(route('dashboard.invoices.bulk'), [
                'ids' => [$invoice->id],
                'action' => 'paid',
            ])
            ->assertOk()
            ->assertJson([
                'affected' => 0,
                'failed' => 1,
                'skipped' => 0,
            ]);

        $this->assertSame('unpaid', $invoice->fresh()->status);
        $this->assertSame(1000, $invoice->fresh()->total_cents);
        Queue::assertNothingPushed();
    }

    public function test_idle_unpaid_invoice_remains_editable(): void
    {
        [, $invoice, $subscription] = $this->makeSubscriptionInvoice();
        $originalItemId = $invoice->items()->sole()->id;
        $payload = $this->invoicePayload($subscription);
        $payload['status'] = 'draft';
        $payload['items'][0]['description'] = 'Updated while idle';
        $payload['items'][0]['unit_price_cents'] = 50000;

        $this->put(route('dashboard.invoices.update', $invoice), $payload)
            ->assertRedirect(route('dashboard.invoices.index'))
            ->assertSessionHas('ok');

        $this->assertSame('draft', $invoice->fresh()->status);
        $this->assertSame(50000, $invoice->fresh()->subtotal_cents);
        $this->assertSame(50000, $invoice->fresh()->total_cents);
        $this->assertNotSame($originalItemId, $invoice->items()->sole()->id);
        $this->assertSame('Updated while idle', $invoice->items()->sole()->description);
        $this->assertSame(50000, $invoice->items()->sole()->unit_price_cents);
        Queue::assertNothingPushed();
    }

    public function test_duplicate_paid_invoice_resets_all_payment_ownership_fields_and_copies_items(): void
    {
        [, $invoice, $subscription] = $this->makeSubscriptionInvoice(withCoupon: true);
        $this->put(route('dashboard.invoices.update', $invoice), $this->invoicePayload($subscription))
            ->assertSessionHas('ok');
        $attempt = $this->claimHostedSession($invoice, Invoice::PAYMENT_SESSION_READY);
        $invoice->update(['payment_attempt_id' => $attempt->id]);
        $paymentAttemptCount = PaymentAttempt::query()->count();
        $originalItem = $invoice->items()->sole();
        $original = $invoice->fresh();

        $this->post(route('dashboard.invoices.bulk'), [
            'ids' => [$invoice->id],
            'action' => 'duplicate',
        ])->assertSessionHas('ok');

        $clone = Invoice::query()->where('id', '!=', $invoice->id)->sole();
        $clonedItem = $clone->items()->sole();
        $this->assertNull($clone->order_id);
        $this->assertNull($clone->coupon_id);
        $this->assertSame('draft', $clone->status);
        $this->assertNull($clone->paid_date);
        $this->assertNull($clone->payment_attempt_id);
        $this->assertNull($clone->payment_session_attempt_id);
        $this->assertSame(Invoice::PAYMENT_SESSION_IDLE, $clone->payment_session_status);
        $this->assertSame($paymentAttemptCount, PaymentAttempt::query()->count());
        $this->assertNotSame($originalItem->id, $clonedItem->id);
        $this->assertSame($original->client_id, $clone->client_id);
        $this->assertSame($original->currency, $clone->currency);
        $this->assertSame($original->subtotal_cents, $clone->subtotal_cents);
        $this->assertSame($original->discount_cents, $clone->discount_cents);
        $this->assertSame($original->tax_cents, $clone->tax_cents);
        $this->assertSame($original->total_cents, $clone->total_cents);
        $this->assertSame($originalItem->item_type, $clonedItem->item_type);
        $this->assertSame($originalItem->reference_id, $clonedItem->reference_id);
        $this->assertSame($originalItem->description, $clonedItem->description);
        $this->assertSame($originalItem->qty, $clonedItem->qty);
        $this->assertSame($originalItem->unit_price_cents, $clonedItem->unit_price_cents);
        $this->assertSame($originalItem->total_cents, $clonedItem->total_cents);
    }

    public function test_duplicate_active_session_invoice_starts_idle_without_payment_links(): void
    {
        [, $invoice] = $this->makeSubscriptionInvoice(withCoupon: true);
        $this->claimHostedSession($invoice, Invoice::PAYMENT_SESSION_CREATING);
        $paymentAttemptCount = PaymentAttempt::query()->count();

        $this->post(route('dashboard.invoices.bulk'), [
            'ids' => [$invoice->id],
            'action' => 'duplicate',
        ])->assertSessionHas('ok');

        $clone = Invoice::query()->where('id', '!=', $invoice->id)->sole();
        $this->assertNull($clone->order_id);
        $this->assertNull($clone->coupon_id);
        $this->assertSame('draft', $clone->status);
        $this->assertNull($clone->paid_date);
        $this->assertNull($clone->payment_attempt_id);
        $this->assertNull($clone->payment_session_attempt_id);
        $this->assertSame(Invoice::PAYMENT_SESSION_IDLE, $clone->payment_session_status);
        $this->assertSame(1, $clone->items()->count());
        $this->assertSame($paymentAttemptCount, PaymentAttempt::query()->count());
    }

    public function test_domain_duplicate_is_skipped_before_clone_while_other_bulk_invoices_continue(): void
    {
        [$domainOrder, $domainInvoice, $domainSubscription] = $this->makeSubscriptionInvoice();
        [, $safeInvoice] = $this->makeSubscriptionInvoice();
        $domain = Domain::query()->create([
            'client_id' => $domainInvoice->client_id,
            'domain_name' => uniqid('duplicate-guard-', false) . '.example.test',
            'registrar' => 'namecheap',
            'registration_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'pending',
            'payment_method' => null,
        ]);

        $domainInvoice->items()->delete();
        $domainItem = $domainInvoice->items()->create([
            'item_type' => 'domain',
            'reference_id' => $domain->id,
            'description' => 'Existing domain reference',
            'qty' => 1,
            'unit_price_cents' => 1000,
            'total_cents' => 1000,
        ]);
        $invoiceSnapshot = $domainInvoice->fresh()->getAttributes();
        $domainSnapshot = $domain->fresh()->getAttributes();
        $invoiceCount = Invoice::query()->count();
        $itemCount = InvoiceItem::query()->count();

        $this->post(route('dashboard.invoices.bulk'), [
            'ids' => [$domainInvoice->id, $safeInvoice->id],
            'action' => 'duplicate',
        ])->assertSessionHas('error');

        $this->assertSame($invoiceCount + 1, Invoice::query()->count());
        $this->assertSame($itemCount + 1, InvoiceItem::query()->count());
        $this->assertSame(1, Invoice::query()->where('client_id', $domainInvoice->client_id)->count());
        $this->assertSame(2, Invoice::query()->where('client_id', $safeInvoice->client_id)->count());
        $this->assertSame($invoiceSnapshot, $domainInvoice->fresh()->getAttributes());
        $this->assertSame($domainSnapshot, $domain->fresh()->getAttributes());
        $this->assertDatabaseHas('invoice_items', [
            'id' => $domainItem->id,
            'invoice_id' => $domainInvoice->id,
            'item_type' => 'domain',
            'reference_id' => $domain->id,
        ]);
        $this->assertSame(1, InvoiceItem::query()->where('item_type', 'domain')->count());
        $this->assertSame(Order::STATUS_PENDING, $domainOrder->fresh()->status);
        $this->assertSame('pending', $domainSubscription->fresh()->status);
        $this->assertSame(0, PaymentAttempt::query()->count());
        Queue::assertNothingPushed();
    }

    public function test_paying_duplicate_does_not_reactivate_original_order_or_consume_coupon_twice(): void
    {
        [$order, $invoice, $subscription] = $this->makeSubscriptionInvoice(withCoupon: true);

        $this->put(route('dashboard.invoices.update', $invoice), $this->invoicePayload($subscription))
            ->assertSessionHas('ok');

        $coupon = $invoice->fresh()->coupon;
        $subscription = $subscription->fresh();
        $lifecycle = [
            'starts_at' => $subscription->getRawOriginal('starts_at'),
            'ends_at' => $subscription->getRawOriginal('ends_at'),
            'next_due_date' => $subscription->getRawOriginal('next_due_date'),
        ];
        $this->assertSame(1, $coupon->fresh()->used_count);
        Queue::assertPushed(ProvisionSubscription::class, 1);

        $this->post(route('dashboard.invoices.bulk'), [
            'ids' => [$invoice->id],
            'action' => 'duplicate',
        ])->assertSessionHas('ok');

        $clone = Invoice::query()->where('id', '!=', $invoice->id)->sole();
        $this->assertNull($clone->order_id);
        $this->assertNull($clone->coupon_id);

        $this->put(route('dashboard.invoices.update', $clone), $this->invoicePayload($subscription))
            ->assertSessionHas('ok');

        $this->assertSame('paid', $clone->fresh()->status);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
        $this->assertSame($lifecycle, [
            'starts_at' => $subscription->fresh()->getRawOriginal('starts_at'),
            'ends_at' => $subscription->fresh()->getRawOriginal('ends_at'),
            'next_due_date' => $subscription->fresh()->getRawOriginal('next_due_date'),
        ]);
        $this->assertSame(1, $coupon->fresh()->used_count);
        Queue::assertPushed(ProvisionSubscription::class, 1);
    }

    public function test_admin_invoice_controller_has_no_direct_paid_write_or_order_activation_bypass(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Admin/Management/InvoiceController.php'));

        $this->assertStringContainsString('InvoiceSettlementService', $source);
        $this->assertStringContainsString("'admin_manual'", $source);
        $this->assertSame(1, substr_count($source, '->markPaid('));
        $this->assertStringNotContainsString('OrderActivationService', $source);
        $this->assertStringNotContainsString('maybeActivateRelatedOrder', $source);
        $this->assertStringNotContainsString("'status' => 'paid'", $source);
        $this->assertStringNotContainsString("'status'          => 'paid'", $source);
        $this->assertStringContainsString('lockForUpdate()', $source);
        $this->assertStringContainsString('paidInvoiceImmutableMessage', $source);
        $this->assertStringContainsString('hasActiveHostedPaymentSession', $source);
        $this->assertStringContainsString('payment_session_attempt_id = null', $source);
        $this->assertStringContainsString('payment_attempt_id = null', $source);
        $this->assertStringContainsString('$clone->order_id = null', $source);
        $this->assertStringContainsString('$clone->coupon_id = null', $source);
        $this->assertStringContainsString("contains('item_type', 'domain')", $source);
        $this->assertSame(1, substr_count($source, '$clone = $inv->replicate();'));
        $this->assertLessThan(
            strpos($source, '$clone = $inv->replicate();'),
            strpos($source, "contains('item_type', 'domain')"),
        );
    }

    public function test_admin_settlement_keeps_domain_activation_delegated_to_order_activation_service(): void
    {
        [$order, $invoice, $subscription] = $this->makeSubscriptionInvoice();
        $order->items()->create([
            'domain' => 'admin-settlement.example',
            'item_option' => 'register',
            'price_cents' => 1000,
            'meta' => [],
        ]);

        $registrar = Mockery::mock(RegistrarProvisioningService::class);
        $registrar->shouldReceive('provisionOrderDomain')
            ->once()
            ->withArgs(fn (Order $activatedOrder, ?string $method) =>
                $activatedOrder->id === $order->id && $method === 'admin_manual')
            ->andReturn(['ok' => true]);
        $this->app->instance(RegistrarProvisioningService::class, $registrar);

        $this->put(route('dashboard.invoices.update', $invoice), $this->invoicePayload($subscription))
            ->assertSessionHas('ok');

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
    }

    private function invoicePayload(Subscription $subscription): array
    {
        return [
            'status' => 'paid',
            'due_date' => now()->addWeek()->toDateString(),
            'paid_date' => now()->toDateString(),
            'items' => [[
                'item_type' => 'subscription',
                'reference_id' => $subscription->id,
                'description' => 'Admin manual settlement subscription',
                'qty' => 1,
                'unit_price_cents' => 1000,
            ]],
        ];
    }

    private function makeSubscriptionInvoice(bool $withCoupon = false): array
    {
        $client = $this->makeClient();
        $subscription = $this->makeSubscription($client);
        $order = Order::query()->create([
            'client_id' => $client->id,
            'status' => Order::STATUS_PENDING,
            'type' => 'subscription',
        ]);

        $coupon = $withCoupon ? Coupon::query()->create([
            'code' => uniqid('ADMIN-', false),
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
            'description' => 'Admin manual settlement subscription',
            'qty' => 1,
            'unit_price_cents' => 1000,
            'total_cents' => 1000,
        ]);

        return [$order, $invoice, $subscription];
    }

    private function claimHostedSession(Invoice $invoice, string $status): PaymentAttempt
    {
        $attempt = PaymentAttempt::query()->create([
            'invoice_id' => $invoice->id,
            'order_id' => $invoice->order_id,
            'client_id' => $invoice->client_id,
            'gateway' => 'lahza',
            'idempotency_key' => uniqid('attempt-', true),
            'gateway_session_id' => uniqid('session-', true),
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
            'first_name' => 'Admin',
            'last_name' => 'Settlement',
            'email' => uniqid('admin-settlement-', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Admin Settlement Test',
        ]);
    }

    private function makeSubscription(Client $client): Subscription
    {
        $server = Server::query()->create([
            'name' => 'Admin Settlement WHM',
            'type' => 'cpanel',
            'hostname' => uniqid('whm-', false) . '.example.test',
            'username' => 'root',
            'api_token' => 'test-token',
            'is_active' => true,
        ]);
        $plan = Plan::query()->create([
            'name' => 'Admin Settlement Plan',
            'slug' => uniqid('admin-settlement-plan-', false),
            'plan_type' => Plan::TYPE_HOSTING,
            'server_id' => $server->id,
            'server_package' => 'admin_settlement_package',
            'is_active' => true,
        ]);

        return Subscription::query()->create([
            'client_id' => $client->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'provisioning_status' => Subscription::PROVISIONING_PENDING,
            'price_cents' => 1000,
            'billing_cycle' => 'monthly',
            'username' => uniqid('adminsettle', false),
            'server_id' => $server->id,
            'server_package' => 'admin_settlement_package',
            'domain_option' => 'subdomain',
            'domain_name' => uniqid('admin-', false) . '.example.test',
            'subdomain' => uniqid('admin-', false),
        ]);
    }
}
