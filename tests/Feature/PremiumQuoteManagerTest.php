<?php

namespace Tests\Feature;

use App\Models\DomainProvider;
use App\Models\DomainTld;
use App\Services\Domains\Exceptions\InvalidPremiumQuoteException;
use App\Services\Domains\PremiumQuoteManager;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PremiumQuoteManagerTest extends TestCase
{
    use DatabaseMigrations;

    private const SESSION_KEY = 'palgoals_premium_quotes';

    private PremiumQuoteManager $manager;

    private DomainProvider $provider;

    private DomainTld $tld;

    protected function setUp(): void
    {
        parent::setUp();
        $this->startSession();
        $this->provider = $this->makeProvider();
        $this->tld = $this->makeTld($this->provider);
        $this->manager = app(PremiumQuoteManager::class);
        CarbonImmutable::setTestNow('2026-09-13 12:00:00 UTC');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    public function test_issue_stores_a_complete_authoritative_quote_with_fixed_ttl(): void
    {
        $token = $this->manager->issue($this->quote(domain: '  Premium.Example.COM.  '));
        $stored = session(self::SESSION_KEY.'.'.$token);

        $this->assertTrue(Str::isUuid($token));
        $this->assertSame([
            'domain', 'is_premium', 'provider_id', 'provider_type', 'provider_mode',
            'domain_tld_id', 'price', 'price_cents', 'currency', 'years',
            'fetched_at', 'expires_at', 'quote_token', 'source',
        ], array_keys($stored));
        $this->assertSame('premium.example.com', $stored['domain']);
        $this->assertTrue($stored['is_premium']);
        $this->assertSame($token, $stored['quote_token']);
        $this->assertSame('namecheap_live', $stored['source']);
        $this->assertSame(12.34, $stored['price']);
        $this->assertSame(1234, $stored['price_cents']);
        $this->assertSame(300, (int) CarbonImmutable::parse($stored['fetched_at'])->diffInSeconds(CarbonImmutable::parse($stored['expires_at']), false));
    }

    public function test_valid_quote_accepts_normalized_matching_domain(): void
    {
        $token = $this->manager->issue($this->quote(domain: 'Premium.Example.COM.'));

        $result = $this->manager->validate($token, ' premium.example.com ');

        $this->assertSame('premium.example.com', $result['domain']);
        $this->assertSame($this->provider->id, $result['provider_id']);
    }

    #[DataProvider('invalidLookupCases')]
    public function test_missing_forged_or_mismatched_lookup_fails(string $case): void
    {
        $token = $this->manager->issue($this->quote());

        $this->expectException(InvalidPremiumQuoteException::class);
        match ($case) {
            'missing' => $this->manager->validate('', 'premium.example.com'),
            'forged' => $this->manager->validate((string) Str::uuid(), 'premium.example.com'),
            'domain mismatch' => $this->manager->validate($token, 'other.example.com'),
        };
    }

    public static function invalidLookupCases(): array
    {
        return ['missing' => ['missing'], 'forged' => ['forged'], 'domain mismatch' => ['domain mismatch']];
    }

    public function test_expired_quote_fails_without_sliding_expiration(): void
    {
        $token = $this->manager->issue($this->quote());
        $expiresAt = session(self::SESSION_KEY.'.'.$token.'.expires_at');
        CarbonImmutable::setTestNow(CarbonImmutable::parse($expiresAt));

        try {
            $this->manager->validate($token, 'premium.example.com');
            $this->fail('Expired quote must fail.');
        } catch (InvalidPremiumQuoteException) {
            $this->assertSame($expiresAt, session(self::SESSION_KEY.'.'.$token.'.expires_at'));
        }
    }

    public function test_malformed_session_quote_fails(): void
    {
        $token = (string) Str::uuid();
        session()->put(self::SESSION_KEY.'.'.$token, ['domain' => 'premium.example.com']);

        $this->expectException(InvalidPremiumQuoteException::class);
        $this->manager->validate($token, 'premium.example.com');
    }

    #[DataProvider('providerFailureCases')]
    public function test_current_provider_trust_failures_are_rejected(string $case): void
    {
        $token = $this->manager->issue($this->quote());

        match ($case) {
            'missing' => $this->provider->delete(),
            'inactive' => $this->provider->update(['is_active' => false]),
            'type' => $this->provider->update(['type' => 'enom']),
            'mode' => $this->provider->update(['mode' => 'test']),
        };

        $this->expectException(InvalidPremiumQuoteException::class);
        $this->manager->validate($token, 'premium.example.com');
    }

    public static function providerFailureCases(): array
    {
        return [
            'missing provider' => ['missing'],
            'inactive provider' => ['inactive'],
            'provider type changed' => ['type'],
            'provider changed to test mode' => ['mode'],
        ];
    }

    public function test_stored_provider_type_or_mode_mismatch_fails(): void
    {
        foreach ([['provider_type', 'enom'], ['provider_mode', 'test']] as [$key, $value]) {
            $token = $this->manager->issue($this->quote());
            $quote = session(self::SESSION_KEY.'.'.$token);
            $quote[$key] = $value;
            session()->put(self::SESSION_KEY.'.'.$token, $quote);
            try {
                $this->manager->validate($token, 'premium.example.com');
                $this->fail("Tampered {$key} must fail.");
            } catch (InvalidPremiumQuoteException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_current_tld_provider_identity_mismatch_fails(): void
    {
        $token = $this->manager->issue($this->quote());
        $other = $this->makeProvider();
        $this->tld->update(['provider_id' => $other->id]);

        $this->expectException(InvalidPremiumQuoteException::class);
        $this->manager->validate($token, 'premium.example.com');
    }

    #[DataProvider('invalidIssueProviderCases')]
    public function test_issue_rejects_an_untrusted_provider_state(string $case): void
    {
        $quote = $this->quote();
        match ($case) {
            'missing' => $quote['provider_id'] = 999999,
            'inactive' => $this->provider->update(['is_active' => false]),
            'type mismatch' => $quote['provider_type'] = 'enom',
            'mode mismatch' => $quote['provider_mode'] = 'test',
            'premium unsupported' => $this->tld->update(['supports_premium' => false]),
        };

        $this->expectException(InvalidPremiumQuoteException::class);
        $this->manager->issue($quote);
    }

    public static function invalidIssueProviderCases(): array
    {
        return [
            'missing provider' => ['missing'],
            'inactive provider' => ['inactive'],
            'provider type mismatch' => ['type mismatch'],
            'provider mode mismatch' => ['mode mismatch'],
            'TLD does not support premium' => ['premium unsupported'],
        ];
    }

    #[DataProvider('invalidPrices')]
    public function test_invalid_or_inconsistent_price_cannot_be_issued(mixed $price, mixed $priceCents): void
    {
        $this->expectException(InvalidPremiumQuoteException::class);
        $this->manager->issue($this->quote(price: $price, priceCents: $priceCents));
    }

    public static function invalidPrices(): array
    {
        return [
            'zero' => [0, 0],
            'negative' => [-1, -100],
            'malformed' => ['twelve', 1200],
            'cents mismatch' => [12.34, 1235],
        ];
    }

    public function test_missing_quote_never_falls_back_to_catalog_pricing(): void
    {
        $this->tld->prices()->create(['action' => 'register', 'years' => 1, 'sale' => 9.99, 'cost' => 5.00]);

        $this->expectException(InvalidPremiumQuoteException::class);
        $this->manager->validate((string) Str::uuid(), 'premium.example.com');
    }

    public function test_multiple_quotes_coexist_and_validation_does_not_mutate_or_extend_them(): void
    {
        $first = $this->manager->issue($this->quote(domain: 'first.example.com'));
        CarbonImmutable::setTestNow(CarbonImmutable::now()->addSecond());
        $second = $this->manager->issue($this->quote(domain: 'second.example.com'));
        $before = session(self::SESSION_KEY);

        $this->manager->validate($first, 'first.example.com');
        $this->manager->validate($first, 'first.example.com');

        $this->assertCount(2, session(self::SESSION_KEY));
        $this->assertSame($before, session(self::SESSION_KEY));
        $this->assertNotSame($first, $second);
    }

    #[DataProvider('tamperedQuoteFields')]
    public function test_tampered_authoritative_metadata_fails(string $key, mixed $value): void
    {
        $token = $this->manager->issue($this->quote());
        $quote = session(self::SESSION_KEY.'.'.$token);
        $quote[$key] = $value;
        session()->put(self::SESSION_KEY.'.'.$token, $quote);

        $this->expectException(InvalidPremiumQuoteException::class);
        $this->manager->validate($token, 'premium.example.com');
    }

    public static function tamperedQuoteFields(): array
    {
        return [
            'domain' => ['domain', 'other.example.com'],
            'premium flag' => ['is_premium', false],
            'price' => ['price', 99.99],
            'provider id' => ['provider_id', 999999],
            'tld id' => ['domain_tld_id', 999999],
            'currency' => ['currency', 'EUR'],
            'years' => ['years', 2],
            'fetched timestamp' => ['fetched_at', '2026-09-13T11:59:00+00:00'],
            'expiry' => ['expires_at', '2026-09-13T12:10:00+00:00'],
            'token' => ['quote_token', 'forged'],
            'source' => ['source', 'browser'],
        ];
    }

    private function quote(
        string $domain = 'premium.example.com',
        mixed $price = 12.34,
        mixed $priceCents = 1234,
    ): array {
        return [
            'domain' => $domain,
            'is_premium' => true,
            'provider_id' => $this->provider->id,
            'provider_type' => 'namecheap',
            'provider_mode' => 'live',
            'domain_tld_id' => $this->tld->id,
            'price' => $price,
            'price_cents' => $priceCents,
            'currency' => 'USD',
            'years' => 1,
        ];
    }

    private function makeProvider(): DomainProvider
    {
        return DomainProvider::query()->create([
            'name' => 'Namecheap '.uniqid(),
            'type' => 'namecheap',
            'mode' => 'live',
            'endpoint' => 'https://api.namecheap.test',
            'username' => 'test-user',
            'password' => 'test-password',
            'api_key' => 'test-key',
            'client_ip' => '127.0.0.1',
            'is_active' => true,
        ]);
    }

    private function makeTld(DomainProvider $provider): DomainTld
    {
        return DomainTld::query()->create([
            'provider_id' => $provider->id,
            'provider' => 'namecheap',
            'tld' => 'com',
            'currency' => 'USD',
            'enabled' => true,
            'supports_premium' => true,
            'in_catalog' => true,
        ]);
    }
}
