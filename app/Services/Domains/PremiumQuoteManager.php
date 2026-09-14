<?php

namespace App\Services\Domains;

use App\Models\DomainProvider;
use App\Models\DomainTld;
use App\Services\Domains\Exceptions\InvalidPremiumQuoteException;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Str;

final class PremiumQuoteManager
{
    private const SESSION_KEY = 'palgoals_premium_quotes';

    private const TTL_SECONDS = 300;

    private const PROVIDER_TYPE = 'namecheap';

    private const SOURCE = 'namecheap_live';

    private const QUOTE_KEYS = [
        'domain',
        'is_premium',
        'provider_id',
        'provider_type',
        'provider_mode',
        'domain_tld_id',
        'price',
        'price_cents',
        'currency',
        'years',
        'fetched_at',
        'expires_at',
        'quote_token',
        'source',
    ];

    public function __construct(private readonly Session $session) {}

    public function issue(array $trustedQuote): string
    {
        $now = CarbonImmutable::now();
        $token = (string) Str::uuid();
        $quote = [
            'domain' => $this->normalizeDomain($this->requiredString($trustedQuote, 'domain')),
            'is_premium' => $trustedQuote['is_premium'] ?? null,
            'provider_id' => $this->positiveInteger($trustedQuote['provider_id'] ?? null),
            'provider_type' => strtolower(trim($this->requiredString($trustedQuote, 'provider_type'))),
            'provider_mode' => strtolower(trim($this->requiredString($trustedQuote, 'provider_mode'))),
            'domain_tld_id' => $this->positiveInteger($trustedQuote['domain_tld_id'] ?? null),
            'price' => $this->positivePrice($trustedQuote['price'] ?? null),
            'price_cents' => $this->positiveInteger($trustedQuote['price_cents'] ?? null),
            'currency' => strtoupper(trim($this->requiredString($trustedQuote, 'currency'))),
            'years' => $this->positiveInteger($trustedQuote['years'] ?? null),
            'fetched_at' => $now->toIso8601String(),
            'expires_at' => $now->addSeconds(self::TTL_SECONDS)->toIso8601String(),
            'quote_token' => $token,
            'source' => self::SOURCE,
        ];

        $this->assertTrustedQuote($quote, $token, $quote['domain'], $now);
        $this->session->put(self::SESSION_KEY.'.'.$token, $quote);

        return $token;
    }

    public function validate(string $token, string $domain): array
    {
        $token = trim($token);
        if ($token === '' || ! Str::isUuid($token)) {
            throw new InvalidPremiumQuoteException('The premium quote is missing or invalid.');
        }

        $quote = $this->session->get(self::SESSION_KEY.'.'.$token);
        if (! is_array($quote)) {
            throw new InvalidPremiumQuoteException('The premium quote is missing or invalid.');
        }

        $normalizedDomain = $this->normalizeDomain($domain);
        $this->assertTrustedQuote($quote, $token, $normalizedDomain, CarbonImmutable::now());

        return $quote;
    }

    private function assertTrustedQuote(array $quote, string $token, string $domain, CarbonImmutable $now): void
    {
        if (array_keys($quote) !== self::QUOTE_KEYS) {
            $this->fail();
        }

        if (! Str::isUuid($token) || ($quote['quote_token'] ?? null) !== $token) {
            $this->fail();
        }

        $storedDomain = $this->normalizeDomainValue($quote['domain'] ?? null);
        if ($storedDomain === null || $storedDomain !== $domain || $storedDomain !== ($quote['domain'] ?? null)) {
            $this->fail();
        }

        if (($quote['is_premium'] ?? null) !== true || ($quote['years'] ?? null) !== 1) {
            $this->fail();
        }

        if (($quote['source'] ?? null) !== self::SOURCE) {
            $this->fail();
        }

        $providerId = $this->strictPositiveInteger($quote['provider_id'] ?? null);
        $domainTldId = $this->strictPositiveInteger($quote['domain_tld_id'] ?? null);
        $priceCents = $this->strictPositiveInteger($quote['price_cents'] ?? null);
        $price = $this->strictPositivePrice($quote['price'] ?? null);
        $providerType = $this->strictNormalizedString($quote['provider_type'] ?? null);
        $providerMode = $this->strictNormalizedString($quote['provider_mode'] ?? null);
        $currency = $quote['currency'] ?? null;

        if ($providerId === null || $domainTldId === null || $priceCents === null || $price === null
            || $providerType !== self::PROVIDER_TYPE || $providerMode !== 'live'
            || ! is_string($currency) || ! preg_match('/^[A-Z]{3}$/', $currency)
            || (int) round($price * 100) !== $priceCents) {
            $this->fail();
        }

        $provider = DomainProvider::query()->whereKey($providerId)->first();
        if (! $provider instanceof DomainProvider || ! $provider->is_active
            || strtolower(trim((string) $provider->type)) !== $providerType
            || strtolower(trim((string) $provider->mode)) !== $providerMode) {
            $this->fail();
        }

        $tld = $this->extractTld($storedDomain);
        $domainTld = DomainTld::query()->whereKey($domainTldId)->first();
        if (! $domainTld instanceof DomainTld || ! $domainTld->enabled || ! $domainTld->supports_premium
            || (int) $domainTld->provider_id !== $providerId
            || strtolower(trim((string) $domainTld->provider)) !== $providerType
            || strtolower(trim((string) $domainTld->tld)) !== $tld
            || strtoupper(trim((string) $domainTld->currency)) !== $currency) {
            $this->fail();
        }

        try {
            $fetchedAt = CarbonImmutable::parse($quote['fetched_at'] ?? null);
            $expiresAt = CarbonImmutable::parse($quote['expires_at'] ?? null);
        } catch (\Throwable) {
            $this->fail();
        }

        if (! is_string($quote['fetched_at'] ?? null) || ! is_string($quote['expires_at'] ?? null)
            || $fetchedAt->toIso8601String() !== $quote['fetched_at']
            || $expiresAt->toIso8601String() !== $quote['expires_at']
            || (int) $fetchedAt->diffInSeconds($expiresAt, false) !== self::TTL_SECONDS
            || $fetchedAt->isFuture() || $now->greaterThanOrEqualTo($expiresAt)) {
            $this->fail();
        }
    }

    private function normalizeDomain(string $domain): string
    {
        $normalized = $this->normalizeDomainValue($domain);
        if ($normalized === null || ! str_contains($normalized, '.')) {
            $this->fail();
        }

        return $normalized;
    }

    private function normalizeDomainValue(mixed $domain): ?string
    {
        if (! is_string($domain)) {
            return null;
        }

        $domain = strtolower(rtrim(trim($domain), '.'));
        if (function_exists('idn_to_ascii')) {
            $ascii = @idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($ascii) {
                $domain = strtolower($ascii);
            }
        }

        return $domain !== '' ? $domain : null;
    }

    private function extractTld(string $domain): string
    {
        return ltrim(strtolower(trim(pathinfo($domain, PATHINFO_EXTENSION))), '.');
    }

    private function requiredString(array $quote, string $key): string
    {
        $value = $quote[$key] ?? null;
        if (! is_string($value) || trim($value) === '') {
            $this->fail();
        }

        return $value;
    }

    private function positiveInteger(mixed $value): int
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($integer === false) {
            $this->fail();
        }

        return $integer;
    }

    private function positivePrice(mixed $value): float
    {
        if (! is_int($value) && ! is_float($value) && ! is_string($value)) {
            $this->fail();
        }
        if (! is_numeric($value)) {
            $this->fail();
        }

        $price = (float) $value;
        if (! is_finite($price) || $price <= 0) {
            $this->fail();
        }

        return $price;
    }

    private function strictPositiveInteger(mixed $value): ?int
    {
        return is_int($value) && $value > 0 ? $value : null;
    }

    private function strictPositivePrice(mixed $value): ?float
    {
        return is_float($value) && is_finite($value) && $value > 0 ? $value : null;
    }

    private function strictNormalizedString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' && $value === strtolower(trim($value)) ? $value : null;
    }

    private function fail(): never
    {
        throw new InvalidPremiumQuoteException('The premium quote is missing, expired, or no longer trusted.');
    }
}
