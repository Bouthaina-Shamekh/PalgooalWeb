<?php

namespace App\Http\Controllers\Dashboard\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDomainRequest;
use App\Http\Requests\UpdateDomainRequest;
use App\Http\Requests\UpdateDomainDnsRequest;
use App\Models\Client;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\DomainProvider;
use App\Services\DomainProviders\EnomClient;
use App\Services\DomainProviders\NamecheapClient;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    /** قائمة النطاقات */
    public function index()
    {
        $domains = Domain::latest()->paginate(10);
        return view('dashboard.management.domains.index', compact('domains'));
    }

    /** فورم إنشاء */
    public function create()
    {
        $clients = Client::all();
        $domain = new Domain();
        return view('dashboard.management.domains.create', compact('clients', 'domain'));
    }

    /** حفظ إنشاء */
    public function store(StoreDomainRequest $request)
    {
        $data = $request->validated();

        // تطبيع اسم النطاق
        $data['domain_name'] = $this->normalizeDomain($data['domain_name']);

        $price_cents = 0;

        DB::transaction(function () use ($data, $price_cents) {
            $domain = Domain::create($data);

            // امنع إنشاء فاتورة مزدوجة غير مدفوعة لنفس النطاق
            $existingUnpaid = Invoice::where('client_id', $data['client_id'])
                ->where('status', 'unpaid')
                ->whereHas('items', fn($q) => $q->where('item_type', 'domain')->where('reference_id', $domain->id))
                ->lockForUpdate()
                ->first();

            if (!$existingUnpaid) {
                $dueDate = !empty($data['renewal_date'])
                    ? Carbon::parse($data['renewal_date'])
                    : now()->addDays(7);

                $invoice = Invoice::create([
                    'client_id' => $data['client_id'],
                    'number' => sprintf('INV-%s', strtoupper(Str::uuid()->toString())),
                    'status' => 'unpaid',
                    'subtotal_cents' => $price_cents,
                    'total_cents' => $price_cents,
                    'currency' => 'USD',
                    'due_date' => $dueDate,
                ]);

                $invoice->items()->create([
                    'item_type' => 'domain',
                    'reference_id' => $domain->id,
                    'description' => 'تسجيل النطاق: ' . $domain->domain_name,
                    'qty' => 1,
                    'unit_price_cents' => $price_cents,
                    'total_cents' => $price_cents,
                ]);
            }
        });

        return redirect()->route('dashboard.domains.index')->with('success', 'تم إنشاء الدومين بنجاح');
    }

    /** فورم تعديل */
    public function edit(Domain $domain)
    {
        $clients = Client::all();
        return view('dashboard.management.domains.edit', compact('domain', 'clients'));
    }

    /** حفظ تعديل */
    public function update(UpdateDomainRequest $request, Domain $domain)
    {
        $data = $request->validated();
        $data['domain_name'] = $this->normalizeDomain($data['domain_name']);

        DB::transaction(function () use ($domain, $data) {
            $domain->update($data);

            // تحديث وصف أول بند فاتورة مرتبط
            $invoiceItem = $domain->invoiceItems()->first();
            if ($invoiceItem) {
                $invoiceItem->update([
                    'description' => 'تحديث النطاق: ' . $domain->domain_name,
                ]);
            }
        });

        return redirect()->route('dashboard.domains.index')->with('success', 'تم تحديث الدومين بنجاح');
    }

    /** حذف */
    public function destroy(Domain $domain)
    {
        $domain->delete();
        return redirect()->route('dashboard.domains.index')->with('success', 'تم حذف الدومين بنجاح');
    }

    /** فورم إجراءات التسجيل */
    public function editRegister(Domain $domain)
    {
        $registrarOptions = [
            'enom' => 'enom',
            'namecheap' => 'namecheap',
        ];

        return view('dashboard.management.domains.register', [
            'domain' => $domain,
            'registrarOptions' => $registrarOptions,
        ]);
    }

    /** تنفيذ التسجيل مع المزود */
    public function updateRegister(Request $request, Domain $domain)
    {
        $validated = $request->validate([
            'registrar' => ['required', 'string', 'max:255'],
            'registration_date' => ['required', 'date'],
            'renewal_date' => ['required', 'date', 'after_or_equal:registration_date'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // تطبيع اسم النطاق قبل التعامل مع المزود
        $domain->domain_name = $this->normalizeDomain($domain->domain_name);

        $client = $domain->client;
        if (!$client) {
            return back()->withErrors([
                'client_id' => __('Domain does not have an associated client. Please assign a client first.'),
            ]);
        }

        $provider = DomainProvider::query()
            ->active()
            ->ofType(strtolower($validated['registrar']))
            ->first();

        if (!$provider) {
            return back()->withErrors([
                'registrar' => __('No active provider configuration found for :registrar.', ['registrar' => $validated['registrar']]),
            ]);
        }

        $registrationDate = Carbon::parse($validated['registration_date']);
        $renewalDate      = Carbon::parse($validated['renewal_date']);
        $years = max(1, (int) ceil($registrationDate->diffInDays($renewalDate) / 365));

        $contact = $this->buildRegistrarContactPayload($client);

        $result = $this->registerDomainWithProvider(
            $provider,
            $domain,
            [
                'years' => $years,
                'registration_date' => $registrationDate,
                'renewal_date' => $renewalDate,
                'notes' => $validated['notes'] ?? null,
            ],
            $contact
        );

        if (!($result['ok'] ?? false)) {
            $message = $result['message'] ?? __('Unable to complete registration with the external provider.');
            if (!empty($result['cid'])) $message .= " (cid: {$result['cid']})";
            return back()->withInput()->withErrors(['registrar' => $message]);
        }

        $domain->update([
            'registrar' => strtolower($validated['registrar']),
            'registration_date' => $registrationDate->toDateString(),
            'renewal_date'      => $renewalDate->toDateString(),
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('dashboard.domains.index')
            ->with('success', __('Domain registered successfully via :provider.', ['provider' => Str::title($provider->type)]));
    }

    /** فورم التجديد */
    public function editRenew(Domain $domain)
    {
        $currentRenewal = $domain->renewal_date ? Carbon::parse($domain->renewal_date) : null;
        $suggestedRenewal = ($currentRenewal ?? now())->copy()->addYear()->format('Y-m-d');
        $renewalDateValue = ($currentRenewal ?? now())->format('Y-m-d');

        $statusOptions = [
            'active'  => __('Active'),
            'pending' => __('Pending'),
            'expired' => __('Expired'),
        ];

        return view('dashboard.management.domains.renew', [
            'domain' => $domain,
            'currentRenewal' => $renewalDateValue,
            'suggestedRenewal' => $suggestedRenewal,
            'statusOptions' => $statusOptions,
        ]);
    }

    /** حفظ التجديد (Placeholder) */
    public function updateRenew(Request $request, Domain $domain)
    {
        $minimumRenewalDate = $domain->renewal_date
            ? Carbon::parse($domain->renewal_date)->format('Y-m-d')
            : now()->format('Y-m-d');

        $validated = $request->validate([
            'renewal_date'   => ['required', 'date', 'after_or_equal:' . $minimumRenewalDate],
            'status'         => ['required', 'string', 'max:50'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        $domain->update([
            'renewal_date'   => $validated['renewal_date'],
            'status'         => $validated['status'],
            'payment_method' => $validated['payment_method'] ?? $domain->payment_method,
        ]);

        // @todo: إنشاء فاتورة/عملية دفع للتجديد

        return redirect()
            ->route('dashboard.domains.index')
            ->with('success', __('Domain renewal saved. Automation with registrar pending.'));
    }

    /** DNS: فورم */
    public function editDns(Domain $domain)
    {
        $nameserversRaw = $domain->nameservers ?? [];

        if (is_string($nameserversRaw)) {
            $decoded = json_decode($nameserversRaw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $nameserversRaw = $decoded;
            }
        }

        if (!is_array($nameserversRaw)) {
            $nameserversRaw = [];
        }

        $nameservers = array_pad(
            array_values(array_filter($nameserversRaw, fn($value) => filled($value))),
            2,
            ''
        );

        return view('dashboard.management.domains.dns', [
            'domain' => $domain,
            'nameservers' => $nameservers,
        ]);
    }

    /** DNS: حفظ + دفع للمسجل */
    public function updateDns(UpdateDomainDnsRequest $request, Domain $domain)
    {
        $validated = $request->validated();

        $requestedNameservers = $this->normalizeNameservers($validated['nameservers'] ?? []);

        if (count($requestedNameservers) < 2) {
            return back()->withInput()
                ->withErrors(['nameservers' => __('Please provide at least two nameservers.')]);
        }

        $invalidNameservers = collect($requestedNameservers)
            ->reject(fn($ns) => $this->isValidNameserver($ns))
            ->values();

        if ($invalidNameservers->isNotEmpty()) {
            return back()->withInput()
                ->withErrors([
                    'nameservers' => __('Invalid nameserver value(s): :nameservers', [
                        'nameservers' => $invalidNameservers->implode(', '),
                    ]),
                ]);
        }

        $registrar = strtolower((string) $domain->registrar);
        if ($registrar === '') {
            return back()->withInput()
                ->withErrors(['nameservers' => __('Domain registrar is not set. Assign a registrar before pushing DNS changes.')]);
        }

        $provider = DomainProvider::query()
            ->active()
            ->ofType($registrar)
            ->first();

        if (!$provider) {
            return back()->withInput()
                ->withErrors(['nameservers' => __('No active provider configuration found for :registrar.', ['registrar' => $registrar])]);
        }

        $syncResult = $this->pushNameserversToProvider($provider, $domain, $requestedNameservers);
        if (!($syncResult['ok'] ?? false)) {
            $message = $syncResult['message'] ?? __('Unable to update nameservers with the registrar.');
            if (!empty($syncResult['cid'])) $message .= " (cid: {$syncResult['cid']})";
            return back()->withInput()->withErrors(['nameservers' => $message]);
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

        return back()->with('success', __('Nameservers updated and synced with :provider.', [
            'provider' => Str::title($providerName),
        ]));
    }

    /** ————— Helpers ————— */

    protected function normalizeDomain(string $fqdn): string
    {
        $fqdn = strtolower(trim(rtrim($fqdn, '.')));
        if (function_exists('idn_to_ascii')) {
            $ascii = @idn_to_ascii($fqdn, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($ascii) $fqdn = strtolower($ascii);
        }
        return $fqdn;
    }

    protected function splitDomainAscii(string $fqdn): array
    {
        $fqdn = $this->normalizeDomain($fqdn);
        if (!str_contains($fqdn, '.')) return [null, null];
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
        if ($value === '') $value = $fallback;
        return Str::of($value)->ascii()->substr(0, $max)->value();
    }

    protected function formatRegistrarPhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (strlen($digits) < 4) return '+1.5555555555';

        $countryLength = max(1, strlen($digits) - 10);
        $country = substr($digits, 0, $countryLength);
        $number = substr($digits, -10);

        $country = ltrim($country, '0');
        if ($country === '') $country = '1';

        return '+' . $country . '.' . str_pad($number, 10, '0', STR_PAD_RIGHT);
    }

    protected function normalizeNameservers(array $nameservers): array
    {
        return collect($nameservers ?? [])
            ->map(fn($value) => strtolower(trim((string) $value)))
            ->map(fn($ns) => rtrim($ns, '.'))
            ->filter(fn($ns) => $ns !== '')
            ->unique()
            ->values()
            ->take(12)
            ->all();
    }

    protected function isValidNameserver(string $hostname): bool
    {
        return (bool) preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $hostname);
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
                    return ['ok' => false, 'message' => $response['message'] ?? __('Registration failed with Namecheap.'), 'cid' => $response['cid'] ?? null];
                }

                return ['ok' => true, 'cid' => $response['cid'] ?? null];
            }

            if ($provider->type === 'enom') {
                $client = new EnomClient();
                [$sld, $tld] = $this->splitDomainAscii($domain->domain_name);
                if (!$sld || !$tld) {
                    return ['ok' => false, 'message' => __('Unable to split domain into SLD and TLD.')];
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
                    return ['ok' => false, 'message' => $response['message'] ?? __('Registration failed with Enom.'), 'cid' => $response['cid'] ?? null];
                }

                return ['ok' => true, 'cid' => $response['cid'] ?? null];
            }

            return ['ok' => false, 'message' => __('Unsupported registrar integration: :provider', ['provider' => $provider->type])];
        } catch (\Throwable $e) {
            Log::error('Domain registration failed', [
                'provider_id' => $provider->id,
                'provider_type' => $provider->type,
                'domain' => $domain->domain_name,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'message' => __('Registrar error: :message', ['message' => $e->getMessage()])];
        }
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
                        'message' => $response['message'] ?? __('Namecheap rejected the DNS update request.'),
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
                        'message' => $response['message'] ?? __('Enom rejected the DNS update request.'),
                        'cid' => $response['cid'] ?? null,
                    ];
                }

                return ['ok' => true, 'cid' => $response['cid'] ?? null];
            }

            return [
                'ok' => false,
                'message' => __('DNS sync is not yet implemented for :provider.', ['provider' => $type ?: 'unknown']),
            ];
        } catch (\Throwable $e) {
            Log::error('Domain DNS sync failed', [
                'domain_id' => $domain->id,
                'domain'    => $domain->domain_name,
                'provider_id' => $provider->id,
                'provider_type' => $provider->type,
                'error'     => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => __('Failed to sync with registrar: :message', ['message' => $e->getMessage()]),
            ];
        }
    }
}
