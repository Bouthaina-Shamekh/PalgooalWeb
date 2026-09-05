<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\DomainProvisioningAttempt;
use App\Models\DomainTld;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\DTOs\PaymentSession;
use App\Payments\PaymentManager;
use App\Services\Domains\DomainAvailabilityService;
use App\Services\Domains\DomainPricingService;
use App\Services\Domains\RegistrarProvisioningService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Mockery;
use Tests\TestCase;

class ProviderSourceOfTruthTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    public function test_quote_contains_the_complete_namecheap_and_enom_provider_snapshot(): void
    {
        [$namecheap, $enom] = $this->createCatalog();
        $pricing = app(DomainPricingService::class);

        $com = $pricing->registrationQuoteForDomain('quoted.com');
        $net = $pricing->registrationQuoteForDomain('quoted.net');

        $this->assertSame($namecheap->id, $com['provider_id']);
        $this->assertSame('namecheap', $com['provider_type']);
        $this->assertSame('live', $com['provider_mode']);
        $this->assertSame('com', $com['tld']);
        $this->assertGreaterThan(0, $com['domain_tld_id']);
        $this->assertSame(1000, $com['price_cents']);
        $this->assertSame('USD', $com['currency']);

        $this->assertSame($enom->id, $net['provider_id']);
        $this->assertSame('enom', $net['provider_type']);
        $this->assertSame('live', $net['provider_mode']);
        $this->assertSame(900, $net['price_cents']);
    }

    public function test_cart_ignores_forged_provider_fields_and_stores_the_server_quote_snapshot(): void
    {
        [$namecheap, $enom] = $this->createCatalog();

        $this->postJson(route('cart.store'), [
            'items' => [[
                'domain' => 'trusted.com',
                'item_option' => 'register',
                'price_cents' => 1,
                'provider_id' => $enom->id,
                'provider_type' => 'enom',
                'provider_mode' => 'live',
                'domain_tld_id' => 999999,
                'currency' => 'EUR',
                'meta' => [
                    'provider_id' => $enom->id,
                    'provider_type' => 'enom',
                ],
            ]],
        ])->assertOk();

        $item = session('palgoals_cart_domains')[0];
        $this->assertSame($namecheap->id, $item['provider_id']);
        $this->assertSame('namecheap', $item['provider_type']);
        $this->assertSame('live', $item['provider_mode']);
        $this->assertSame($namecheap->id, $item['meta']['provider_id']);
        $this->assertSame('namecheap', $item['meta']['provider_type']);
        $this->assertSame('USD', $item['meta']['currency']);
        $this->assertSame(1000, $item['price_cents']);
    }

    public function test_availability_uses_only_the_explicit_quote_provider_without_fallback(): void
    {
        [$namecheap, $enom] = $this->createCatalog();
        $availability = new class extends DomainAvailabilityService
        {
            public array $calls = [];

            protected function namecheapCheck(DomainProvider $provider, array $domains): array
            {
                $this->calls[] = ['provider_id' => $provider->id, 'domains' => $domains];

                return $this->available($domains);
            }

            protected function enomCheck(DomainProvider $provider, array $domains): array
            {
                $this->calls[] = ['provider_id' => $provider->id, 'domains' => $domains];

                return $this->available($domains);
            }

            private function available(array $domains): array
            {
                return [
                    'ok' => true,
                    'reason' => 'ok',
                    'message' => 'ok',
                    'results' => array_map(fn (string $domain) => [
                        'domain' => $domain,
                        'available' => true,
                    ], $domains),
                ];
            }
        };

        $result = $availability->verifyRegistrationAvailabilityBatch(['selected.net'], $enom);

        $this->assertSame(['selected.net' => true], $result);
        $this->assertSame([[
            'provider_id' => $enom->id,
            'domains' => ['selected.net'],
        ]], $availability->calls);
        $this->assertNotSame($namecheap->id, $availability->calls[0]['provider_id']);
    }

    public function test_checkout_groups_availability_by_quote_provider_and_persists_each_snapshot(): void
    {
        [$namecheap, $enom] = $this->createCatalog();
        $client = $this->makeClient();
        $availability = $this->fakeAvailability();
        $this->app->instance(DomainAvailabilityService::class, $availability);
        $this->fakePaymentManager();

        $response = $this->actingAs($client, 'client')->postJson(route('checkout.cart.process'), [
            'items' => [
                [
                    'domain' => 'mixed-provider.com',
                    'option' => 'register',
                    'price_cents' => 1000,
                    'provider_id' => $enom->id,
                ],
                [
                    'domain' => 'mixed-provider.net',
                    'option' => 'register',
                    'price_cents' => 900,
                    'provider_id' => $namecheap->id,
                ],
            ],
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertSame([
            $namecheap->id => ['mixed-provider.com'],
            $enom->id => ['mixed-provider.net'],
        ], $availability->verifiedGroups);

        $items = OrderItem::query()->orderBy('domain')->get()->keyBy('domain');
        $this->assertSame($namecheap->id, $items['mixed-provider.com']->meta['provider_id']);
        $this->assertSame('namecheap', $items['mixed-provider.com']->meta['provider_type']);
        $this->assertSame($enom->id, $items['mixed-provider.net']->meta['provider_id']);
        $this->assertSame('enom', $items['mixed-provider.net']->meta['provider_type']);
        $this->assertSame('draft', Invoice::query()->sole()->status);
    }

    public function test_client_domain_purchase_saves_the_trusted_snapshot_instead_of_request_provider_data(): void
    {
        [$namecheap, $enom] = $this->createCatalog();
        $client = $this->makeClient();
        $availability = $this->fakeAvailability();
        $this->app->instance(DomainAvailabilityService::class, $availability);

        $response = $this->actingAs($client, 'client')->post(route('client.domains.purchase'), [
            'client_id' => $client->id,
            'domain_name' => 'client-purchase.com',
            'provider_id' => $enom->id,
            'provider_type' => 'enom',
        ]);

        $response->assertRedirect();
        $item = OrderItem::query()->sole();
        $this->assertSame($namecheap->id, $item->meta['provider_id']);
        $this->assertSame('namecheap', $item->meta['provider_type']);
        $this->assertSame('live', $item->meta['provider_mode']);
        $this->assertGreaterThan(0, $item->meta['domain_tld_id']);
        $this->assertSame('USD', $item->meta['currency']);
        $this->assertSame([$namecheap->id], array_values(array_unique($availability->providerIds)));
    }

    public function test_checkout_rejects_mixed_quote_currencies_before_creating_an_order(): void
    {
        [$namecheap] = $this->createCatalog();
        $this->makeTldPrice($namecheap, 'org', 11, 'EUR');
        $client = $this->makeClient();
        $this->fakePaymentManager();

        $this->actingAs($client, 'client')->postJson(route('checkout.cart.process'), [
            'items' => [
                ['domain' => 'currency.com', 'option' => 'register', 'price_cents' => 1000],
                ['domain' => 'currency.org', 'option' => 'register', 'price_cents' => 1100],
            ],
        ])->assertStatus(422);

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, Invoice::query()->count());
    }

    public function test_provisioning_uses_each_order_item_provider_and_attempt_matches_the_snapshot(): void
    {
        [$namecheap, $enom] = $this->createCatalog();
        $client = $this->makeClient();
        $order = Order::query()->create([
            'client_id' => $client->id,
            'status' => Order::STATUS_PENDING,
            'type' => 'domains',
        ]);
        $this->makeRegisterItem($order, 'provision-namecheap.com', $namecheap);
        $this->makeRegisterItem($order, 'provision-enom.net', $enom);
        $service = $this->fakeRegistrar();

        $result = $service->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));

        $this->assertTrue($result['ok']);
        $expectedProviderCalls = [
            'provision-namecheap.com' => $namecheap->id,
            'provision-enom.net' => $enom->id,
        ];
        ksort($expectedProviderCalls);
        ksort($service->providerCalls);
        $this->assertSame($expectedProviderCalls, $service->providerCalls);

        $attempts = DomainProvisioningAttempt::query()
            ->with('orderItem')
            ->get();
        $this->assertCount(2, $attempts);
        foreach ($attempts as $attempt) {
            $this->assertSame((int) $attempt->orderItem->meta['provider_id'], $attempt->provider_id);
            $this->assertSame($attempt->orderItem->meta['provider_type'], $attempt->provider_type);
            $this->assertSame($attempt->orderItem->meta['provider_mode'], $attempt->provider_mode);
        }
    }

    public function test_missing_inactive_or_mismatched_provider_snapshot_never_falls_back_or_calls_provider(): void
    {
        [$namecheap, $enom] = $this->createCatalog();

        $cases = [
            'missing' => [],
            'inactive' => [
                'provider_id' => $enom->id,
                'provider_type' => 'enom',
                'provider_mode' => 'live',
            ],
            'type_mismatch' => [
                'provider_id' => $namecheap->id,
                'provider_type' => 'enom',
                'provider_mode' => 'test',
            ],
            // TLD-3F.1: $namecheap is now provisioned as mode=live (fixture updated for the
            // live-only eligibility contract), so the genuine mismatch here is claiming a
            // 'test' snapshot mode against the real 'live' provider — drift detection must
            // still reject it.
            'mode_mismatch' => [
                'provider_id' => $namecheap->id,
                'provider_type' => 'namecheap',
                'provider_mode' => 'test',
            ],
        ];
        $enom->update(['is_active' => false]);

        foreach ($cases as $case => $snapshot) {
            $client = $this->makeClient();
            $order = Order::query()->create([
                'client_id' => $client->id,
                'status' => Order::STATUS_PENDING,
                'type' => 'domains',
            ]);
            $item = $order->items()->create([
                'domain' => $case . '-' . uniqid() . '.com',
                'item_option' => 'register',
                'price_cents' => 1000,
                'meta' => array_merge($snapshot, [
                    'registration_date' => now()->toDateString(),
                    'renewal_date' => now()->addYear()->toDateString(),
                ]),
                'provisioning_status' => OrderItem::PROVISIONING_NOT_STARTED,
            ]);
            $service = $this->fakeRegistrar();

            $result = $service->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));

            $this->assertFalse($result['ok'], $case);
            $this->assertSame([], $service->providerCalls, $case);
            $this->assertSame(OrderItem::PROVISIONING_NOT_STARTED, $item->fresh()->provisioning_status, $case);
        }

        $this->assertSame(0, DomainProvisioningAttempt::query()->count());
    }

    /**
     * TLD-3D — Domain Provider Identity Writers (test B of the implementation test plan).
     *
     * Managed registration must write the exact trusted provider_id already resolved by
     * trustedRegistrationProvider() onto the new Domain row — not merely its registrar type
     * string. Domain.provider_id is the new Source of Truth for renewal pricing/provisioning.
     */
    public function test_managed_registration_writes_the_exact_trusted_provider_id_onto_the_domain(): void
    {
        [$namecheap, $enom] = $this->createCatalog();
        $client = $this->makeClient();
        $order = Order::query()->create([
            'client_id' => $client->id,
            'status' => Order::STATUS_PENDING,
            'type' => 'domains',
        ]);
        $this->makeRegisterItem($order, 'writes-provider-id.com', $namecheap);
        $service = $this->fakeRegistrar();

        $result = $service->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));

        $this->assertTrue($result['ok']);
        $domain = Domain::query()->where('domain_name', 'writes-provider-id.com')->sole();
        $this->assertSame($namecheap->id, $domain->provider_id);
        $this->assertSame('namecheap', $domain->registrar);
        $this->assertNotSame($enom->id, $domain->provider_id);
    }

    /**
     * TLD-3D — Domain Provider Identity Writers (test C of the implementation test plan).
     *
     * With two ACTIVE providers sharing the same type ("namecheap"), Domain.provider_id after
     * registration must equal the exact provider_id from the trusted registration snapshot —
     * never merely "some active provider of the same type". A type-only resolver would not be
     * able to tell these two rows apart; the provider_id-based writer must.
     */
    public function test_managed_registration_with_two_same_type_providers_writes_the_exact_snapshot_provider_id(): void
    {
        $namecheapPrimary = $this->makeProvider('namecheap', 'live');
        $namecheapSecondary = $this->makeProvider('namecheap', 'live');
        $this->makeTldPrice($namecheapPrimary, 'com', 10);
        $this->makeTldPrice($namecheapSecondary, 'net', 8);

        $client = $this->makeClient();
        $order = Order::query()->create([
            'client_id' => $client->id,
            'status' => Order::STATUS_PENDING,
            'type' => 'domains',
        ]);
        // The trusted snapshot explicitly names the SECOND same-type provider row.
        $this->makeRegisterItem($order, 'two-same-type.net', $namecheapSecondary);
        $service = $this->fakeRegistrar();

        $result = $service->provisionOrderDomain($order->fresh(['client', 'items', 'invoices.items']));

        $this->assertTrue($result['ok']);
        $domain = Domain::query()->where('domain_name', 'two-same-type.net')->sole();
        $this->assertSame($namecheapSecondary->id, $domain->provider_id);
        $this->assertNotSame($namecheapPrimary->id, $domain->provider_id);
    }

    private function createCatalog(): array
    {
        $namecheap = $this->makeProvider('namecheap', 'live');
        $enom = $this->makeProvider('enom', 'live');

        $this->makeTldPrice($namecheap, 'com', 10);
        $this->makeTldPrice($enom, 'com', 15);
        $this->makeTldPrice($namecheap, 'net', 14);
        $this->makeTldPrice($enom, 'net', 9);

        return [$namecheap, $enom];
    }

    private function makeProvider(string $type, string $mode): DomainProvider
    {
        return DomainProvider::query()->create([
            'name' => strtoupper($type) . ' ' . uniqid(),
            'type' => $type,
            'mode' => $mode,
            'endpoint' => 'https://' . $type . '.example.test',
            'username' => 'test-user',
            'password' => 'test-password',
            'api_key' => 'test-key',
            'client_ip' => '127.0.0.1',
            'is_active' => true,
        ]);
    }

    private function makeTldPrice(
        DomainProvider $provider,
        string $tldName,
        float $price,
        string $currency = 'USD'
    ): void
    {
        $tld = DomainTld::query()->create([
            'provider_id' => $provider->id,
            'provider' => $provider->type,
            'tld' => $tldName,
            'currency' => $currency,
            'enabled' => true,
        ]);
        $tld->prices()->create([
            'action' => 'register',
            'years' => 1,
            'cost' => $price,
            'sale' => $price,
        ]);
    }

    private function makeClient(): Client
    {
        return Client::query()->create([
            'first_name' => 'Provider',
            'last_name' => 'Truth',
            'email' => uniqid('provider_truth_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Provider Truth Test',
            'can_login' => true,
        ]);
    }

    private function makeRegisterItem(Order $order, string $domain, DomainProvider $provider): OrderItem
    {
        $quote = app(DomainPricingService::class)->registrationQuoteForDomain($domain);

        return $order->items()->create([
            'domain' => $domain,
            'item_option' => 'register',
            'price_cents' => $quote['price_cents'],
            'meta' => [
                'provider_id' => $provider->id,
                'provider_type' => $provider->type,
                'provider_mode' => $provider->mode,
                'domain_tld_id' => $quote['domain_tld_id'],
                'currency' => $quote['currency'],
                'years' => 1,
                'registration_date' => now()->toDateString(),
                'renewal_date' => now()->addYear()->toDateString(),
            ],
            'provisioning_status' => OrderItem::PROVISIONING_NOT_STARTED,
        ]);
    }

    private function fakeAvailability(): DomainAvailabilityService
    {
        return new class extends DomainAvailabilityService
        {
            public array $providerIds = [];
            public array $verifiedGroups = [];

            public function verifyRegistrationAvailabilityBatch(array $domains, DomainProvider $provider): ?array
            {
                $this->providerIds[] = $provider->id;
                $this->verifiedGroups[$provider->id] = array_values($domains);

                return array_fill_keys(array_map('strtolower', $domains), true);
            }

            public function checkDomains(array $domains, ?DomainProvider $provider = null): array
            {
                if ($provider instanceof DomainProvider) {
                    $this->providerIds[] = $provider->id;
                }

                return [
                    'ok' => true,
                    'reason' => 'ok',
                    'message' => 'ok',
                    'results' => array_map(fn (string $domain) => [
                        'domain' => $domain,
                        'available' => true,
                        'is_premium' => false,
                        'price' => null,
                        'currency' => null,
                    ], $domains),
                ];
            }
        };
    }

    private function fakeRegistrar(): RegistrarProvisioningService
    {
        return new class extends RegistrarProvisioningService
        {
            public array $providerCalls = [];

            protected function registerDomainWithProvider(
                DomainProvider $provider,
                Domain $domain,
                array $context,
                array $contact
            ): array {
                $this->providerCalls[$domain->domain_name] = $provider->id;

                return [
                    'ok' => true,
                    'cid' => 'provider-source-' . $provider->id,
                    'provider_reference' => 'reference-' . $provider->id,
                ];
            }
        };
    }

    private function fakePaymentManager(): void
    {
        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('name')->andReturn('lahza');
        $gateway->shouldReceive('createSession')
            ->zeroOrMoreTimes()
            ->andReturn(new PaymentSession('provider-session', 'https://pay.test/provider-session'));

        $manager = Mockery::mock(PaymentManager::class);
        $manager->shouldReceive('isEnabled')->andReturnTrue();
        $manager->shouldReceive('gateway')->andReturn($gateway);
        $this->app->instance(PaymentManager::class, $manager);
    }
}
