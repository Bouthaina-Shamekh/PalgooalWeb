<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\DomainProvisioningAttempt;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Billing\OrderActivationService;
use App\Services\Domains\RegistrarProvisioningService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MultiDomainProvisioningTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    public function test_multi_domain_order_calls_provider_for_every_domain(): void
    {
        [$order, $items] = $this->makeOrder(['multi-a.test', 'multi-b.test', 'multi-c.test']);
        $registrar = $this->fakeRegistrar([]);

        $result = DB::transaction(
            fn () => (new OrderActivationService($registrar))->activate($order)
        );

        $this->assertTrue($result['domain_registration']['ok']);
        $this->assertSame(['multi-a.test', 'multi-b.test', 'multi-c.test'], $registrar->providerCalls);
        $this->assertCount(3, $result['domain_registration']['domains']);
        $this->assertSame(3, DomainProvisioningAttempt::query()->count());

        foreach ($items as $item) {
            $this->assertSame(OrderItem::PROVISIONING_COMPLETED, $item->fresh()->provisioning_status);
        }
    }

    public function test_one_domain_failure_does_not_stop_later_domains_and_invoice_links_stay_independent(): void
    {
        [$order, $items, $invoice] = $this->makeOrder(
            ['result-a.test', 'result-b.test', 'result-c.test'],
            withInvoice: true
        );
        $registrar = $this->fakeRegistrar([
            'result-b.test' => false,
        ]);

        $result = (new OrderActivationService($registrar))->activate($order);

        $this->assertFalse($result['domain_registration']['ok']);
        $this->assertSame(['result-a.test', 'result-b.test', 'result-c.test'], $registrar->providerCalls);
        $this->assertSame(
            [
                'result-a.test' => OrderItem::PROVISIONING_COMPLETED,
                'result-b.test' => OrderItem::PROVISIONING_FAILED,
                'result-c.test' => OrderItem::PROVISIONING_COMPLETED,
            ],
            $items->mapWithKeys(fn (OrderItem $item) => [
                $item->domain => $item->fresh()->provisioning_status,
            ])->all()
        );

        $reportedStatuses = collect($result['domain_registration']['domains'])
            ->mapWithKeys(fn (array $domainResult) => [
                $domainResult['domain'] => $domainResult['provisioning_status'],
            ])
            ->all();

        $this->assertSame(OrderItem::PROVISIONING_COMPLETED, $reportedStatuses['result-a.test']);
        $this->assertSame(OrderItem::PROVISIONING_FAILED, $reportedStatuses['result-b.test']);
        $this->assertSame(OrderItem::PROVISIONING_COMPLETED, $reportedStatuses['result-c.test']);

        $domainIds = Domain::query()->pluck('id', 'domain_name');
        $invoiceItems = $invoice->items()->orderBy('id')->get();

        $this->assertSame($domainIds['result-a.test'], $invoiceItems[0]->reference_id);
        $this->assertSame($domainIds['result-b.test'], $invoiceItems[1]->reference_id);
        $this->assertSame($domainIds['result-c.test'], $invoiceItems[2]->reference_id);
        $this->assertNotSame($invoiceItems[0]->reference_id, $invoiceItems[2]->reference_id);
        $this->assertSame(3, $invoiceItems->pluck('reference_id')->unique()->count());
    }

    public function test_domain_activation_path_does_not_select_the_first_order_item(): void
    {
        $activationSource = file_get_contents(app_path('Services/Billing/OrderActivationService.php'));
        $registrarSource = file_get_contents(app_path('Services/Domains/RegistrarProvisioningService.php'));

        $this->assertIsString($activationSource);
        $this->assertIsString($registrarSource);
        $this->assertStringNotContainsString('extractDomainData', $activationSource);
        $this->assertStringNotContainsString('$order->items->first', $activationSource);
        $this->assertStringNotContainsString('$order->items()->first', $activationSource);
        $this->assertStringNotContainsString('$order->items->first', $registrarSource);
        $this->assertStringNotContainsString('$order->items()->first', $registrarSource);
    }

    protected function makeOrder(array $domainNames, bool $withInvoice = false): array
    {
        $client = Client::query()->create([
            'first_name' => 'Multi',
            'last_name' => 'Domain',
            'email' => 'multi_' . uniqid() . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Multi Domain Test',
        ]);

        $provider = DomainProvider::query()->create([
            'name' => 'Multi Domain eNom',
            'type' => 'enom',
            'username' => 'test-user',
            'password' => 'test-password',
            'is_active' => true,
            'mode' => 'test',
        ]);

        $order = Order::query()->create([
            'client_id' => $client->getKey(),
            'status' => Order::STATUS_PENDING,
            'type' => 'domains',
        ]);

        $items = collect($domainNames)->map(function (string $domainName) use ($order, $provider): OrderItem {
            return $order->items()->create([
                'domain' => $domainName,
                'item_option' => 'register',
                'price_cents' => 1000,
                'meta' => [
                    'provider_id' => $provider->id,
                    'provider_type' => $provider->type,
                    'provider_mode' => $provider->mode,
                    'registration_date' => now()->toDateString(),
                    'renewal_date' => now()->addYear()->toDateString(),
                ],
                'provisioning_status' => OrderItem::PROVISIONING_NOT_STARTED,
            ]);
        });

        $invoice = null;

        if ($withInvoice) {
            $invoice = Invoice::query()->create([
                'client_id' => $client->getKey(),
                'order_id' => $order->getKey(),
                'number' => 'INV-' . $order->order_number,
                'status' => 'paid',
                'subtotal_cents' => 3000,
                'total_cents' => 3000,
                'currency' => 'USD',
            ]);

            foreach ($domainNames as $domainName) {
                $invoice->items()->create([
                    'item_type' => 'domain',
                    'reference_id' => null,
                    'description' => 'Domain registration: ' . $domainName,
                    'qty' => 1,
                    'unit_price_cents' => 1000,
                    'total_cents' => 1000,
                ]);
            }
        }

        return [$order->fresh(['client', 'items', 'invoices.items']), $items, $invoice];
    }

    protected function fakeRegistrar(array $outcomes): RegistrarProvisioningService
    {
        return new class($outcomes) extends RegistrarProvisioningService {
            public array $providerCalls = [];

            public function __construct(protected array $outcomes) {}

            protected function registerDomainWithProvider(
                DomainProvider $provider,
                Domain $domain,
                array $context,
                array $contact
            ): array {
                $this->providerCalls[] = $domain->domain_name;

                if (($this->outcomes[$domain->domain_name] ?? true) === false) {
                    return [
                        'ok' => false,
                        'reason' => 'provider_error',
                        'message' => 'Simulated definitive registrar failure.',
                        'definitive' => true,
                    ];
                }

                return [
                    'ok' => true,
                    'reason' => 'ok',
                    'provider_reference' => 'REF-' . $domain->domain_name,
                    'provider_domain_id' => 'ID-' . $domain->domain_name,
                ];
            }
        };
    }
}
