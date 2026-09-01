<?php

namespace Tests\Feature;

use App\Jobs\ProvisionSubscription;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Server;
use App\Models\Tenancy\Subscription;
use App\Services\Billing\InvoiceSettlementService;
use App\Services\Billing\OrderActivationService;
use App\Services\Domains\RegistrarProvisioningService;
use App\Services\Templates\TemplateCloner;
use App\Services\Tenancy\DomainVerificationService;
use App\Services\Tenancy\SubscriptionSyncService;
use App\Services\Tenancy\TenantDomainHostService;
use App\Services\Tenancy\TenantProvisioningService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class OrderActivationServiceTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    public function test_mark_paid_keeps_current_invoice_paid_and_only_opens_other_drafts(): void
    {
        [$order, $invoiceA, $invoiceB] = $this->makeOrderWithTwoDraftInvoices();
        $activation = $this->activationService();
        $settlement = new InvoiceSettlementService($activation);

        $settlement->markPaid($invoiceA);

        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
        $this->assertSame('paid', $invoiceA->fresh()->status);
        $this->assertSame('unpaid', $invoiceB->fresh()->status);
    }

    public function test_preloaded_stale_invoice_relation_cannot_overwrite_paid_status(): void
    {
        [$order, $invoiceA, $invoiceB] = $this->makeOrderWithTwoDraftInvoices();
        $order->load(['invoices.items', 'items']);

        $invoiceA->update(['status' => 'paid', 'paid_date' => now()]);

        // Prove the supplied Order really contains the stale pre-payment snapshot.
        $this->assertSame(
            'draft',
            $order->invoices->firstWhere('id', $invoiceA->id)?->status,
        );

        $this->activationService()->activate($order);

        $this->assertSame('paid', $invoiceA->fresh()->status);
        $this->assertSame('unpaid', $invoiceB->fresh()->status);
    }

    public function test_subscription_provisioning_is_dispatched_only_after_commit(): void
    {
        [$order, $invoice, $subscription] = $this->makeOrderWithSubscription(Plan::TYPE_HOSTING);
        $providerTransactionLevels = [];
        $provisioning = Mockery::mock(TenantProvisioningService::class);
        $provisioning->shouldReceive('provision')
            ->once()
            ->withArgs(function (Subscription $queuedSubscription, bool $force) use (&$providerTransactionLevels, $subscription): bool {
                $providerTransactionLevels[] = DB::transactionLevel();
                $this->assertSame($subscription->id, $queuedSubscription->id);
                $this->assertFalse($force);

                return true;
            });
        $this->app->instance(TenantProvisioningService::class, $provisioning);

        DB::beginTransaction();

        try {
            $this->activationService()->activate($order->fresh(['invoices.items', 'items']));

            $this->assertSame([], $providerTransactionLevels);
            $this->assertSame('active', $subscription->fresh()->status);
            $this->assertSame(Subscription::PROVISIONING_PENDING, $subscription->fresh()->provisioning_status);

            DB::commit();
        } catch (\Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            throw $exception;
        }

        $this->assertSame([0], $providerTransactionLevels);
        $this->assertSame('unpaid', $invoice->fresh()->status);
    }

    public function test_activation_outside_a_transaction_dispatches_without_losing_the_job(): void
    {
        [$order, , $subscription] = $this->makeOrderWithSubscription(Plan::TYPE_HOSTING);
        $providerTransactionLevels = [];
        $provisioning = Mockery::mock(TenantProvisioningService::class);
        $provisioning->shouldReceive('provision')
            ->once()
            ->withArgs(function (Subscription $queuedSubscription, bool $force) use (&$providerTransactionLevels, $subscription): bool {
                $providerTransactionLevels[] = DB::transactionLevel();
                $this->assertSame($subscription->id, $queuedSubscription->id);
                $this->assertFalse($force);

                return true;
            });
        $this->app->instance(TenantProvisioningService::class, $provisioning);

        $this->activationService()->activate($order->fresh(['invoices.items', 'items']));

        $this->assertSame([0], $providerTransactionLevels);
    }

    public function test_mark_paid_never_calls_whm_inside_settlement_and_provider_failure_cannot_rollback_payment(): void
    {
        Queue::fake();
        [$order, $invoice, $subscription] = $this->makeOrderWithSubscription(Plan::TYPE_HOSTING);
        $providerTransactionLevels = [];
        Http::fake(function (Request $request) use (&$providerTransactionLevels) {
            $providerTransactionLevels[] = DB::transactionLevel();

            return Http::response([
                'metadata' => [
                    'result' => 0,
                    'reason' => 'The selected package is not available.',
                ],
            ], 200);
        });

        (new InvoiceSettlementService($this->activationService()))->markPaid($invoice);

        Http::assertNothingSent();
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
        Queue::assertPushed(ProvisionSubscription::class, 1);

        /** @var ProvisionSubscription $job */
        $job = Queue::pushed(ProvisionSubscription::class)->first();
        $service = new TenantProvisioningService(
            new SubscriptionSyncService(),
            Mockery::mock(TemplateCloner::class),
            Mockery::mock(TenantDomainHostService::class)->shouldIgnoreMissing(),
            Mockery::mock(DomainVerificationService::class)->shouldIgnoreMissing(),
        );

        try {
            $job->handle($service);
            $this->fail('Confirmed WHM failure should be reported by the provisioning job.');
        } catch (RuntimeException) {
            // The queue failure occurs after the financial transaction committed.
        }

        $this->assertSame([0], $providerTransactionLevels);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertSame(Subscription::PROVISIONING_FAILED, $subscription->fresh()->provisioning_status);
    }

    public function test_timeout_after_payment_keeps_financial_state_and_does_not_send_createacct_again(): void
    {
        Queue::fake();
        [$order, $invoice, $subscription] = $this->makeOrderWithSubscription(Plan::TYPE_HOSTING);

        (new InvoiceSettlementService($this->activationService()))->markPaid($invoice);

        $calls = 0;
        Http::fake(function () use (&$calls) {
            $calls++;
            throw new RuntimeException('request timed out after transmission');
        });

        $service = new TenantProvisioningService(
            new SubscriptionSyncService(),
            Mockery::mock(TemplateCloner::class),
            Mockery::mock(TenantDomainHostService::class)->shouldIgnoreMissing(),
            Mockery::mock(DomainVerificationService::class)->shouldIgnoreMissing(),
        );
        /** @var ProvisionSubscription $job */
        $job = Queue::pushed(ProvisionSubscription::class)->first();

        $job->handle($service);
        $job->handle($service);

        $this->assertSame(1, $calls);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertSame(Subscription::PROVISIONING_UNKNOWN, $subscription->fresh()->provisioning_status);
    }

    public function test_multi_tenant_provisioning_is_also_deferred_to_the_job(): void
    {
        Queue::fake();
        [$order, $invoice, $subscription] = $this->makeOrderWithSubscription(Plan::TYPE_MULTI_TENANT);

        (new InvoiceSettlementService($this->activationService()))->markPaid($invoice);

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(Subscription::PROVISIONING_PENDING, $subscription->fresh()->provisioning_status);
        Queue::assertPushed(ProvisionSubscription::class, 1);

        $service = Mockery::mock(TenantProvisioningService::class);
        $service->shouldReceive('provision')
            ->once()
            ->withArgs(function (Subscription $queuedSubscription, bool $force): bool {
                $this->assertSame(0, DB::transactionLevel());
                $this->assertSame(Plan::TYPE_MULTI_TENANT, $queuedSubscription->plan->plan_type);
                $this->assertFalse($force);

                return true;
            });

        /** @var ProvisionSubscription $job */
        $job = Queue::pushed(ProvisionSubscription::class)->first();
        $job->handle($service);
    }

    public function test_order_activation_has_no_direct_tenant_provisioning_call(): void
    {
        $source = file_get_contents(app_path('Services/Billing/OrderActivationService.php'));

        $this->assertStringNotContainsString('TenantProvisioningService', $source);
        $this->assertStringNotContainsString('SubscriptionSyncService', $source);
        $this->assertStringContainsString('ProvisionSubscription::dispatch', $source);
        $this->assertStringContainsString('->afterCommit()', $source);
    }

    private function makeOrderWithTwoDraftInvoices(): array
    {
        $client = Client::query()->create([
            'first_name' => 'Invoice',
            'last_name' => 'Regression',
            'email' => uniqid('invoice_regression_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Invoice Regression Test',
        ]);

        $order = Order::query()->create([
            'client_id' => $client->id,
            'status' => Order::STATUS_PENDING,
            'type' => 'subscription',
        ]);

        $invoiceA = $this->makeDraftInvoice($client, $order, 'A');
        $invoiceB = $this->makeDraftInvoice($client, $order, 'B');

        return [$order, $invoiceA, $invoiceB];
    }

    private function makeDraftInvoice(Client $client, Order $order, string $suffix): Invoice
    {
        return Invoice::query()->create([
            'client_id' => $client->id,
            'order_id' => $order->id,
            'number' => 'INV-' . $suffix . '-' . uniqid(),
            'status' => 'draft',
            'subtotal_cents' => 1000,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 1000,
            'currency' => 'USD',
        ]);
    }

    private function makeOrderWithSubscription(string $planType): array
    {
        $client = Client::query()->create([
            'first_name' => 'Provisioning',
            'last_name' => 'After Commit',
            'email' => uniqid('after_commit_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'After Commit Test',
        ]);

        $server = Server::query()->create([
            'name' => 'After Commit WHM',
            'type' => 'cpanel',
            'hostname' => 'whm.example.test',
            'username' => 'root',
            'api_token' => 'test-token',
            'is_active' => true,
        ]);

        $plan = Plan::query()->create([
            'name' => 'After Commit Plan',
            'slug' => uniqid('after-commit-plan-', false),
            'plan_type' => $planType,
            'server_id' => $server->id,
            'server_package' => 'after_commit_package',
            'is_active' => true,
        ]);

        $subscription = Subscription::query()->create([
            'client_id' => $client->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'provisioning_status' => Subscription::PROVISIONING_PENDING,
            'price_cents' => 1000,
            'billing_cycle' => 'monthly',
            'username' => 'aftercommit',
            'server_id' => $server->id,
            'server_package' => 'after_commit_package',
            'domain_option' => 'subdomain',
            'domain_name' => uniqid('tenant-', false) . '.example.test',
            'subdomain' => uniqid('tenant-', false),
        ]);

        $order = Order::query()->create([
            'client_id' => $client->id,
            'status' => Order::STATUS_PENDING,
            'type' => 'subscription',
        ]);

        $invoice = $this->makeDraftInvoice($client, $order, 'PROVISION');
        InvoiceItem::query()->create([
            'invoice_id' => $invoice->id,
            'item_type' => 'subscription',
            'reference_id' => $subscription->id,
            'description' => 'Provision subscription after commit',
            'qty' => 1,
            'unit_price_cents' => 1000,
            'total_cents' => 1000,
        ]);

        return [$order->fresh(['invoices.items', 'items']), $invoice, $subscription];
    }

    private function activationService(): OrderActivationService
    {
        return new OrderActivationService(Mockery::mock(RegistrarProvisioningService::class));
    }
}
