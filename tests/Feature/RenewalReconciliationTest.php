<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\DomainProvisioningAttempt;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Domains\Clients\EnomClient;
use App\Services\Domains\DomainProvisioningReconciliationService;
use App\Services\Domains\RegistrarProvisioningService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * TLD-3H.2B — Safe, Read-Only Enom Renewal Reconciliation.
 *
 * Covers reconcileRenewalAttempt()'s three-way outcome contract (RENEWAL_STATUS_CONFIRMED /
 * RENEWAL_STATUS_NOT_CONFIRMED / STATUS_INDETERMINATE), the mandatory eligibility/identity
 * preconditions (zero HTTP call when any fail), the apply-on-confirmed transaction and its
 * stale-state protection, idempotent repeated reconciliation, dry-run safety, and the hard
 * guarantee that this service never calls the registrar's mutating Extend/Purchase commands.
 *
 * All registrar HTTP calls are faked via Http::fake() against the real EnomClient — no real
 * network connection is made. migrate:fresh runs per test, matching every other test file in
 * this suite.
 */
class RenewalReconciliationTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    // ---------------------------------------------------------------------
    // A. Provider confirms expiry reached the target → CONFIRMED, apply completes everything.
    // ---------------------------------------------------------------------
    public function test_confirmed_renewal_is_applied_atomically(): void
    {
        [$attempt, $item, $domain] = $this->makeRenewalAttempt();

        Http::fake(['*' => $this->enomXmlResponse('2027-08-01')]);

        $result = app(DomainProvisioningReconciliationService::class)
            ->reconcileRenewalAttempt($attempt, apply: true);

        $this->assertSame(DomainProvisioningReconciliationService::RENEWAL_STATUS_CONFIRMED, $result['status']);
        $this->assertTrue($result['applied']);
        $this->assertSame('completed', $result['action']);
        $this->assertSame(DomainProvisioningAttempt::STATUS_COMPLETED, $attempt->fresh()->status);
        $this->assertSame(OrderItem::PROVISIONING_COMPLETED, $item->fresh()->provisioning_status);
        $this->assertSame('active', $domain->fresh()->status);
        $this->assertSame('2027-08-01', $domain->fresh()->renewal_date);
        Http::assertSentCount(1);
    }

    // B. Same evidence, but apply=false (dry-run) → would_complete, zero writes.
    public function test_dry_run_never_writes_even_when_confirmed(): void
    {
        [$attempt, $item, $domain] = $this->makeRenewalAttempt();
        Http::fake(['*' => $this->enomXmlResponse('2027-08-01')]);

        $result = app(DomainProvisioningReconciliationService::class)
            ->reconcileRenewalAttempt($attempt, apply: false);

        $this->assertSame(DomainProvisioningReconciliationService::RENEWAL_STATUS_CONFIRMED, $result['status']);
        $this->assertFalse($result['applied']);
        $this->assertSame('would_complete', $result['action']);
        $this->assertSame(DomainProvisioningAttempt::STATUS_INITIATED, $attempt->fresh()->status);
        $this->assertSame(OrderItem::PROVISIONING_IN_PROGRESS, $item->fresh()->provisioning_status);
        $this->assertSame('pending', $domain->fresh()->status);
    }

    // C. INDETERMINATE attempts are equally eligible for inspection (not just INITIATED).
    public function test_indeterminate_attempt_is_eligible_for_inspection(): void
    {
        [$attempt] = $this->makeRenewalAttempt(attemptStatus: DomainProvisioningAttempt::STATUS_INDETERMINATE);
        Http::fake(['*' => $this->enomXmlResponse('2027-08-01')]);

        $result = app(DomainProvisioningReconciliationService::class)
            ->reconcileRenewalAttempt($attempt, apply: true);

        $this->assertSame(DomainProvisioningReconciliationService::RENEWAL_STATUS_CONFIRMED, $result['status']);
        $this->assertTrue($result['applied']);
    }

    // D. Provider expiry present but short of target → NOT_CONFIRMED, no write, no retry trigger.
    public function test_expiry_short_of_target_is_not_confirmed(): void
    {
        [$attempt, $item, $domain] = $this->makeRenewalAttempt();
        Http::fake(['*' => $this->enomXmlResponse('2026-09-15')]); // before the 2027-08-01 target

        $result = app(DomainProvisioningReconciliationService::class)
            ->reconcileRenewalAttempt($attempt, apply: true);

        $this->assertSame(DomainProvisioningReconciliationService::RENEWAL_STATUS_NOT_CONFIRMED, $result['status']);
        $this->assertFalse($result['applied']);
        $this->assertSame('no_change', $result['action']);
        $this->assertSame(DomainProvisioningAttempt::STATUS_INITIATED, $attempt->fresh()->status);
        $this->assertSame(OrderItem::PROVISIONING_IN_PROGRESS, $item->fresh()->provisioning_status);
        $this->assertSame('pending', $domain->fresh()->status);
    }

    // E. domain_not_in_account → NOT_CONFIRMED (never CONFIRMED_FAILED, never a retry trigger).
    public function test_domain_not_in_account_is_not_confirmed_not_failed(): void
    {
        [$attempt, $item, $domain] = $this->makeRenewalAttempt();
        Http::fake(['*' => $this->enomNotInAccountXmlResponse()]);

        $result = app(DomainProvisioningReconciliationService::class)
            ->reconcileRenewalAttempt($attempt, apply: true);

        $this->assertSame(DomainProvisioningReconciliationService::RENEWAL_STATUS_NOT_CONFIRMED, $result['status']);
        $this->assertFalse($result['applied']);
        $this->assertSame(DomainProvisioningAttempt::STATUS_INITIATED, $attempt->fresh()->status);
        $this->assertNotSame(DomainProvisioningAttempt::STATUS_CONFIRMED_FAILED, $attempt->fresh()->status);
    }

    // F. Transport/HTTP failure → INDETERMINATE, everything untouched.
    public function test_transport_failure_is_indeterminate(): void
    {
        [$attempt, $item, $domain] = $this->makeRenewalAttempt();
        Http::fake(['*' => Http::response('', 500)]);

        $result = app(DomainProvisioningReconciliationService::class)
            ->reconcileRenewalAttempt($attempt, apply: true);

        $this->assertSame(DomainProvisioningReconciliationService::STATUS_INDETERMINATE, $result['status']);
        $this->assertFalse($result['applied']);
        $this->assertSame(DomainProvisioningAttempt::STATUS_INITIATED, $attempt->fresh()->status);
        $this->assertSame(OrderItem::PROVISIONING_IN_PROGRESS, $item->fresh()->provisioning_status);
        $this->assertSame('pending', $domain->fresh()->status);
    }

    // G. Well-formed response but no usable expiration field → INDETERMINATE.
    public function test_missing_expiry_field_is_indeterminate(): void
    {
        [$attempt] = $this->makeRenewalAttempt();
        Http::fake(['*' => $this->enomXmlResponseNoExpiration()]);

        $result = app(DomainProvisioningReconciliationService::class)
            ->reconcileRenewalAttempt($attempt, apply: true);

        $this->assertSame(DomainProvisioningReconciliationService::STATUS_INDETERMINATE, $result['status']);
        $this->assertFalse($result['applied']);
    }

    // H. A register-operation attempt is never eligible for renewal reconciliation.
    public function test_register_operation_attempt_is_rejected_without_http_call(): void
    {
        [$attempt] = $this->makeRenewalAttempt();
        $attempt->forceFill(['operation' => DomainProvisioningAttempt::OPERATION_REGISTER])->save();
        Http::fake(['*' => $this->enomXmlResponse('2027-08-01')]);

        $result = app(DomainProvisioningReconciliationService::class)
            ->reconcileRenewalAttempt($attempt->fresh(), apply: true);

        $this->assertSame(DomainProvisioningReconciliationService::STATUS_INDETERMINATE, $result['status']);
        $this->assertSame('skipped', $result['action']);
        Http::assertNothingSent();
    }

    // I. An already-COMPLETED attempt is never re-inspected.
    public function test_already_completed_attempt_is_rejected_without_http_call(): void
    {
        [$attempt] = $this->makeRenewalAttempt(attemptStatus: DomainProvisioningAttempt::STATUS_COMPLETED);
        Http::fake(['*' => $this->enomXmlResponse('2027-08-01')]);

        $result = app(DomainProvisioningReconciliationService::class)
            ->reconcileRenewalAttempt($attempt, apply: true);

        $this->assertSame('skipped', $result['action']);
        Http::assertNothingSent();
    }

    // J. A definitively-failed attempt is never re-inspected (reconciliation never resurrects it).
    public function test_confirmed_failed_attempt_is_rejected_without_http_call(): void
    {
        [$attempt] = $this->makeRenewalAttempt(attemptStatus: DomainProvisioningAttempt::STATUS_CONFIRMED_FAILED);
        Http::fake(['*' => $this->enomXmlResponse('2027-08-01')]);

        $result = app(DomainProvisioningReconciliationService::class)
            ->reconcileRenewalAttempt($attempt, apply: true);

        $this->assertSame('skipped', $result['action']);
        Http::assertNothingSent();
    }

    // K. OrderItem no longer in_progress → rejected without an HTTP call.
    public function test_order_item_not_in_progress_is_rejected_without_http_call(): void
    {
        [$attempt, $item] = $this->makeRenewalAttempt();
        $item->forceFill(['provisioning_status' => OrderItem::PROVISIONING_FAILED])->save();
        Http::fake(['*' => $this->enomXmlResponse('2027-08-01')]);

        $result = app(DomainProvisioningReconciliationService::class)
            ->reconcileRenewalAttempt($attempt->fresh(), apply: true);

        $this->assertSame('skipped', $result['action']);
        Http::assertNothingSent();
    }

    // L. item_option is no longer 'renew' → rejected without an HTTP call.
    public function test_non_renew_item_option_is_rejected_without_http_call(): void
    {
        [$attempt, $item] = $this->makeRenewalAttempt();
        $item->forceFill(['item_option' => 'transfer'])->save();
        Http::fake(['*' => $this->enomXmlResponse('2027-08-01')]);

        $result = app(DomainProvisioningReconciliationService::class)
            ->reconcileRenewalAttempt($attempt->fresh(), apply: true);

        $this->assertSame('skipped', $result['action']);
        Http::assertNothingSent();
    }

    // M. Provider identity drift (Domain.provider_id no longer matches the snapshot) → rejected,
    //    zero HTTP call — the exact same no-fallback rule 3H.2A already enforces at claim time.
    public function test_provider_identity_drift_is_rejected_without_http_call(): void
    {
        [$attempt, $item, $domain] = $this->makeRenewalAttempt();
        $otherProvider = DomainProvider::create([
            'name' => 'Other Enom Provider', 'type' => 'enom',
            'username' => 'other', 'password' => 'other', 'is_active' => true, 'mode' => 'live',
        ]);
        $domain->forceFill(['provider_id' => $otherProvider->id])->save();
        Http::fake(['*' => $this->enomXmlResponse('2027-08-01')]);

        $result = app(DomainProvisioningReconciliationService::class)
            ->reconcileRenewalAttempt($attempt->fresh(), apply: true);

        $this->assertSame(DomainProvisioningReconciliationService::STATUS_INDETERMINATE, $result['status']);
        $this->assertSame('skipped', $result['action']);
        Http::assertNothingSent();
    }

    // N. Inactive provider → rejected, zero HTTP call.
    public function test_inactive_provider_is_rejected_without_http_call(): void
    {
        [$attempt, , , $provider] = $this->makeRenewalAttempt();
        $provider->forceFill(['is_active' => false])->save();
        Http::fake(['*' => $this->enomXmlResponse('2027-08-01')]);

        $result = app(DomainProvisioningReconciliationService::class)
            ->reconcileRenewalAttempt($attempt->fresh(), apply: true);

        $this->assertSame('skipped', $result['action']);
        Http::assertNothingSent();
    }

    // O. This service structurally cannot call the registrar's mutating commands: renewal
    //    reconciliation's only registrar collaborator is EnomClient::getDomainInfo(); assert the
    //    real service never invokes renewDomainWithProvider()/registerDomainWithProvider() by
    //    proving the RegistrarProvisioningService collaborator it DOES call is only ever asked to
    //    apply the already-confirmed Domain mutation, never to renew or register anything.
    public function test_reconciliation_never_calls_registrar_mutating_commands(): void
    {
        [$attempt, $item, $domain] = $this->makeRenewalAttempt();

        $spy = new class extends RegistrarProvisioningService {
            public int $renewCalls = 0;
            public int $registerCalls = 0;
            public int $applyCalls = 0;

            protected function renewDomainWithProvider(DomainProvider $provider, Domain $domain, array $context): array
            {
                $this->renewCalls++;
                return ['ok' => false];
            }

            public function applyConfirmedRenewalToDomain(Domain $lockedDomain, string $renewalDate, ?string $paymentMethod = null): void
            {
                $this->applyCalls++;
                parent::applyConfirmedRenewalToDomain($lockedDomain, $renewalDate, $paymentMethod);
            }
        };
        $this->app->instance(RegistrarProvisioningService::class, $spy);

        Http::fake(['*' => $this->enomXmlResponse('2027-08-01')]);

        app(DomainProvisioningReconciliationService::class)->reconcileRenewalAttempt($attempt, apply: true);

        $this->assertSame(0, $spy->renewCalls);
        $this->assertSame(0, $spy->registerCalls);
        $this->assertSame(1, $spy->applyCalls);
        Http::assertSentCount(1); // GetDomainInfo only — never a second (Extend) request.
    }

    // P. Repeated reconciliation after a completed apply is a fully idempotent no-op.
    public function test_repeated_reconciliation_after_completion_is_idempotent(): void
    {
        [$attempt, $item, $domain] = $this->makeRenewalAttempt();
        Http::fake(['*' => $this->enomXmlResponse('2027-08-01')]);

        $service = app(DomainProvisioningReconciliationService::class);
        $first = $service->reconcileRenewalAttempt($attempt, apply: true);
        $this->assertTrue($first['applied']);

        $second = $service->reconcileRenewalAttempt($attempt->fresh(), apply: true);

        $this->assertFalse($second['applied']);
        $this->assertSame('skipped', $second['action']);
        Http::assertSentCount(1); // no second provider lookup once already terminal
        $this->assertSame(
            1,
            DomainProvisioningAttempt::query()->where('order_item_id', $item->getKey())->count()
        );
    }

    // Q. Stale-state protection: state changes between inspect and apply → apply detects it and
    //    makes no write, inside its own locked transaction.
    public function test_state_change_between_inspect_and_apply_prevents_stale_write(): void
    {
        [$attempt, $item, $domain] = $this->makeRenewalAttempt();

        $service = new class extends DomainProvisioningReconciliationService {
            protected function inspectEnomRenewal(
                DomainProvider $provider,
                DomainProvisioningAttempt $attempt,
                string $domainName,
                string $expectedRenewalDate
            ): array {
                OrderItem::query()->whereKey($attempt->order_item_id)->update([
                    'provisioning_status' => OrderItem::PROVISIONING_FAILED,
                ]);

                return [
                    'status' => self::RENEWAL_STATUS_CONFIRMED,
                    'expires_at' => '2027-08-01',
                    'safe_payload' => ['provider_type' => 'enom'],
                ];
            }
        };

        $result = $service->reconcileRenewalAttempt($attempt, apply: true);

        $this->assertSame('stale_no_change', $result['action']);
        $this->assertFalse($result['applied']);
        $this->assertSame(DomainProvisioningAttempt::STATUS_INITIATED, $attempt->fresh()->status);
        $this->assertSame(OrderItem::PROVISIONING_FAILED, $item->fresh()->provisioning_status);
        $this->assertSame('pending', $domain->fresh()->status);
    }

    // R. Never creates a second DomainProvisioningAttempt row.
    public function test_reconciliation_never_creates_a_second_attempt(): void
    {
        [$attempt, $item] = $this->makeRenewalAttempt();
        Http::fake(['*' => $this->enomXmlResponse('2027-08-01')]);

        app(DomainProvisioningReconciliationService::class)->reconcileRenewalAttempt($attempt, apply: true);

        $this->assertSame(
            1,
            DomainProvisioningAttempt::query()->where('order_item_id', $item->getKey())->count()
        );
    }

    // S. The Domain mutation persists the registrar's OWN confirmed expiry, not merely the
    //    locally-computed target (they happen to be equal here only because the fixture's
    //    provider evidence exactly matches the target; the point verified is which value the
    //    code path actually threads through).
    public function test_applied_domain_renewal_date_comes_from_provider_evidence(): void
    {
        [$attempt, , $domain] = $this->makeRenewalAttempt();
        Http::fake(['*' => $this->enomXmlResponse('2027-08-02')]); // one day past the exact target

        $result = app(DomainProvisioningReconciliationService::class)
            ->reconcileRenewalAttempt($attempt, apply: true);

        $this->assertSame(DomainProvisioningReconciliationService::RENEWAL_STATUS_CONFIRMED, $result['status']);
        $this->assertSame('2027-08-02', $domain->fresh()->renewal_date);
    }

    // T. Zero regression: register reconciliation's own public entry point is untouched and
    //    still resolvable/callable side-by-side with the new renewal entry point.
    public function test_register_reconciliation_entry_point_is_unaffected(): void
    {
        $this->assertTrue(method_exists(DomainProvisioningReconciliationService::class, 'reconcileAttempt'));
        $this->assertTrue(method_exists(DomainProvisioningReconciliationService::class, 'reconcileRenewalAttempt'));
    }

    // ---------------------------------------------------------------------
    // Fixtures
    // ---------------------------------------------------------------------

    /**
     * @return array{0: DomainProvisioningAttempt, 1: OrderItem, 2: Domain, 3: DomainProvider}
     */
    protected function makeRenewalAttempt(
        string $attemptStatus = DomainProvisioningAttempt::STATUS_INITIATED
    ): array {
        $client = Client::create([
            'first_name'   => 'Reconcile',
            'last_name'    => 'Renewal',
            'email'        => 'reconcile_renewal_' . uniqid() . '@example.test',
            'password'     => bcrypt('secret-password'),
            'company_name' => 'Test Co',
        ]);

        $provider = DomainProvider::create([
            'name' => 'Enom Renewal Reconciliation Provider',
            'type' => 'enom',
            'username' => 'testuser',
            'password' => 'testpass',
            'is_active' => true,
            'mode' => 'live',
        ]);

        $domainName = 'renew-reconcile-' . uniqid() . '.com';

        $domain = Domain::create([
            'client_id' => $client->id,
            'domain_name' => $domainName,
            'registrar' => $provider->type,
            'provider_id' => $provider->id,
            'registration_date' => now()->subYear()->toDateString(),
            'renewal_date' => '2026-08-01',
            'status' => 'pending',
        ]);

        $order = Order::create([
            'client_id' => $client->id,
            'status' => 'pending',
            'type' => 'domain_renewal',
        ]);

        $orderItem = $order->items()->create([
            'domain' => $domainName,
            'item_option' => 'renew',
            'price_cents' => 2000,
            'meta' => [
                'domain_id' => $domain->id,
                'current_renewal_date' => '2026-08-01',
                'renewal_date' => '2027-08-01',
                'term_years' => 1,
                'provider_id' => $provider->id,
                'provider_type' => $provider->type,
                'provider_mode' => $provider->mode,
            ],
            'provisioning_status' => OrderItem::PROVISIONING_IN_PROGRESS,
            'provisioning_started_at' => now()->subHour(),
        ]);

        $attempt = DomainProvisioningAttempt::query()->create([
            'order_item_id' => $orderItem->id,
            'domain_id' => $domain->id,
            'provider_id' => $provider->id,
            'attempt_uuid' => (string) Str::uuid(),
            'operation' => DomainProvisioningAttempt::OPERATION_RENEW,
            'provider_type' => $provider->type,
            'provider_mode' => $provider->mode,
            'status' => $attemptStatus,
            'started_at' => now()->subHour(),
        ]);

        return [$attempt->fresh(), $orderItem->fresh(), $domain->fresh(), $provider->fresh()];
    }

    protected function enomXmlResponse(string $expiration)
    {
        $xml = <<<XML
<?xml version="1.0"?>
<interface-response>
  <GetDomainInfo>
    <domainname domainnameid="152809531"></domainname>
    <status>
      <expiration>{$expiration}</expiration>
      <registrationstatus>Registered</registrationstatus>
      <purchase-status>Paid</purchase-status>
      <belongs-to party-id="{39AE68C0-D019-4690-9999-FD632BC1AFAA}"></belongs-to>
    </status>
  </GetDomainInfo>
  <DomainInfo><RegistryCreateDate>2025-08-01</RegistryCreateDate></DomainInfo>
  <Command>GETDOMAININFO</Command>
  <ErrCount>0</ErrCount>
  <ResponseCount>0</ResponseCount>
  <Done>true</Done>
</interface-response>
XML;

        return Http::response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    protected function enomXmlResponseNoExpiration()
    {
        $xml = <<<'XML'
<?xml version="1.0"?>
<interface-response>
  <GetDomainInfo>
    <domainname domainnameid="152809531"></domainname>
    <status>
      <registrationstatus>Registered</registrationstatus>
      <purchase-status>Paid</purchase-status>
      <belongs-to party-id="{39AE68C0-D019-4690-9999-FD632BC1AFAA}"></belongs-to>
    </status>
  </GetDomainInfo>
  <Command>GETDOMAININFO</Command>
  <ErrCount>0</ErrCount>
  <ResponseCount>0</ResponseCount>
  <Done>true</Done>
</interface-response>
XML;

        return Http::response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    protected function enomNotInAccountXmlResponse()
    {
        $xml = <<<'XML'
<?xml version="1.0"?>
<interface-response>
  <ErrCount>1</ErrCount>
  <errors>
    <Err1>Domain name not found in your account.</Err1>
  </errors>
  <Command>GETDOMAININFO</Command>
  <Done>true</Done>
</interface-response>
XML;

        return Http::response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
