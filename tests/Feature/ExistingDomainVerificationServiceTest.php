<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\DomainProvisioningAttempt;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAttempt;
use App\Services\Domains\Clients\EnomClient;
use App\Services\Domains\ExistingDomainVerificationService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * TLD-3G.1A — Enom Existing Domain Read-Only Verification.
 *
 * Every scenario uses Http::fake() — no real Enom HTTP request is ever made from this file.
 * Provider-eligibility and domain-validation rejections are additionally asserted with
 * Http::assertNothingSent() to prove they happen strictly before any registrar call.
 */
class ExistingDomainVerificationServiceTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    /* ====================== A — happy path ====================== */

    public function test_active_live_enom_provider_and_registered_owned_domain_is_verified(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response(
                $this->fakeGetDomainInfoXml(),
                200,
                ['Content-Type' => 'application/xml']
            ),
        ]);

        $provider = $this->makeProvider('enom', 'live');
        $service = app(ExistingDomainVerificationService::class);

        $result = $service->verify($provider, 'Example.COM.');

        $this->assertTrue($result['verified']);
        $this->assertSame('ok', $result['reason']);
        $this->assertSame('example.com', $result['domain_name']);
        $this->assertSame($provider->id, $result['provider_id']);
        $this->assertSame('enom', $result['provider_type']);
        $this->assertSame('live', $result['provider_mode']);
        $this->assertSame('12345', $result['provider_domain_id']);
        $this->assertSame('registered', $result['registration_status']);
        $this->assertSame('paid', $result['purchase_status']);
        $this->assertSame('98765', $result['belongs_to_party_id']);
        $this->assertNotNull($result['registered_at']);
        $this->assertNotNull($result['expires_at']);

        Http::assertSentCount(1);
        $this->assertSame(0, $this->totalSideEffectRows());
    }

    /* ====================== B/C/D — provider eligibility guard ====================== */

    public function test_inactive_enom_provider_is_rejected_before_any_enom_call(): void
    {
        Http::fake();
        $provider = $this->makeProvider('enom', 'live');
        $provider->update(['is_active' => false]);

        $result = app(ExistingDomainVerificationService::class)->verify($provider, 'example.com');

        $this->assertFalse($result['verified']);
        $this->assertSame('provider_inactive', $result['reason']);
        Http::assertNothingSent();
        $this->assertSame(0, $this->totalSideEffectRows());
    }

    public function test_test_mode_enom_provider_is_rejected_before_any_enom_call(): void
    {
        Http::fake();
        $provider = $this->makeProvider('enom', 'test');

        $result = app(ExistingDomainVerificationService::class)->verify($provider, 'example.com');

        $this->assertFalse($result['verified']);
        $this->assertSame('provider_not_live', $result['reason']);
        Http::assertNothingSent();
        $this->assertSame(0, $this->totalSideEffectRows());
    }

    public function test_active_live_namecheap_provider_is_rejected_before_any_enom_call(): void
    {
        Http::fake();
        $provider = $this->makeProvider('namecheap', 'live');

        $result = app(ExistingDomainVerificationService::class)->verify($provider, 'example.com');

        $this->assertFalse($result['verified']);
        $this->assertSame('provider_not_enom', $result['reason']);
        Http::assertNothingSent();
        $this->assertSame(0, $this->totalSideEffectRows());
    }

    /* ====================== E — invalid domain input ====================== */

    public function test_invalid_domain_input_is_rejected_before_any_enom_call(): void
    {
        Http::fake();
        $provider = $this->makeProvider('enom', 'live');

        foreach ([
            '',
            '   ',
            'not-a-domain',
            'https://example.com/path?x=1',
            'example.com/evil',
            'user@example.com:8080',
            "example.com\r\nSet-Cookie: x=1",
            '-example.com',
        ] as $badDomain) {
            $result = app(ExistingDomainVerificationService::class)->verify($provider, $badDomain);
            $this->assertFalse($result['verified'], "Expected rejection for: {$badDomain}");
            $this->assertSame('invalid_domain', $result['reason'], "Expected invalid_domain for: {$badDomain}");
        }

        Http::assertNothingSent();
        $this->assertSame(0, $this->totalSideEffectRows());
    }

    /* ====================== F — Enom API failure ====================== */

    public function test_enom_api_failure_is_rejected(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response(
                '<?xml version="1.0"?><interface-response><ErrCount>1</ErrCount><errors><Err1>Domain not found</Err1></errors></interface-response>',
                200,
                ['Content-Type' => 'application/xml']
            ),
        ]);

        $provider = $this->makeProvider('enom', 'live');
        $result = app(ExistingDomainVerificationService::class)->verify($provider, 'example.com');

        $this->assertFalse($result['verified']);
        $this->assertSame('enom_api_failure', $result['reason']);
        Http::assertSentCount(1);
        $this->assertSame(0, $this->totalSideEffectRows());
    }

    /* ====================== G — missing belongs_to_party_id ====================== */

    public function test_missing_belongs_to_party_id_is_rejected(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response(
                $this->fakeGetDomainInfoXml(belongsToPartyId: null),
                200,
                ['Content-Type' => 'application/xml']
            ),
        ]);

        $provider = $this->makeProvider('enom', 'live');
        $result = app(ExistingDomainVerificationService::class)->verify($provider, 'example.com');

        $this->assertFalse($result['verified']);
        $this->assertSame('missing_account_membership_evidence', $result['reason']);
        $this->assertSame(0, $this->totalSideEffectRows());
    }

    /* ====================== H — unregistered/ambiguous status ====================== */

    public function test_unregistered_registration_status_is_rejected(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response(
                $this->fakeGetDomainInfoXml(registrationStatus: 'Pending', purchaseStatus: 'Pending'),
                200,
                ['Content-Type' => 'application/xml']
            ),
        ]);

        $provider = $this->makeProvider('enom', 'live');
        $result = app(ExistingDomainVerificationService::class)->verify($provider, 'example.com');

        $this->assertFalse($result['verified']);
        $this->assertSame('registration_not_confirmed', $result['reason']);
        $this->assertSame(0, $this->totalSideEffectRows());
    }

    /* ====================== I — returned domain mismatch ====================== */

    public function test_returned_domain_mismatch_is_rejected(): void
    {
        // The real EnomClient::getDomainInfo() echoes the caller's own normalized input as
        // domain_name, so a genuine mismatch cannot occur organically today. This test proves
        // the service's own comparison guard using a stubbed EnomClient — defense-in-depth
        // against a future EnomClient change, exactly as documented in the service's docblock.
        $stub = new class extends EnomClient {
            public function getDomainInfo(DomainProvider $p, string $fqdn): array
            {
                return [
                    'ok' => true,
                    'reason' => 'ok',
                    'domain_name' => 'not-the-requested-domain.com',
                    'provider_domain_id' => '12345',
                    'registration_status' => 'registered',
                    'purchase_status' => 'paid',
                    'belongs_to_party_id' => '98765',
                    'registered_at' => '2020-01-01',
                    'expires_at' => '2027-12-31',
                ];
            }
        };
        $this->app->instance(EnomClient::class, $stub);

        Http::fake();
        $provider = $this->makeProvider('enom', 'live');
        $result = app(ExistingDomainVerificationService::class)->verify($provider, 'example.com');

        $this->assertFalse($result['verified']);
        $this->assertSame('domain_mismatch', $result['reason']);
        Http::assertNothingSent();
        $this->assertSame(0, $this->totalSideEffectRows());
    }

    /* ====================== J — trustworthy fields returned when supplied ====================== */

    public function test_trustworthy_dates_and_provider_domain_id_are_returned_when_supplied(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response(
                $this->fakeGetDomainInfoXml(
                    providerDomainId: '555444',
                    registeredAt: '01/15/2019',
                    expiresAt: '01/15/2028'
                ),
                200,
                ['Content-Type' => 'application/xml']
            ),
        ]);

        $provider = $this->makeProvider('enom', 'live');
        $result = app(ExistingDomainVerificationService::class)->verify($provider, 'example.com');

        $this->assertTrue($result['verified']);
        $this->assertSame('555444', $result['provider_domain_id']);
        $this->assertSame('01/15/2019', $result['registered_at']);
        $this->assertSame('01/15/2028', $result['expires_at']);
    }

    /* ====================== K/L — zero persistence, no mutating command ====================== */

    public function test_verification_never_sends_a_mutating_enom_command(): void
    {
        Http::fake([
            'reseller.enom.com/*' => Http::response(
                $this->fakeGetDomainInfoXml(),
                200,
                ['Content-Type' => 'application/xml']
            ),
        ]);

        $provider = $this->makeProvider('enom', 'live');
        app(ExistingDomainVerificationService::class)->verify($provider, 'example.com');

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $url = (string) $request->url();
            $this->assertStringContainsString('command=GetDomainInfo', $url);
            foreach (['Purchase', 'Extend', 'ModifyNS', 'RegisterNameServer', 'UpdateNameServer', 'CheckNSStatus'] as $mutating) {
                $this->assertStringNotContainsString('command=' . $mutating, $url);
            }

            return true;
        });

        $this->assertSame(0, $this->totalSideEffectRows());
    }

    /* ====================== Helpers ====================== */

    private function totalSideEffectRows(): int
    {
        return Domain::query()->count()
            + Order::query()->count()
            + Invoice::query()->count()
            + OrderItem::query()->count()
            + PaymentAttempt::query()->count()
            + DomainProvisioningAttempt::query()->count();
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

    private function fakeGetDomainInfoXml(
        ?string $providerDomainId = '12345',
        ?string $registrationStatus = 'Registered',
        ?string $purchaseStatus = 'Paid',
        ?string $belongsToPartyId = '98765',
        ?string $registeredAt = '01/01/2020',
        ?string $expiresAt = '12/31/2027'
    ): string {
        $domainNameAttr = $providerDomainId !== null ? ' domainnameid="' . $providerDomainId . '"' : '';
        $belongsTo = $belongsToPartyId !== null
            ? '<belongs-to party-id="' . $belongsToPartyId . '"/>'
            : '';
        $registrationStatusEl = $registrationStatus !== null
            ? '<registrationstatus>' . $registrationStatus . '</registrationstatus>'
            : '';
        $purchaseStatusEl = $purchaseStatus !== null
            ? '<purchase-status>' . $purchaseStatus . '</purchase-status>'
            : '';
        $expirationEl = $expiresAt !== null ? '<expiration>' . $expiresAt . '</expiration>' : '';
        $registryCreateDateEl = $registeredAt !== null
            ? '<RegistryCreateDate>' . $registeredAt . '</RegistryCreateDate>'
            : '';

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<interface-response>
    <ErrCount>0</ErrCount>
    <GetDomainInfo>
        <domainname{$domainNameAttr}>example.com</domainname>
        <status>
            {$registrationStatusEl}
            {$purchaseStatusEl}
            {$expirationEl}
            {$belongsTo}
        </status>
    </GetDomainInfo>
    {$registryCreateDateEl}
</interface-response>
XML;
    }
}
