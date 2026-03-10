<?php

namespace App\Services\Domains;

use App\Models\Domain;
use App\Models\DomainProvider;
use App\Services\Domains\Clients\EnomClient;
use App\Services\Domains\Clients\NamecheapClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DomainDnsService
{
    public function buildEditorData(Domain $domain): array
    {
        $minNameservers = 2;
        $maxNameservers = 12;
        $remoteDns = $this->fetchRemoteDnsSnapshot($domain);

        $nameservers = array_values(array_filter(
            $remoteDns['nameservers'] ?? $domain->nameservers ?? [],
            fn ($value) => filled($value)
        ));

        $nameservers = array_slice($nameservers, 0, $maxNameservers);

        if (count($nameservers) < $minNameservers) {
            $nameservers = array_pad($nameservers, $minNameservers, '');
        }

        return [
            'remoteDns' => $remoteDns,
            'nameservers' => $nameservers,
            'minNameservers' => $minNameservers,
            'maxNameservers' => $maxNameservers,
        ];
    }

    public function updateDomainDns(Domain $domain, array $validated): array
    {
        $requestedNameservers = $this->normalizeNameservers($validated['nameservers'] ?? []);
        $nameserverIps = collect($validated['nameserver_ips'] ?? [])
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->values()
            ->all();

        if (count($requestedNameservers) < 2) {
            return [
                'ok' => false,
                'message' => 'Please provide at least two nameservers.',
            ];
        }

        $invalidNameservers = collect($requestedNameservers)
            ->reject(fn ($ns) => $this->isValidNameserver($ns))
            ->values();

        if ($invalidNameservers->isNotEmpty()) {
            return [
                'ok' => false,
                'message' => 'Invalid nameserver value(s): ' . $invalidNameservers->implode(', '),
            ];
        }

        $registrar = strtolower((string) $domain->registrar);
        if ($registrar === '') {
            return [
                'ok' => false,
                'message' => 'Domain registrar is not set. Assign a registrar before pushing DNS changes.',
            ];
        }

        $provider = DomainProvider::query()
            ->active()
            ->ofType($registrar)
            ->first();

        if (!$provider) {
            return [
                'ok' => false,
                'message' => 'No active provider configuration found for ' . $registrar . '.',
            ];
        }

        $childHostResult = $this->ensureSubordinateHostsReady($provider, $domain, $requestedNameservers, $nameserverIps);
        if (!($childHostResult['ok'] ?? false)) {
            return $childHostResult;
        }

        $syncResult = $this->pushNameserversToProvider($provider, $domain, $requestedNameservers);
        if (!($syncResult['ok'] ?? false)) {
            $message = $syncResult['message'] ?? 'Unable to update nameservers with the registrar.';

            if (!empty($syncResult['cid'])) {
                $message .= ' (cid: ' . $syncResult['cid'] . ')';
            }

            Log::warning('Domain DNS sync rejected by provider', [
                'domain_id' => $domain->id,
                'domain' => $domain->domain_name,
                'provider_id' => $provider->id,
                'provider_type' => $provider->type,
                'message' => $syncResult['message'] ?? null,
                'cid' => $syncResult['cid'] ?? null,
            ]);

            return [
                'ok' => false,
                'message' => $message,
            ];
        }

        $payload = [
            'nameservers' => $requestedNameservers,
            'dns_last_synced_at' => now(),
        ];

        if (array_key_exists('notes', $validated)) {
            $payload['dns_last_note'] = $validated['notes'];
        }

        $domain->forceFill($payload)->save();

        Log::info('Domain DNS synced with provider', [
            'domain_id' => $domain->id,
            'domain' => $domain->domain_name,
            'provider_id' => $provider->id,
            'provider_type' => $provider->type,
            'nameservers' => $requestedNameservers,
        ]);

        $providerName = $provider->name ?: $provider->type;

        return [
            'ok' => true,
            'message' => 'Nameservers updated and synced with ' . Str::title($providerName) . '.',
        ];
    }

    protected function ensureSubordinateHostsReady(
        DomainProvider $provider,
        Domain $domain,
        array $nameservers,
        array $nameserverIps
    ): array {
        if (strtolower((string) $provider->type) !== 'enom') {
            return ['ok' => true];
        }

        $domainName = $this->normalizeDomain($domain->domain_name);
        $client = new EnomClient();

        foreach (array_values($nameservers) as $index => $nameserver) {
            $host = strtolower(trim((string) $nameserver));

            if (!$this->isSubordinateHost($host, $domainName)) {
                continue;
            }

            $requestedIp = trim((string) ($nameserverIps[$index] ?? ''));

            if ($requestedIp === '') {
                return [
                    'ok' => false,
                    'message' => 'The nameserver ' . $host . ' belongs to the same domain and requires a glue record IP before it can be assigned.',
                ];
            }

            $status = $client->checkNameserverStatus($provider, $host);
            if (!($status['ok'] ?? false)) {
                return [
                    'ok' => false,
                    'message' => $status['message'] ?? ('Unable to verify glue record status for ' . $host . '.'),
                ];
            }

            if (!($status['exists'] ?? false)) {
                $registration = $client->registerNameserver($provider, $host, $requestedIp);

                if (!($registration['ok'] ?? false)) {
                    return [
                        'ok' => false,
                        'message' => $registration['message'] ?? ('Unable to register the glue record for ' . $host . '.'),
                    ];
                }

                continue;
            }

            $currentIp = trim((string) ($status['ip'] ?? ''));
            if ($currentIp !== '' && $currentIp !== $requestedIp) {
                $update = $client->updateNameserverIp($provider, $host, $currentIp, $requestedIp);

                if (!($update['ok'] ?? false)) {
                    return [
                        'ok' => false,
                        'message' => $update['message'] ?? ('Unable to update the glue record IP for ' . $host . '.'),
                    ];
                }
            }
        }

        return ['ok' => true];
    }

    protected function fetchRemoteDnsSnapshot(Domain $domain): array
    {
        $remoteDns = [
            'provider' => null,
            'status' => null,
            'nameservers' => [],
            'error' => null,
            'fetched_at' => null,
        ];

        $registrar = strtolower((string) $domain->registrar);
        if ($registrar === '') {
            $remoteDns['error'] = 'Domain registrar is not set.';

            return $remoteDns;
        }

        $provider = DomainProvider::query()
            ->active()
            ->ofType($registrar)
            ->first();

        if (!$provider) {
            $remoteDns['error'] = 'No active provider configuration found for ' . $registrar . '.';

            return $remoteDns;
        }

        $remoteDns['provider'] = $provider->type;

        try {
            if ($provider->type === 'enom') {
                $client = new EnomClient();
                $result = $client->getDns($provider, $domain->domain_name);

                if ($result['ok'] ?? false) {
                    $remoteDns['status'] = $result['use_dns'] ?? null;
                    $remoteDns['nameservers'] = $result['nameservers'] ?? [];

                    if (empty($remoteDns['nameservers']) && ($remoteDns['status'] ?? null) === 'default') {
                        $remoteDns['nameservers'] = [
                            'dns1.name-services.com',
                            'dns2.name-services.com',
                            'dns3.name-services.com',
                            'dns4.name-services.com',
                        ];
                    }
                } else {
                    $remoteDns['error'] = $result['message'] ?? 'Unable to fetch DNS state from registrar.';
                }
            } elseif ($provider->type === 'namecheap') {
                $client = new NamecheapClient($provider);
                $result = $client->getNameservers($domain->domain_name);

                if ($result['ok'] ?? false) {
                    $remoteDns['status'] = ($result['is_using_default'] ?? null) === true ? 'default' : 'custom';
                    $remoteDns['nameservers'] = $result['nameservers'] ?? [];

                    if (empty($remoteDns['nameservers']) && ($remoteDns['status'] ?? null) === 'default') {
                        $remoteDns['nameservers'] = [
                            'dns1.registrar-servers.com',
                            'dns2.registrar-servers.com',
                        ];
                    }
                } else {
                    $remoteDns['error'] = $result['message'] ?? 'Unable to fetch DNS state from registrar.';
                }
            } else {
                $remoteDns['error'] = 'Fetching DNS snapshot is not implemented for ' . Str::title($provider->type) . ' yet.';
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch registrar DNS snapshot', [
                'domain_id' => $domain->id,
                'domain' => $domain->domain_name,
                'provider_id' => $provider->id,
                'provider_type' => $provider->type,
                'error' => $e->getMessage(),
            ]);

            $remoteDns['error'] = 'Unable to contact registrar: ' . $e->getMessage();
        }

        $remoteDns['fetched_at'] = now();

        return $remoteDns;
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

    protected function isSubordinateHost(string $host, string $domainName): bool
    {
        return $host !== $domainName && str_ends_with($host, '.' . $domainName);
    }

    protected function normalizeNameservers(array $nameservers): array
    {
        return collect($nameservers ?? [])
            ->map(fn ($value) => strtolower(trim((string) $value)))
            ->map(fn ($ns) => rtrim($ns, '.'))
            ->filter(fn ($ns) => $ns !== '')
            ->unique()
            ->values()
            ->take(12)
            ->all();
    }

    protected function isValidNameserver(string $hostname): bool
    {
        return (bool) preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $hostname);
    }

    protected function pushNameserversToProvider(DomainProvider $provider, Domain $domain, array $nameservers): array
    {
        try {
            $type = strtolower((string) $provider->type);

            if ($type === 'namecheap') {
                $client = new NamecheapClient($provider);
                $response = $client->setCustomNameservers($domain->domain_name, $nameservers);

                if (!($response['ok'] ?? false)) {
                    return [
                        'ok' => false,
                        'message' => $response['message'] ?? 'Namecheap rejected the DNS update request.',
                        'cid' => $response['cid'] ?? null,
                    ];
                }

                return ['ok' => true, 'cid' => $response['cid'] ?? null];
            }

            if ($type === 'enom') {
                $client = new EnomClient();
                $response = $client->updateNameservers($provider, $this->normalizeDomain($domain->domain_name), $nameservers);

                if (!($response['ok'] ?? false)) {
                    return [
                        'ok' => false,
                        'message' => $response['message'] ?? 'Enom rejected the DNS update request.',
                        'cid' => $response['cid'] ?? null,
                    ];
                }

                return ['ok' => true, 'cid' => $response['cid'] ?? null];
            }

            return [
                'ok' => false,
                'message' => 'DNS sync is not yet implemented for ' . ($type ?: 'unknown') . '.',
            ];
        } catch (\Throwable $e) {
            Log::error('Domain DNS sync failed', [
                'domain_id' => $domain->id,
                'domain' => $domain->domain_name,
                'provider_id' => $provider->id,
                'provider_type' => $provider->type,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'Failed to sync with registrar: ' . $e->getMessage(),
            ];
        }
    }
}
