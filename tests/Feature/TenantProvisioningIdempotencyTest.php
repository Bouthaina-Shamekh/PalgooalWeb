<?php

namespace Tests\Feature;

use App\Jobs\ProvisionSubscription;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Server;
use App\Models\Tenancy\Subscription;
use App\Services\Templates\TemplateCloner;
use App\Services\Tenancy\DomainVerificationService;
use App\Services\Tenancy\SubscriptionSyncService;
use App\Services\Tenancy\TenantDomainHostService;
use App\Services\Tenancy\TenantProvisioningService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class TenantProvisioningIdempotencyTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    public function test_active_subscription_does_not_call_createacct(): void
    {
        $this->assertBlockedStateDoesNotCallProvider(Subscription::PROVISIONING_ACTIVE);
    }

    public function test_provisioning_subscription_does_not_call_createacct(): void
    {
        $this->assertBlockedStateDoesNotCallProvider(Subscription::PROVISIONING_IN_PROGRESS);
    }

    public function test_unknown_subscription_does_not_call_createacct(): void
    {
        $this->assertBlockedStateDoesNotCallProvider(Subscription::PROVISIONING_UNKNOWN);
    }

    public function test_pending_is_claimed_before_one_createacct_and_becomes_active(): void
    {
        $subscription = $this->makeHostingSubscription(Subscription::PROVISIONING_PENDING);
        $statesSeenByProvider = [];

        Http::fake(function (Request $request) use ($subscription, &$statesSeenByProvider) {
            $statesSeenByProvider[] = $subscription->fresh()->provisioning_status;

            return Http::response(['metadata' => ['result' => 1]], 200);
        });

        $result = (new SubscriptionSyncService())->provisionAccount($subscription);

        $this->assertSame([Subscription::PROVISIONING_IN_PROGRESS], $statesSeenByProvider);
        $this->assertSame(SubscriptionSyncService::RESULT_SUCCESS, $result['result']);
        $this->assertSame(Subscription::PROVISIONING_ACTIVE, $subscription->fresh()->provisioning_status);
        $this->assertNotNull($subscription->fresh()->provisioned_at);
        $this->assertSame($subscription->fresh()->username, $subscription->fresh()->cpanel_username);
        $this->assertNotEmpty($subscription->fresh()->cpanel_password);
        Http::assertSentCount(1);
    }

    public function test_failed_subscription_can_make_one_new_attempt_and_become_active(): void
    {
        $subscription = $this->makeHostingSubscription(Subscription::PROVISIONING_FAILED);
        Http::fake(['*' => Http::response(['metadata' => ['result' => 1]], 200)]);

        $result = (new SubscriptionSyncService())->provisionAccount($subscription);

        $this->assertSame(SubscriptionSyncService::RESULT_SUCCESS, $result['result']);
        $this->assertSame(Subscription::PROVISIONING_ACTIVE, $subscription->fresh()->provisioning_status);
        Http::assertSentCount(1);
    }

    public function test_confirmed_provider_rejection_becomes_failed(): void
    {
        $subscription = $this->makeHostingSubscription(Subscription::PROVISIONING_PENDING);
        Http::fake([
            '*' => Http::response([
                'metadata' => [
                    'result' => 0,
                    'reason' => 'The selected package is not available.',
                ],
            ], 200),
        ]);

        $result = (new SubscriptionSyncService())->provisionAccount($subscription);

        $this->assertSame(SubscriptionSyncService::RESULT_FAILED, $result['result']);
        $this->assertSame(Subscription::PROVISIONING_FAILED, $subscription->fresh()->provisioning_status);
        Http::assertSentCount(1);
    }

    public function test_timeout_becomes_unknown_and_a_later_run_does_not_retry(): void
    {
        $subscription = $this->makeHostingSubscription(Subscription::PROVISIONING_PENDING);
        $calls = 0;

        Http::fake(function () use (&$calls) {
            $calls++;
            throw new RuntimeException('request timed out after transmission');
        });

        $service = new SubscriptionSyncService();
        $first = $service->provisionAccount($subscription);
        $second = $service->provisionAccount($subscription->fresh());

        $this->assertSame(SubscriptionSyncService::RESULT_UNKNOWN, $first['result']);
        $this->assertSame(SubscriptionSyncService::RESULT_SKIPPED, $second['result']);
        $this->assertSame(Subscription::PROVISIONING_UNKNOWN, $subscription->fresh()->provisioning_status);
        $this->assertSame(1, $calls);
    }

    public function test_crash_state_left_as_provisioning_is_not_reclaimed(): void
    {
        $subscription = $this->makeHostingSubscription(Subscription::PROVISIONING_IN_PROGRESS);
        $subscription->forceFill([
            'username' => 'stableuser',
            'cpanel_username' => 'stableuser',
            'last_sync_message' => 'WHM account creation claimed and in progress.',
        ])->save();
        Http::fake();

        $result = (new SubscriptionSyncService())->provisionAccount($subscription->fresh());

        $this->assertSame(SubscriptionSyncService::RESULT_SKIPPED, $result['result']);
        $this->assertSame(Subscription::PROVISIONING_IN_PROGRESS, $subscription->fresh()->provisioning_status);
        Http::assertNothingSent();
    }

    public function test_second_execution_after_the_atomic_claim_cannot_send_createacct(): void
    {
        $subscription = $this->makeHostingSubscription(Subscription::PROVISIONING_PENDING);
        $service = new SubscriptionSyncService();
        $secondResult = null;
        $calls = 0;

        Http::fake(function () use ($subscription, $service, &$secondResult, &$calls) {
            $calls++;
            $secondResult = $service->provisionAccount($subscription->fresh());

            return Http::response(['metadata' => ['result' => 1]], 200);
        });

        $firstResult = $service->provisionAccount($subscription);

        $this->assertSame(SubscriptionSyncService::RESULT_SUCCESS, $firstResult['result']);
        $this->assertSame(SubscriptionSyncService::RESULT_SKIPPED, $secondResult['result']);
        $this->assertSame(Subscription::PROVISIONING_IN_PROGRESS, $secondResult['state']);
        $this->assertSame(1, $calls);
        Http::assertSentCount(1);
    }

    public function test_malformed_response_keeps_the_same_username_and_never_tries_a_fallback(): void
    {
        $subscription = $this->makeHostingSubscription(Subscription::PROVISIONING_PENDING, 'chosenuser');
        $usernames = [];

        Http::fake(function (Request $request) use (&$usernames) {
            $usernames[] = $request['username'];

            return Http::response('not-json', 200);
        });

        $service = new SubscriptionSyncService();
        $service->provisionAccount($subscription);
        $service->provisionAccount($subscription->fresh());

        $this->assertSame(['chosenuser'], $usernames);
        $this->assertSame('chosenuser', $subscription->fresh()->username);
        $this->assertSame(Subscription::PROVISIONING_UNKNOWN, $subscription->fresh()->provisioning_status);
        Http::assertSentCount(1);
    }

    public function test_existing_account_response_is_unknown_instead_of_trying_another_username(): void
    {
        $subscription = $this->makeHostingSubscription(Subscription::PROVISIONING_PENDING, 'fixeduser');
        Http::fake([
            '*' => Http::response([
                'metadata' => [
                    'result' => 0,
                    'reason' => 'Account fixeduser already exists.',
                ],
            ], 200),
        ]);

        $result = (new SubscriptionSyncService())->provisionAccount($subscription);

        $this->assertSame(SubscriptionSyncService::RESULT_UNKNOWN, $result['result']);
        $this->assertSame('fixeduser', $subscription->fresh()->username);
        $this->assertSame(Subscription::PROVISIONING_UNKNOWN, $subscription->fresh()->provisioning_status);
        Http::assertSentCount(1);
    }

    public function test_ProvisionSubscription_job_is_safe_when_run_twice_after_success(): void
    {
        $subscription = $this->makeHostingSubscription(Subscription::PROVISIONING_PENDING, 'jobuser');
        Http::fake(['*' => Http::response(['metadata' => ['result' => 1]], 200)]);
        Notification::fake();

        $service = new TenantProvisioningService(
            new SubscriptionSyncService(),
            Mockery::mock(TemplateCloner::class),
            Mockery::mock(TenantDomainHostService::class)->shouldIgnoreMissing(),
            Mockery::mock(DomainVerificationService::class)->shouldIgnoreMissing(),
        );
        $job = new ProvisionSubscription($subscription->id);

        $job->handle($service);
        $job->handle($service);

        $this->assertSame(Subscription::PROVISIONING_ACTIVE, $subscription->fresh()->provisioning_status);
        Http::assertSentCount(1);
    }

    private function assertBlockedStateDoesNotCallProvider(string $state): void
    {
        $subscription = $this->makeHostingSubscription($state);
        Http::fake();

        $result = (new SubscriptionSyncService())->provisionAccount($subscription);

        $this->assertSame(SubscriptionSyncService::RESULT_SKIPPED, $result['result']);
        $this->assertSame($state, $subscription->fresh()->provisioning_status);
        Http::assertNothingSent();
    }

    private function makeHostingSubscription(string $state, ?string $username = null): Subscription
    {
        $client = Client::query()->create([
            'first_name' => 'WHM',
            'last_name' => 'Idempotency',
            'email' => uniqid('whm_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'WHM Test',
        ]);

        $server = Server::query()->create([
            'name' => 'WHM Test Server',
            'type' => 'cpanel',
            'hostname' => 'whm.example.test',
            'username' => 'root',
            'api_token' => 'test-token',
            'is_active' => true,
        ]);

        $plan = Plan::query()->create([
            'name' => 'Hosting Test Plan',
            'slug' => uniqid('hosting-plan-', false),
            'plan_type' => Plan::TYPE_HOSTING,
            'server_id' => $server->id,
            'server_package' => 'test_package',
            'is_active' => true,
        ]);

        return Subscription::query()->create([
            'client_id' => $client->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'provisioning_status' => $state,
            'price_cents' => 1000,
            'billing_cycle' => 'monthly',
            'username' => $username,
            'server_id' => $server->id,
            'server_package' => 'test_package',
            'domain_option' => 'subdomain',
            'domain_name' => uniqid('tenant-', false) . '.example.test',
            'subdomain' => uniqid('tenant-', false),
        ]);
    }
}
