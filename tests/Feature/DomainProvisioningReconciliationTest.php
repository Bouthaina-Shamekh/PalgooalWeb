<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\DomainProvisioningAttempt;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Domains\DomainProvisioningReconciliationService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class DomainProvisioningReconciliationTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    public function test_namecheap_registered_by_us_is_applied_atomically(): void
    {
        [$attempt, $item, $domain] = $this->makeAttempt('namecheap');

        Http::fake([
            '*' => Http::response($this->namecheapOwnedXml($domain->domain_name), 200, [
                'Content-Type' => 'application/xml',
            ]),
        ]);

        $result = app(DomainProvisioningReconciliationService::class)
            ->reconcileAttempt($attempt, apply: true);

        $this->assertSame(DomainProvisioningReconciliationService::STATUS_REGISTERED_BY_US, $result['status']);
        $this->assertTrue($result['applied']);
        $this->assertSame(DomainProvisioningAttempt::STATUS_COMPLETED, $attempt->fresh()->status);
        $this->assertSame('736542', $attempt->fresh()->provider_domain_id);
        $this->assertSame(OrderItem::PROVISIONING_COMPLETED, $item->fresh()->provisioning_status);
        $this->assertSame('active', $domain->fresh()->status);
        $this->assertNotNull($domain->fresh()->registration_date);
        $this->assertNotNull($domain->fresh()->renewal_date);
        $this->assertArrayNotHasKey('xml', $attempt->fresh()->response_payload ?? []);
        Http::assertSentCount(1);
    }

    public function test_enom_registered_by_us_is_applied_atomically(): void
    {
        [$attempt, $item, $domain] = $this->makeAttempt('enom');

        Http::fake([
            '*' => Http::response($this->enomOwnedXml(), 200, [
                'Content-Type' => 'application/xml',
            ]),
        ]);

        $result = app(DomainProvisioningReconciliationService::class)
            ->reconcileAttempt($attempt, apply: true);

        $this->assertSame(DomainProvisioningReconciliationService::STATUS_REGISTERED_BY_US, $result['status']);
        $this->assertTrue($result['applied']);
        $this->assertSame(DomainProvisioningAttempt::STATUS_COMPLETED, $attempt->fresh()->status);
        $this->assertSame('152809531', $attempt->fresh()->provider_domain_id);
        $this->assertSame(OrderItem::PROVISIONING_COMPLETED, $item->fresh()->provisioning_status);
        $this->assertSame('active', $domain->fresh()->status);
        $this->assertSame('2026-08-01', $domain->fresh()->registration_date);
        $this->assertSame('2027-08-01', $domain->fresh()->renewal_date);
        Http::assertSentCount(1);
    }

    public function test_provider_processing_does_not_change_local_state(): void
    {
        $this->assertNonConclusiveResultDoesNotWrite(
            DomainProvisioningReconciliationService::STATUS_PROVIDER_PROCESSING
        );
    }

    public function test_external_unavailable_does_not_change_local_state(): void
    {
        $this->assertNonConclusiveResultDoesNotWrite(
            DomainProvisioningReconciliationService::STATUS_EXTERNAL_UNAVAILABLE
        );
    }

    public function test_likely_not_sent_does_not_change_local_state(): void
    {
        $this->assertNonConclusiveResultDoesNotWrite(
            DomainProvisioningReconciliationService::STATUS_LIKELY_NOT_SENT
        );
    }

    public function test_indeterminate_does_not_change_local_state(): void
    {
        $this->assertNonConclusiveResultDoesNotWrite(
            DomainProvisioningReconciliationService::STATUS_INDETERMINATE
        );
    }

    public function test_dry_run_does_not_write_even_when_registered_by_us(): void
    {
        [$attempt, $item, $domain] = $this->makeAttempt('namecheap');
        $service = $this->fakeService(DomainProvisioningReconciliationService::STATUS_REGISTERED_BY_US);
        $this->app->instance(DomainProvisioningReconciliationService::class, $service);

        $this->artisan('domains:reconcile-provisioning', [
            '--attempt' => $attempt->getKey(),
            '--older-than' => 30,
        ])->assertSuccessful();

        $this->assertSame(1, $service->providerChecks);
        $this->assertSame(DomainProvisioningAttempt::STATUS_INITIATED, $attempt->fresh()->status);
        $this->assertSame(OrderItem::PROVISIONING_IN_PROGRESS, $item->fresh()->provisioning_status);
        $this->assertSame('pending', $domain->fresh()->status);
    }

    public function test_attempt_whose_order_item_is_not_in_progress_is_not_applied(): void
    {
        [$attempt, $item, $domain] = $this->makeAttempt('enom');
        $item->forceFill(['provisioning_status' => OrderItem::PROVISIONING_FAILED])->save();
        $service = $this->fakeService(DomainProvisioningReconciliationService::STATUS_REGISTERED_BY_US);

        $result = $service->reconcileAttempt($attempt->fresh(), apply: true);

        $this->assertSame('skipped', $result['action']);
        $this->assertSame(0, $service->providerChecks);
        $this->assertSame(DomainProvisioningAttempt::STATUS_INITIATED, $attempt->fresh()->status);
        $this->assertSame(OrderItem::PROVISIONING_FAILED, $item->fresh()->provisioning_status);
        $this->assertSame('pending', $domain->fresh()->status);
    }

    public function test_non_register_order_item_is_not_applied(): void
    {
        [$attempt, $item, $domain] = $this->makeAttempt('enom');
        $item->forceFill(['item_option' => 'transfer'])->save();
        $service = $this->fakeService(DomainProvisioningReconciliationService::STATUS_REGISTERED_BY_US);

        $result = $service->reconcileAttempt($attempt->fresh(), apply: true);

        $this->assertSame('skipped', $result['action']);
        $this->assertSame(0, $service->providerChecks);
        $this->assertSame(DomainProvisioningAttempt::STATUS_INITIATED, $attempt->fresh()->status);
        $this->assertSame(OrderItem::PROVISIONING_IN_PROGRESS, $item->fresh()->provisioning_status);
        $this->assertSame('pending', $domain->fresh()->status);
    }

    public function test_state_change_between_lookup_and_apply_prevents_stale_write(): void
    {
        [$attempt, $item, $domain] = $this->makeAttempt('enom');

        $service = new class extends DomainProvisioningReconciliationService {
            protected function inspectProvider(
                DomainProvisioningAttempt $attempt,
                DomainProvider $provider,
                string $domainName
            ): array {
                OrderItem::query()->whereKey($attempt->order_item_id)->update([
                    'provisioning_status' => OrderItem::PROVISIONING_FAILED,
                ]);

                return [
                    'status' => self::STATUS_REGISTERED_BY_US,
                    'provider_domain_id' => 'STALE-123',
                    'safe_payload' => ['provider_type' => 'enom'],
                ];
            }
        };

        $result = $service->reconcileAttempt($attempt, apply: true);

        $this->assertSame('stale_no_change', $result['action']);
        $this->assertFalse($result['applied']);
        $this->assertSame(DomainProvisioningAttempt::STATUS_INITIATED, $attempt->fresh()->status);
        $this->assertSame(OrderItem::PROVISIONING_FAILED, $item->fresh()->provisioning_status);
        $this->assertSame('pending', $domain->fresh()->status);
    }

    public function test_command_reconciles_each_order_item_independently(): void
    {
        [$firstAttempt, $firstItem, $firstDomain] = $this->makeAttempt('enom');
        [$secondAttempt, $secondItem, $secondDomain] = $this->makeAttempt('namecheap');
        $service = $this->fakeService(DomainProvisioningReconciliationService::STATUS_REGISTERED_BY_US);
        $this->app->instance(DomainProvisioningReconciliationService::class, $service);

        $this->artisan('domains:reconcile-provisioning', [
            '--apply' => true,
            '--older-than' => 30,
        ])->assertSuccessful();

        $this->assertSame(2, $service->providerChecks);
        $this->assertSame(DomainProvisioningAttempt::STATUS_COMPLETED, $firstAttempt->fresh()->status);
        $this->assertSame(DomainProvisioningAttempt::STATUS_COMPLETED, $secondAttempt->fresh()->status);
        $this->assertSame(OrderItem::PROVISIONING_COMPLETED, $firstItem->fresh()->provisioning_status);
        $this->assertSame(OrderItem::PROVISIONING_COMPLETED, $secondItem->fresh()->provisioning_status);
        $this->assertSame('active', $firstDomain->fresh()->status);
        $this->assertSame('active', $secondDomain->fresh()->status);
    }

    protected function assertNonConclusiveResultDoesNotWrite(string $status): void
    {
        [$attempt, $item, $domain] = $this->makeAttempt('enom');
        $service = $this->fakeService($status);

        $result = $service->reconcileAttempt($attempt, apply: true);

        $this->assertSame($status, $result['status']);
        $this->assertSame('no_change', $result['action']);
        $this->assertFalse($result['applied']);
        $this->assertSame(DomainProvisioningAttempt::STATUS_INITIATED, $attempt->fresh()->status);
        $this->assertSame(OrderItem::PROVISIONING_IN_PROGRESS, $item->fresh()->provisioning_status);
        $this->assertSame('pending', $domain->fresh()->status);
    }

    protected function fakeService(string $status): DomainProvisioningReconciliationService
    {
        return new class($status) extends DomainProvisioningReconciliationService {
            public int $providerChecks = 0;

            public function __construct(protected string $fakeStatus) {}

            protected function inspectProvider(
                DomainProvisioningAttempt $attempt,
                DomainProvider $provider,
                string $domainName
            ): array {
                $this->providerChecks++;
                $this->assertOutsideTransaction();

                return [
                    'status' => $this->fakeStatus,
                    'provider_reference' => 'REF-' . $attempt->getKey(),
                    'provider_domain_id' => 'DOMAIN-' . $attempt->getKey(),
                    'registered_at' => '2026-08-01',
                    'expires_at' => '2027-08-01',
                    'message' => 'Safe test result.',
                    'safe_payload' => ['provider_type' => $attempt->provider_type],
                ];
            }

            protected function assertOutsideTransaction(): void
            {
                if (DB::transactionLevel() !== 0) {
                    throw new \RuntimeException('Provider reconciliation lookup ran inside a transaction.');
                }
            }
        };
    }

    protected function makeAttempt(
        string $providerType,
        string $attemptStatus = DomainProvisioningAttempt::STATUS_INITIATED
    ): array {
        $client = Client::create([
            'first_name' => 'Reconcile',
            'last_name' => 'Client',
            'email' => 'reconcile_' . uniqid() . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Reconciliation Test',
        ]);

        $provider = DomainProvider::create([
            'name' => ucfirst($providerType) . ' Reconciliation ' . uniqid(),
            'type' => $providerType,
            'endpoint' => 'https://' . $providerType . '.example.test/api',
            'username' => 'test-user',
            'password' => 'test-password',
            'api_key' => 'test-api-key',
            'client_ip' => '127.0.0.1',
            'is_active' => true,
            'mode' => 'test',
        ]);

        $order = Order::create([
            'client_id' => $client->id,
            'status' => 'pending',
            'type' => 'domains',
        ]);

        $domainName = 'reconcile-' . Str::lower(Str::random(10)) . '.com';
        $item = $order->items()->create([
            'domain' => $domainName,
            'item_option' => 'register',
            'price_cents' => 1000,
            'provisioning_status' => OrderItem::PROVISIONING_IN_PROGRESS,
            'provisioning_started_at' => now()->subHour(),
        ]);

        $domain = Domain::create([
            'client_id' => $client->id,
            'domain_name' => $domainName,
            'registrar' => $providerType,
            'registration_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'pending',
        ]);

        $attempt = DomainProvisioningAttempt::create([
            'order_item_id' => $item->id,
            'domain_id' => $domain->id,
            'provider_id' => $provider->id,
            'attempt_uuid' => (string) Str::uuid(),
            'operation' => DomainProvisioningAttempt::OPERATION_REGISTER,
            'provider_type' => $providerType,
            'provider_mode' => 'test',
            'status' => $attemptStatus,
            'started_at' => now()->subHour(),
        ]);

        return [$attempt, $item, $domain, $provider];
    }

    protected function namecheapOwnedXml(string $domainName): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse xmlns="http://api.namecheap.com/xml.response" Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.domains.getinfo">
    <DomainGetInfoResult Status="Ok" ID="736542" DomainName="{$domainName}" IsOwner="true">
      <DomainDetails>
        <CreatedDate>08/01/2026</CreatedDate>
        <ExpiredDate>08/01/2027</ExpiredDate>
      </DomainDetails>
    </DomainGetInfoResult>
  </CommandResponse>
</ApiResponse>
XML;
    }

    protected function enomOwnedXml(): string
    {
        return <<<'XML'
<?xml version="1.0"?>
<interface-response>
  <GetDomainInfo>
    <domainname domainnameid="152809531"></domainname>
    <status>
      <expiration>2027-08-01</expiration>
      <registrationstatus>Registered</registrationstatus>
      <purchase-status>Paid</purchase-status>
      <belongs-to party-id="{39AE68C0-D019-4690-9999-FD632BC1AFAA}"></belongs-to>
    </status>
  </GetDomainInfo>
  <DomainInfo><RegistryCreateDate>2026-08-01</RegistryCreateDate></DomainInfo>
  <Command>GETDOMAININFO</Command>
  <ErrCount>0</ErrCount>
  <ResponseCount>0</ResponseCount>
  <Done>true</Done>
</interface-response>
XML;
    }
}
