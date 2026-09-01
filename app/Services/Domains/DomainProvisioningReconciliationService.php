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
