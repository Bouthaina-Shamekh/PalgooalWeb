<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use App\Services\Billing\OrderActivationService;
use App\Services\Domains\RegistrarProvisioningService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Mockery;
use Tests\TestCase;

/**
 * TLD-3H.2C — OrderController Activation Defense-in-Depth.
 *
 * Proves that OrderController::updateStatus() and OrderController::bulk() never re-invoke
 * OrderActivationService::activate() for an order that is already active (active -> active),
 * while every genuine transition INTO active from a non-active status still activates exactly
 * once — for both domain_renewal orders and generic (non-domain) orders, individually and in
 * mixed bulk selections. OrderActivationService::activate() itself is replaced with a counting
 * spy: this phase is about the CONTROLLER's decision to call activate() at all, not the
 * registrar/subscription behavior inside it (already covered by OrderActivationServiceTest.php
 * and the TLD-3H.2A/3H.2B suites) — so a real Extend call is structurally impossible here
 * regardless of outcome, since the spy never reaches RegistrarProvisioningService.
 */
class AdminOrderActivationIdempotencyTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    // -----------------------------------------------------------------
    // updateStatus()
    // -----------------------------------------------------------------

    // A / E — active -> active never calls activate(), for a generic (non-domain) order.
    public function test_update_status_active_to_active_does_not_call_activate(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeOrder('subscription', Order::STATUS_ACTIVE);
        $spy = $this->bindActivationSpy();

        $response = $this->actingAs($admin)->patch(
            route('dashboard.orders.status', $order),
            ['status' => 'active']
        );

        $response->assertRedirect(route('dashboard.orders.show', $order->id));
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
        $this->assertSame([], $spy->activatedOrderIds);
    }

    // B / F — pending -> active still calls activate() exactly once, for a generic order.
    public function test_update_status_pending_to_active_calls_activate_once(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeOrder('subscription', Order::STATUS_PENDING);
        $spy = $this->bindActivationSpy();

        $response = $this->actingAs($admin)->patch(
            route('dashboard.orders.status', $order),
            ['status' => 'active']
        );

        $response->assertRedirect(route('dashboard.orders.show', $order->id));
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
        $this->assertSame([$order->id], $spy->activatedOrderIds);
    }

    // 7.A — domain_renewal order already active: updateStatus(active) never calls activate().
    public function test_domain_renewal_update_status_active_to_active_does_not_call_activate(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeOrder('domain_renewal', Order::STATUS_ACTIVE);
        $spy = $this->bindActivationSpy();

        $this->actingAs($admin)->patch(route('dashboard.orders.status', $order), ['status' => 'active']);

        $this->assertSame([], $spy->activatedOrderIds);
    }

    // 7.C — domain_renewal order pending: updateStatus(active) still activates exactly once.
    public function test_domain_renewal_update_status_pending_to_active_calls_activate_once(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeOrder('domain_renewal', Order::STATUS_PENDING);
        $spy = $this->bindActivationSpy();

        $this->actingAs($admin)->patch(route('dashboard.orders.status', $order), ['status' => 'active']);

        $this->assertSame([$order->id], $spy->activatedOrderIds);
    }

    // Other transitions (e.g. active -> cancelled) are completely unaffected.
    public function test_update_status_active_to_cancelled_is_unaffected(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeOrder('subscription', Order::STATUS_ACTIVE);
        $spy = $this->bindActivationSpy();

        $this->actingAs($admin)->patch(route('dashboard.orders.status', $order), ['status' => 'cancelled']);

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertSame([], $spy->activatedOrderIds);
    }

    // H — repeated active -> active stays idempotent across multiple calls.
    public function test_repeated_active_to_active_remains_idempotent(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeOrder('subscription', Order::STATUS_ACTIVE);
        $spy = $this->bindActivationSpy();

        $this->actingAs($admin)->patch(route('dashboard.orders.status', $order), ['status' => 'active']);
        $this->actingAs($admin)->patch(route('dashboard.orders.status', $order), ['status' => 'active']);
        $this->actingAs($admin)->patch(route('dashboard.orders.status', $order), ['status' => 'active']);

        $this->assertSame([], $spy->activatedOrderIds);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
    }

    // -----------------------------------------------------------------
    // bulk()
    // -----------------------------------------------------------------

    // C — bulk active skips an already-active order.
    public function test_bulk_active_skips_already_active_orders(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeOrder('subscription', Order::STATUS_ACTIVE);
        $spy = $this->bindActivationSpy();

        $this->actingAs($admin)->post(route('dashboard.orders.bulk'), [
            'ids' => [$order->id],
            'action' => 'active',
        ]);

        $this->assertSame([], $spy->activatedOrderIds);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
    }

    // D — bulk active still activates a pending order exactly once.
    public function test_bulk_active_activates_pending_orders_once(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeOrder('subscription', Order::STATUS_PENDING);
        $spy = $this->bindActivationSpy();

        $this->actingAs($admin)->post(route('dashboard.orders.bulk'), [
            'ids' => [$order->id],
            'action' => 'active',
        ]);

        $this->assertSame([$order->id], $spy->activatedOrderIds);
        $this->assertSame(Order::STATUS_ACTIVE, $order->fresh()->status);
    }

    // 7.B — domain_renewal order already active: bulk active never calls activate().
    public function test_domain_renewal_bulk_active_already_active_does_not_call_activate(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeOrder('domain_renewal', Order::STATUS_ACTIVE);
        $spy = $this->bindActivationSpy();

        $this->actingAs($admin)->post(route('dashboard.orders.bulk'), [
            'ids' => [$order->id],
            'action' => 'active',
        ]);

        $this->assertSame([], $spy->activatedOrderIds);
    }

    // 7.D — domain_renewal order pending: bulk active still activates exactly once.
    public function test_domain_renewal_bulk_active_pending_activates_once(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeOrder('domain_renewal', Order::STATUS_PENDING);
        $spy = $this->bindActivationSpy();

        $this->actingAs($admin)->post(route('dashboard.orders.bulk'), [
            'ids' => [$order->id],
            'action' => 'active',
        ]);

        $this->assertSame([$order->id], $spy->activatedOrderIds);
    }

    // G — mixed bulk selection: the already-active order is skipped, the pending one is
    // activated exactly once, and one order never blocks the other; both end correctly.
    public function test_bulk_mixed_selection_processes_each_order_independently(): void
    {
        $admin = $this->makeAdmin();
        $activeOrder = $this->makeOrder('subscription', Order::STATUS_ACTIVE);
        $pendingOrder = $this->makeOrder('domain_renewal', Order::STATUS_PENDING);
        $spy = $this->bindActivationSpy();

        $this->actingAs($admin)->post(route('dashboard.orders.bulk'), [
            'ids' => [$activeOrder->id, $pendingOrder->id],
            'action' => 'active',
        ]);

        $this->assertSame([$pendingOrder->id], $spy->activatedOrderIds);
        $this->assertSame(Order::STATUS_ACTIVE, $activeOrder->fresh()->status);
        $this->assertSame(Order::STATUS_ACTIVE, $pendingOrder->fresh()->status);
    }

    // I — non-active bulk actions (cancelled/fraud/pending/delete) are unchanged.
    public function test_bulk_non_active_actions_are_unaffected(): void
    {
        $admin = $this->makeAdmin();
        $orderA = $this->makeOrder('subscription', Order::STATUS_ACTIVE);
        $orderB = $this->makeOrder('subscription', Order::STATUS_PENDING);
        $orderC = $this->makeOrder('subscription', Order::STATUS_PENDING);
        $spy = $this->bindActivationSpy();

        $this->actingAs($admin)->post(route('dashboard.orders.bulk'), [
            'ids' => [$orderA->id],
            'action' => 'cancelled',
        ]);
        $this->actingAs($admin)->post(route('dashboard.orders.bulk'), [
            'ids' => [$orderB->id],
            'action' => 'fraud',
        ]);
        $this->actingAs($admin)->post(route('dashboard.orders.bulk'), [
            'ids' => [$orderC->id],
            'action' => 'pending',
        ]);

        $this->assertSame(Order::STATUS_CANCELLED, $orderA->fresh()->status);
        $this->assertSame(Order::STATUS_FRAUD, $orderB->fresh()->status);
        $this->assertSame(Order::STATUS_PENDING, $orderC->fresh()->status);
        $this->assertSame([], $spy->activatedOrderIds);
    }

    // J — authorization and validation behavior are unchanged.
    public function test_authorization_and_validation_are_unchanged(): void
    {
        $nonAdmin = User::factory()->create(['super_admin' => false]);
        $order = $this->makeOrder('subscription', Order::STATUS_PENDING);

        $this->actingAs($nonAdmin)
            ->patch(route('dashboard.orders.status', $order), ['status' => 'active'])
            ->assertForbidden();

        $admin = $this->makeAdmin();
        $this->actingAs($admin)
            ->patch(route('dashboard.orders.status', $order), ['status' => 'not-a-real-status'])
            ->assertSessionHasErrors('status');

        $this->actingAs($nonAdmin)
            ->post(route('dashboard.orders.bulk'), ['ids' => [$order->id], 'action' => 'active'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('dashboard.orders.bulk'), ['ids' => [$order->id], 'action' => 'not-a-real-action'])
            ->assertSessionHasErrors('action');
    }

    // -----------------------------------------------------------------
    // Fixtures
    // -----------------------------------------------------------------

    protected function makeOrder(string $type, string $status): Order
    {
        $client = Client::create([
            'first_name' => 'Activation',
            'last_name' => 'Idempotency',
            'email' => 'activation_idem_' . uniqid() . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Test Co',
        ]);

        return Order::create([
            'client_id' => $client->id,
            'status' => $status,
            'type' => $type,
        ]);
    }

    protected function makeAdmin(): User
    {
        return User::factory()->create(['super_admin' => true]);
    }

    /**
     * @return object{activatedOrderIds: array<int>}
     */
    protected function bindActivationSpy(): object
    {
        $spy = new class(Mockery::mock(RegistrarProvisioningService::class)) extends OrderActivationService {
            public array $activatedOrderIds = [];

            public function activate(Order $order, ?string $paymentMethod = null): array
            {
                $this->activatedOrderIds[] = $order->getKey();

                return ['domain_registration' => null, 'subscriptions' => collect()];
            }
        };

        $this->app->instance(OrderActivationService::class, $spy);

        return $spy;
    }
}
