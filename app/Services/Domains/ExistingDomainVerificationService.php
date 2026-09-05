<?php

namespace App\Services\Domains;

use App\Models\DomainProvider;
use App\Services\Domains\Clients\EnomClient;

/**
 * TLD-3G.1A — Enom Existing Domain Read-Only Verification.
 *
 * The reusable server-side layer that proves an already-existing domain genuinely belongs to
 * the exact configured Enom Live provider account, WITHOUT importing/creating/updating any
 * Domain row and WITHOUT registering or renewing anything. This service is intentionally the
 * smallest possible read-only building block for a future "Adopt Existing Domain" flow
 * (TLD-3G.1B) — it has no persistence, no route, no console command, and no knowledge of
 * clients/orders/invoices.
 *
 * Provider eligibility (checked BEFORE any Enom call, so an ineligible provider never causes an
 * HTTP request): the exact DomainProvider instance passed in must be is_active=true,
 * mode='live', and type='enom'. There is no ofType()->first(), no default-provider resolution,
 * no same-type fallback, and no registrar-string resolution anywhere in this class — the exact
 * provider row the caller supplies is the only provider ever used.
 *
 * Domain verification uses ONLY EnomClient::getDomainInfo() (the "GetDomainInfo" read-only
 * command) — never purchaseDomain(), renewDomain(), updateNameservers(), registerNameserver(),
 * updateNameserverIp(), or any other mutating Enom command.
 *
 * "Verified" reuses, unchanged, the strictest ownership tier already proven by
 * DomainProvisioningReconciliationService::inspectEnom() — belongs_to_party_id present AND
 * registration_status==='registered' AND purchase_status==='paid'. This phase does not loosen
 * that bar; see the TLD-3G.1A implementation report for the explicit note on why it was reused
 * as-is rather than relaxed.
 */
class ExistingDomainVerificationService
{
    public function __construct(protected EnomClient $enomClient)
    {
    }

    /**
     * @return array{
     *     verified: bool,
     *     reason: string,
     *     message: string,
     *     domain_name?: string,
     *     provider_id?: int,
     *     provider_type?: string,
     *     provider_mode?: string,
     *     provider_domain_id?: string|null,
     *     registration_status?: string,
     *     purchase_status?: string,
     *     belongs_to_party_id?: string,
     *     registered_at?: string|null,
     *     expires_at?: string|null,
     * }
     */
    public function verify(DomainProvider $provider, string $domain): array
    {
        // 1) Exact provider eligibility guard — evaluated before any normalization or Enom call.
        if (!$provider->is_active) {
            return $this->fail('provider_inactive', 'The selected provider is not active.');
        }

        if (strtolower((string) $provider->mode) !== 'live') {
            return $this->fail('provider_not_live', 'The selected provider is not in live mode.');
        }

        if (strtolower((string) $provider->type) !== 'enom') {
            return $this->fail('provider_not_enom', 'The selected provider is not an eNom provider.');
        }

        // 2) Domain normalization/validation — before any Enom call. Reuses the project's
        // existing normalization policy (Admin\Management\DomainController::normalizeDomain():
        // lowercase, trim, strip a trailing dot, IDN-to-ASCII if available — no new IDN policy
        // is introduced here) plus the project's existing label/TLD shape checks
        // (Admin\Management\DomainSearchController::isValidLabel()/isValidTld()) so that
        // URL/path/query/userinfo input can never reach the registrar as a "domain".
        $normalized = $this->normalizeAndValidateDomain($domain);

        if ($normalized === null) {
            return $this->fail('invalid_domain', 'The domain name is missing or not a valid domain.');
        }

        // 3) Read-only Enom verification — GetDomainInfo only.
        $info = $this->enomClient->getDomainInfo($provider, $normalized);

        if (!($info['ok'] ?? false)) {
            return $this->fail(
                'enom_api_failure',
                (string) ($info['message'] ?? 'eNom did not confirm this domain.')
            );
        }

        // 4) The returned domain must correspond to the requested normalized domain. Under the
        // current EnomClient::getDomainInfo() implementation this field is populated from the
        // caller's own input (not independently re-parsed from the registrar's XML), so this
        // comparison is defense-in-depth against a future EnomClient change or a caller that
        // bypasses normalizeAndValidateDomain() — it is still enforced and tested.
        $returnedDomain = strtolower(trim((string) ($info['domain_name'] ?? '')));

        if ($returnedDomain === '' || $returnedDomain !== $normalized) {
            return $this->fail('domain_mismatch', 'The registrar response does not correspond to the requested domain.');
        }

        // 5) Account-membership evidence — belongs_to_party_id must be present and non-empty.
        $belongsToPartyId = trim((string) ($info['belongs_to_party_id'] ?? ''));

        if ($belongsToPartyId === '') {
            return $this->fail(
                'missing_account_membership_evidence',
                'eNom did not confirm that this domain belongs to the configured reseller account.'
            );
        }

        // 6) Registered-state requirement — reuses, unmodified, the strictest ownership tier
        // already proven safe by DomainProvisioningReconciliationService::inspectEnom(): both
        // registration_status === 'registered' AND purchase_status === 'paid'. A domain that is
        // merely "seen" in the account (DomainProvisioningReconciliationService's
        // STATUS_PROVIDER_PROCESSING tier) is NOT sufficient evidence for adoption — this phase
        // deliberately does not relax that bar.
        $registrationStatus = strtolower(trim((string) ($info['registration_status'] ?? '')));
        $purchaseStatus = strtolower(trim((string) ($info['purchase_status'] ?? '')));

        if ($registrationStatus !== 'registered' || $purchaseStatus !== 'paid') {
            return $this->fail(
                'registration_not_confirmed',
                'eNom did not conclusively confirm this domain as registered and paid in the configured account.'
            );
        }

        return [
            'verified' => true,
            'reason' => 'ok',
            'message' => 'eNom confirms this domain is registered and paid in the configured account.',
            'domain_name' => $normalized,
            'provider_id' => (int) $provider->getKey(),
            'provider_type' => strtolower((string) $provider->type),
            'provider_mode' => strtolower((string) $provider->mode),
            'provider_domain_id' => $this->nullableString($info['provider_domain_id'] ?? null),
            'registration_status' => $registrationStatus,
            'purchase_status' => $purchaseStatus,
            'belongs_to_party_id' => $belongsToPartyId,
            'registered_at' => $this->nullableString($info['registered_at'] ?? null),
            'expires_at' => $this->nullableString($info['expires_at'] ?? null),
        ];
    }

    protected function fail(string $reason, string $message): array
    {
        return [
            'verified' => false,
            'reason' => $reason,
            'message' => $message,
        ];
    }

    /**
     * Normalizes then validates a domain name, returning null for anything that is not
     * safely and unambiguously a bare domain name — never a URL, path, query string, or
     * anything containing whitespace/userinfo/port syntax. Mirrors the project's existing
     * normalization (lowercase/trim/strip trailing dot/IDN-to-ASCII when available) and its
     * existing label/TLD validation shape rather than inventing a new policy.
     */
    protected function normalizeAndValidateDomain(string $domain): ?string
    {
        $domain = strtolower(trim($domain));
        $domain = rtrim($domain, '.');

        if ($domain === '') {
            return null;
        }

        // Reject anything URL/path/query/userinfo/port-shaped before it ever reaches IDN
        // conversion or the registrar — a bare domain name never contains any of these.
        if (preg_match('/[\s\/\?\#\@\:\\\\]/', $domain) === 1) {
            return null;
        }

        if (function_exists('idn_to_ascii')) {
            $ascii = @idn_to_ascii($domain, \IDNA_DEFAULT, \INTL_IDNA_VARIANT_UTS46);
            if ($ascii) {
                $domain = strtolower($ascii);
            }
        }

        if (!str_contains($domain, '.')) {
            return null;
        }

        [$sld, $tld] = explode('.', $domain, 2);

        if ($sld === '' || $tld === '') {
            return null;
        }

        if (preg_match('/^(?!-)[a-z0-9-]{1,63}(?<!-)$/', $sld) !== 1) {
            return null;
        }

        if (preg_match('/^(?:[a-z]{2,63}|[a-z0-9.-]{2,63})$/', $tld) !== 1) {
            return null;
        }

        return $domain;
    }

    protected function nullableString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
