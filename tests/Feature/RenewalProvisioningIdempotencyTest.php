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
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * TLD-3H.2A — Durable Enom Renewal Claim + At-Most-Once Extend.
 *
 * Covers the renewal-specific provisioning-idempotency test matrix required by TLD-3H.2A:
 * claim-before-Extend, at-most-once Extend across duplicate/retried activation, the
 * definitive/ambiguous (CONFIRMED_FAILED / INDETERMINATE) classification, transaction
 * deferral + rollback safety, and that renewal never touches the register-only attempt
 * population or provider fallback rules.
 *
 * The actual registrar HTTP call is replaced by renewDomainWithProvider() overridden in an
 * anonymous subclass below — no real network connection is made here. The rest of the path
 * (Order/OrderItem/Domain/DomainProvisioningAttempt via Eloquent) runs against the real
 * in-memory SQLite test database (phpunit.xml). migrate:fresh runs per test so that no
 * DB::afterCommit() callback is ever left wrapped inside a leftover test transaction.
 */
class RenewalProvisioningIdempotencyTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    /**
     * @return array{0: Order, 1: OrderItem, 2: Domain, 3: DomainProvider}
     */
    protected function makeRenewalOrder(
        string $provisioningStatus = OrderItem::PROVISIONING_NOT_STARTED,
        array $metaOverrides = []
    ): array {
        $client = Client::create([
            'first_name'   => 'Test',
            'last_name'    => 'Client',
            'email'        => 'client_' . uniqid() . '@example.test',
            'password'     => bcrypt('secret-password'),
            'company_name' => 'Test Co',
        ]);

        $provider = DomainProvider::create([
            'name'      => 'Enom Renewal Test Provider',
            'type'      => 'enom',
            'username'  => 'testuser',
            'password'  => 'testpass',
            'is_active' => true,
            'mode'      => 'live',
        ]);

        $domainName = 'renew-idem-' . uniqid() . '.com';

        $domain = Domain::create([
            'client_id' => $client->id,
            'domain_name' => $domainName,
            'registrar' => $provider->type,
            'provider_id' => $provider->id,
            'registration_date' => now()->subYear()->toDateString(),
            'renewal_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $order = Order::create([
            'client_id' => $client->id,
            'status'    => 'pending',
            'type'      => 'domain_renewal',
        ]);

        $defaultMeta = [
            'domain_id' => $domain->id,
            'current_renewal_date' => now()->toDateString(),
            'renewal_date' => now()->addYear()->toDateString(),
            'term_years' => 1,
            'provider_id' => $provider->id,
            'provider_type' => $provider->type,
            'provider_mode' => $provider->mode,
        ];

        $orderItem = $order->items()->create([
            'domain'      => $domainName,
            'item_option' => 'renew',
            'price_cents' => 2000,
            'meta'        => array_merge($defaultMeta, $metaOverrides),
            'provisioning_status' => $provisioningStatus,
        ]);

        return [$order->fresh(), $orderItem, $domain, $provider];
    }

    /**
     * Anonymous subclass: overrides only renewDomainWithProvider() (and registerDomainWithProvider()
     * as a tripwire — it must never be called from a renewal path) — the exact boundary with the
     * external registrar. No other logic is changed. No real network connection is made.
     */
    protected function fakeRenewalService(
        bool $ok,
        string $message = '',
        bool $crash = false,
        ?bool $definitive = null,
        ?string $reason = null
    ): RegistrarProvisioningService {
        return new class($ok, $message, $crash, $definitive, $reason) extends RegistrarProvisioningService {
            public int $renewCalls = 0;
            public int $registerCalls = 0;
            public array $providerTransactionLevels = [];
            public ?string $statusAtProviderCall = null;
            public ?string $attemptStatusAtProviderCall = null;

            public function __construct(
                protected bool $ok,
                protected string $message,
                protected bool $crash,
                protected ?bool $definitive,
                protected ?string $reason
            ) {}

            protected function renewDomainWithProvider(DomainProvider $provider, Domain $domain, array $context): array
            {
                $this->renewCalls++;
                $this->providerTransactionLevels[] = DB::transactionLevel();
                $this->statusAtProviderCall = OrderItem::query()
                    ->where('domain', $domain->domain_name)
                    ->value('provisioning_status');
                $attempt = DomainProvisioningAttempt::query()
                    ->where('domain_id', $domain->getKey())
                    ->where('operation', DomainProvisioningAttempt::OPERATION_RENEW)
                    ->latest('id')
                    ->first();
                $this->attemptStatusAtProviderCall = $attempt?->status;

                if ($this->crash) {
                    throw new \RuntimeException('Simulated crash after renewal provider call started.');
                }

                if ($this->ok) {
                    return [
                        'ok' => true,
                        'cid' => 'FAKE-RENEW-CID-123',
                        'provider_reference' => 'FAKE-RENEW-REF-123',
                        'provider_domain_id' => 'FAKE-RENEW-DOMAIN-456',
                    ];
                }

                return array_filter([
                    'ok' => false,
                    'reason' => $this->reason ?? ($this->definitive === false ? 'timeout' : 'provider_error'),
                    'message' => $this->message !== '' ? $this->message : 'Simulated renewal failure.',
                    'definitive' => $this->definitive,
                ], fn ($value) => $value !== null);
            }

            protected function registerDomainWithProvider(DomainProvider $provider, Domain $domain, array $context, array $contact): array
            {
                $this->registerCalls++;

                return ['ok' => true, 'reason' => 'ok', 'cid' => 'UNEXPECTED-REGISTER-CALL'];
            }
        };
    }

    /* ================================ A ================================ */

    public function test_first_renewal_creates_one_attempt_and_completes(): void
    {
        [$order, $orderItem, $domain, $provider] = $this->makeRenewalOrder();
        $service = $this->fakeRenewalService(true);

        $result = $service->provisionOrderDomain($order);

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $service->renewCalls);
        $this->assertSame(0, $service->registerCalls);

        $freshItem = $orderItem->fresh();
        $this->assertSame(OrderItem::PROVISIONING_COMPLETED, $freshItem->provisioning_status);
        $this->assertNotNull($freshItem->provisioning_started_at);
        $this->assertNotNull($freshItem->provisioning_completed_at);

        $attempt = DomainProvisioningAttempt::query()->sole();
        $this->assertSame(DomainProvisioningAttempt::OPERATION_RENEW, $attempt->operation);
        $this->assertSame(DomainProvisioningAttempt::STATUS_COMPLETED, $attempt->status);
        $this->assertSame($orderItem->id, $attempt->order_item_id);
        $this->assertSame($domain->id, $attempt->domain_id);
        $this->assertSame($provider->id, $attempt->provider_id);
        $this->assertSame('enom', $attempt->provider_type);
        $this->assertSame('live', $attempt->provider_mode);
        $this->assertSame('FAKE-RENEW-REF-123', $attempt->provider_reference);
        $this->assertSame('FAKE-RENEW-DOMAIN-456', $attempt->provider_domain_id);
        $this->assertNotNull($attempt->started_at);
        $this->assertNotNull($attempt->finished_at);

        $this->assertSame([0], $service->providerTransactionLevels);
        $this->assertSame(OrderItem::PROVISIONING_IN_PROGRESS, $service->statusAtProviderCall);
        $this->assertSame(DomainProvisioningAttempt::STATUS_INITIATED, $service->attemptStatusAtProviderCall);

        $freshDomain = $domain->fresh();
        $this->assertSame('active', $freshDomain->status);
        $this->assertSame($orderItem->meta['renewal_date'], $freshDomain->renewal_date);
        $this->assertNull($freshDomain->dns_last_note);
    }

    /* ================================ B ================================ */

    public function test_duplicate_activation_after_completed_sends_zero_second_extend(): void
    {
        [$order, $orderItem] = $this->makeRenewalOrder();
        $service = $this->fakeRenewalService(true);

        $first = $service->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));
        $this->assertTrue($first['ok']);
        $this->assertSame(1, $service->renewCalls);

        $second = $service->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));

        $this->assertTrue($second['ok']);
        $this->assertTrue($second['skipped'] ?? false);
        $this->assertSame(1, $service->renewCalls);
        $this->assertSame(1, DomainProvisioningAttempt::query()->count());
    }

    /* ================================ C ================================ */

    public function test_duplicate_activation_while_in_progress_sends_zero_extend(): void
    {
        [$order, $orderItem] = $this->makeRenewalOrder(OrderItem::PROVISIONING_IN_PROGRESS);
        $service = $this->fakeRenewalService(true);

        $result = $service->provisionOrderDomain($order);

        $this->assertFalse($result['ok']);
        $this->assertSame(0, $service->renewCalls);
        $this->assertSame(OrderItem::PROVISIONING_IN_PROGRESS, $orderItem->fresh()->provisioning_status);
        $this->assertSame(0, DomainProvisioningAttempt::query()->count());
    }

    /* ================================ D ================================ */

    public function test_existing_initiated_attempt_prevents_new_attempt_and_extend(): void
    {
        [$order, $orderItem, $domain, $provider] = $this->makeRenewalOrder(OrderItem::PROVISIONING_IN_PROGRESS);
        $existingAttempt = DomainProvisioningAttempt::query()->create([
            'order_item_id' => $orderItem->id,
            'domain_id' => $domain->id,
            'provider_id' => $provider->id,
            'attempt_uuid' => (string) Str::uuid(),
            'operation' => DomainProvisioningAttempt::OPERATION_RENEW,
            'provider_type' => $provider->type,
            'provider_mode' => $provider->mode,
            'status' => DomainProvisioningAttempt::STATUS_INITIATED,
            'started_at' => now(),
        ]);

        $service = $this->fakeRenewalService(true);
        $result = $service->provisionOrderDomain($order);

        $this->assertFalse($result['ok']);
        $this->assertSame(0, $service->renewCalls);
        $this->assertSame(1, DomainProvisioningAttempt::query()->count());
        $this->assertSame(DomainProvisioningAttempt::STATUS_INITIATED, $existingAttempt->fresh()->status);
    }

    /* ================================ E ================================ */

    public function test_existing_indeterminate_attempt_prevents_new_attempt_and_extend(): void
    {
        [$order, $orderItem, $domain, $provider] = $this->makeRenewalOrder(OrderItem::PROVISIONING_IN_PROGRESS);
        $existingAttempt = DomainProvisioningAttempt::query()->create([
            'order_item_id' => $orderItem->id,
            'domain_id' => $domain->id,
            'provider_id' => $provider->id,
            'attempt_uuid' => (string) Str::uuid(),
            'operation' => DomainProvisioningAttempt::OPERATION_RENEW,
            'provider_type' => $provider->type,
            'provider_mode' => $provider->mode,
            'status' => DomainProvisioningAttempt::STATUS_INDETERMINATE,
            'started_at' => now()->subMinute(),
            'finished_at' => now()->subMinute(),
        ]);

        $service = $this->fakeRenewalService(true);
        $result = $service->provisionOrderDomain($order);

        $this->assertFalse($result['ok']);
        $this->assertSame(0, $service->renewCalls);
        $this->assertSame(1, DomainProvisioningAttempt::query()->count());
        $this->assertSame(DomainProvisioningAttempt::STATUS_INDETERMINATE, $existingAttempt->fresh()->status);
        $this->assertSame(OrderItem::PROVISIONING_IN_PROGRESS, $orderItem->fresh()->provisioning_status);
    }

    /* ================================ F ================================ */

    public function test_definitive_provider_rejection_sets_confirmed_failed(): void
    {
        [$order, $orderItem, $domain] = $this->makeRenewalOrder();
        $originalRenewalDate = $domain->renewal_date;

        $service = $this->fakeRenewalService(false, 'Simulated definitive registrar rejection.', false, true, 'provider_error');
        $result = $service->provisionOrderDomain($order);

        $this->assertFalse($result['ok']);
        $this->assertSame(1, $service->renewCalls);

        $freshItem = $orderItem->fresh();
        $this->assertSame(OrderItem::PROVISIONING_FAILED, $freshItem->provisioning_status);
        $this->assertNull($freshItem->provisioning_completed_at);

        $attempt = DomainProvisioningAttempt::query()->sole();
        $this->assertSame(DomainProvisioningAttempt::STATUS_CONFIRMED_FAILED, $attempt->status);
        $this->assertSame('provider_error', $attempt->response_payload['reason']);
        $this->assertNotNull($attempt->finished_at);

        $freshDomain = $domain->fresh();
        $this->assertSame($originalRenewalDate, $freshDomain->renewal_date);
        $this->assertNotNull($freshDomain->dns_last_note);
    }

    /* ================================ G ================================ */

    public function test_retry_after_confirmed_failure_creates_new_attempt(): void
    {
        [$order, $orderItem] = $this->makeRenewalOrder();

        $failService = $this->fakeRenewalService(false, 'first failure', false, true, 'provider_error');
        $first = $failService->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));
        $this->assertFalse($first['ok']);
        $this->assertSame(OrderItem::PROVISIONING_FAILED, $orderItem->fresh()->provisioning_status);
        $oldAttempt = DomainProvisioningAttempt::query()->sole();

        $retryService = $this->fakeRenewalService(true);
        $second = $retryService->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));

        $this->assertTrue($second['ok']);
        $this->assertSame(1, $retryService->renewCalls);
        $this->assertSame(2, DomainProvisioningAttempt::query()->count());
        $this->assertSame(DomainProvisioningAttempt::STATUS_CONFIRMED_FAILED, $oldAttempt->fresh()->status);

        $newAttempt = DomainProvisioningAttempt::query()->whereKeyNot($oldAttempt->id)->sole();
        $this->assertSame(DomainProvisioningAttempt::STATUS_COMPLETED, $newAttempt->status);
        $this->assertNotSame($oldAttempt->attempt_uuid, $newAttempt->attempt_uuid);
    }

    /* ================================ H ================================ */

    public function test_ambiguous_failure_keeps_in_progress_and_leaves_domain_unchanged(): void
    {
        [$order, $orderItem, $domain] = $this->makeRenewalOrder();
        $originalRenewalDate = $domain->renewal_date;
        $originalDnsNote = $domain->dns_last_note;
        $originalStatus = $domain->status;

        $service = $this->fakeRenewalService(false, 'Simulated transport timeout.', false, false, 'timeout');
        $result = $service->provisionOrderDomain($order);

        $this->assertFalse($result['ok']);
        $this->assertSame(1, $service->renewCalls);
        $this->assertSame(OrderItem::PROVISIONING_IN_PROGRESS, $orderItem->fresh()->provisioning_status);

        $attempt = DomainProvisioningAttempt::query()->sole();
        $this->assertSame(DomainProvisioningAttempt::STATUS_INDETERMINATE, $attempt->status);
        $this->assertSame('timeout', $attempt->response_payload['reason']);
        $this->assertNotNull($attempt->finished_at);

        $freshDomain = $domain->fresh();
        $this->assertSame($originalRenewalDate, $freshDomain->renewal_date);
        $this->assertSame($originalDnsNote, $freshDomain->dns_last_note);
        $this->assertSame($originalStatus, $freshDomain->status);
    }

    /* ================================ I ================================ */

    public function test_repeat_activation_after_ambiguous_result_sends_zero_second_extend(): void
    {
        [$order, $orderItem] = $this->makeRenewalOrder();

        $ambiguousService = $this->fakeRenewalService(false, 'timeout', false, false, 'timeout');
        $ambiguousService->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));
        $this->assertSame(1, $ambiguousService->renewCalls);

        $retryService = $this->fakeRenewalService(true);
        $result = $retryService->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));

        $this->assertFalse($result['ok']);
        $this->assertSame(0, $retryService->renewCalls);
        $this->assertSame(1, DomainProvisioningAttempt::query()->count());
    }

    /* ================================ J ================================ */

    public function test_transaction_deferral_calls_extend_only_after_commit(): void
    {
        [$order, $orderItem] = $this->makeRenewalOrder();
        $service = $this->fakeRenewalService(true);

        $result = DB::transaction(
            fn () => DB::transaction(fn () => $service->provisionOrderDomain($order))
        );

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['deferred'] ?? false);
        $this->assertSame(1, $service->renewCalls);
        $this->assertSame([0], $service->providerTransactionLevels);
        $this->assertSame(OrderItem::PROVISIONING_IN_PROGRESS, $service->statusAtProviderCall);
        $this->assertSame(OrderItem::PROVISIONING_COMPLETED, $orderItem->fresh()->provisioning_status);
        $this->assertSame(DomainProvisioningAttempt::STATUS_COMPLETED, DomainProvisioningAttempt::query()->sole()->status);
    }

    /* ================================ K ================================ */

    public function test_rollback_of_wrapping_transaction_never_calls_extend(): void
    {
        [$order, $orderItem] = $this->makeRenewalOrder();
        $service = $this->fakeRenewalService(true);

        try {
            DB::transaction(function () use ($service, $order) {
                $service->provisionOrderDomain($order);
                throw new \RuntimeException('Forced rollback for test.');
            });
            $this->fail('Expected the forced rollback exception to propagate.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Forced rollback for test.', $e->getMessage());
        }

        $this->assertSame(0, $service->renewCalls);
        $this->assertSame(OrderItem::PROVISIONING_NOT_STARTED, $orderItem->fresh()->provisioning_status);
        $this->assertSame(0, DomainProvisioningAttempt::query()->count());
    }

    /* ================================ L ================================ */

    public function test_provider_inactive_blocks_before_any_attempt(): void
    {
        [$order, $orderItem, $domain, $provider] = $this->makeRenewalOrder();
        $provider->update(['is_active' => false]);

        $service = $this->fakeRenewalService(true);
        $result = $service->provisionOrderDomain($order);

        $this->assertFalse($result['ok']);
        $this->assertSame('renewal_provider_inactive', $result['reason']);
        $this->assertSame(0, $service->renewCalls);
        $this->assertSame(0, DomainProvisioningAttempt::query()->count());
        $this->assertSame(OrderItem::PROVISIONING_NOT_STARTED, $orderItem->fresh()->provisioning_status);
    }

    /* ================================ M ================================ */

    public function test_provider_snapshot_remains_authoritative_no_fallback(): void
    {
        [$order, $orderItem, $domain, $provider] = $this->makeRenewalOrder();
        $otherProvider = DomainProvider::create([
            'name' => 'Other Enom Provider',
            'type' => 'enom',
            'username' => 'otheruser',
            'password' => 'otherpass',
            'is_active' => true,
            'mode' => 'live',
        ]);

        $service = $this->fakeRenewalService(true);
        $result = $service->provisionOrderDomain($order);

        $this->assertTrue($result['ok']);
        $attempt = DomainProvisioningAttempt::query()->sole();
        $this->assertSame($provider->id, $attempt->provider_id);
        $this->assertNotSame($otherProvider->id, $attempt->provider_id);
    }

    /* ================================ N ================================ */

    public function test_renewal_never_creates_a_register_operation_attempt(): void
    {
        [$order, $orderItem] = $this->makeRenewalOrder();
        $service = $this->fakeRenewalService(true);

        $service->provisionOrderDomain($order);

        $this->assertSame(
            0,
            DomainProvisioningAttempt::query()->where('operation', DomainProvisioningAttempt::OPERATION_REGISTER)->count()
        );
        $this->assertSame(
            1,
            DomainProvisioningAttempt::query()->where('operation', DomainProvisioningAttempt::OPERATION_RENEW)->count()
        );
        $this->assertSame(0, $service->registerCalls);
    }

    /* ================================ P ================================ */

    public function test_no_duplicate_domain_mutation_on_repeat_activation(): void
    {
        [$order, $orderItem, $domain] = $this->makeRenewalOrder();
        $service = $this->fakeRenewalService(true);

        $service->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));
        $renewalDateAfterFirst = $domain->fresh()->renewal_date;

        $service->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));
        $renewalDateAfterSecond = $domain->fresh()->renewal_date;

        $this->assertSame($renewalDateAfterFirst, $renewalDateAfterSecond);
        $this->assertSame(1, $service->renewCalls);
    }

    /* ============================ Crash safety ============================ */

    public function test_crash_after_extend_before_finalize_leaves_in_progress_committed(): void
    {
        [$order, $orderItem] = $this->makeRenewalOrder();
        $service = $this->fakeRenewalService(true, '', true);

        $result = $service->provisionOrderDomain($order);

        $this->assertFalse($result['ok'] ?? true);
        $this->assertSame(1, $service->renewCalls);
        $this->assertSame(OrderItem::PROVISIONING_IN_PROGRESS, $orderItem->fresh()->provisioning_status);

        $attempt = DomainProvisioningAttempt::query()->sole();
        $this->assertSame(DomainProvisioningAttempt::STATUS_INITIATED, $attempt->status);
        $this->assertNull($attempt->finished_at);

        // Repeated activation after the crash: the durable claim (committed before Extend was
        // ever called) blocks a second Extend, exactly as it would for a real process crash.
        $retry = $service->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));
        $this->assertFalse($retry['ok']);
        $this->assertSame(1, $service->renewCalls);
    }
}
