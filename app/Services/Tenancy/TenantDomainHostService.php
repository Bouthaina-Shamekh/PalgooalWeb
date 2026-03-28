<?php

namespace App\Services\Tenancy;

use App\Models\Tenancy\Subscription;
use Illuminate\Support\Str;

class TenantDomainHostService
{
    public function ensureFallbackSubdomain(Subscription $subscription): ?string
    {
        $current = trim(strtolower((string) $subscription->subdomain));

        if ($current !== '') {
            return $current;
        }

        if ($subscription->domain_option === 'subdomain') {
            $domainName = trim(strtolower((string) $subscription->domain_name));
            $derived = trim(Str::before($domainName, '.'), ". \t\n\r\0\x0B");

            if ($derived !== '') {
                $subscription->forceFill([
                    'subdomain' => $derived,
                ])->save();

                return $derived;
            }
        }

        $generated = $this->generateSubdomain($subscription);

        $subscription->forceFill([
            'subdomain' => $generated,
        ])->save();

        return $generated;
    }

    public function generateSubdomain(Subscription $subscription): string
    {
        $maxLength = (int) config('tenancy.subdomain_max_length', 24);
        $base = $subscription->client?->company_name
            ?? $subscription->client?->first_name
            ?? $subscription->template?->translation()?->name
            ?? 'site';

        $base = Str::slug($base, '-');

        if ($base === '') {
            $base = 'site';
        }

        $base = Str::limit($base, max(8, $maxLength - 5), '');
        $attempt = 0;

        do {
            $suffix = $attempt === 0 ? '' : '-' . Str::lower(Str::random(4));
            $candidate = trim(Str::limit($base . $suffix, $maxLength, ''), '-');
            $attempt++;
        } while ($this->subdomainExists($candidate, $subscription) && $attempt < 10);

        if ($candidate === '' || $this->subdomainExists($candidate, $subscription)) {
            $candidate = Str::lower(Str::random(min(12, $maxLength)));
        }

        return $candidate;
    }

    protected function subdomainExists(string $candidate, ?Subscription $subscription = null): bool
    {
        $query = Subscription::query()->where('subdomain', $candidate);

        if ($subscription?->exists) {
            $query->whereKeyNot($subscription->getKey());
        }

        return $query->exists();
    }
}
