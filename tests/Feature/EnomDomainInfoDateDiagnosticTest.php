<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\DomainProvisioningAttempt;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAttempt;
use App\Services\Domains\Clients\EnomClient;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * TLD-3G.1C-A — Safe Enom GetDomainInfo Date Diagnostics.
 *
 * Locks in that EnomClient::inspectDomainInfoDateFields() (a TEMPORARY diagnostic method, safe
 * to remove once the real eNom date-field name is confirmed and the parser is fixed):
 *   - sends ONLY command=GetDomainInfo, never a mutating command;
 *   - returns ONLY date/time/expiry/creation/registration-like element and attribute names and
 *     values, never unrelated or secret-like nodes, even when their names would otherwise match
 *     the keyword filter (the exclusion list is checked first);
 *   - creates zero database rows of any kind;
 *   - is gated by the exact same provider-eligibility contract as
 *     ExistingDomainVerificationService (is_active, mode==='live', type==='enom', no fallback).
 *
 * Every scenario uses Http::fake() — no real Enom HTTP request is ever made from this file.
 */
class EnomDomainInfoDateDiagnosticTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    /* ====================== A — only GetDomainInfo is sent, never a mutating command ====================== */

    public function test_only_get_domain_info_is_sent_never_a_mutating_command(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response($this->fakeXmlWithDateFields(), 200, ['Content-Type' => 'application/xml']),
        ]);

        $provider = $this->makeProvider('enom', true, 'live');
        $client = app(EnomClient::class);

        $result = $client->inspectDomainInfoDateFields($provider, 'wpgoals.com');

        $this->assertTrue($result['ok']);

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $url = (string) $request->url();
            $this->assertStringContainsString('command=GetDomainInfo', $url);
            foreach (['Purchase', 'Extend', 'ModifyNS', 'RegisterNameServer', 'UpdateNameServer', 'CheckNSStatus'] as $mutating) {
                $this->assertStringNotContainsString('command=' . $mutating, $url);
            }
            return true;
        });
    }

    /* ====================== B — only whitelisted date-like fields are returned ====================== */

    public function test_returned_diagnostics_contain_only_whitelisted_date_like_fields(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response($this->fakeXmlWithDateFields(), 200, ['Content-Type' => 'application/xml']),
        ]);

        $provider = $this->makeProvider('enom', true, 'live');
        $client = app(EnomClient::class);

        $result = $client->inspectDomainInfoDateFields($provider, 'wpgoals.com');

        $this->assertTrue($result['ok']);
        $names = array_column($result['fields'], 'name');

        // Legitimate date-like fields, from anywhere in the tree (including a wrapper element
        // that is a sibling of <GetDomainInfo>, and a matching attribute name).
        $this->assertContains('expiration', $names);
        $this->assertContains('SomeUpdatedDate', $names);
        $this->assertContains('@raw-date-value', $names);

        $expiration = collect($result['fields'])->firstWhere('name', 'expiration');
        $this->assertSame('9/29/2026 9:31:00 AM', $expiration['value']);
    }

    /* ====================== C — secret-like / unrelated nodes are excluded ====================== */

    public function test_secret_like_and_unrelated_nodes_are_excluded_even_when_name_would_match(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response($this->fakeXmlWithDateFields(), 200, ['Content-Type' => 'application/xml']),
        ]);

        $provider = $this->makeProvider('enom', true, 'live');
        $client = app(EnomClient::class);

        $result = $client->inspectDomainInfoDateFields($provider, 'wpgoals.com');
        $names = array_column($result['fields'], 'name');
        $values = array_column($result['fields'], 'value');

        // Never present at all: credentials/account/party identifiers, whether as elements or
        // attributes, and regardless of whether their name would otherwise match the keyword
        // filter (PartyIdCreateDate contains "creat"+"date" but is excluded by name; the
        // token-created attribute contains "creat" but is excluded by name).
        $this->assertNotContains('UID', $names);
        $this->assertNotContains('PW', $names);
        $this->assertNotContains('ClientIP', $names);
        $this->assertNotContains('PartyIdCreateDate', $names);
        $this->assertNotContains('@token-created', $names);
        $this->assertNotContains('@party-id', $names);
        $this->assertNotContains('belongs-to', $names);

        // Never present at all: unrelated non-date nodes, even ones next to date fields.
        $this->assertNotContains('registrationstatus', $names);
        $this->assertNotContains('domainname', $names);

        // The secret-like literal values never leak either, even under a different key.
        $this->assertNotContains('should-not-appear', $values);
        $this->assertNotContains('secret-token-value', $values);
    }

    /* ====================== D — hard cap of 50 entries ====================== */

    public function test_output_is_hard_capped_at_fifty_entries(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response($this->fakeXmlWithManyDateFields(60), 200, ['Content-Type' => 'application/xml']),
        ]);

        $provider = $this->makeProvider('enom', true, 'live');
        $client = app(EnomClient::class);

        $result = $client->inspectDomainInfoDateFields($provider, 'wpgoals.com');

        $this->assertTrue($result['ok']);
        // 60 candidate date-like elements exist in the fixture; the hard cap must stop at 50.
        $this->assertSame(50, count($result['fields']));
    }

    /* ====================== E/F/G — provider eligibility guard, no fallback ====================== */

    public function test_inactive_provider_is_rejected_before_any_enom_call(): void
    {
        Http::fake();
        $provider = $this->makeProvider('enom', false, 'live');
        $result = app(EnomClient::class)->inspectDomainInfoDateFields($provider, 'wpgoals.com');

        $this->assertFalse($result['ok']);
        $this->assertSame('provider_inactive', $result['reason']);
        Http::assertNothingSent();
    }

    public function test_test_mode_provider_is_rejected_before_any_enom_call(): void
    {
        Http::fake();
        $provider = $this->makeProvider('enom', true, 'test');
        $result = app(EnomClient::class)->inspectDomainInfoDateFields($provider, 'wpgoals.com');

        $this->assertFalse($result['ok']);
        $this->assertSame('provider_not_live', $result['reason']);
        Http::assertNothingSent();
    }

    public function test_non_enom_provider_is_rejected_before_any_enom_call(): void
    {
        Http::fake();
        $provider = $this->makeProvider('namecheap', true, 'live');
        $result = app(EnomClient::class)->inspectDomainInfoDateFields($provider, 'wpgoals.com');

        $this->assertFalse($result['ok']);
        $this->assertSame('provider_not_enom', $result['reason']);
        Http::assertNothingSent();
    }

    /* ====================== H — zero database side effects ====================== */

    public function test_diagnostic_creates_zero_database_rows(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response($this->fakeXmlWithDateFields(), 200, ['Content-Type' => 'application/xml']),
        ]);

        $provider = $this->makeProvider('enom', true, 'live');
        app(EnomClient::class)->inspectDomainInfoDateFields($provider, 'wpgoals.com');

        $this->assertSame(0, Domain::query()->count());
        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(0, InvoiceItem::query()->count());
        $this->assertSame(0, OrderItem::query()->count());
        $this->assertSame(0, PaymentAttempt::query()->count());
        $this->assertSame(0, DomainProvisioningAttempt::query()->count());
    }

    /* ====================== Helpers ====================== */

    private function makeProvider(string $type, bool $active, string $mode): DomainProvider
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
            'is_active' => $active,
        ]);
    }

    /**
     * Mirrors the real observed wpgoals.com shape (expiration present, registered_at absent)
     * plus a sibling <DomainInfo> wrapper (matching the older DomainProvisioningReconciliationTest
     * fixture's structure) containing one legitimate deep-nested date-like field with a matching
     * attribute, and several deliberately secret-like / unrelated nodes that must never appear
     * in the diagnostic output.
     */
    private function fakeXmlWithDateFields(): string
    {
        return <<<'XML'
<?xml version="1.0"?>
<interface-response>
  <GetDomainInfo>
    <domainname domainnameid="179821282">wpgoals.com</domainname>
    <status>
      <registrationstatus>Registered</registrationstatus>
      <purchase-status>Paid</purchase-status>
      <expiration>9/29/2026 9:31:00 AM</expiration>
      <belongs-to party-id="{SECRET-PARTY-ID}"></belongs-to>
    </status>
  </GetDomainInfo>
  <DomainInfo>
    <SomeUpdatedDate updated-by="opsuser" raw-date-value="20260101" token-created="secret-token-value">2026-01-01</SomeUpdatedDate>
    <PartyIdCreateDate>2020-01-01</PartyIdCreateDate>
  </DomainInfo>
  <UID>should-not-appear</UID>
  <PW>should-not-appear</PW>
  <ClientIP>should-not-appear</ClientIP>
  <ErrCount>0</ErrCount>
</interface-response>
XML;
    }

    private function fakeXmlWithManyDateFields(int $count): string
    {
        $items = '';
        for ($i = 0; $i < $count; $i++) {
            $items .= "<CreateDateItem{$i}>2026-01-0" . (($i % 9) + 1) . "</CreateDateItem{$i}>";
        }

        return <<<XML
<?xml version="1.0"?>
<interface-response>
  <GetDomainInfo>
    <domainname domainnameid="179821282">wpgoals.com</domainname>
    <status>
      <registrationstatus>Registered</registrationstatus>
      <purchase-status>Paid</purchase-status>
      <belongs-to party-id="{SECRET-PARTY-ID}"></belongs-to>
    </status>
  </GetDomainInfo>
  <DomainInfo>{$items}</DomainInfo>
  <ErrCount>0</ErrCount>
</interface-response>
XML;
    }
}
