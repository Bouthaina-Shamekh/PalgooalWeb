<?php

namespace App\Services\Domains;

use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\DomainProvisioningAttempt;
use App\Models\OrderItem;
use App\Services\Domains\Clients\EnomClient;
use App\Services\Domains\Clients\NamecheapClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DomainProvisioningReconciliationService
{
    public const STATUS_REGISTERED_BY_US = 'registered_by_us';
    public const STATUS_PROVIDER_PROCESSING = 'provider_processing';
    public const STATUS_EXTERNAL_UNAVAILABLE = 'external_unavailable';
    public const STATUS_LIKELY_NOT_SENT = 'likely_not_sent';
    public const STATUS_INDETERMINATE = 'indeterminate';

    // TLD-3H.2B — Renewal reconciliation outcomes. Deliberately distinct from the register-only
    // STATUS_REGISTERED_BY_US/STATUS_PROVIDER_PROCESSING/STATUS_LIKELY_NOT_SENT/
    // STATUS_EXTERNAL_UNAVAILABLE constants above: a renewal reconciliation is never asking
    // "does this domain exist in our account" (already true before the attempt), only "did the
    // registrar's own expiry advance far enough to prove this specific renewal was applied."
    // STATUS_INDETERMINATE is intentionally reused unchanged — "we do not know, do not act" has
    // the exact same meaning for both operations.
    public const RENEWAL_STATUS_CONFIRMED = 'renewal_confirmed_by_provider';
    public const RENEWAL_STATUS_NOT_CONFIRMED = 'renewal_not_confirmed';

    /**
     * Inspect one durable registration attempt and optionally apply a conclusive result.
     * The provider lookup always happens before the short database transaction used by apply.
     */
    public function reconcileAttempt(DomainProvisioningAttempt $attempt, bool $apply = false): array
    {
        $attempt->loadMissing(['orderItem', 'domain', 'provider']);

        $precondition = $this->validateAttempt($attempt);
        if ($precondition !== null) {
            return $precondition;
        }

        $domainName = strtolower(trim((string) ($attempt->domain?->domain_name ?: $attempt->orderItem?->domain)));
        $result = $this->inspectProvider($attempt, $attempt->provider, $domainName);
        $result = $this->normalizeResult($result);

        if ($result['status'] !== self::STATUS_REGISTERED_BY_US) {
            return array_merge($result, [
                'applied' => false,
                'action' => 'no_change',
            ]);
        }

        if (!$apply) {
            return array_merge($result, [
                'applied' => false,
                'action' => 'would_complete',
            ]);
        }

        $applied = $this->applyRegisteredByUs($attempt, $result);

        return array_merge($result, [
            'applied' => $applied,
            'action' => $applied ? 'completed' : 'stale_no_change',
        ]);
    }

    /**
     * TLD-3H.2B — Safe, read-only Enom renewal reconciliation. Inspects one durable renewal
     * DomainProvisioningAttempt (operation=renew, status INITIATED or INDETERMINATE) via
     * EnomClient::getDomainInfo() ONLY — never Extend/Purchase, never DNS, never a second
     * attempt, never an invoice/order state change. Classifies into exactly three outcomes:
     * RENEWAL_STATUS_CONFIRMED (trustworthy evidence the registrar's expiry reached the
     * expected post-renewal date — only then may $apply finalize the attempt/item/domain),
     * RENEWAL_STATUS_NOT_CONFIRMED (the provider responded but does not prove renewal — this
     * must NEVER be treated as a definitive failure and must NEVER trigger an automatic retry;
     * this service has no code path back to renewDomainWithProvider()/Extend at all), or
     * STATUS_INDETERMINATE (API/transport/parse failure, or an unusable local baseline — leaves
     * everything untouched and recoverable, exactly like register's own INDETERMINATE bucket).
     *
     * The durable PRE-EXTEND expiry baseline this reconciliation relies on is
     * OrderItem.meta['renewal_date'] (the expected target expiry) and, transitively,
     * meta['current_renewal_date'] — both snapshotted once by
     * DomainRenewalService::prepareRenewalCheckout() at invoice-creation time, strictly before
     * any Extend attempt could exist, and never mutated afterward by any renewal-provisioning
     * code path. Domain.renewal_date itself is never used as the baseline (it could have
     * changed independently of this specific attempt) — only as the value written back AFTER a
     * CONFIRMED result, and even then only with the registrar's own returned expiry, not a
     * locally computed one.
     */
    public function reconcileRenewalAttempt(DomainProvisioningAttempt $attempt, bool $apply = false): array
    {
        $attempt->loadMissing(['orderItem', 'domain', 'provider']);

        $precondition = $this->validateRenewalAttempt($attempt);
        if ($precondition !== null) {
            return $precondition;
        }

        $domain = $attempt->domain;
        $meta = is_array($attempt->orderItem->meta) ? $attempt->orderItem->meta : [];
        $expectedRenewalDate = $this->normalizeDate($meta['renewal_date'] ?? null);

        if ($expectedRenewalDate === null) {
            return array_merge($this->result(
                self::STATUS_INDETERMINATE,
                message: 'The durable pre-renewal expiry baseline for this order item is missing or unusable.',
                safePayload: ['reason' => 'renewal_baseline_missing']
            ), [
                'applied' => false,
                'action' => 'skipped',
            ]);
        }

        $domainName = strtolower(trim((string) $domain->domain_name));
        $result = $this->inspectRenewalProvider($attempt, $attempt->provider, $domainName, $expectedRenewalDate);
        $result = $this->normalizeRenewalResult($result);

        if ($result['status'] !== self::RENEWAL_STATUS_CONFIRMED) {
            return array_merge($result, [
                'applied' => false,
                'action' => 'no_change',
            ]);
        }

        if (!$apply) {
            return array_merge($result, [
                'applied' => false,
                'action' => 'would_complete',
            ]);
        }

        $applied = $this->applyRenewalConfirmed($attempt, $result);

        return array_merge($result, [
            'applied' => $applied,
            'action' => $applied ? 'completed' : 'stale_no_change',
        ]);
    }

    /**
     * TLD-3H.2B — Section 7 identity re-validation: reuses 3H.2A's own exact, already-proven
     * no-fallback rule (RegistrarProvisioningService::trustedRenewalProvider()) rather than
     * inventing an equivalent check here, then additionally cross-checks the resolved provider
     * against THIS attempt's own frozen provider_id/type/mode snapshot. Any failure returns
     * before any HTTP call is ever made.
     */
    protected function validateRenewalAttempt(DomainProvisioningAttempt $attempt): ?array
    {
        $item = $attempt->orderItem;
        $provider = $attempt->provider;
        $domain = $attempt->domain;
        $allowedAttemptStatuses = [
            DomainProvisioningAttempt::STATUS_INITIATED,
            DomainProvisioningAttempt::STATUS_INDETERMINATE,
        ];

        $structurallyValid = in_array($attempt->status, $allowedAttemptStatuses, true)
            && $attempt->operation === DomainProvisioningAttempt::OPERATION_RENEW
            && $item instanceof OrderItem
            && $item->provisioning_status === OrderItem::PROVISIONING_IN_PROGRESS
            && strtolower((string) $item->item_option) === DomainProvisioningAttempt::OPERATION_RENEW
            && $provider instanceof DomainProvider
            && strtolower((string) $provider->type) === strtolower((string) $attempt->provider_type)
            && strtolower((string) $provider->mode) === strtolower((string) $attempt->provider_mode)
            && $domain instanceof Domain
            && trim((string) $domain->domain_name) !== '';

        if (!$structurallyValid) {
            return array_merge($this->result(
                self::STATUS_INDETERMINATE,
                message: 'The attempt is not eligible for renewal reconciliation.',
                safePayload: ['reason' => 'precondition_failed']
            ), [
                'applied' => false,
                'action' => 'skipped',
            ]);
        }

        $meta = is_array($item->meta) ? $item->meta : [];
        $providerResolution = app(RegistrarProvisioningService::class)->trustedRenewalProvider($domain, $meta);

        $identityOk = ($providerResolution['ok'] ?? false)
            && $providerResolution['provider'] instanceof DomainProvider
            && (int) $providerResolution['provider']->getKey() === (int) $provider->getKey()
            && (int) $providerResolution['provider']->getKey() === (int) $attempt->provider_id
            && strtolower((string) $providerResolution['provider']->type) === strtolower((string) $attempt->provider_type)
            && strtolower((string) $providerResolution['provider']->mode) === strtolower((string) $attempt->provider_mode);

        if (!$identityOk) {
            return array_merge($this->result(
                self::STATUS_INDETERMINATE,
                message: $providerResolution['message'] ?? 'The renewal provider identity could not be re-confirmed for reconciliation.',
                safePayload: ['reason' => $providerResolution['reason'] ?? 'renewal_provider_identity_mismatch']
            ), [
                'applied' => false,
                'action' => 'skipped',
            ]);
        }

        return null;
    }

    protected function inspectRenewalProvider(
        DomainProvisioningAttempt $attempt,
        DomainProvider $provider,
        string $domainName,
        string $expectedRenewalDate
    ): array {
        return match (strtolower((string) $attempt->provider_type)) {
            'enom' => $this->inspectEnomRenewal($provider, $attempt, $domainName, $expectedRenewalDate),
            default => $this->result(
                self::STATUS_INDETERMINATE,
                message: 'Renewal reconciliation is not yet supported for this registrar type.',
                safePayload: ['reason' => 'unsupported_provider_for_renewal_reconciliation']
            ),
        };
    }

    /**
     * TLD-3H.2B — Read-only ONLY: EnomClient::getDomainInfo() (command=GetDomainInfo)
     * exclusively — the exact same client method register reconciliation already uses. Never
     * calls renewDomain()/Extend, purchaseDomain(), or any DNS command; EnomClient itself is
     * not modified. Does NOT use EnomClient::inspectDomainInfoDateFields() — that TLD-3G.1C-A
     * method is documented as a TEMPORARY DIAGNOSTIC that exists only to have confirmed the
     * real field name getDomainInfo()'s own 'expires_at' parsing now uses; it is not a
     * substitute data source.
     */
    protected function inspectEnomRenewal(
        DomainProvider $provider,
        DomainProvisioningAttempt $attempt,
        string $domainName,
        string $expectedRenewalDate
    ): array {
        /** @var EnomClient $client */
        $client = app(EnomClient::class);
        $info = $client->getDomainInfo($provider, $domainName);

        if (!($info['ok'] ?? false)) {
            if (($info['reason'] ?? null) === 'domain_not_in_account') {
                return $this->result(
                    self::RENEWAL_STATUS_NOT_CONFIRMED,
                    message: 'eNom does not currently show this domain in the configured account.',
                    safePayload: [
                        'provider_type' => 'enom',
                        'reason' => 'domain_not_in_account',
                        'expected_renewal_date' => $expectedRenewalDate,
                    ]
                );
            }

            return $this->result(
                self::STATUS_INDETERMINATE,
                message: 'The eNom registrar response did not provide conclusive renewal evidence.',
                safePayload: [
                    'provider_type' => 'enom',
                    'reason' => $this->nullableString($info['reason'] ?? null),
                ]
            );
        }

        $providerDomainId = $this->nullableString($info['provider_domain_id'] ?? null);
        $registrationStatus = strtolower((string) ($info['registration_status'] ?? ''));
        $purchaseStatus = strtolower((string) ($info['purchase_status'] ?? ''));
        $rawExpiresAt = $info['expires_at'] ?? null;
        $expiresAt = $this->normalizeDate($rawExpiresAt);

        $safePayload = [
            'provider_type' => 'enom',
            'registration_status' => $registrationStatus !== '' ? $registrationStatus : null,
            'purchase_status' => $purchaseStatus !== '' ? $purchaseStatus : null,
            'provider_domain_id' => $providerDomainId,
            'expected_renewal_date' => $expectedRenewalDate,
            'returned_expires_at' => $this->nullableString($rawExpiresAt),
        ];

        if ($attempt->provider_domain_id !== null && $providerDomainId !== null
            && (string) $attempt->provider_domain_id !== $providerDomainId) {
            return $this->result(
                self::STATUS_INDETERMINATE,
                providerDomainId: $providerDomainId,
                message: 'eNom returned a different provider domain identifier than this attempt previously recorded.',
                safePayload: array_merge($safePayload, ['reason' => 'provider_domain_id_mismatch'])
            );
        }

        if ($expiresAt === null) {
            return $this->result(
                self::STATUS_INDETERMINATE,
                providerDomainId: $providerDomainId,
                message: 'eNom did not return a usable expiration date for this domain.',
                safePayload: array_merge($safePayload, ['reason' => 'expiry_field_unparseable_or_missing'])
            );
        }

        $advanced = Carbon::parse($expiresAt)->startOfDay()
            ->gte(Carbon::parse($expectedRenewalDate)->startOfDay());

        if ($advanced) {
            return $this->result(
                self::RENEWAL_STATUS_CONFIRMED,
                providerDomainId: $providerDomainId,
                expiresAt: $expiresAt,
                message: 'eNom confirms the domain expiration has reached the expected post-renewal date.',
                safePayload: $safePayload
            );
        }

        return $this->result(
            self::RENEWAL_STATUS_NOT_CONFIRMED,
            providerDomainId: $providerDomainId,
            expiresAt: $expiresAt,
            message: 'eNom reports an expiration date that does not yet reach the expected post-renewal date.',
            safePayload: $safePayload
        );
    }

    protected function normalizeRenewalResult(array $result): array
    {
        $allowed = [
            self::RENEWAL_STATUS_CONFIRMED,
            self::RENEWAL_STATUS_NOT_CONFIRMED,
            self::STATUS_INDETERMINATE,
        ];

        if (!in_array($result['status'] ?? null, $allowed, true)) {
            return $this->result(
                self::STATUS_INDETERMINATE,
                message: 'The renewal reconciliation result was not recognized.',
                safePayload: ['reason' => 'invalid_result_contract']
            );
        }

        return $this->result(
            $result['status'],
            $this->nullableString($result['provider_reference'] ?? null),
            $this->nullableString($result['provider_domain_id'] ?? null),
            $this->normalizeDate($result['registered_at'] ?? null),
            $this->normalizeDate($result['expires_at'] ?? null),
            $this->nullableString($result['message'] ?? null),
            $this->safePayload((array) ($result['safe_payload'] ?? []))
        );
    }

    /**
     * TLD-3H.2B — Renewal equivalent of applyRegisteredByUs(): its own committed transaction,
     * re-locking Attempt+OrderItem+Domain and fully re-validating terminal state before writing
     * anything (protects against the attempt/item/domain having changed between inspect and
     * apply — e.g. a concurrent process already resolved it). On success, the actual Domain
     * field mutation is 3H.2A's own applyConfirmedRenewalToDomain() helper — reused, not
     * duplicated — applied with the registrar's own confirmed expiry (ground truth from this
     * reconciliation's GetDomainInfo lookup), not a locally computed target date.
     */
    protected function applyRenewalConfirmed(DomainProvisioningAttempt $attempt, array $result): bool
    {
        return DB::transaction(function () use ($attempt, $result): bool {
            $lockedAttempt = DomainProvisioningAttempt::query()
                ->lockForUpdate()
                ->find($attempt->getKey());

            if (!$lockedAttempt instanceof DomainProvisioningAttempt
                || !in_array($lockedAttempt->status, [
                    DomainProvisioningAttempt::STATUS_INITIATED,
                    DomainProvisioningAttempt::STATUS_INDETERMINATE,
                ], true)
                || $lockedAttempt->operation !== DomainProvisioningAttempt::OPERATION_RENEW) {
                return false;
            }

            $lockedItem = OrderItem::query()
                ->lockForUpdate()
                ->find($lockedAttempt->order_item_id);

            if (!$lockedItem instanceof OrderItem
                || $lockedItem->provisioning_status !== OrderItem::PROVISIONING_IN_PROGRESS
                || strtolower((string) $lockedItem->item_option) !== DomainProvisioningAttempt::OPERATION_RENEW) {
                return false;
            }

            $lockedDomain = $lockedAttempt->domain_id
                ? Domain::query()->lockForUpdate()->find($lockedAttempt->domain_id)
                : null;

            if (!$lockedDomain instanceof Domain) {
                return false;
            }

            if ($result['expires_at'] === null) {
                return false;
            }

            $completedAt = now();
            $lockedAttempt->forceFill([
                'status' => DomainProvisioningAttempt::STATUS_COMPLETED,
                'provider_reference' => $result['provider_reference'] ?? $lockedAttempt->provider_reference,
                'provider_domain_id' => $result['provider_domain_id'] ?? $lockedAttempt->provider_domain_id,
                'finished_at' => $completedAt,
                'response_payload' => $result['safe_payload'] ?: null,
            ])->save();

            $lockedItem->forceFill([
                'provisioning_status' => OrderItem::PROVISIONING_COMPLETED,
                'provisioning_completed_at' => $completedAt,
            ])->save();

            app(RegistrarProvisioningService::class)->applyConfirmedRenewalToDomain(
                $lockedDomain,
                $result['expires_at'],
                null
            );

            return true;
        });
    }

    protected function inspectProvider(
        DomainProvisioningAttempt $attempt,
        DomainProvider $provider,
        string $domainName
    ): array {
        return match (strtolower((string) $attempt->provider_type)) {
            'namecheap' => $this->inspectNamecheap($provider, $domainName),
            'enom' => $this->inspectEnom($provider, $domainName),
            default => $this->result(
                self::STATUS_INDETERMINATE,
                message: 'The saved registrar type is not supported for reconciliation.',
                safePayload: ['reason' => 'unsupported_provider']
            ),
        };
    }

    protected function inspectNamecheap(DomainProvider $provider, string $domainName): array
    {
        $client = new NamecheapClient($provider);
        $info = $client->getDomainInfo($domainName);

        if (!($info['ok'] ?? false)) {
            if (($info['reason'] ?? null) === 'domain_not_in_account') {
                return $this->classifyAbsentDomain($client->checkAvailability($domainName), 'namecheap', $info);
            }

            return $this->indeterminateProviderResult('namecheap', $info);
        }

        $isOwner = $info['is_owner'] ?? null;
        $providerDomainId = $this->nullableString($info['provider_domain_id'] ?? null);
        $status = strtolower((string) ($info['status'] ?? ''));
        $nameMatches = strtolower((string) ($info['domain_name'] ?? '')) === $domainName;
        $safePayload = [
            'provider_type' => 'namecheap',
            'is_owner' => $isOwner,
            'status' => $status !== '' ? $status : null,
            'domain_name_matches' => $nameMatches,
            'provider_domain_id' => $providerDomainId,
        ];

        if ($isOwner === true && $providerDomainId !== null && $nameMatches
            && in_array($status, ['ok', 'locked'], true)) {
            return $this->result(
                self::STATUS_REGISTERED_BY_US,
                providerDomainId: $providerDomainId,
                registeredAt: $this->normalizeDate($info['registered_at'] ?? null),
                expiresAt: $this->normalizeDate($info['expires_at'] ?? null),
                message: 'Namecheap confirms that this domain is owned by the configured account.',
                safePayload: $safePayload
            );
        }

        if ($isOwner === true && $providerDomainId !== null && $nameMatches) {
            return $this->result(
                self::STATUS_PROVIDER_PROCESSING,
                providerDomainId: $providerDomainId,
                registeredAt: $this->normalizeDate($info['registered_at'] ?? null),
                expiresAt: $this->normalizeDate($info['expires_at'] ?? null),
                message: 'Namecheap sees the domain in this account, but its status is not conclusively active.',
                safePayload: $safePayload
            );
        }

        if ($isOwner === false) {
            return $this->classifyAbsentDomain($client->checkAvailability($domainName), 'namecheap', $info);
        }

        return $this->result(
            self::STATUS_INDETERMINATE,
            providerDomainId: $providerDomainId,
            message: 'Namecheap ownership evidence was incomplete.',
            safePayload: $safePayload
        );
    }

    protected function inspectEnom(DomainProvider $provider, string $domainName): array
    {
        /** @var EnomClient $client */
        $client = app(EnomClient::class);
        $info = $client->getDomainInfo($provider, $domainName);

        if (!($info['ok'] ?? false)) {
            if (($info['reason'] ?? null) === 'domain_not_in_account') {
                [$sld, $tld] = $this->splitDomain($domainName);
                $availability = $sld !== null && $tld !== null
                    ? $client->checkAvailability($provider, $sld, $tld, retrySafe: false)
                    : ['ok' => false, 'reason' => 'invalid_domain'];

                return $this->classifyAbsentDomain($availability, 'enom', $info);
            }

            return $this->indeterminateProviderResult('enom', $info);
        }

        $providerDomainId = $this->nullableString($info['provider_domain_id'] ?? null);
        $registrationStatus = strtolower((string) ($info['registration_status'] ?? ''));
        $purchaseStatus = strtolower((string) ($info['purchase_status'] ?? ''));
        $belongsToAccount = $this->nullableString($info['belongs_to_party_id'] ?? null) !== null;
        $safePayload = [
            'provider_type' => 'enom',
            'registration_status' => $registrationStatus !== '' ? $registrationStatus : null,
            'purchase_status' => $purchaseStatus !== '' ? $purchaseStatus : null,
            'account_membership_confirmed' => $belongsToAccount,
            'provider_domain_id' => $providerDomainId,
        ];

        if ($providerDomainId !== null && $belongsToAccount
            && $registrationStatus === 'registered' && $purchaseStatus === 'paid') {
            return $this->result(
                self::STATUS_REGISTERED_BY_US,
                providerDomainId: $providerDomainId,
                registeredAt: $this->normalizeDate($info['registered_at'] ?? null),
                expiresAt: $this->normalizeDate($info['expires_at'] ?? null),
                message: 'eNom confirms that this domain is registered and paid in the configured account.',
                safePayload: $safePayload
            );
        }

        if ($providerDomainId !== null && $belongsToAccount) {
            return $this->result(
                self::STATUS_PROVIDER_PROCESSING,
                providerDomainId: $providerDomainId,
                registeredAt: $this->normalizeDate($info['registered_at'] ?? null),
                expiresAt: $this->normalizeDate($info['expires_at'] ?? null),
                message: 'eNom sees the domain in this account, but registration or payment is not conclusive.',
                safePayload: $safePayload
            );
        }

        return $this->result(
            self::STATUS_INDETERMINATE,
            providerDomainId: $providerDomainId,
            message: 'eNom account ownership evidence was incomplete.',
            safePayload: $safePayload
        );
    }

    protected function classifyAbsentDomain(array $availability, string $providerType, array $info): array
    {
        if (!($availability['ok'] ?? false) || !array_key_exists('available', $availability)) {
            return $this->indeterminateProviderResult($providerType, $availability);
        }

        $available = $availability['available'];
        if (!is_bool($available)) {
            return $this->indeterminateProviderResult($providerType, $availability);
        }

        return $this->result(
            $available ? self::STATUS_LIKELY_NOT_SENT : self::STATUS_EXTERNAL_UNAVAILABLE,
            message: $available
                ? 'The domain is absent from this provider account and currently available; delivery is still not proven.'
                : 'The domain is absent from this provider account and unavailable externally.',
            safePayload: [
                'provider_type' => $providerType,
                'account_membership_confirmed' => false,
                'available' => $available,
                'info_reason' => $this->nullableString($info['reason'] ?? null),
            ]
        );
    }

    protected function validateAttempt(DomainProvisioningAttempt $attempt): ?array
    {
        $item = $attempt->orderItem;
        $provider = $attempt->provider;
        $allowedAttemptStatuses = [
            DomainProvisioningAttempt::STATUS_INITIATED,
            DomainProvisioningAttempt::STATUS_INDETERMINATE,
        ];

        $valid = in_array($attempt->status, $allowedAttemptStatuses, true)
            && $attempt->operation === DomainProvisioningAttempt::OPERATION_REGISTER
            && $item instanceof OrderItem
            && $item->provisioning_status === OrderItem::PROVISIONING_IN_PROGRESS
            && strtolower((string) $item->item_option) === DomainProvisioningAttempt::OPERATION_REGISTER
            && $provider instanceof DomainProvider
            && strtolower((string) $provider->type) === strtolower((string) $attempt->provider_type)
            && strtolower((string) $provider->mode) === strtolower((string) $attempt->provider_mode)
            && trim((string) ($attempt->domain?->domain_name ?: $item?->domain)) !== '';

        if ($valid) {
            return null;
        }

        return array_merge($this->result(
            self::STATUS_INDETERMINATE,
            message: 'The attempt is not eligible for registration reconciliation.',
            safePayload: ['reason' => 'precondition_failed']
        ), [
            'applied' => false,
            'action' => 'skipped',
        ]);
    }

    protected function applyRegisteredByUs(DomainProvisioningAttempt $attempt, array $result): bool
    {
        return DB::transaction(function () use ($attempt, $result): bool {
            $lockedAttempt = DomainProvisioningAttempt::query()
                ->lockForUpdate()
                ->find($attempt->getKey());

            if (!$lockedAttempt instanceof DomainProvisioningAttempt
                || !in_array($lockedAttempt->status, [
                    DomainProvisioningAttempt::STATUS_INITIATED,
                    DomainProvisioningAttempt::STATUS_INDETERMINATE,
                ], true)
                || $lockedAttempt->operation !== DomainProvisioningAttempt::OPERATION_REGISTER) {
                return false;
            }

            $lockedItem = OrderItem::query()
                ->lockForUpdate()
                ->find($lockedAttempt->order_item_id);

            if (!$lockedItem instanceof OrderItem
                || $lockedItem->provisioning_status !== OrderItem::PROVISIONING_IN_PROGRESS
                || strtolower((string) $lockedItem->item_option) !== DomainProvisioningAttempt::OPERATION_REGISTER) {
                return false;
            }

            $lockedDomain = $lockedAttempt->domain_id
                ? Domain::query()->lockForUpdate()->find($lockedAttempt->domain_id)
                : null;

            if (!$lockedDomain instanceof Domain) {
                return false;
            }

            $completedAt = now();
            $lockedAttempt->forceFill([
                'status' => DomainProvisioningAttempt::STATUS_COMPLETED,
                'provider_reference' => $result['provider_reference'] ?? $lockedAttempt->provider_reference,
                'provider_domain_id' => $result['provider_domain_id'] ?? $lockedAttempt->provider_domain_id,
                'finished_at' => $completedAt,
                'response_payload' => $result['safe_payload'] ?: null,
            ])->save();

            $lockedItem->forceFill([
                'provisioning_status' => OrderItem::PROVISIONING_COMPLETED,
                'provisioning_completed_at' => $completedAt,
            ])->save();

            $domainUpdates = ['status' => 'active'];
            if ($result['registered_at'] !== null) {
                $domainUpdates['registration_date'] = $result['registered_at'];
            }
            if ($result['expires_at'] !== null) {
                $domainUpdates['renewal_date'] = $result['expires_at'];
            }
            $lockedDomain->forceFill($domainUpdates)->save();

            return true;
        });
    }

    protected function indeterminateProviderResult(string $providerType, array $response): array
    {
        $payload = [
            'provider_type' => $providerType,
            'reason' => $this->nullableString($response['reason'] ?? null),
        ];

        foreach (['code', 'http_code'] as $key) {
            if (isset($response[$key]) && is_scalar($response[$key])) {
                $payload[$key] = $response[$key];
            }
        }

        return $this->result(
            self::STATUS_INDETERMINATE,
            message: 'The registrar response did not provide conclusive ownership evidence.',
            safePayload: $payload
        );
    }

    protected function normalizeResult(array $result): array
    {
        $allowed = [
            self::STATUS_REGISTERED_BY_US,
            self::STATUS_PROVIDER_PROCESSING,
            self::STATUS_EXTERNAL_UNAVAILABLE,
            self::STATUS_LIKELY_NOT_SENT,
            self::STATUS_INDETERMINATE,
        ];

        if (!in_array($result['status'] ?? null, $allowed, true)) {
            return $this->result(
                self::STATUS_INDETERMINATE,
                message: 'The reconciliation result was not recognized.',
                safePayload: ['reason' => 'invalid_result_contract']
            );
        }

        return $this->result(
            $result['status'],
            $this->nullableString($result['provider_reference'] ?? null),
            $this->nullableString($result['provider_domain_id'] ?? null),
            $this->normalizeDate($result['registered_at'] ?? null),
            $this->normalizeDate($result['expires_at'] ?? null),
            $this->nullableString($result['message'] ?? null),
            $this->safePayload((array) ($result['safe_payload'] ?? []))
        );
    }

    protected function result(
        string $status,
        ?string $providerReference = null,
        ?string $providerDomainId = null,
        ?string $registeredAt = null,
        ?string $expiresAt = null,
        ?string $message = null,
        array $safePayload = []
    ): array {
        return [
            'status' => $status,
            'provider_reference' => $providerReference,
            'provider_domain_id' => $providerDomainId,
            'registered_at' => $registeredAt,
            'expires_at' => $expiresAt,
            'message' => $message,
            'safe_payload' => $this->safePayload($safePayload),
        ];
    }

    protected function safePayload(array $payload): array
    {
        $safeKeys = [
            'provider_type',
            'reason',
            'code',
            'http_code',
            'is_owner',
            'status',
            'registration_status',
            'purchase_status',
            'account_membership_confirmed',
            'domain_name_matches',
            'provider_domain_id',
            'available',
            'info_reason',
            // TLD-3H.2B — renewal reconciliation diagnostics only; additive, nothing removed.
            'expected_renewal_date',
            'returned_expires_at',
        ];

        return array_filter(
            array_intersect_key($payload, array_flip($safeKeys)),
            static fn ($value) => $value !== null
        );
    }

    protected function normalizeDate(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function nullableString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? Str::limit($value, 1000, '') : null;
    }

    protected function splitDomain(string $fqdn): array
    {
        $parts = explode('.', strtolower(trim($fqdn)), 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return [null, null];
        }

        return [$parts[0], $parts[1]];
    }
}
