<?php

namespace App\Services\Domains;

use App\Models\Client;
use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\Order;
use App\Services\Domains\Clients\EnomClient;
use App\Services\Domains\Clients\NamecheapClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RegistrarProvisioningService
{
    public function provisionOrderDomain(Order $order, ?string $paymentMethod = null): array
    {
        $order->loadMissing(['client', 'items', 'invoices.items']);

        $orderItem = $order->items->first(function ($item) {
            return filled($item->domain)
                && in_array(strtolower((string) $item->item_option), ['register', 'renew'], true);
        });

        if (!$orderItem) {
            return [
                'ok' => true,
                'skipped' => true,
                'message' => 'No renewable or registerable domain item was found for this order.',
            ];
        }

        $action = strtolower((string) $orderItem->item_option);

        if ($action === 'renew') {
            return $this->renewOrderDomain($order, $orderItem, $paymentMethod);
        }

        $client = $order->client;

        if (!$client instanceof Client) {
            return [
                'ok' => false,
                'message' => 'Order client is missing. Unable to register the domain automatically.',
            ];
        }

        $provider = $this->defaultProvider();

        if (!$provider instanceof DomainProvider) {
            return [
                'ok' => false,
                'message' => 'No active registrar provider is configured. Expected Namecheap as the default provider.',
            ];
        }

        $domainName = $this->normalizeDomain((string) $orderItem->domain);
        $meta = is_array($orderItem->meta) ? $orderItem->meta : [];

        $registrationDate = Carbon::parse($meta['registration_date'] ?? now()->toDateString());
        $renewalDate = Carbon::parse($meta['renewal_date'] ?? $registrationDate->copy()->addYear()->toDateString());
        $years = max(1, (int) ceil(max(1, $registrationDate->diffInDays($renewalDate)) / 365));

        $domain = Domain::firstOrNew([
            'domain_name' => $domainName,
        ]);

        $domain->fill([
            'client_id' => $order->client_id,
            'registrar' => $provider->type,
            'registration_date' => $registrationDate->toDateString(),
            'renewal_date' => $renewalDate->toDateString(),
            'status' => 'pending',
            'payment_method' => $paymentMethod ?: $domain->payment_method,
        ]);

        if ($domain->exists && $domain->status === 'active' && strtolower((string) $domain->registrar) === strtolower($provider->type)) {
            $this->attachDomainToOrderInvoices($order, $domain);

            return [
                'ok' => true,
                'provider' => $provider,
                'domain' => $domain,
                'message' => 'Domain was already active with the default registrar.',
                'skipped' => true,
            ];
        }

        $domain->dns_last_note = null;
        $domain->save();

        $contact = $this->buildRegistrarContactPayload($client);

        $registration = $this->registerDomainWithProvider($provider, $domain, [
            'years' => $years,
            'registration_date' => $registrationDate,
            'renewal_date' => $renewalDate,
        ], $contact);

        if (!($registration['ok'] ?? false)) {
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

        $provider = $this->providerForDomain($domain, $meta['registrar'] ?? null);

        if (!$provider instanceof DomainProvider) {
            return [
                'ok' => false,
                'domain' => $domain,
                'message' => 'No active registrar provider is configured for the domain renewal request.',
            ];
        }

        $currentRenewalDate = Carbon::parse($meta['current_renewal_date'] ?? $domain->renewal_date ?? now()->toDateString());
        $renewalDate = Carbon::parse($meta['renewal_date'] ?? $currentRenewalDate->copy()->addYear()->toDateString());
        $years = max(1, (int) ($meta['term_years'] ?? ceil(max(1, $currentRenewalDate->diffInDays($renewalDate)) / 365)));

        $renewal = $this->renewDomainWithProvider($provider, $domain, [
            'years' => $years,
            'current_renewal_date' => $currentRenewalDate,
            'renewal_date' => $renewalDate,
        ]);

        if (!($renewal['ok'] ?? false)) {
            $domain->forceFill([
                'status' => $domain->status ?: 'active',
                'registrar' => $provider->type,
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
            'registrar' => $provider->type,
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

    public function defaultProvider(): ?DomainProvider
    {
        return DomainProvider::query()
            ->active()
            ->whereIn('type', ['namecheap', 'enom'])
            ->orderByRaw("CASE WHEN type = 'namecheap' THEN 0 WHEN type = 'enom' THEN 1 ELSE 2 END")
            ->first();
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
        foreach ($order->invoices as $invoice) {
            $invoice->items()
                ->where('item_type', 'domain')
                ->where(function ($query) use ($domain) {
                    $query->whereNull('reference_id')
                        ->orWhere('reference_id', $domain->id)
                        ->orWhere('description', 'like', '%' . $domain->domain_name . '%');
                })
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

                if (!($response['ok'] ?? false)) {
                    return [
                        'ok' => false,
                        'message' => $response['message'] ?? 'Registration failed with Namecheap.',
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
                [$sld, $tld] = $this->splitDomainAscii($domain->domain_name);

                if (!$sld || !$tld) {
                    return [
                        'ok' => false,
                        'message' => 'Unable to split domain into SLD and TLD.',
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

                if (!($response['ok'] ?? false)) {
                    return [
                        'ok' => false,
                        'message' => $response['message'] ?? 'Registration failed with Enom.',
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
            Log::error('Registrar provisioning failed', [
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
