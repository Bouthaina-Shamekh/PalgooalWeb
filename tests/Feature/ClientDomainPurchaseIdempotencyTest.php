<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\DomainProvider;
use App\Models\DomainTld;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Domains\DomainAvailabilityService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ClientDomainPurchaseIdempotencyTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    public function test_repeated_purchase_reuses_the_same_complete_order_and_invoice(): void
    {
        $this->makeCatalog('com', 1000, 'USD');
        $this->fakeAvailability();
        $client = $this->makeClient();

        $first = $this->actingAs($client, 'client')->post(route('client.domains.purchase'), [
            'client_id' => $client->id,
            'domain_name' => 'Idempotent.COM.',
            'price_cents' => 1,
            'currency' => 'ILS',
            'provider_id' => 999999,
        ]);

        $order = Order::query()->sole();
        $invoice = Invoice::query()->sole();
        $first->assertRedirect(route('client.invoices.checkout', $invoice));
        $this->assertNotNull($order->checkout_fingerprint);
        $this->assertSame(64, strlen($order->checkout_fingerprint));
        $this->assertSame(1, OrderItem::query()->count());
        $this->assertSame(1, InvoiceItem::query()->count());
        $this->assertSame('idempotent.com', OrderItem::query()->sole()->domain);
        $this->assertSame(1000, OrderItem::query()->sole()->price_cents);
        $this->assertSame('USD', Invoice::query()->sole()->currency);

        $orderNumber = $order->order_number;
        $second = $this->actingAs($client, 'client')->post(route('client.domains.purchase'), [
            'client_id' => $client->id,
            'domain_name' => 'idempotent.com',
            'price_cents' => 999999,
            'currency' => 'JOD',
            'provider_id' => 1,
        ]);

        $second->assertRedirect(route('client.invoices.checkout', $invoice));
        $this->assertSame(1, Order::query()->count());
        $this->assertSame(1, OrderItem::query()->count());
        $this->assertSame(1, Invoice::query()->count());
        $this->assertSame(1, InvoiceItem::query()->count());
        $this->assertSame($invoice->id, Invoice::query()->sole()->id);
        $this->assertSame($orderNumber, Order::query()->sole()->order_number);
    }

    public function test_different_clients_get_different_fingerprints_for_the_same_domain(): void
    {
        $this->makeCatalog('com', 1000, 'USD');
        $this->fakeAvailability();
        $firstClient = $this->makeClient();
        $secondClient = $this->makeClient();

        $this->actingAs($firstClient, 'client')->post(route('client.domains.purchase'), [
            'client_id' => $firstClient->id,
            'domain_name' => 'shared.com',
        ])->assertRedirect();
        auth('client')->logout();
        $this->actingAs($secondClient, 'client')->post(route('client.domains.purchase'), [
            'client_id' => $secondClient->id,
            'domain_name' => 'shared.com',
        ])->assertRedirect();

        $this->assertSame(2, Order::query()->count());
        $this->assertSame(2, Invoice::query()->count());
        $this->assertCount(2, Order::query()->pluck('checkout_fingerprint')->unique());
        $this->assertSame(
            [$firstClient->id, $secondClient->id],
            Order::query()->orderBy('client_id')->pluck('client_id')->all()
        );
    }

    public function test_price_change_or_different_domain_produces_a_different_fingerprint(): void
    {
        $tld = $this->makeCatalog('com', 1000, 'USD');
        $this->makeCatalog('net', 1200, 'USD');
        $this->fakeAvailability();
        $client = $this->makeClient();

        $this->purchase($client, 'change.com')->assertRedirect();
        $firstFingerprint = Order::query()->sole()->checkout_fingerprint;

        $tld->prices()->where('action', 'register')->update(['sale' => 11, 'cost' => 11]);
        $this->purchase($client, 'change.com')->assertRedirect();
        $this->purchase($client, 'different.net')->assertRedirect();

        $fingerprints = Order::query()->pluck('checkout_fingerprint');
        $this->assertSame(3, $fingerprints->count());
        $this->assertSame(3, $fingerprints->unique()->count());
        $this->assertNotSame($firstFingerprint, $fingerprints[1]);
    }

    public function test_incomplete_matching_order_is_not_treated_as_idempotent_success(): void
    {
        $this->makeCatalog('com', 1000, 'USD');
        $this->fakeAvailability();
        $client = $this->makeClient();
        $this->purchase($client, 'incomplete.com')->assertRedirect();
        InvoiceItem::query()->sole()->delete();

        $response = $this->from(route('client.domains.search'))
            ->actingAs($client, 'client')
            ->post(route('client.domains.purchase'), [
                'client_id' => $client->id,
                'domain_name' => 'incomplete.com',
            ]);

        $response->assertRedirect(route('client.domains.search'))->assertSessionHas('error');
        $this->assertSame(1, Order::query()->count());
        $this->assertSame(1, Invoice::query()->count());
        $this->assertSame(1, InvoiceItem::withTrashed()->count());
    }

    public function test_paid_and_cancelled_invoices_are_reused_without_new_orders_and_with_state_feedback(): void
    {
        $this->makeCatalog('com', 1000, 'USD');
        $this->makeCatalog('net', 1200, 'USD');
        $this->fakeAvailability();
        $client = $this->makeClient();

        $this->purchase($client, 'paid.com');
        $paidInvoice = Invoice::query()->sole();
        $paidInvoice->update(['status' => 'paid', 'paid_date' => now()]);
        $this->purchase($client, 'paid.com')->assertRedirect(route('client.invoices.checkout', [
            'invoice' => $paidInvoice,
            'state' => 'paid',
        ]));

        $this->purchase($client, 'cancelled.net');
        $cancelledInvoice = Invoice::query()->latest('id')->firstOrFail();
        $cancelledInvoice->update(['status' => 'cancelled']);
        $this->purchase($client, 'cancelled.net')
            ->assertRedirect(route('client.invoices.checkout', [
                'invoice' => $cancelledInvoice,
                'state' => 'cancelled',
            ]))
            ->assertSessionHas('error');

        $this->assertSame(2, Order::query()->count());
        $this->assertSame(2, Invoice::query()->count());
    }

    public function test_database_unique_constraint_rejects_duplicate_fingerprint(): void
    {
        $client = $this->makeClient();
        $attributes = [
            'client_id' => $client->id,
            'status' => Order::STATUS_PENDING,
            'type' => 'domain',
            'checkout_fingerprint' => str_repeat('a', 64),
        ];
        Order::query()->create($attributes);

        try {
            Order::query()->create($attributes);
            $this->fail('The database accepted a duplicate checkout fingerprint.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertStringContainsString('checkout_fingerprint', $exception->getMessage());
        }

        $this->assertSame(1, Order::query()->count());
    }

    private function purchase(Client $client, string $domain)
    {
        return $this->actingAs($client, 'client')->post(route('client.domains.purchase'), [
            'client_id' => $client->id,
            'domain_name' => $domain,
        ]);
    }

    // TLD-3F.1 — fixture note: mode is now 'live'. This file tests purchase-fingerprint
    // idempotency only, never provider-mode eligibility, but the trusted registration
    // quote now requires a live provider to resolve at all.
    private function makeCatalog(string $tldName, int $priceCents, string $currency): DomainTld
    {
        $provider = DomainProvider::query()->firstOrCreate(
            ['type' => 'namecheap'],
            [
                'name' => 'Namecheap Purchase Test',
                'mode' => 'live',
                'endpoint' => 'https://namecheap.example.test',
                'username' => 'test-user',
                'password' => 'test-password',
                'api_key' => 'test-key',
                'client_ip' => '127.0.0.1',
                'is_active' => true,
            ]
        );
        $tld = DomainTld::query()->create([
            'provider_id' => $provider->id,
            'provider' => $provider->type,
            'tld' => $tldName,
            'currency' => $currency,
            'enabled' => true,
        ]);
        $price = $priceCents / 100;
        $tld->prices()->create([
            'action' => 'register',
            'years' => 1,
            'cost' => $price,
            'sale' => $price,
        ]);

        return $tld;
    }

    private function makeClient(): Client
    {
        return Client::query()->create([
            'first_name' => 'Direct',
            'last_name' => 'Purchase',
            'email' => uniqid('direct_purchase_', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Direct Purchase Test',
            'can_login' => true,
        ]);
    }

    private function fakeAvailability(): void
    {
        $this->app->instance(DomainAvailabilityService::class, new class extends DomainAvailabilityService
        {
            public function checkDomains(array $domains, ?DomainProvider $provider = null): array
            {
                return [
                    'ok' => true,
                    'reason' => 'ok',
                    'message' => 'ok',
                    'results' => array_map(fn (string $domain) => [
                        'domain' => strtolower(trim($domain)),
                        'available' => true,
                        'is_premium' => false,
                        'price' => null,
                        'currency' => null,
                    ], $domains),
                ];
            }

            public function verifyRegistrationAvailabilityBatch(array $domains, DomainProvider $provider): ?array
            {
                return array_fill_keys(array_map(fn ($domain) => strtolower(trim($domain)), $domains), true);
            }
        });
    }
}
