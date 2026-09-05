<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Domain;
use App\Models\DomainProvisioningAttempt;
use App\Models\DomainProvider;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Domains\RegistrarProvisioningService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ADR — Provisioning Idempotency Phase 1 (Register Domain فقط).
 *
 * تغطي هذه الاختبارات فقط سلوك OrderItem.provisioning_status داخل
 * RegistrarProvisioningService::provisionOrderDomain() لعملية "register":
 *   completed    → لا يُستدعى المزوّد إطلاقًا.
 *   in_progress  → لا يُستدعى المزوّد إطلاقًا (يعاد نتيجة فشل واضحة).
 *   failed       → يُسمح بإعادة المحاولة (يُستدعى المزوّد مجددًا).
 *   not_started  → in_progress ثم completed بعد نجاح المزوّد.
 *   فشل المزوّد  → الحالة تصبح failed.
 *
 * الاستدعاء الفعلي للمزوّد (HTTP) مُستبدَل بـ registerDomainWithProvider() المُتجاوَزة في
 * subclass تجريبي — لا يوجد أي اتصال شبكة حقيقي هنا. باقي المسار (Order/OrderItem/Domain
 * عبر Eloquent) يعمل فعليًا ضد قاعدة SQLite في الذاكرة (phpunit.xml: DB_CONNECTION=sqlite,
 * DB_DATABASE=:memory:). يُستخدم migrate:fresh لكل اختبار كي لا تُحاط عملية afterCommit بمعاملة اختبارية.
 *
 * TLD-3F.1 — fixture note: the shared provider fixture in makeOrderWithRegisterItem() is now
 * mode='live'. This file tests provisioning state-machine mechanics only (retry, locking,
 * idempotent skip, commit-before-call ordering, ambiguous-failure handling) — it was never
 * testing sandbox/live eligibility — but trustedRegistrationProvider() now rejects any
 * mode!=='live' provider outright, so the generic fixture had to move to 'live' to keep these
 * tests exercising the mechanics they were written for.
 */
class RegistrarProvisioningDomainTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    protected function makeOrderWithRegisterItem(string $provisioningStatus): array
    {
        $client = Client::create([
            'first_name'   => 'Test',
            'last_name'    => 'Client',
            'email'        => 'client_' . uniqid() . '@example.test',
            'password'     => bcrypt('secret-password'),
            'company_name' => 'Test Co',
        ]);

        $provider = DomainProvider::create([
            'name'      => 'Enom Test Provider',
            'type'      => 'enom',
            'username'  => 'testuser',
            'password'  => 'testpass',
            'is_active' => true,
            'mode'      => 'live',
        ]);

        $order = Order::create([
            'client_id' => $client->id,
            'status'    => 'pending',
            'type'      => 'domains',
        ]);

        $orderItem = $order->items()->create([
            'domain'      => 'example-' . uniqid() . '.com',
            'item_option' => 'register',
            'price_cents' => 1000,
            'meta'        => [
                'provider_id' => $provider->id,
                'provider_type' => $provider->type,
                'provider_mode' => $provider->mode,
                'registration_date' => now()->toDateString(),
                'renewal_date'      => now()->addYear()->toDateString(),
            ],
            'provisioning_status' => $provisioningStatus,
        ]);

        return [$order->fresh(), $orderItem];
    }

    /**
     * subclass تجريبي: يتجاوز registerDomainWithProvider() فقط (الحد الفاصل مع الشبكة
     * الخارجية) ليعيد نتيجة محدَّدة سلفًا، ويُحصي عدد مرات استدعائه — دون أي منطق آخر مُتغيَّر.
     */
    protected function fakeRegistrarService(
        bool $ok,
        string $message = '',
        bool $crash = false,
        ?bool $definitive = null
    ): RegistrarProvisioningService
    {
        return new class($ok, $message, $crash, $definitive) extends RegistrarProvisioningService {
            public int $registerCalls = 0;
            public int $renewCalls = 0;
            public array $providerTransactionLevels = [];
            public ?string $statusAtProviderCall = null;
            public ?string $attemptStatusAtProviderCall = null;
            public ?int $attemptProviderIdAtProviderCall = null;
            public ?string $attemptProviderTypeAtProviderCall = null;
            public ?string $attemptProviderModeAtProviderCall = null;

            public function __construct(
                protected bool $ok,
                protected string $message,
                protected bool $crash,
                protected ?bool $definitive
            ) {}

            protected function registerDomainWithProvider(DomainProvider $provider, Domain $domain, array $context, array $contact): array
            {
                $this->registerCalls++;
                $this->providerTransactionLevels[] = DB::transactionLevel();
                $this->statusAtProviderCall = OrderItem::query()
                    ->where('domain', $domain->domain_name)
                    ->value('provisioning_status');
                $attempt = DomainProvisioningAttempt::query()->latest('id')->first();
                $this->attemptStatusAtProviderCall = $attempt?->status;
                $this->attemptProviderIdAtProviderCall = $attempt?->provider_id;
                $this->attemptProviderTypeAtProviderCall = $attempt?->provider_type;
                $this->attemptProviderModeAtProviderCall = $attempt?->provider_mode;

                if ($this->crash) {
                    throw new \RuntimeException('Simulated crash after registrar call started.');
                }

                if ($this->ok) {
                    return [
                        'ok' => true,
                        'reason' => 'ok',
                        'cid' => 'FAKE-CID-123',
                        'provider_reference' => 'FAKE-REFERENCE-123',
                        'provider_domain_id' => 'FAKE-DOMAIN-456',
                    ];
                }

                return array_filter([
                    'ok' => false,
                    'reason' => $this->definitive === false ? 'timeout' : 'provider_error',
                    'message' => $this->message !== '' ? $this->message : 'Simulated registrar failure.',
                    'definitive' => $this->definitive,
                ], fn ($value) => $value !== null);
            }

            protected function renewDomainWithProvider(DomainProvider $provider, Domain $domain, array $context): array
            {
                $this->renewCalls++;

                return ['ok' => true, 'cid' => 'FAKE-RENEW-CID'];
            }
        };
    }

    public function test_completed_order_item_skips_provider_call(): void
    {
        [$order, $orderItem] = $this->makeOrderWithRegisterItem(OrderItem::PROVISIONING_COMPLETED);

        $service = $this->fakeRegistrarService(true);
        $result  = $service->provisionOrderDomain($order);

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['skipped'] ?? false);
        $this->assertSame(0, $service->registerCalls);
        $this->assertSame(OrderItem::PROVISIONING_COMPLETED, $orderItem->fresh()->provisioning_status);
        $this->assertSame(0, DomainProvisioningAttempt::query()->count());
    }

    public function test_in_progress_order_item_skips_provider_call(): void
    {
        [$order, $orderItem] = $this->makeOrderWithRegisterItem(OrderItem::PROVISIONING_IN_PROGRESS);

        $service = $this->fakeRegistrarService(true);
        $result  = $service->provisionOrderDomain($order);

        $this->assertFalse($result['ok']);
        $this->assertSame(0, $service->registerCalls);
        $this->assertSame(OrderItem::PROVISIONING_IN_PROGRESS, $orderItem->fresh()->provisioning_status);
        $this->assertSame(0, DomainProvisioningAttempt::query()->count());
    }

    public function test_transfer_action_does_not_call_provider(): void
    {
        [$order, $orderItem] = $this->makeOrderWithRegisterItem(OrderItem::PROVISIONING_NOT_STARTED);
        $orderItem->update(['item_option' => 'transfer']);

        $service = $this->fakeRegistrarService(true);
        $result = $service->provisionOrderDomain($order);

        $this->assertFalse($result['ok']);
        $this->assertSame('Unsupported domain provisioning action.', $result['message']);
        $this->assertSame(0, $service->registerCalls);
        $this->assertSame(OrderItem::PROVISIONING_NOT_STARTED, $orderItem->fresh()->provisioning_status);
        $this->assertSame(0, DomainProvisioningAttempt::query()->count());
    }

    public function test_failed_order_item_allows_retry_and_completes(): void
    {
        [$order, $orderItem] = $this->makeOrderWithRegisterItem(OrderItem::PROVISIONING_FAILED);

        $service = $this->fakeRegistrarService(true);
        $result  = $service->provisionOrderDomain($order);

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $service->registerCalls);

        $fresh = $orderItem->fresh();
        $this->assertSame(OrderItem::PROVISIONING_COMPLETED, $fresh->provisioning_status);
        $this->assertNotNull($fresh->provisioning_completed_at);

        $attempt = DomainProvisioningAttempt::query()->sole();
        $this->assertSame(DomainProvisioningAttempt::STATUS_COMPLETED, $attempt->status);
    }

    public function test_not_started_transitions_to_in_progress_then_completed_on_success(): void
    {
        [$order, $orderItem] = $this->makeOrderWithRegisterItem(OrderItem::PROVISIONING_NOT_STARTED);

        $service = $this->fakeRegistrarService(true);
        $result  = $service->provisionOrderDomain($order);

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $service->registerCalls);

        $fresh = $orderItem->fresh();
        $this->assertSame(OrderItem::PROVISIONING_COMPLETED, $fresh->provisioning_status);
        $this->assertNotNull($fresh->provisioning_started_at);
        $this->assertNotNull($fresh->provisioning_completed_at);
        $this->assertSame([0], $service->providerTransactionLevels);
        $this->assertSame(OrderItem::PROVISIONING_IN_PROGRESS, $service->statusAtProviderCall);
        $this->assertSame(DomainProvisioningAttempt::STATUS_INITIATED, $service->attemptStatusAtProviderCall);

        $attempt = DomainProvisioningAttempt::query()->sole();
        $provider = DomainProvider::query()->sole();
        $this->assertSame(DomainProvisioningAttempt::STATUS_COMPLETED, $attempt->status);
        $this->assertSame($orderItem->id, $attempt->order_item_id);
        $this->assertNotNull($attempt->domain_id);
        $this->assertSame($provider->id, $attempt->provider_id);
        $this->assertSame('enom', $attempt->provider_type);
        $this->assertSame('live', $attempt->provider_mode);
        $this->assertSame(DomainProvisioningAttempt::OPERATION_REGISTER, $attempt->operation);
        $this->assertSame('FAKE-REFERENCE-123', $attempt->provider_reference);
        $this->assertSame('FAKE-DOMAIN-456', $attempt->provider_domain_id);
        $this->assertNotNull($attempt->started_at);
        $this->assertNotNull($attempt->finished_at);
        $this->assertSame($provider->id, $service->attemptProviderIdAtProviderCall);
        $this->assertSame('enom', $service->attemptProviderTypeAtProviderCall);
        $this->assertSame('live', $service->attemptProviderModeAtProviderCall);
    }

    public function test_external_transaction_commits_before_provider_call(): void
    {
        [$order, $orderItem] = $this->makeOrderWithRegisterItem(OrderItem::PROVISIONING_NOT_STARTED);
        $service = $this->fakeRegistrarService(true);

        $result = DB::transaction(
            fn () => DB::transaction(fn () => $service->provisionOrderDomain($order))
        );

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['deferred'] ?? false);
        $this->assertSame(1, $service->registerCalls);
        $this->assertSame([0], $service->providerTransactionLevels);
        $this->assertSame(OrderItem::PROVISIONING_IN_PROGRESS, $service->statusAtProviderCall);
        $this->assertSame(OrderItem::PROVISIONING_COMPLETED, $orderItem->fresh()->provisioning_status);
        $this->assertSame(DomainProvisioningAttempt::STATUS_COMPLETED, DomainProvisioningAttempt::query()->sole()->status);
    }

    public function test_provider_crash_leaves_in_progress_committed(): void
    {
        [$order, $orderItem] = $this->makeOrderWithRegisterItem(OrderItem::PROVISIONING_NOT_STARTED);
        $service = $this->fakeRegistrarService(true, '', true);

        $result = DB::transaction(fn () => $service->provisionOrderDomain($order));

        $this->assertTrue($result['deferred'] ?? false);
        $this->assertSame(1, $service->registerCalls);
        $this->assertSame([0], $service->providerTransactionLevels);
        $this->assertSame(OrderItem::PROVISIONING_IN_PROGRESS, $service->statusAtProviderCall);
        $this->assertSame(OrderItem::PROVISIONING_IN_PROGRESS, $orderItem->fresh()->provisioning_status);
        $attempt = DomainProvisioningAttempt::query()->sole();
        $this->assertSame(DomainProvisioningAttempt::STATUS_INITIATED, $attempt->status);
        $this->assertNull($attempt->finished_at);
    }

    public function test_provider_failure_sets_status_to_failed(): void
    {
        [$order, $orderItem] = $this->makeOrderWithRegisterItem(OrderItem::PROVISIONING_NOT_STARTED);

        $service = $this->fakeRegistrarService(false, 'Simulated registrar rejection.');
        $result  = $service->provisionOrderDomain($order);

        $this->assertFalse($result['ok']);
        $this->assertSame(1, $service->registerCalls);

        $fresh = $orderItem->fresh();
        $this->assertSame(OrderItem::PROVISIONING_FAILED, $fresh->provisioning_status);
        $this->assertNotNull($fresh->provisioning_started_at);
        $this->assertNull($fresh->provisioning_completed_at);

        $attempt = DomainProvisioningAttempt::query()->sole();
        $this->assertSame(DomainProvisioningAttempt::STATUS_CONFIRMED_FAILED, $attempt->status);
        $this->assertSame('provider_error', $attempt->response_payload['reason']);
        $this->assertSame('Simulated registrar rejection.', $attempt->response_payload['message']);
        $this->assertNotNull($attempt->finished_at);
    }

    public function test_ambiguous_provider_failure_keeps_in_progress(): void
    {
        [$order, $orderItem] = $this->makeOrderWithRegisterItem(OrderItem::PROVISIONING_NOT_STARTED);

        $service = $this->fakeRegistrarService(false, 'Simulated transport timeout.', false, false);
        $result = $service->provisionOrderDomain($order);

        $this->assertFalse($result['ok']);
        $this->assertSame(1, $service->registerCalls);
        $this->assertSame(OrderItem::PROVISIONING_IN_PROGRESS, $orderItem->fresh()->provisioning_status);

        $attempt = DomainProvisioningAttempt::query()->sole();
        $this->assertSame(DomainProvisioningAttempt::STATUS_INDETERMINATE, $attempt->status);
        $this->assertSame('timeout', $attempt->response_payload['reason']);
        $this->assertSame('Simulated transport timeout.', $attempt->response_payload['message']);
        $this->assertNotNull($attempt->finished_at);
    }

    public function test_existing_initiated_attempt_prevents_another_attempt_and_provider_call(): void
    {
        [$order, $orderItem] = $this->makeOrderWithRegisterItem(OrderItem::PROVISIONING_IN_PROGRESS);
        $provider = DomainProvider::query()->sole();
        $domain = Domain::query()->create([
            'client_id' => $order->client_id,
            'domain_name' => $orderItem->domain,
            'registrar' => $provider->type,
            'registration_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'status' => 'pending',
        ]);
        $existingAttempt = DomainProvisioningAttempt::query()->create([
            'order_item_id' => $orderItem->id,
            'domain_id' => $domain->id,
            'provider_id' => $provider->id,
            'attempt_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'operation' => DomainProvisioningAttempt::OPERATION_REGISTER,
            'provider_type' => $provider->type,
            'provider_mode' => $provider->mode,
            'status' => DomainProvisioningAttempt::STATUS_INITIATED,
            'started_at' => now(),
        ]);

        $service = $this->fakeRegistrarService(true);
        $result = $service->provisionOrderDomain($order);

        $this->assertFalse($result['ok']);
        $this->assertSame(0, $service->registerCalls);
        $this->assertSame(1, DomainProvisioningAttempt::query()->count());
        $this->assertSame(DomainProvisioningAttempt::STATUS_INITIATED, $existingAttempt->fresh()->status);
    }

    public function test_failed_item_retry_creates_a_new_attempt_without_changing_the_old_attempt(): void
    {
        [$order, $orderItem] = $this->makeOrderWithRegisterItem(OrderItem::PROVISIONING_FAILED);
        $provider = DomainProvider::query()->sole();
        $oldAttempt = DomainProvisioningAttempt::query()->create([
            'order_item_id' => $orderItem->id,
            'provider_id' => $provider->id,
            'attempt_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'operation' => DomainProvisioningAttempt::OPERATION_REGISTER,
            'provider_type' => $provider->type,
            'provider_mode' => $provider->mode,
            'status' => DomainProvisioningAttempt::STATUS_CONFIRMED_FAILED,
            'started_at' => now()->subMinute(),
            'finished_at' => now()->subMinute(),
            'response_payload' => ['reason' => 'provider_error'],
        ]);

        $service = $this->fakeRegistrarService(true);
        $result = $service->provisionOrderDomain($order);

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $service->registerCalls);
        $this->assertSame(2, DomainProvisioningAttempt::query()->count());
        $this->assertSame(DomainProvisioningAttempt::STATUS_CONFIRMED_FAILED, $oldAttempt->fresh()->status);

        $newAttempt = DomainProvisioningAttempt::query()->whereKeyNot($oldAttempt->id)->sole();
        $this->assertNotSame($oldAttempt->attempt_uuid, $newAttempt->attempt_uuid);
        $this->assertSame(DomainProvisioningAttempt::STATUS_COMPLETED, $newAttempt->status);
    }

    public function test_renew_action_does_not_create_register_attempt(): void
    {
        [$order, $orderItem] = $this->makeOrderWithRegisterItem(OrderItem::PROVISIONING_NOT_STARTED);
        $provider = DomainProvider::query()->sole();
        $orderItem->update([
            'item_option' => 'renew',
            'meta' => [
                'current_renewal_date' => now()->toDateString(),
                'renewal_date' => now()->addYear()->toDateString(),
                // TLD-3D — Hybrid Provider Identity: renewal provisioning now requires the exact
                // trusted provider snapshot on the OrderItem, cross-checked against the domain's
                // own Domain.provider_id below. This fixture is unrelated to that contract (it
                // only proves renew routes away from registration attempts), so the snapshot is
                // filled in from the same $provider the Domain below is given.
                'provider_id' => $provider->getKey(),
                'provider_type' => $provider->type,
                'provider_mode' => $provider->mode,
            ],
        ]);
        Domain::query()->create([
            'client_id' => $order->client_id,
            'domain_name' => $orderItem->domain,
            'registrar' => $provider->type,
            'provider_id' => $provider->getKey(),
            'registration_date' => now()->subYear()->toDateString(),
            'renewal_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $service = $this->fakeRegistrarService(true);
        $result = $service->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $service->renewCalls);
        $this->assertSame(0, $service->registerCalls);
        $this->assertSame(0, DomainProvisioningAttempt::query()->count());
    }
}
