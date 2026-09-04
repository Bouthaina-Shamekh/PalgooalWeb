<?php

namespace App\Http\Controllers\Admin\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDomainRequest;
use App\Http\Requests\UpdateDomainRequest;
use App\Http\Requests\UpdateDomainDnsRequest;
use App\Models\Client;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\DomainProvider;
use App\Services\Domains\Clients\EnomClient;
use App\Services\Domains\Clients\NamecheapClient;
use App\Services\Domains\DomainRenewalService;
use App\Services\Domains\Exceptions\MissingDomainProviderException;
use App\Services\Domains\Exceptions\MissingRenewalPriceException;
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
        $this->authorize('viewAny', Domain::class);
        $domains = Domain::latest()->paginate(10);
        return view('dashboard.management.domains.index', compact('domains'));
    }

    /** فورم إنشاء */
    public function create()
    {
        $this->authorize('create', Domain::class);
        $clients = Client::all();
        $domain = new Domain();
        return view('dashboard.management.domains.create', compact('clients', 'domain'));
    }

    /**
     * حفظ إنشاء
     *
     * TLD-3E.4A — Formalize External-Only Admin Create Contract.
     *
     * Admin Create produces External/Unmanaged domains ONLY. provider_id and auto_renew are
     * NOT accepted from the request at all (StoreDomainRequest has no rule for either key, so
     * FormRequest::validated() already strips them before this method ever runs) — the explicit
     * assignments below are a defense-in-depth restatement of that contract, not a new
     * restriction: they guarantee provider_id stays null and auto_renew stays false even if a
     * future change to the request/form ever adds those keys, or a forged raw request body
     * contains them. No provider is resolved or looked up here, and registrar is left exactly
     * as submitted (display/legacy metadata only — TLD-3E.4B territory). The only route from
     * External to Managed remains the existing Admin Register success path (TLD-3E.1/TLD-3E.2).
     */
    public function store(StoreDomainRequest $request)
    {
        $this->authorize('create', Domain::class);
        $data = $request->validated();

        // TLD-3E.4A: Admin Create can never produce a Managed or auto-renewing domain.
        $data['provider_id'] = null;
        $data['auto_renew'] = false;

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
        $this->authorize('update', $domain);
        $clients = Client::all();
        return view('dashboard.management.domains.edit', compact('domain', 'clients'));
    }

    /** حفظ تعديل */
    public function update(UpdateDomainRequest $request, Domain $domain)
    {
        $this->authorize('update', $domain);
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
        $this->authorize('delete', $domain);
        $domain->delete();
        return redirect()->route('dashboard.domains.index')->with('success', 'تم حذف الدومين بنجاح');
    }

    /** فورم إجراءات التسجيل */
    public function editRegister(Domain $domain)
    {
        $this->authorize('update', $domain);

        // TLD-3E.2 — Admin Register Exact Provider Selection: pass the exact active
        // DomainProvider rows (id/name/type/mode) so the view can offer an unambiguous
        // provider_id select instead of a bare registrar-type string.
        $providers = DomainProvider::query()
            ->active()
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'mode']);

        return view('dashboard.management.domains.register', [
            'domain' => $domain,
            'providers' => $providers,
        ]);
    }

    /** تنفيذ التسجيل مع المزود */
    public function updateRegister(Request $request, Domain $domain)
    {
        $this->authorize('update', $domain);
        // TLD-3E.2 — Admin Register Exact Provider Selection: the admin now selects an exact
        // DomainProvider row (provider_id), never a bare registrar-type string.
        $validated = $request->validate([
            'provider_id' => ['required', 'integer', 'exists:domain_providers,id'],
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

        // TLD-3E.2 — resolve ONLY the exact selected provider_id — never ofType()->first(), never
        // a type-based lookup, never defaultProvider(), never a fallback of any kind.
        $provider = DomainProvider::query()->active()->find($validated['provider_id']);

        if (!$provider) {
            return back()->withInput()->withErrors([
                'provider_id' => __('The selected provider is inactive or no longer exists.'),
            ]);
        }

        // TLD-3E.2 — Existing Managed Domain Safety: a domain that already has its own trusted
        // provider_id can only be (re-)registered through THAT SAME provider from this screen.
        // Registering it through a different provider_id would be a provider reassignment /
        // transfer, which is explicitly out of scope for this phase — no transfer semantics, no
        // silent provider switch. An external/unmanaged domain (provider_id null) is unaffected
        // and may register through any active provider, transitioning to managed on success.
        if ($domain->provider_id !== null && (int) $domain->provider_id !== (int) $provider->getKey()) {
            return back()->withInput()->withErrors([
                'provider_id' => __('This domain is already managed by a different provider. Switching providers is not supported from this screen.'),
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

        // TLD-3E.1 — Admin Register Exact Provider Identity: persist the EXACT $provider
        // instance already used for the (successful) registrar API call above — never
        // re-resolved by type after the call. This is the only point where an Admin-registered
        // Domain transitions from external/unmanaged (provider_id null) to managed, and it must
        // only happen after the registrar API confirmed success (the failure branch above
        // returns before reaching this point and never touches the Domain).
        $domain->update([
            'provider_id' => $provider->getKey(),
            'registrar' => $provider->type,
            'registration_date' => $registrationDate->toDateString(),
            'renewal_date'      => $renewalDate->toDateString(),
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('dashboard.domains.index')
            ->with('success', __('Domain registered successfully via :provider.', ['provider' => Str::title($provider->type)]));
    }

    /**
     * TLD-3E.3A — Replace Admin Renew Placeholder with Trusted Renewal Invoice Flow.
     *
     * This is now a trusted renewal SUMMARY/confirmation screen — never a direct Domain-record
     * editor. It shows the domain's current renewal date and its exact managed provider
     * identity (Domain.provider_id → DomainProvider, never Domain.registrar), and whether a
     * pending renewal invoice already exists (via DomainRenewalService::findPendingRenewalInvoice(),
     * a read-only query — no pricing logic is duplicated here). No renewal price is previewed:
     * DomainRenewalService::buildRenewalQuote() is protected and intentionally not exposed or
     * duplicated in this phase (correctness over a price preview, per TLD-3E.3A section 5).
     */
    public function editRenew(Domain $domain, DomainRenewalService $renewals)
    {
        $this->authorize('update', $domain);

        $domain->loadMissing('provider');

        $hasPendingInvoice = $domain->provider_id !== null
            && $renewals->findPendingRenewalInvoice($domain) !== null;

        return view('dashboard.management.domains.renew', [
            'domain' => $domain,
            'currentRenewal' => $domain->renewal_date
                ? Carbon::parse($domain->renewal_date)->format('Y-m-d')
                : null,
            'provider' => $domain->provider,
            'hasPendingInvoice' => $hasPendingInvoice,
        ]);
    }

    /**
     * TLD-3E.3A — Replace Admin Renew Placeholder with Trusted Renewal Invoice Flow.
     *
     * Admin renewal no longer directly mutates Domain.renewal_date / Domain.status /
     * Domain.payment_method, no longer accepts a price/provider/date/status/payment_method from
     * the request, and never calls the registrar API here. It invokes the SAME trusted,
     * exact-provider-identity, sale-only pipeline the client renewal flow uses
     * (DomainRenewalService::prepareRenewalCheckout()) and redirects to the existing Admin
     * invoice view for the resulting/reused Invoice — payment/settlement and registrar
     * provisioning happen later, through the existing OrderActivationService /
     * RegistrarProvisioningService pipeline, exactly as they do for the client flow.
     */
    public function updateRenew(Request $request, Domain $domain, DomainRenewalService $renewals)
    {
        $this->authorize('update', $domain);

        try {
            $checkout = $renewals->prepareRenewalCheckout($domain);
        } catch (MissingDomainProviderException $e) {
            Log::warning('Admin renewal blocked: domain has no trusted managed provider identity.', [
                'domain_id' => $domain->id,
                'domain' => $domain->domain_name,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'renewal' => __('لا يمكن تجديد هذا النطاق عبر المنصة لأنه غير مرتبط بمزوّد مُدار.'),
            ]);
        } catch (MissingRenewalPriceException $e) {
            Log::warning('Admin renewal blocked: no trusted renewal sale price.', [
                'domain_id' => $domain->id,
                'domain' => $domain->domain_name,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'renewal' => __('تعذر تجديد هذا النطاق حالياً: لا يوجد سعر تجديد معتمد لهذا النطاق.'),
            ]);
        }

        $invoice = $checkout['invoice'];

        return redirect()
            ->route('dashboard.invoices.show', $invoice)
            ->with('success', $checkout['created']
                ? __('Renewal invoice created. Settle the invoice to renew the domain with the registrar.')
                : __('An existing pending renewal invoice was found. Settle it to renew the domain.'));
    }

    /** DNS: فورم */
    public function editDns(Domain $domain)
    {
        $this->authorize('update', $domain);
        $minNameservers = 2;
        $maxNameservers = 12;

        $nameservers = [];

        $remoteDns = [
            'provider' => null,
            'status' => null,
            'nameservers' => [],
            'error' => null,
            'fetched_at' => null,
        ];

        // TLD-3E.2 — Admin DNS Exact Provider Selection: resolve ONLY via Domain.provider_id —
        // never Domain.registrar (display/compatibility metadata only) and never ofType()->first().
        if ($domain->provider_id === null) {
            $remoteDns['error'] = __('لا يوجد مزود مُدار مرتبط بهذا النطاق.');
        } else {
            $provider = DomainProvider::query()->active()->find($domain->provider_id);

            if ($provider) {
                // TLD-3E.2 — DNS Provider Consistency: Domain.registrar is display/compatibility
                // metadata only and is never used to resolve or override the provider — but a
                // mismatch against the trusted provider_id is a drift signal worth logging (the
                // same non-blocking pattern documented for renewal in TLD-3D), not auto-corrected.
                if (strtolower((string) $domain->registrar) !== '' && strtolower((string) $domain->registrar) !== strtolower((string) $provider->type)) {
                    Log::warning('Domain registrar does not match its trusted provider (DNS view).', [
                        'domain_id' => $domain->id,
                        'domain' => $domain->domain_name,
                        'registrar' => $domain->registrar,
                        'provider_id' => $provider->id,
                        'provider_type' => $provider->type,
                    ]);
                }

                $remoteDns['provider'] = $provider->type;
                $remoteDns['provider_label'] = trim(($provider->name ?: Str::title($provider->type)) . ' — ' . Str::title($provider->type) . ' — ' . Str::title($provider->mode));
                try {
                    if ($provider->type === 'enom') {
                        $client = new EnomClient();
                        $result = $client->getDns($provider, $domain->domain_name);

                        if ($result['ok'] ?? false) {
                            $remoteDns['status'] = $result['use_dns'] ?? null;
                            $remoteDns['nameservers'] = $result['nameservers'] ?? [];
                            if (empty($remoteDns['nameservers'] ?? []) && ($remoteDns['status'] ?? null) === 'default') {
                                $remoteDns['nameservers'] = [
                                    'dns1.name-services.com',
                                    'dns2.name-services.com',
                                    'dns3.name-services.com',
                                    'dns4.name-services.com',
                                ];
                            }
                        } else {
                            $remoteDns['error'] = $result['message'] ?? __('Unable to fetch DNS state from registrar.');
                        }
                    } elseif ($provider->type === 'namecheap') {
                        $client = new NamecheapClient($provider);
                        $result = $client->getNameservers($domain->domain_name);

                        if ($result['ok'] ?? false) {
                            $remoteDns['status'] = ($result['is_using_default'] ?? null) === true ? 'default' : 'custom';
                            $remoteDns['nameservers'] = $result['nameservers'] ?? [];
                            if (empty($remoteDns['nameservers'] ?? []) && ($remoteDns['status'] ?? null) === 'default') {
                                $remoteDns['nameservers'] = [
                                    'dns1.registrar-servers.com',
                                    'dns2.registrar-servers.com',
                                ];
                            }
                        } else {
                            $remoteDns['error'] = $result['message'] ?? __('Unable to fetch DNS state from registrar.');
                        }
                    } else {
                        $remoteDns['error'] = __('Fetching DNS snapshot is not implemented for :provider yet.', ['provider' => Str::title($provider->type)]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to fetch registrar DNS snapshot', [
                        'domain_id' => $domain->id,
                        'domain' => $domain->domain_name,
                        'provider_id' => $provider->id,
                        'provider_type' => $provider->type,
                        'error' => $e->getMessage(),
                    ]);
                    $remoteDns['error'] = __('Unable to contact registrar: :message', ['message' => $e->getMessage()]);
                }
                $remoteDns['fetched_at'] = now();
            } else {
                $remoteDns['error'] = __('The linked provider is inactive or no longer exists.');
            }
        }

        $nameservers = array_values(array_filter($remoteDns['nameservers'] ?? [], fn($value) => filled($value)));
        $nameservers = array_slice($nameservers, 0, $maxNameservers);
        if (count($nameservers) < $minNameservers) {
            $nameservers = array_pad($nameservers, $minNameservers, '');
        }

        return view('dashboard.management.domains.dns', [
            'domain' => $domain,
            'nameservers' => $nameservers,
            'remoteDns' => $remoteDns,
            'minNameservers' => $minNameservers,
            'maxNameservers' => $maxNameservers,
        ]);
    }

    /** DNS: حفظ + دفع للمسجل */
    public function updateDns(UpdateDomainDnsRequest $request, Domain $domain)
    {
        $this->authorize('update', $domain);
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

        // TLD-3E.2 — Admin DNS Exact Provider Selection: resolve ONLY via Domain.provider_id —
        // never Domain.registrar and never ofType()->first(). registrar/provider.type mismatch is
        // logged as a drift signal (see below) but never used to change or auto-correct provider_id.
        if ($domain->provider_id === null) {
            return back()->withInput()
                ->withErrors(['nameservers' => __('لا يوجد مزود مُدار مرتبط بهذا النطاق.')]);
        }

        $provider = DomainProvider::query()->active()->find($domain->provider_id);

        if (!$provider) {
            return back()->withInput()
                ->withErrors(['nameservers' => __('The linked provider is inactive or no longer exists.')]);
        }

        if (strtolower((string) $domain->registrar) !== '' && strtolower((string) $domain->registrar) !== strtolower((string) $provider->type)) {
            Log::warning('Domain registrar does not match its trusted provider (DNS update).', [
                'domain_id' => $domain->id,
                'domain' => $domain->domain_name,
                'registrar' => $domain->registrar,
                'provider_id' => $provider->id,
                'provider_type' => $provider->type,
            ]);
        }

        $syncResult = $this->pushNameserversToProvider($provider, $domain, $requestedNameservers);
        if (!($syncResult['ok'] ?? false)) {
            Log::warning('Domain DNS sync rejected by provider', [
                'domain_id' => $domain->id,
                'domain' => $domain->domain_name,
                'provider_id' => $provider->id,
                'provider_type' => $provider->type,
                'message' => $syncResult['message'] ?? null,
                'code' => $syncResult['code'] ?? null,
                'cid' => $syncResult['cid'] ?? null,
            ]);
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
