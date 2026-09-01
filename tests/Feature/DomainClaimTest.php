<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\DomainRegistrationClaim;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Domains\RegistrarProvisioningService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class DomainClaimTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    public function test_success_completes_global_claim_and_second_order_never_calls_provider(): void
    {
        [$firstOrder, $firstItem] = $this->makeOrder('Example.COM.');
        [$secondOrder, $secondItem] = $this->makeOrder('example.com');
        $service = $this->fakeRegistrar([
            ['ok' => true, 'reason' => 'ok', 'provider_reference' => 'success-1'],
        ]);

        $firstResult = $service->provisionOrderDomain($firstOrder);
        $secondResult = $service->provisionOrderDomain($secondOrder);

        $this->assertTrue($firstResult['ok']);
        $this->assertFalse($secondResult['ok']);
        $this->assertSame(1, $service->registerCalls);
        $claim = DomainRegistrationClaim::query()->sole();
        $this->assertSame('example.com', $claim->domain_name_normalized);
        $this->assertSame(DomainRegistrationClaim::STATUS_COMPLETED, $claim->status);
        $this->assertSame($firstItem->id, $claim->order_item_id);
        $this->assertSame(OrderItem::PROVISIONING_COMPLETED, $firstItem->fresh()->provisioning_status);
        $this->assertSame(OrderItem::PROVISIONING_NOT_STARTED, $secondItem->fresh()->provisioning_status);
    }

    public function test_confirmed_failure_releases_claim_and_retry_can_acquire_it(): void
    {
        [$order, $item] = $this->makeOrder('retry-claim.com');
        $service = $this->fakeRegistrar([
            [
                'ok' => false,
                'reason' => 'provider_error',
                'message' => 'Confirmed rejection.',
                'definitive' => true,
            ],
            ['ok' => true, 'reason' => 'ok', 'provider_reference' => 'retry-success'],
        ]);

        $firstResult = $service->provisionOrderDomain($order);
        $this->assertFalse($firstResult['ok']);
        $this->assertSame(DomainRegistrationClaim::STATUS_RELEASED, DomainRegistrationClaim::query()->sole()->status);
        $this->assertNotNull(DomainRegistrationClaim::query()->sole()->released_at);
        $this->assertSame(OrderItem::PROVISIONING_FAILED, $item->fresh()->provisioning_status);

        $secondResult = $service->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));
        $this->assertTrue($secondResult['ok']);
        $this->assertSame(2, $service->registerCalls);
        $claim = DomainRegistrationClaim::query()->sole();
        $this->assertSame(DomainRegistrationClaim::STATUS_COMPLETED, $claim->status);
        $this->assertNull($claim->released_at);
        $this->assertSame($item->id, $claim->order_item_id);
    }

    public function test_indeterminate_result_keeps_claimed_and_blocks_another_order(): void
    {
        [$firstOrder, $firstItem] = $this->makeOrder('timeout-claim.com');
        [$secondOrder, $secondItem] = $this->makeOrder('timeout-claim.com');
        $service = $this->fakeRegistrar([[
            'ok' => false,
            'reason' => 'timeout',
            'message' => 'Connection timed out.',
            'definitive' => false,
        ]]);

        $firstResult = $service->provisionOrderDomain($firstOrder);
        $secondResult = $service->provisionOrderDomain($secondOrder);

        $this->assertFalse($firstResult['ok']);
        $this->assertFalse($secondResult['ok']);
        $this->assertSame(1, $service->registerCalls);
        $claim = DomainRegistrationClaim::query()->sole();
        $this->assertSame(DomainRegistrationClaim::STATUS_CLAIMED, $claim->status);
        $this->assertSame($firstItem->id, $claim->order_item_id);
        $this->assertNull($claim->released_at);
        $this->assertSame(OrderItem::PROVISIONING_IN_PROGRESS, $firstItem->fresh()->provisioning_status);
        $this->assertSame(OrderItem::PROVISIONING_NOT_STARTED, $secondItem->fresh()->provisioning_status);
    }

    public function test_unique_collision_blocks_duplicate_claim_and_provider_call(): void
    {
        [, $firstItem] = $this->makeOrder('unique-claim.com');
        [$secondOrder, $secondItem] = $this->makeOrder('unique-claim.com');
        DomainRegistrationClaim::query()->create([
            'domain_name_normalized' => 'unique-claim.com',
            'order_item_id' => $firstItem->id,
            'status' => DomainRegistrationClaim::STATUS_CLAIMED,
            'claimed_at' => now(),
        ]);

        try {
            DomainRegistrationClaim::query()->create([
                'domain_name_normalized' => 'unique-claim.com',
                'order_item_id' => $secondItem->id,
                'status' => DomainRegistrationClaim::STATUS_CLAIMED,
                'claimed_at' => now(),
            ]);
            $this->fail('The database accepted two global claims for the same normalized domain.');
        } catch (QueryException) {
            $this->assertSame(1, DomainRegistrationClaim::query()->count());
        }

        $service = $this->fakeRegistrar([]);
        $result = $service->provisionOrderDomain($secondOrder);

        $this->assertFalse($result['ok']);
        $this->assertSame(0, $service->registerCalls);
        $this->assertSame(OrderItem::PROVISIONING_NOT_STARTED, $secondItem->fresh()->provisioning_status);
    }

    public function test_provider_is_called_only_after_claim_is_committed(): void
    {
        [$order, $item] = $this->makeOrder('committed-claim.com');
        $service = $this->fakeRegistrar([
            ['ok' => true, 'reason' => 'ok'],
        ]);

        $service->provisionOrderDomain($order);

        $this->assertSame(1, $service->registerCalls);
        $this->assertSame([0], $service->transactionLevels);
        $this->assertSame([DomainRegistrationClaim::STATUS_CLAIMED], $service->claimStatusesAtProvider);
        $this->assertSame($item->id, DomainRegistrationClaim::query()->sole()->order_item_id);
    }

    private function makeOrder(string $domain): array
    {
        $client = Client::query()->create([
            'first_name' => 'Domain',
            'last_name' => 'Claim',
            'email' => uniqid('domain_claim_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Domain Claim Test',
        ]);
        $provider = DomainProvider::query()->firstOrCreate(
            ['type' => 'enom'],
            [
                'name' => 'Enom Claim Test',
                'username' => 'test-user',
                'password' => 'test-password',
                'is_active' => true,
                'mode' => 'test',
            ]
        );
        $order = Order::query()->create([
            'client_id' => $client->id,
            'status' => Order::STATUS_PENDING,
            'type' => 'domains',
        ]);
        $item = $order->items()->create([
            'domain' => $domain,
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

        return [$order->fresh(['client', 'items', 'invoices.items']), $item];
    }

    private function fakeRegistrar(array $results): RegistrarProvisioningService
    {
        return new class($results) extends RegistrarProvisioningService
        {
            public int $registerCalls = 0;
            public array $transactionLevels = [];
            public array $claimStatusesAtProvider = [];

            public function __construct(private array $results) {}

            protected function registerDomainWithProvider(
                DomainProvider $provider,
                Domain $domain,
                array $context,
                array $contact
            ): array {
                $this->registerCalls++;
                $this->transactionLevels[] = \Illuminate\Support\Facades\DB::transactionLevel();
                $this->claimStatusesAtProvider[] = DomainRegistrationClaim::query()
                    ->where('domain_name_normalized', $domain->domain_name)
                    ->value('status');

                return array_shift($this->results) ?? [
                    'ok' => false,
                    'reason' => 'unexpected_call',
                    'message' => 'Provider should not have been called.',
                    'definitive' => true,
                ];
            }
        };
    }
}
