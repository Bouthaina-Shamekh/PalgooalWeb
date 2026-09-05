<?php

namespace App\Services\Domains;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * TLD-3G.1C-C — Authoritative RDAP Registration-Date Fallback.
 *
 * Used ONLY when the exact Enom Live provider account (already verified via
 * ExistingDomainVerificationService) does not itself return a trustworthy registration date for
 * a domain being adopted — real evidence (TLD-3G.1C-A/B) proved this genuinely happens for a
 * domain that was not originally registered through the configured reseller account.
 *
 * This service is intentionally the smallest possible read-only building block:
 *  - It NEVER writes to the database.
 *  - It NEVER resolves or changes any DomainProvider/provider identity.
 *  - It NEVER creates a Domain/Order/Invoice/OrderItem/PaymentAttempt/DomainProvisioningAttempt
 *    row.
 *  - It NEVER calls Enom (no EnomClient dependency at all).
 *  - It NEVER calls Purchase/Extend/DNS-mutation of any kind.
 *  - It has no knowledge of clients, orders, invoices, or the adoption transaction itself.
 *
 * It performs at most two outbound read-only HTTPS GET requests per call:
 *   1) the official IANA RDAP bootstrap registry (https://data.iana.org/rdap/dns.json) to
 *      discover the trusted, registry-operated RDAP base URL for the domain's TLD, and
 *   2) that resolved base URL's own "domain/{name}" endpoint.
 * Both requests disable HTTP redirect-following, so a response can never silently redirect this
 * service to an untrusted/arbitrary host — a non-redirect response is required from the exact
 * pinned/derived host or the lookup fails closed.
 *
 * Trust contract: only an RDAP "events" entry whose eventAction is EXACTLY "registration" is
 * ever accepted, and only when the response's own domain identity (ldhName / unicodeName)
 * exactly matches the normalized domain that was queried. Any ambiguity — no registration event,
 * multiple conflicting registration instants, a domain-identity mismatch, a malformed/oversized/
 * non-JSON response, a network failure, or a future-dated "registration" event — fails closed.
 * This service never fabricates, guesses, or falls back to any other date.
 */
class RdapDomainRegistrationDateService
{
    protected const BOOTSTRAP_URL = 'https://data.iana.org/rdap/dns.json';

    /** Defense-in-depth cap against an oversized response body; RDAP JSON is normally tiny. */
    protected const MAX_RESPONSE_BYTES = 262144;

    /**
     * @return array{
     *     ok: bool,
     *     reason: string,
     *     domain_name?: string|null,
     *     registered_at?: string|null,
     * }
     */
    public function resolveRegistrationDate(string $normalizedDomain): array
    {
        $domain = strtolower(trim($normalizedDomain));
        $domain = rtrim($domain, '.');

        if ($domain === '' || !str_contains($domain, '.')) {
            return $this->fail('invalid_domain');
        }

        $tld = strtolower((string) substr($domain, strrpos($domain, '.') + 1));

        if ($tld === '') {
            return $this->fail('invalid_domain');
        }

        try {
            $baseUrl = $this->resolveBaseUrl($tld);

            if ($baseUrl === null) {
                return $this->fail('rdap_endpoint_unresolved');
            }

            $payload = $this->queryRdap($baseUrl, $domain);

            if ($payload === null) {
                return $this->fail('rdap_lookup_failed');
            }

            return $this->extractRegistrationDate($payload, $domain);
        } catch (\Throwable $e) {
            return $this->fail('exception');
        }
    }

    /**
     * Resolves the trusted, registry-operated RDAP base URL for a TLD via the official IANA
     * RDAP bootstrap registry — never an arbitrary/user-controlled host, never a third-party
     * WHOIS/RDAP proxy. Only an HTTPS base URL taken directly from IANA's own bootstrap document
     * is ever used.
     */
    protected function resolveBaseUrl(string $tld): ?string
    {
        $response = Http::withHeaders(['Accept' => 'application/json'])
            ->withOptions(['allow_redirects' => false])
            ->connectTimeout(5)
            ->timeout(10)
            ->get(self::BOOTSTRAP_URL);

        if (!$response->ok()) {
            return null;
        }

        $body = (string) $response->body();

        if (strlen($body) > self::MAX_RESPONSE_BYTES) {
            return null;
        }

        $data = json_decode($body, true);

        if (!is_array($data) || !isset($data['services']) || !is_array($data['services'])) {
            return null;
        }

        foreach ($data['services'] as $service) {
            if (!is_array($service) || count($service) < 2 || !is_array($service[0]) || !is_array($service[1])) {
                continue;
            }

            $tlds = array_map(static fn ($t) => strtolower(trim((string) $t)), $service[0]);

            if (!in_array($tld, $tlds, true)) {
                continue;
            }

            foreach ($service[1] as $candidateUrl) {
                if (is_string($candidateUrl) && str_starts_with($candidateUrl, 'https://')) {
                    return rtrim($candidateUrl, '/') . '/';
                }
            }

            // Matched the TLD but no HTTPS base URL was offered — do not downgrade to HTTP.
            return null;
        }

        return null;
    }

    /**
     * Issues exactly one read-only RDAP domain lookup against the resolved base URL. Redirect
     * following is disabled so this call can never be silently steered to an untrusted host.
     *
     * @return array<string, mixed>|null
     */
    protected function queryRdap(string $baseUrl, string $domain): ?array
    {
        $response = Http::withHeaders(['Accept' => 'application/rdap+json'])
            ->withOptions(['allow_redirects' => false])
            ->connectTimeout(5)
            ->timeout(10)
            ->get($baseUrl . 'domain/' . $domain);

        if (!$response->ok()) {
            return null;
        }

        $body = (string) $response->body();

        if (strlen($body) > self::MAX_RESPONSE_BYTES) {
            return null;
        }

        $data = json_decode($body, true);

        return is_array($data) ? $data : null;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{ok: bool, reason: string, domain_name?: string|null, registered_at?: string|null}
     */
    protected function extractRegistrationDate(array $payload, string $domain): array
    {
        if (!$this->domainIdentityMatches($payload, $domain)) {
            return $this->fail('rdap_domain_mismatch');
        }

        $events = $payload['events'] ?? null;

        if (!is_array($events)) {
            return $this->fail('rdap_no_registration_event');
        }

        $instants = [];

        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }

            $action = (string) ($event['eventAction'] ?? '');

            if ($action !== 'registration') {
                continue;
            }

            $rawDate = $event['eventDate'] ?? null;

            if (!is_string($rawDate) || trim($rawDate) === '') {
                return $this->fail('rdap_malformed_event_date');
            }

            try {
                $parsed = Carbon::parse($rawDate)->utc();
            } catch (\Throwable $e) {
                return $this->fail('rdap_malformed_event_date');
            }

            $instants[$parsed->getTimestamp()] = $parsed;
        }

        if (count($instants) === 0) {
            return $this->fail('rdap_no_registration_event');
        }

        if (count($instants) > 1) {
            return $this->fail('rdap_conflicting_registration_events');
        }

        /** @var Carbon $registeredAt */
        $registeredAt = array_values($instants)[0];

        if ($registeredAt->greaterThan(Carbon::now('UTC'))) {
            return $this->fail('rdap_future_registration_date');
        }

        return [
            'ok' => true,
            'reason' => 'ok',
            'domain_name' => $domain,
            'registered_at' => $registeredAt->toIso8601String(),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function domainIdentityMatches(array $payload, string $domain): bool
    {
        $ldhName = $this->normalizedIdentity($payload['ldhName'] ?? null);

        if ($ldhName !== null && $ldhName === $domain) {
            return true;
        }

        $unicodeName = $this->normalizedIdentity($payload['unicodeName'] ?? null);

        if ($unicodeName !== null && $unicodeName === $domain) {
            return true;
        }

        if ($unicodeName !== null && function_exists('idn_to_ascii')) {
            $ascii = @idn_to_ascii($unicodeName, \IDNA_DEFAULT, \INTL_IDNA_VARIANT_UTS46);

            if (is_string($ascii) && strtolower($ascii) === $domain) {
                return true;
            }
        }

        return false;
    }

    protected function normalizedIdentity(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return strtolower(rtrim(trim($value), '.'));
    }

    protected function fail(string $reason): array
    {
        return [
            'ok' => false,
            'reason' => $reason,
            'domain_name' => null,
            'registered_at' => null,
        ];
    }
}
