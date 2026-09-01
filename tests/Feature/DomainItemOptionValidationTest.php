<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\DomainProvider;
use App\Models\DomainTld;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\PaymentManager;
use App\Services\Domains\DomainAvailabilityService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Mockery;
use Tests\TestCase;

class DomainItemOptionValidationTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    public function test_register_is_stored_as_the_only_trusted_cart_action(): void
    {
        $this->createCatalog();

        $this->postJson(route('cart.store'), [
            'items' => [[
                'domain' => 'allowed.com',
                'item_option' => 'register',
                'price_cents' => 1,
            ]],
        ])->assertOk();

        $items = session('palgoals_cart_domains');

        $this->assertCount(1, $items);
        $this->assertSame('register', $items[0]['item_option']);
        $this->assertSame(1000, $items[0]['price_cents']);
    }

    public function test_cart_rejects_every_unsupported_domain_action_without_saving_a_session_cart(): void
    {
        foreach (['transfer', 'renew', 'restore', 'new', 'existing', 'own', 'subdomain'] as $action) {
            session()->forget('palgoals_cart_domains');

            $response = $this->postJson(route('cart.store'), [
                'items' => [[
                    'domain' => $action . '.com',
                    'item_option' => $action,
                ]],
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors('items.0.item_option');
            $this->assertNull(session('palgoals_cart_domains'), $action);
        }

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, Invoice::query()->count());
    }

    public function test_cart_rejects_a_mixed_batch_atomically(): void
    {
        $this->postJson(route('cart.store'), [
            'items' => [
                ['domain' => 'valid.com', 'item_option' => 'register'],
                ['domain' => 'invalid.com', 'item_option' => 'transfer'],
            ],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('items.1.item_option');

        $this->assertNull(session('palgoals_cart_domains'));
        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, Invoice::query()->count());
    }

    public function test_checkout_rejects_a_forged_session_before_any_database_write(): void
    {
        $client = $this->makeClient();

        $this->actingAs($client, 'client')
            ->withSession([
                'palgoals_cart_domains' => [[
                    'domain' => 'forged.com',
                    'item_option' => 'transfer',
                    'price_cents' => 1000,
                ]],
            ])
            ->postJson(route('checkout.process', ['template_id' => 0]))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath(
                'message',
                t('site.Domain_Item_Option_Unsupported', 'نوع عملية الدومين غير مدعوم في هذا المسار.')
            );

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(0, OrderItem::query()->count());
    }

    public function test_checkout_rejects_a_missing_item_option_before_any_database_write(): void
    {
        $client = $this->makeClient();

        $this->actingAs($client, 'client')
            ->withSession([
                'palgoals_cart_domains' => [[
                    'domain' => 'missing.com',
                    'price_cents' => 1000,
                ]],
            ])
            ->postJson(route('checkout.process', ['template_id' => 0]))
            ->assertStatus(422);

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(0, OrderItem::query()->count());
    }

    public function test_valid_checkout_persists_register_explicitly_on_the_order_item(): void
    {
        $this->createCatalog();
        $client = $this->makeClient();
        $this->fakeAvailability();
        $this->fakePaymentManager();

        $this->actingAs($client, 'client')->postJson(route('checkout.cart.process'), [
            'items' => [[
                'domain' => 'checkout-valid.com',
                'option' => 'register',
                'price_cents' => 1000,
            ]],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame('register', OrderItem::query()->sole()->item_option);
        $this->assertSame('draft', Invoice::query()->sole()->status);
    }

    private function createCatalog(): void
    {
        $provider = DomainProvider::query()->create([
            'name' => 'Item Option Provider',
            'type' => 'namecheap',
            'mode' => 'test',
            'endpoint' => 'https://provider.example.test',
            'username' => 'test-user',
            'password' => 'test-password',
            'api_key' => 'test-key',
            'client_ip' => '127.0.0.1',
            'is_active' => true,
        ]);

        $tld = DomainTld::query()->create([
            'provider_id' => $provider->id,
            'provider' => $provider->type,
            'tld' => 'com',
            'currency' => 'USD',
            'enabled' => true,
        ]);

        $tld->prices()->create([
            'action' => 'register',
            'years' => 1,
            'cost' => 10,
            'sale' => 10,
        ]);
    }

    private function makeClient(): Client
    {
        return Client::query()->create([
            'first_name' => 'Item',
            'last_name' => 'Option',
            'email' => uniqid('domain_item_option_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Domain Item Option Test',
            'can_login' => true,
        ]);
    }

    private function fakeAvailability(): void
    {
        $this->app->instance(DomainAvailabilityService::class, new class extends DomainAvailabilityService
        {
            public function verifyRegistrationAvailabilityBatch(array $domains, DomainProvider $provider): ?array
            {
                return array_fill_keys(array_map('strtolower', $domains), true);
            }
        });
    }

    private function fakePaymentManager(): void
    {
        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('name')->andReturn('lahza');
        $gateway->shouldNotReceive('createSession');

        $manager = Mockery::mock(PaymentManager::class);
        $manager->shouldReceive('isEnabled')->andReturnTrue();
        $manager->shouldReceive('gateway')->andReturn($gateway);
        $this->app->instance(PaymentManager::class, $manager);
    }
}
