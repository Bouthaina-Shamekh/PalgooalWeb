<?php

namespace App\Services\Tenancy;

use App\Models\Plan;
use App\Models\Tenancy\Subscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class DomainVerificationService
{
    public function __construct(
        protected TenantDomainHostService $hostService,
    ) {
    }

    public function reset(Subscription $subscription, ?string $message = null): Subscription
    {
        $subscription->logDomainBindingMismatchIfNeeded();

        if (! $subscription->requiresDomainVerification()) {
            $subscription->forceFill([
                'domain_verification_status' => Subscription::DOMAIN_VERIFICATION_ACTIVE,
                'domain_last_checked_at' => now(),
                'domain_verified_at' => now(),
                'domain_verification_error' => null,
            ])->save();

            return $subscription->fresh();
        }

        $subscription->forceFill([
            'domain_verification_status' => Subscription::DOMAIN_VERIFICATION_PENDING,
            'domain_last_checked_at' => null,
            'domain_verified_at' => null,
            'domain_verification_error' => $message,
        ])->save();

        return $subscription->fresh();
    }

    public function verify(Subscription $subscription): array
    {
        $subscription->refresh();
        $subscription->logDomainBindingMismatchIfNeeded();

        if (! $subscription->requiresDomainVerification()) {
            $this->reset($subscription);

            return $this->detailsFor($subscription->fresh());
        }

        try {
            $this->hostService->ensureFallbackSubdomain($subscription);
            $subscription->refresh();

            $customDomain = $subscription->customDomainHost();
            $fallbackHost = $subscription->fallbackSiteHost();

            if ($customDomain === null || $fallbackHost === null) {
                return $this->storeStatus(
                    $subscription,
                    Subscription::DOMAIN_VERIFICATION_FAILED,
                    'A platform fallback subdomain is required before this custom domain can be verified.'
                );
            }

            $dnsCheck = $this->inspectDns($subscription, $customDomain, $fallbackHost);

            if (! ($dnsCheck['ok'] ?? false)) {
                return $this->storeStatus(
                    $subscription,
                    Subscription::DOMAIN_VERIFICATION_DNS_PENDING,
                    (string) ($dnsCheck['message'] ?? 'Custom domain DNS is still propagating.')
                );
            }

            $httpsCheck = $this->inspectHttps($subscription, $customDomain, $fallbackHost);

            if (! ($httpsCheck['ok'] ?? false)) {
                return $this->storeStatus(
                    $subscription,
                    Subscription::DOMAIN_VERIFICATION_SSL_PENDING,
                    (string) ($httpsCheck['message'] ?? 'Custom domain HTTPS is not ready yet.')
                );
            }

            return $this->storeStatus($subscription, Subscription::DOMAIN_VERIFICATION_ACTIVE, null);
        } catch (Throwable $exception) {
            return $this->storeStatus(
                $subscription,
                Subscription::DOMAIN_VERIFICATION_FAILED,
                $exception->getMessage()
            );
        }
    }

    public function detailsFor(Subscription $subscription): array
    {
        $subscription->logDomainBindingMismatchIfNeeded();

        $status = $subscription->effectiveDomainVerificationStatus();
        $meta = $this->statusMeta($status, $subscription->requiresDomainVerification());

        return [
            'mode' => $subscription->requiresDomainVerification() ? 'custom_domain' : 'platform_subdomain',
            'needs_verification' => $subscription->requiresDomainVerification(),
            'status' => $status,
            'label' => $meta['label'],
            'tone' => $meta['tone'],
            'custom_domain' => $subscription->customDomainHost(),
            'fallback_host' => $subscription->fallbackSiteHost(),
            'active_host' => $subscription->activeSiteHost(),
            'active_url' => $subscription->activeSiteUrl(),
            'last_checked_at' => $subscription->domain_last_checked_at,
            'verified_at' => $subscription->domain_verified_at,
            'error' => $subscription->domain_verification_error,
            'instructions' => $this->instructionsFor($subscription),
        ];
    }

    public function probePath(): string
    {
        return (string) config('tenancy.domain_verification.path', '/.well-known/palgoals-domain-check');
    }

    public function resolveProbeSubscription(string $host): ?Subscription
    {
        return Subscription::query()
            ->with('plan')
            ->where('domain_name', strtolower(trim($host)))
            ->where('status', 'active')
            ->first();
    }

    public function statusMeta(string $status, bool $needsVerification = true): array
    {
        if (! $needsVerification) {
            return [
                'label' => 'Platform subdomain active',
                'tone' => 'emerald',
            ];
        }

        return match ($status) {
            Subscription::DOMAIN_VERIFICATION_ACTIVE => ['label' => 'Custom domain active', 'tone' => 'emerald'],
            Subscription::DOMAIN_VERIFICATION_DNS_PENDING => ['label' => 'Verification pending (DNS not detected yet)', 'tone' => 'amber'],
            Subscription::DOMAIN_VERIFICATION_SSL_PENDING => ['label' => 'Waiting for HTTPS (SSL not ready)', 'tone' => 'sky'],
            Subscription::DOMAIN_VERIFICATION_FAILED => ['label' => 'Verification failed', 'tone' => 'red'],
            default => ['label' => 'Verification pending (DNS not detected yet)', 'tone' => 'amber'],
        };
    }

    protected function storeStatus(Subscription $subscription, string $status, ?string $message): array
    {
        $payload = [
            'domain_verification_status' => $status,
            'domain_last_checked_at' => now(),
            'domain_verification_error' => $message,
        ];

        if ($status === Subscription::DOMAIN_VERIFICATION_ACTIVE) {
            $payload['domain_verified_at'] = now();
            $payload['domain_verification_error'] = null;
        } else {
            $payload['domain_verified_at'] = null;
        }

        $subscription->forceFill($payload)->save();

        return $this->detailsFor($subscription->fresh());
    }

    protected function inspectDns(Subscription $subscription, string $customDomain, string $fallbackHost): array
    {
        $cnameTargets = array_map(
            fn ($record) => $this->normalizeHost((string) ($record['target'] ?? '')),
            $this->dnsRecords($customDomain, DNS_CNAME)
        );

        if (in_array($fallbackHost, array_filter($cnameTargets), true)) {
            return [
                'ok' => true,
                'message' => 'Custom domain CNAME already points to the platform fallback host.',
            ];
        }

        $actualIps = $this->resolveIpTargets($customDomain);
        $expectedIps = $this->referencePlatformIps($subscription, $fallbackHost);

        if ($actualIps !== [] && $expectedIps !== [] && array_intersect($actualIps, $expectedIps) !== []) {
            return [
                'ok' => true,
                'message' => 'Custom domain A/AAAA records match the platform targets.',
            ];
        }

        return [
            'ok' => false,
            'message' => 'Point this custom domain to ' . $fallbackHost . ' using CNAME, ALIAS/ANAME, or matching A/AAAA records.',
        ];
    }

    protected function inspectHttps(Subscription $subscription, string $customDomain, string $fallbackHost): array
    {
        try {
            $response = Http::acceptJson()
                ->connectTimeout((float) config('tenancy.domain_verification.connect_timeout', 4))
                ->timeout((float) config('tenancy.domain_verification.timeout', 8))
                ->withUserAgent('PalGoals Domain Verification')
                ->get('https://' . $customDomain . $this->probePath(), [
                    'subscription' => $subscription->getKey(),
                ]);
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'message' => 'HTTPS probe could not reach ' . $customDomain . ': ' . $exception->getMessage(),
            ];
        }

        if (! $response->successful()) {
            return [
                'ok' => false,
                'message' => 'HTTPS probe returned HTTP ' . $response->status() . ' for ' . $customDomain . '.',
            ];
        }

        $payload = $response->json();

        if (! is_array($payload) || ($payload['status'] ?? null) !== 'ok') {
            return [
                'ok' => false,
                'message' => 'HTTPS probe reached the host but returned an unexpected response.',
            ];
        }

        return [
            'ok' => true,
            'message' => 'HTTPS probe reached Laravel through the expected custom domain.',
        ];
    }

    protected function instructionsFor(Subscription $subscription): array
    {
        if (! $subscription->requiresDomainVerification()) {
            return [];
        }

        $customDomain = $subscription->customDomainHost();
        $fallbackHost = $subscription->fallbackSiteHost();

        if ($customDomain === null || $fallbackHost === null) {
            return [];
        }

        return [
            'target_host' => $fallbackHost,
            'platform_ips' => $this->referencePlatformIps($subscription, $fallbackHost),
            'probe_path' => $this->probePath(),
            'summary' => 'Point the custom domain to the platform fallback host, wait for DNS to propagate, then retry verification.',
        ];
    }

    protected function referencePlatformIps(Subscription $subscription, string $fallbackHost): array
    {
        $hosts = array_unique(array_filter([
            $fallbackHost,
            tenant_domain(),
            strtolower((string) config('tenancy.primary_domain', '')),
            strtolower((string) (parse_url(config('app.url'), PHP_URL_HOST) ?: '')),
        ]));

        $ips = [];

        foreach ($hosts as $host) {
            $ips = array_merge($ips, $this->resolveIpTargets($host));
        }

        $ips = array_values(array_unique(array_filter($ips)));
        sort($ips);

        return $ips;
    }

    protected function resolveIpTargets(string $host): array
    {
        $records = [];

        foreach ($this->dnsRecords($host, DNS_A) as $record) {
            $value = trim((string) ($record['ip'] ?? ''));

            if ($value !== '') {
                $records[] = $value;
            }
        }

        if (defined('DNS_AAAA')) {
            foreach ($this->dnsRecords($host, DNS_AAAA) as $record) {
                $value = trim((string) ($record['ipv6'] ?? ''));

                if ($value !== '') {
                    $records[] = Str::lower($value);
                }
            }
        }

        if ($records === [] && function_exists('gethostbynamel')) {
            $fallback = @gethostbynamel($host);

            if (is_array($fallback)) {
                $records = array_merge($records, $fallback);
            }
        }

        $records = array_values(array_unique(array_filter($records)));
        sort($records);

        return $records;
    }

    protected function dnsRecords(string $host, int $type): array
    {
        if (! function_exists('dns_get_record')) {
            return [];
        }

        try {
            $records = @dns_get_record($host, $type);

            return is_array($records) ? $records : [];
        } catch (Throwable) {
            return [];
        }
    }

    protected function normalizeHost(string $value): string
    {
        return strtolower(trim($value, ". \t\n\r\0\x0B"));
    }
}
