<?php

namespace App\Services\Domains;

use App\Models\Client;
use App\Models\Domain;
use App\Models\DomainProvisioningAttempt;
use App\Models\DomainProvider;
use App\Models\DomainRegistrationClaim;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Domains\Clients\EnomClient;
use App\Services\Domains\Clients\NamecheapClient;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RegistrarProvisioningService
{
    public function provisionOrderDomain(Order $order, ?string $paymentMethod = null): array
    {
        $order->loadMissing(['client', 'items', 'invoices.items']);

        $orderItems = $order->items
            ->filter(fn ($item) => filled($item->domain))
            ->values();

        if ($orderItems->isEmpty()) {
            return [
                'ok' => true,
                'skipped' => true,
                'message' => 'No renewable or registerable domain item was found for this order.',
            ];
        }

        $domainResults = [];

        foreach ($orderItems as $orderItem) {
            try {
                $result = $this->provisionOrderItem($order, $orderItem, $paymentMethod);
            } catch (\Throwable $e) {
                Log::error('Domain order item provisioning crashed.', [
                    'order_id' => $order->getKey(),
                    'order_item_id' => $orderItem->getKey(),
                    'domain' => $orderItem->domain,
                    'error' => $e->getMessage(),
                ]);

                $result = [
                    'ok' => false,
                    'message' => 'Domain provisioning crashed before it could finish.',
                ];
            }

            $domainResults[] = [
                'order_item_id' => $orderItem->getKey(),
                'domain' => $this->normalizeDomain((string) $orderItem->domain),
                'provisioning_status' => $orderItem->fresh()?->provisioning_status
                    ?: OrderItem::PROVISIONING_NOT_STARTED,
                'ok' => (bool) ($result['ok'] ?? false),
                'message' => $result['message'] ?? null,
                'result' => $result,
            ];
        }

        if (count($domainResults) === 1) {
            return array_merge($domainResults[0]['result'], [
                'domains' => $domainResults,
            ]);
        }

        $allSucceeded = collect($domainResults)->every(
            fn (array $domainResult) => $domainResult['ok'] === true
        );

        return [
            'ok' => $allSucceeded,
            'domains' => $domainResults,
            'message' => $allSucceeded
                ? 'All domain order items were provisioned successfully.'
                : 'One or more domain order items did not complete provisioning.',
        ];
    }

    protected function provisionOrderItem(Order $order, OrderItem $orderItem, ?string $paymentMethod = null): array
    {
        $action = strtolower((string) $orderItem->item_option);

        if ($action === 'renew') {
            return $this->renewOrderDomain($order, $orderItem, $paymentMethod);
        }

        if ($action !== 'register') {
            return [
                'ok' => false,
                'message' => 'Unsupported domain provisioning action.',
            ];
        }

        // ADR — Provisioning Idempotency Phase 1 (Register Domain فقط). OrderItem.provisioning_status
        // هو مصدر الحقيقة الأساسي لمنع إعادة تنفيذ تسجيل الدومين لنفس عملية الشراء (retry، webhook
        // مكرر، إعادة تشغيل worker، إجراء إداري مكرر). لا يُطبَّق هذا الحارس على renew/transfer/restore
        // في هذه المرحلة — النطاق مقصور على "register" فقط.
        $provisioningStatus = $orderItem->provisioning_status ?: OrderItem::PROVISIONING_NOT_STARTED;

        if ($provisioningStatus === OrderItem::PROVISIONING_COMPLETED) {
            return [
                'ok' => true,
                'skipped' => true,
                'message' => 'Domain registration was already completed for this order item.',
            ];
        }

        if ($provisioningStatus === OrderItem::PROVISIONING_IN_PROGRESS) {
            return [
                'ok' => false,
                'message' => 'Domain registration is already in progress for this order item.',
            ];
        }

        // failed أو not_started → يُسمح بالمتابعة (بدء جديد أو إعادة محاولة).

        if (DB::transactionLevel() > 0) {
            return $this->deferRegistrationUntilAfterCommit($order, $orderItem, $paymentMethod);
        }

        $client = $order->client;

        if (!$client instanceof Client) {
            return [
                'ok' => false,
                'message' => 'Order client is missing. Unable to register the domain automatically.',
            ];
        }

        $meta = is_array($orderItem->meta) ? $orderItem->meta : [];
        $providerResolution = $this->trustedRegistrationProvider($meta);

        if (!($providerResolution['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => $providerResolution['message'],
            ];
        }

        $provider = $providerResolution['provider'];
        $domainName = $this->normalizeDomain((string) $orderItem->domain);

        $registrationDate = Carbon::parse($meta['registration_date'] ?? now()->toDateString());
        $renewalDate = Carbon::parse($meta['renewal_date'] ?? $registrationDate->copy()->addYear()->toDateString());
        $years = max(1, (int) ceil(max(1, $registrationDate->diffInDays($renewalDate)) / 365));

        $domainAttributes = [
            'client_id' => $order->client_id,
            // TLD-3D — provider_id هو Source of Truth الجديد؛ registrar يبقى نصاً مُطبَّعاً
            // للتوافق/العرض فقط. $provider هنا هو الـ DomainProvider model الموثوق الذي أعادته
            // trustedRegistrationProvider() بالفعل — لا حاجة لأي lookup إضافي.
            'provider_id' => $provider->getKey(),
            'registrar' => $provider->type,
            'registration_date' => $registrationDate->toDateString(),
            'renewal_date' => $renewalDate->toDateString(),
            'status' => 'pending',
            'payment_method' => $paymentMethod,
            'dns_last_note' => null,
        ];

        $contact = $this->buildRegistrarContactPayload($client);

        try {
            $claim = $this->claimRegistration($orderItem, $domainName, $provider, $domainAttributes);
        } catch (\Throwable $e) {
            Log::error('Unable to create the domain provisioning attempt.', [
                'order_item_id' => $orderItem->getKey(),
                'domain_id' => $domain->getKey(),
                'provider_id' => $provider->getKey(),
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'Domain registration did not start because its provisioning attempt could not be recorded.',
            ];
        }

        if (!($claim['claimed'] ?? false)) {
            if (($claim['domain'] ?? null) instanceof Domain) {
                $this->attachDomainToOrderInvoices($order, $claim['domain']);
            }

            return $claim['result'];
        }

        $orderItem = $claim['order_item'];
        $attempt = $claim['attempt'];
        $registrationClaim = $claim['registration_claim'];
        $domain = $claim['domain'];
        $this->attachDomainToOrderInvoices($order, $domain);

        $registration = $this->registerDomainWithProvider($provider, $domain, [
            'years' => $years,
            'registration_date' => $registrationDate,
            'renewal_date' => $renewalDate,
        ], $contact);

        if (!($registration['ok'] ?? false)) {
            $definitive = ($registration['definitive'] ?? true) === true;
            $this->finalizeRegistration(
                $orderItem,
                $attempt,
                $registrationClaim,
                $definitive ? OrderItem::PROVISIONING_FAILED : null,
                $definitive
                    ? DomainProvisioningAttempt::STATUS_CONFIRMED_FAILED
                    : DomainProvisioningAttempt::STATUS_INDETERMINATE,
                $registration
            );

            $domain->forceFill([
                'status' => 'pending',
                'registrar' => $provider->type,
                'payment_method' => $paymentMethod ?: $domain->payment_method,
                'dns_last_note' => $registration['message'] ?? 'Automatic registrar provisioning failed.',
            ])->save();

            return [
                'ok' => false,
                'provider' => $provider,
                'domain' => $domain,
                'message' => $registration['message'] ?? 'Automatic registrar provisioning failed.',
                'cid' => $registration['cid'] ?? null,
            ];
        }

        $this->finalizeRegistration(
            $orderItem,
            $attempt,
            $registrationClaim,
            OrderItem::PROVISIONING_COMPLETED,
            DomainProvisioningAttempt::STATUS_COMPLETED,
            $registration
        );

        $domain->forceFill([
            'status' => 'active',
            'registrar' => $provider->type,
            'registration_date' => $registrationDate->toDateString(),
            'renewal_date' => $renewalDate->toDateString(),
            'payment_method' => $paymentMethod ?: $domain->payment_method,
            'dns_last_note' => null,
        ])->save();

        $this->attachDomainToOrderInvoices($order, $domain);

        return [
            'ok' => true,
            'provider' => $provider,
            'domain' => $domain,
            'cid' => $registration['cid'] ?? null,
            'message' => 'Domain registered successfully with the registrar.',
        ];
    }

    protected function deferRegistrationUntilAfterCommit(
        Order $order,
        OrderItem $orderItem,
        ?string $paymentMethod
    ): array
    {
        $orderId = $order->getKey();
        $orderItemId = $orderItem->getKey();

        DB::afterCommit(function () use ($orderId, $orderItemId, $paymentMethod): void {
            try {
                $committedOrder = Order::query()
                    ->with(['client', 'items', 'invoices.items'])
                    ->find($orderId);

                if (!$committedOrder instanceof Order) {
                    Log::warning('Deferred registrar provisioning skipped because the order no longer exists.', [
                        'order_id' => $orderId,
                    ]);

                    return;
                }

                $committedOrderItem = OrderItem::query()->find($orderItemId);

                if (!$committedOrderItem instanceof OrderItem
                    || (int) $committedOrderItem->order_id !== (int) $committedOrder->getKey()) {
                    Log::warning('Deferred registrar provisioning skipped because the order item no longer exists.', [
                        'order_id' => $orderId,
                        'order_item_id' => $orderItemId,
                    ]);

                    return;
                }

                $result = $this->provisionOrderItem($committedOrder, $committedOrderItem, $paymentMethod);

                if (!(bool) ($result['ok'] ?? false)) {
                    Log::error('Deferred registrar provisioning failed.', [
                        'order_id' => $orderId,
                        'order_item_id' => $orderItemId,
                        'domain' => $committedOrderItem->domain,
                        'message' => $result['message'] ?? null,
                        'cid' => $result['cid'] ?? null,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Deferred registrar provisioning crashed.', [
                    'order_id' => $orderId,
                    'order_item_id' => $orderItemId,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        return [
            'ok' => true,
            'deferred' => true,
            'message' => 'Domain registration was deferred until after the database transaction commits.',
        ];
    }

    protected function claimRegistration(
        OrderItem $orderItem,
        string $domainName,
        DomainProvider $provider,
        array $domainAttributes
    ): array
    {
        try {
            return DB::transaction(function () use ($orderItem, $domainName, $provider, $domainAttributes): array {
            $lockedItem = OrderItem::query()
                ->lockForUpdate()
                ->find($orderItem->getKey());

            if (!$lockedItem instanceof OrderItem) {
                return [
                    'claimed' => false,
                    'result' => [
                        'ok' => false,
                        'message' => 'The domain order item no longer exists.',
                    ],
                ];
            }

            $lockedMeta = is_array($lockedItem->meta) ? $lockedItem->meta : [];
            $providerResolution = $this->trustedRegistrationProvider($lockedMeta);

            if (!($providerResolution['ok'] ?? false)
                || (int) $providerResolution['provider']->getKey() !== (int) $provider->getKey()
            ) {
                return [
                    'claimed' => false,
                    'result' => [
                        'ok' => false,
                        'message' => $providerResolution['message']
                            ?? 'Trusted registrar identity changed before registration could start.',
                    ],
                ];
            }

            $status = $lockedItem->provisioning_status ?: OrderItem::PROVISIONING_NOT_STARTED;

            if ($status === OrderItem::PROVISIONING_COMPLETED) {
                return [
                    'claimed' => false,
                    'result' => [
                        'ok' => true,
                        'skipped' => true,
                        'message' => 'Domain registration was already completed for this order item.',
                    ],
                ];
            }

            if ($status === OrderItem::PROVISIONING_IN_PROGRESS) {
                return [
                    'claimed' => false,
                    'result' => [
                        'ok' => false,
                        'message' => 'Domain registration is already in progress for this order item.',
                    ],
                ];
            }

            if (!in_array($status, [OrderItem::PROVISIONING_NOT_STARTED, OrderItem::PROVISIONING_FAILED], true)) {
                return [
                    'claimed' => false,
                    'result' => [
                        'ok' => false,
                        'message' => 'Domain registration cannot start from the current provisioning state.',
                    ],
                ];
            }

            $hasInitiatedAttempt = DomainProvisioningAttempt::query()
                ->where('order_item_id', $lockedItem->getKey())
                ->where('operation', DomainProvisioningAttempt::OPERATION_REGISTER)
                ->where('status', DomainProvisioningAttempt::STATUS_INITIATED)
                ->exists();

            if ($hasInitiatedAttempt) {
                return [
                    'claimed' => false,
                    'result' => [
                        'ok' => false,
                        'message' => 'A domain registration attempt is already initiated for this order item.',
                    ],
                ];
            }

            $registrationClaim = DomainRegistrationClaim::query()
                ->where('domain_name_normalized', $domainName)
                ->lockForUpdate()
                ->first();

            if ($registrationClaim instanceof DomainRegistrationClaim
                && $registrationClaim->status !== DomainRegistrationClaim::STATUS_RELEASED
            ) {
                return [
                    'claimed' => false,
                    'result' => [
                        'ok' => false,
                        'message' => $registrationClaim->status === DomainRegistrationClaim::STATUS_COMPLETED
                            ? t('site.Domain_Registration_Already_Completed', 'This domain registration was already completed by another order.')
                            : t('site.Domain_Registration_Already_Claimed', 'This domain registration is already claimed by another order.'),
                    ],
                ];
            }

            $startedAt = now();

            if ($registrationClaim instanceof DomainRegistrationClaim) {
                $registrationClaim->forceFill([
                    'order_item_id' => $lockedItem->getKey(),
                    'status' => DomainRegistrationClaim::STATUS_CLAIMED,
                    'claimed_at' => $startedAt,
                    'released_at' => null,
                ])->save();
            } else {
                $registrationClaim = DomainRegistrationClaim::query()->create([
                    'domain_name_normalized' => $domainName,
                    'order_item_id' => $lockedItem->getKey(),
                    'status' => DomainRegistrationClaim::STATUS_CLAIMED,
                    'claimed_at' => $startedAt,
                ]);
            }

            $domain = Domain::query()
                ->where('domain_name', $domainName)
                ->lockForUpdate()
                ->first();

            if ($domain instanceof Domain
                && $domain->status === 'active'
                && strtolower((string) $domain->registrar) === strtolower((string) $provider->type)
                // TLD-3D — minimal drift guard: a same-type provider row is not necessarily the
                // SAME provider record (e.g. "Namecheap Live" vs "Namecheap Sandbox"). When the
                // existing domain already has a trusted provider_id, it must match exactly; a
                // domain with no provider_id yet (legacy/manual) keeps today's type-only check.
                && ($domain->provider_id === null || (int) $domain->provider_id === (int) $provider->getKey())
            ) {
                $registrationClaim->forceFill([
                    'status' => DomainRegistrationClaim::STATUS_COMPLETED,
                    'released_at' => null,
                ])->save();
                $lockedItem->forceFill([
                    'provisioning_status' => OrderItem::PROVISIONING_COMPLETED,
                    'provisioning_completed_at' => $startedAt,
                ])->save();

                return [
                    'claimed' => false,
                    'domain' => $domain,
                    'result' => [
                        'ok' => true,
                        'provider' => $provider,
                        'domain' => $domain,
                        'message' => 'Domain was already active with the default registrar.',
                        'skipped' => true,
                    ],
                ];
            }

            $domain ??= new Domain(['domain_name' => $domainName]);
            $domain->fill(array_filter(
                $domainAttributes,
                fn ($value) => $value !== null
            ));
            $domain->domain_name = $domainName;
            $domain->dns_last_note = null;
            $domain->save();

            $lockedItem->forceFill([
                'provisioning_status' => OrderItem::PROVISIONING_IN_PROGRESS,
                'provisioning_started_at' => $startedAt,
                'provisioning_completed_at' => null,
            ])->save();

            $attempt = DomainProvisioningAttempt::query()->create([
                'order_item_id' => $lockedItem->getKey(),
                'domain_id' => $domain->getKey(),
                'provider_id' => $provider->getKey(),
                'attempt_uuid' => (string) Str::uuid(),
                'operation' => DomainProvisioningAttempt::OPERATION_REGISTER,
                'provider_type' => strtolower((string) $provider->type),
                'provider_mode' => strtolower((string) $provider->mode),
                'status' => DomainProvisioningAttempt::STATUS_INITIATED,
                'started_at' => $startedAt,
            ]);

            return [
                'claimed' => true,
                'order_item' => $lockedItem,
                'attempt' => $attempt,
                'registration_claim' => $registrationClaim,
                'domain' => $domain,
            ];
            });
        } catch (QueryException $e) {
            if (!$this->isDomainRegistrationClaimCollision($e)) {
                throw $e;
            }

            return [
                'claimed' => false,
                'result' => [
                    'ok' => false,
                    'message' => t(
                        'site.Domain_Registration_Already_Claimed',
                        'This domain registration is already claimed by another order.'
                    ),
                ],
            ];
        }
    }

    protected function finalizeRegistration(
        OrderItem $orderItem,
        DomainProvisioningAttempt $attempt,
        DomainRegistrationClaim $registrationClaim,
        ?string $orderItemStatus,
        string $attemptStatus,
        array $registration
    ): void {
        $responsePayload = $this->safeAttemptResponsePayload($registration);

        DB::transaction(function () use (
            $orderItem,
            $attempt,
            $registrationClaim,
            $orderItemStatus,
            $attemptStatus,
            $registration,
            $responsePayload
        ): void {
            $lockedItem = OrderItem::query()
                ->lockForUpdate()
                ->find($orderItem->getKey());

            $lockedAttempt = DomainProvisioningAttempt::query()
                ->lockForUpdate()
                ->find($attempt->getKey());

            $lockedRegistrationClaim = DomainRegistrationClaim::query()
                ->lockForUpdate()
                ->find($registrationClaim->getKey());

            if (!$lockedItem instanceof OrderItem
                || !$lockedAttempt instanceof DomainProvisioningAttempt
                || !$lockedRegistrationClaim instanceof DomainRegistrationClaim
                || $lockedItem->provisioning_status !== OrderItem::PROVISIONING_IN_PROGRESS
                || $lockedAttempt->status !== DomainProvisioningAttempt::STATUS_INITIATED
                || $lockedRegistrationClaim->status !== DomainRegistrationClaim::STATUS_CLAIMED
                || (int) $lockedRegistrationClaim->order_item_id !== (int) $lockedItem->getKey()) {
                return;
            }

            $lockedAttempt->forceFill([
                'status' => $attemptStatus,
                'provider_reference' => $registration['provider_reference']
                    ?? $registration['cid']
                    ?? null,
                'provider_domain_id' => $registration['provider_domain_id'] ?? null,
                'finished_at' => now(),
                'response_payload' => $responsePayload,
            ])->save();

            if ($attemptStatus === DomainProvisioningAttempt::STATUS_COMPLETED) {
                $lockedRegistrationClaim->forceFill([
                    'status' => DomainRegistrationClaim::STATUS_COMPLETED,
                    'released_at' => null,
                ])->save();
            } elseif ($attemptStatus === DomainProvisioningAttempt::STATUS_CONFIRMED_FAILED) {
                $lockedRegistrationClaim->forceFill([
                    'status' => DomainRegistrationClaim::STATUS_RELEASED,
                    'released_at' => now(),
                ])->save();
            }

            if ($orderItemStatus !== null) {
                $lockedItem->forceFill([
                    'provisioning_status' => $orderItemStatus,
                    'provisioning_completed_at' => $orderItemStatus === OrderItem::PROVISIONING_COMPLETED
                        ? now()
                        : null,
                ])->save();
            }
        });
    }

    protected function isDomainRegistrationClaimCollision(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'domain_registration_claims_domain_unique')
            || str_contains($message, 'domain_registration_claims.domain_name_normalized');
    }

    protected function safeAttemptResponsePayload(array $registration): ?array
    {
        $payload = [];

        foreach (['reason', 'http_code', 'code'] as $key) {
            if (isset($registration[$key]) && is_scalar($registration[$key])) {
                $payload[$key] = $registration[$key];
            }
        }

        if (isset($registration['message']) && is_scalar($registration['message'])) {
            $reason = strtolower((string) ($registration['reason'] ?? ''));
            $payload['message'] = $reason === 'exception'
                ? 'Registrar request ended with an exception.'
                : Str::limit($this->redactSensitiveMessage((string) $registration['message']), 1000, '');
        }

        return $payload !== [] ? $payload : null;
    }

    protected function redactSensitiveMessage(string $message): string
    {
        $message = preg_replace('/([?&](?:ApiKey|ApiToken|PW|Password)=)[^&\s]+/i', '$1[redacted]', $message);

        return preg_replace('/https?:\/\/[^\s?]+\?[^\s]+/i', '[redacted-url]', (string) $message);
    }

    protected function renewOrderDomain(Order $order, $orderItem, ?string $paymentMethod = null): array
    {
        $meta = is_array($orderItem->meta) ? $orderItem->meta : [];
        $domainName = $this->normalizeDomain((string) $orderItem->domain);
        $domain = $this->resolveRenewableDomain($order, $domainName, $meta['domain_id'] ?? null);

        if (!$domain instanceof Domain) {
            return [
                'ok' => false,
                'message' => 'The domain could not be resolved for the renewal request.',
            ];
        }

        // TLD-3D — Hybrid Provider Identity: renewal no longer resolves a provider live via
        // providerForDomain()'s type-based fallback chain (the drift TLD-3C confirmed). It uses
        // ONLY the exact provider_id snapshotted onto this OrderItem at renewal-quote time,
        // cross-checked against Domain.provider_id and the provider's current active/type/mode
        // state — no fallback to any other provider.
        $providerResolution = $this->trustedRenewalProvider($domain, $meta);

        if (!($providerResolution['ok'] ?? false)) {
            Log::warning('Renewal provisioning blocked: provider identity could not be trusted.', [
                'order_id' => $order->getKey(),
                'domain_id' => $domain->getKey(),
                'domain' => $domain->domain_name,
                'reason' => $providerResolution['reason'] ?? 'unknown',
            ]);

            return [
                'ok' => false,
                'domain' => $domain,
                'message' => $providerResolution['message'],
                'reason' => $providerResolution['reason'] ?? null,
            ];
        }

        $provider = $providerResolution['provider'];

        $currentRenewalDate = Carbon::parse($meta['current_renewal_date'] ?? $domain->renewal_date ?? now()->toDateString());
        $renewalDate = Carbon::parse($meta['renewal_date'] ?? $currentRenewalDate->copy()->addYear()->toDateString());
        $years = max(1, (int) ($meta['term_years'] ?? ceil(max(1, $currentRenewalDate->diffInDays($renewalDate)) / 365)));

        $renewal = $this->renewDomainWithProvider($provider, $domain, [
            'years' => $years,
            'current_renewal_date' => $currentRenewalDate,
            'renewal_date' => $renewalDate,
        ]);

        if (!($renewal['ok'] ?? false)) {
            // TLD-3D — never write provider_id/registrar here. $provider is already the exact,
            // cross-checked identity Domain.provider_id already points to; re-stamping it on
            // failure would silently "correct" a drift signal instead of surfacing it, and this
            // path never reaches a mismatched provider in the first place (rejected above).
            $domain->forceFill([
                'status' => $domain->status ?: 'active',
                'payment_method' => $paymentMethod ?: $domain->payment_method,
                'dns_last_note' => $renewal['message'] ?? 'Automatic registrar renewal failed.',
            ])->save();

            return [
                'ok' => false,
                'provider' => $provider,
                'domain' => $domain,
                'message' => $renewal['message'] ?? 'Automatic registrar renewal failed.',
                'cid' => $renewal['cid'] ?? null,
            ];
        }

        $domain->forceFill([
            'status' => 'active',
            'renewal_date' => $renewalDate->toDateString(),
            'payment_method' => $paymentMethod ?: $domain->payment_method,
            'dns_last_note' => null,
        ])->save();

        $this->attachDomainToOrderInvoices($order, $domain);

        return [
            'ok' => true,
            'provider' => $provider,
            'domain' => $domain,
            'cid' => $renewal['cid'] ?? null,
            'message' => 'Domain renewed successfully with the registrar.',
        ];
    }

    /**
     * TLD-3D — Hybrid Provider Identity: the sole, exact-identity resolver for renewal
     * provisioning. Mirrors trustedRegistrationProvider()'s "no fallback" contract, but adds
     * the one extra cross-check registration doesn't need: the OrderItem's provider_id snapshot
     * must still match the domain's OWN current Domain.provider_id (registration has no
     * equivalent concept to drift against). Any failure returns a distinct, non-generic reason
     * code — never a silent substitution.
     */
    protected function trustedRenewalProvider(Domain $domain, array $meta): array
    {
        $snapshotProviderId = filter_var($meta['provider_id'] ?? null, FILTER_VALIDATE_INT);
        $snapshotType = strtolower(trim((string) ($meta['provider_type'] ?? '')));
        $snapshotMode = strtolower(trim((string) ($meta['provider_mode'] ?? '')));

        if (!$snapshotProviderId || $snapshotProviderId < 1 || $snapshotType === '' || $snapshotMode === '') {
            return [
                'ok' => false,
                'reason' => 'renewal_provider_snapshot_missing',
                'message' => 'The renewal provider identity snapshot is missing for this order item.',
            ];
        }

        if ($domain->provider_id === null) {
            return [
                'ok' => false,
                'reason' => 'domain_provider_missing',
                'message' => 'The domain has no trusted provider identity configured.',
            ];
        }

        if ((int) $domain->provider_id !== $snapshotProviderId) {
            return [
                'ok' => false,
                'reason' => 'renewal_provider_domain_mismatch',
                'message' => "The renewal provider snapshot does not match the domain's current trusted provider.",
            ];
        }

        $provider = DomainProvider::query()->whereKey($snapshotProviderId)->first();

        if (!$provider instanceof DomainProvider) {
            return [
                'ok' => false,
                'reason' => 'renewal_provider_snapshot_missing',
                'message' => 'The trusted renewal provider no longer exists.',
            ];
        }

        if (!$provider->is_active) {
            return [
                'ok' => false,
                'reason' => 'renewal_provider_inactive',
                'message' => 'The trusted renewal provider is inactive.',
            ];
        }

        if ($snapshotType !== strtolower((string) $provider->type)) {
            return [
                'ok' => false,
                'reason' => 'renewal_provider_type_mismatch',
                'message' => 'The renewal provider type snapshot does not match the configured provider.',
            ];
        }

        if ($snapshotMode !== strtolower((string) $provider->mode)) {
            return [
                'ok' => false,
                'reason' => 'renewal_provider_mode_mismatch',
                'message' => 'The renewal provider mode snapshot does not match the configured provider.',
            ];
        }

        $domainRegistrar = strtolower((string) $domain->registrar);
        if ($domainRegistrar !== '' && $domainRegistrar !== strtolower((string) $provider->type)) {
            return [
                'ok' => false,
                'reason' => 'renewal_provider_domain_mismatch',
                'message' => "The domain's registrar value does not match its trusted provider — additional drift signal.",
            ];
        }

        return [
            'ok' => true,
            'provider' => $provider,
        ];
    }

    public function defaultProvider(): ?DomainProvider
    {
        return DomainProvider::query()
            ->active()
            ->whereIn('type', ['namecheap', 'enom'])
            ->orderByRaw("CASE WHEN type = 'namecheap' THEN 0 WHEN type = 'enom' THEN 1 ELSE 2 END")
            ->first();
    }

    /**
     * Resolve the registrar snapshot selected by DomainPricingService for a register OrderItem.
     * No default-provider fallback is permitted for new registrations.
     */
    protected function trustedRegistrationProvider(array $meta): array
    {
        $providerId = filter_var($meta['provider_id'] ?? null, FILTER_VALIDATE_INT);

        if (!$providerId || $providerId < 1) {
            return [
                'ok' => false,
                'message' => 'Trusted registrar identity is missing for this order item.',
            ];
        }

        $provider = DomainProvider::query()->whereKey($providerId)->first();

        if (!$provider instanceof DomainProvider || !$provider->is_active) {
            return [
                'ok' => false,
                'message' => 'The trusted registrar provider is missing or inactive.',
            ];
        }

        $snapshotType = strtolower(trim((string) ($meta['provider_type'] ?? '')));
        if ($snapshotType !== '' && $snapshotType !== strtolower((string) $provider->type)) {
            return [
                'ok' => false,
                'message' => 'The trusted registrar type does not match the configured provider.',
            ];
        }

        $snapshotMode = strtolower(trim((string) ($meta['provider_mode'] ?? '')));
        if ($snapshotMode !== '' && $snapshotMode !== strtolower((string) $provider->mode)) {
            return [
                'ok' => false,
                'message' => 'The trusted registrar mode does not match the configured provider.',
            ];
        }

        return [
            'ok' => true,
            'provider' => $provider,
        ];
    }

    protected function providerForDomain(Domain $domain, ?string $preferredType = null): ?DomainProvider
    {
        $types = array_values(array_filter([
            strtolower((string) $preferredType),
            strtolower((string) $domain->registrar),
            optional($this->defaultProvider())->type,
        ]));

        foreach ($types as $type) {
            $provider = DomainProvider::query()
                ->active()
                ->ofType($type)
                ->first();

            if ($provider instanceof DomainProvider) {
                return $provider;
            }
        }

        return null;
    }

    protected function attachDomainToOrderInvoices(Order $order, Domain $domain): void
    {
        $domainName = $this->normalizeDomain((string) $domain->domain_name);
        $escapedDomainName = addcslashes($domainName, '\\%_');

        foreach ($order->invoices as $invoice) {
            $invoice->items()
                ->where('item_type', 'domain')
                ->where('description', 'like', '%' . $escapedDomainName . '%')
                ->update(['reference_id' => $domain->id]);
        }
    }

    protected function normalizeDomain(string $fqdn): string
    {
        $fqdn = strtolower(trim(rtrim($fqdn, '.')));

        if (function_exists('idn_to_ascii')) {
            $ascii = @idn_to_ascii($fqdn, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

            if ($ascii) {
                $fqdn = strtolower($ascii);
            }
        }

        return $fqdn;
    }

    protected function splitDomainAscii(string $fqdn): array
    {
        $fqdn = $this->normalizeDomain($fqdn);

        if (!str_contains($fqdn, '.')) {
            return [null, null];
        }

        $parts = explode('.', $fqdn, 2);
        $sld = Str::of($parts[0] ?? '')->ascii()->trim()->value() ?: null;
        $tld = Str::of($parts[1] ?? '')->ascii()->trim()->value() ?: null;

        return [$sld, $tld];
    }

    protected function buildRegistrarContactPayload(Client $client): array
    {
        $first = $this->sanitizeContactValue($client->first_name, 'Client');
        $last = $this->sanitizeContactValue($client->last_name, 'User');
        $organization = $this->sanitizeContactValue($client->company_name ?? ($first . ' ' . $last), 'Palgooal Client', 64);
        $address = $this->sanitizeContactValue($client->address ?? '', 'Address Line 1', 60);
        $city = $this->sanitizeContactValue($client->city ?? '', 'City', 60);
        $state = $this->sanitizeContactValue($client->state ?? ($client->city ?? ''), 'State', 60);
        $postal = $this->sanitizeContactValue($client->zip_code ?? '', '00000', 15);
        $country = strtoupper($this->sanitizeContactValue($client->country ?? 'US', 'US', 2));
        $email = $this->sanitizeContactValue($client->email ?? '', 'support@example.com', 70);
        $phone = $this->formatRegistrarPhone($client->phone ?? '');

        return [
            'FirstName' => $first,
            'LastName' => $last,
            'OrganizationName' => $organization,
            'Address1' => $address,
            'City' => $city,
            'StateProvince' => $state,
            'PostalCode' => $postal,
            'Country' => $country,
            'EmailAddress' => $email,
            'Phone' => $phone,
        ];
    }

    protected function expandContactForNamecheap(array $contact): array
    {
        $roles = ['Registrant', 'Admin', 'Tech', 'AuxBilling'];
        $payload = [];

        foreach ($roles as $role) {
            foreach ($contact as $key => $value) {
                $payload[$role . $key] = $value;
            }
        }

        return $payload;
    }

    protected function expandContactForEnom(array $contact): array
    {
        $roles = ['Registrant', 'Admin', 'Tech', 'AuxBilling'];
        $payload = [];

        foreach ($roles as $role) {
            foreach ($contact as $key => $value) {
                $payload[$role . $key] = $value;
            }

            $payload[$role . 'Fax'] = '0000000000';
        }

        return $payload;
    }

    protected function sanitizeContactValue(?string $value, string $fallback, int $max = 63): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            $value = $fallback;
        }

        return Str::of($value)->ascii()->substr(0, $max)->value();
    }

    protected function formatRegistrarPhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (strlen($digits) < 4) {
            return '+1.5555555555';
        }

        $countryLength = max(1, strlen($digits) - 10);
        $country = substr($digits, 0, $countryLength);
        $number = substr($digits, -10);

        $country = ltrim($country, '0');

        if ($country === '') {
            $country = '1';
        }

        return '+' . $country . '.' . str_pad($number, 10, '0', STR_PAD_RIGHT);
    }

    protected function registerDomainWithProvider(DomainProvider $provider, Domain $domain, array $context, array $contact): array
    {
        try {
            if ($provider->type === 'namecheap') {
                $client = new NamecheapClient($provider);
                $params = array_merge(
                    [
                        'DomainName' => strtolower($domain->domain_name),
                        'Years' => $context['years'],
                        'AddFreeWhoisguard' => 'no',
                        'WhoisGuard' => 'no',
                    ],
                    $this->expandContactForNamecheap($contact)
                );

                $response = $client->callGeneric('namecheap.domains.create', $params);
                $identifiers = $this->providerIdentifiersFromResponse($response);

                if (!($response['ok'] ?? false)) {
                    return [
                        'ok' => false,
                        'reason' => $response['reason'] ?? 'provider_error',
                        'message' => $response['message'] ?? 'Registration failed with Namecheap.',
                        'cid' => $response['cid'] ?? null,
                        'provider_reference' => $identifiers['provider_reference'],
                        'provider_domain_id' => $identifiers['provider_domain_id'],
                        'http_code' => $response['http_code'] ?? null,
                        'code' => $response['code'] ?? null,
                        'definitive' => ($response['reason'] ?? null) === 'provider_error',
                    ];
                }

                return [
                    'ok' => true,
                    'reason' => 'ok',
                    'cid' => $response['cid'] ?? null,
                    'provider_reference' => $identifiers['provider_reference'],
                    'provider_domain_id' => $identifiers['provider_domain_id'],
                ];
            }

            if ($provider->type === 'enom') {
                $client = new EnomClient();
                [$sld, $tld] = $this->splitDomainAscii($domain->domain_name);

                if (!$sld || !$tld) {
                    return [
                        'ok' => false,
                        'reason' => 'invalid_domain',
                        'message' => 'Unable to split domain into SLD and TLD.',
                        'definitive' => true,
                    ];
                }

                $params = array_merge(
                    [
                        'command' => 'Purchase',
                        'SLD' => $sld,
                        'TLD' => $tld,
                        'NumYears' => $context['years'],
                        'UseDNS' => 'default',
                    ],
                    $this->expandContactForEnom($contact)
                );

                $response = $client->purchaseDomain($provider, $params);
                $identifiers = $this->providerIdentifiersFromResponse($response);

                if (!($response['ok'] ?? false)) {
                    return [
                        'ok' => false,
                        'reason' => $response['reason'] ?? 'provider_error',
                        'message' => $response['message'] ?? 'Registration failed with Enom.',
                        'cid' => $response['cid'] ?? null,
                        'provider_reference' => $identifiers['provider_reference'],
                        'provider_domain_id' => $identifiers['provider_domain_id'],
                        'http_code' => $response['http_code'] ?? null,
                        'code' => $response['code'] ?? null,
                        'definitive' => in_array(
                            $response['reason'] ?? null,
                            ['provider_error', 'provider_response', 'rrp_error'],
                            true
                        ),
                    ];
                }

                return [
                    'ok' => true,
                    'reason' => 'ok',
                    'cid' => $response['cid'] ?? null,
                    'provider_reference' => $identifiers['provider_reference'],
                    'provider_domain_id' => $identifiers['provider_domain_id'],
                ];
            }

            return [
                'ok' => false,
                'reason' => 'unsupported_provider',
                'message' => 'Unsupported registrar integration: ' . $provider->type,
                'definitive' => true,
            ];
        } catch (\Throwable $e) {
            Log::error('Registrar provisioning failed', [
                'provider_id' => $provider->id,
                'provider_type' => $provider->type,
                'domain' => $domain->domain_name,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'reason' => 'exception',
                'message' => 'Registrar error: ' . $e->getMessage(),
                'definitive' => false,
            ];
        }
    }

    protected function providerIdentifiersFromResponse(array $response): array
    {
        $providerReference = isset($response['provider_reference'])
            ? trim((string) $response['provider_reference'])
            : '';
        $providerDomainId = isset($response['provider_domain_id'])
            ? trim((string) $response['provider_domain_id'])
            : '';
        $xml = $response['xml'] ?? null;

        if ($xml instanceof \SimpleXMLElement) {
            $providerReference = $providerReference !== ''
                ? $providerReference
                : $this->findXmlIdentifier($xml, [
                    'transactionid',
                    'orderid',
                    'orderref',
                    'trackingkey',
                    'commandid',
                    'cid',
                ]);
            $providerDomainId = $providerDomainId !== ''
                ? $providerDomainId
                : $this->findXmlIdentifier($xml, ['domainid', 'domainnameid']);
        }

        return [
            'provider_reference' => $providerReference !== '' ? $providerReference : null,
            'provider_domain_id' => $providerDomainId !== '' ? $providerDomainId : null,
        ];
    }

    protected function findXmlIdentifier(\SimpleXMLElement $xml, array $candidateNames): string
    {
        $candidateNames = array_map('strtolower', $candidateNames);
        $nodes = array_merge([$xml], $xml->xpath('//*') ?: []);

        foreach ($nodes as $node) {
            if (in_array(strtolower($node->getName()), $candidateNames, true)) {
                $value = trim((string) $node);

                if ($value !== '') {
                    return $value;
                }
            }

            foreach ($node->attributes() as $name => $value) {
                if (in_array(strtolower((string) $name), $candidateNames, true)) {
                    $identifier = trim((string) $value);

                    if ($identifier !== '') {
                        return $identifier;
                    }
                }
            }
        }

        return '';
    }

    protected function renewDomainWithProvider(DomainProvider $provider, Domain $domain, array $context): array
    {
        try {
            if ($provider->type === 'namecheap') {
                $client = new NamecheapClient($provider);
                $response = $client->renewDomain($domain->domain_name, (int) $context['years']);

                if (!($response['ok'] ?? false)) {
                    return [
                        'ok' => false,
                        'message' => $response['message'] ?? 'Renewal failed with Namecheap.',
                        'cid' => $response['cid'] ?? null,
                    ];
                }

                return [
                    'ok' => true,
                    'cid' => $response['cid'] ?? null,
                ];
            }

            if ($provider->type === 'enom') {
                $client = new EnomClient();
                $response = $client->renewDomain($provider, $domain->domain_name, (int) $context['years']);

                if (!($response['ok'] ?? false)) {
                    return [
                        'ok' => false,
                        'message' => $response['message'] ?? 'Renewal failed with Enom.',
                        'cid' => $response['cid'] ?? null,
                    ];
                }

                return [
                    'ok' => true,
                    'cid' => $response['cid'] ?? null,
                ];
            }

            return [
                'ok' => false,
                'message' => 'Unsupported registrar integration: ' . $provider->type,
            ];
        } catch (\Throwable $e) {
            Log::error('Registrar renewal failed', [
                'provider_id' => $provider->id,
                'provider_type' => $provider->type,
                'domain' => $domain->domain_name,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'Registrar error: ' . $e->getMessage(),
            ];
        }
    }

    protected function resolveRenewableDomain(Order $order, string $domainName, ?int $domainId = null): ?Domain
    {
        return Domain::query()
            ->when($domainId, fn ($query) => $query->whereKey($domainId))
            ->where('client_id', $order->client_id)
            ->where('domain_name', $domainName)
            ->first();
    }
}
