<?php

namespace App\Http\Controllers\Dashboard\Management;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\DomainProvider;
use App\Services\DomainProviders\EnomClient;
use App\Services\DomainProviders\NamecheapClient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DomainController extends Controller
{
    /**
     * Display a listing of the domains.
     */
    public function index()
    {
        $domains = Domain::latest()->paginate(10);
        return view('dashboard.management.domains.index', compact('domains'));
    }

    /**
     * Show the form for creating a new domain.
     */
    public function create()
    {
        $clients = Client::all();
        $domain = new Domain();
        return view('dashboard.management.domains.create', compact('clients', 'domain'));
    }

    /**
     * Store a newly created domain in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'domain_name' => 'required|string|max:255|unique:domains,domain_name',
            'registrar' => 'required|string|max:255',
            'registration_date' => 'required|date',
            'renewal_date' => 'required|date',
            'status' => 'required|string|max:50',
        ]);

        $price_cents = 0;

        DB::transaction(function () use ($validated, $price_cents) {
            $domain = Domain::create($validated);

            $dueDate = isset($validated['renewal_date'])
                ? Carbon::parse($validated['renewal_date'])
                : now()->addDays(7);

            $invoice = Invoice::create([
                'client_id' => $validated['client_id'],
                'number' => 'INV-' . strtoupper(Str::random(6)),
                'status' => 'unpaid',
                'subtotal_cents' => $price_cents,
                'total_cents' => $price_cents,
                'currency' => 'USD',
                'due_date' => $dueDate,
            ]);

            $invoice->items()->create([
                'item_type' => 'domain',
                'reference_id' => $domain->id,
                'description' => 'رسوم الدومين: ' . $domain->domain_name,
                'qty' => 1,
                'unit_price_cents' => $price_cents,
                'total_cents' => $price_cents,
            ]);
        });

        return redirect()->route('dashboard.domains.index')->with('success', 'تم إنشاء الدومين بنجاح');
    }

    /**
     * Show the form for editing the specified domain.
     */
    public function edit(Domain $domain)
    {
        $clients = Client::all();
        return view('dashboard.management.domains.edit', compact('domain', 'clients'));
    }

    /**
     * Update the specified domain in storage.
     */
    public function update(Request $request, Domain $domain)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'domain_name' => 'required|string|max:255|unique:domains,domain_name,' . $domain->id,
            'registrar' => 'required|string|max:255',
            'registration_date' => 'required|date',
            'renewal_date' => 'required|date',
            'status' => 'required|string|max:50',
        ]);

        DB::transaction(function () use ($domain, $validated) {
            $domain->update($validated);

            // تحديث وصف بند الفاتورة إن وجد
            $invoiceItem = $domain->invoiceItems()->first();

            if ($invoiceItem && $invoiceItem->invoice) {
                $invoiceItem->update([
                    'description' => 'تحديث الدومين: ' . $domain->domain_name,
                ]);
            }
        });

        return redirect()->route('dashboard.domains.index')->with('success', 'تم تحديث الدومين بنجاح');
    }

    /**
     * Remove the specified domain from storage.
     */
    public function destroy(Domain $domain)
    {
        $domain->delete();
        return redirect()->route('dashboard.domains.index')->with('success', 'تم حذف الدومين بنجاح');
    }

    /**
     * Show the registration workflow screen for the given domain.
     */
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

    /**
     * Handle a domain registration request (placeholder until registrar integration is available).
     */
    public function updateRegister(Request $request, Domain $domain)
    {
        $validated = $request->validate([
            'registrar' => ['required', 'string', 'max:255'],
            'registration_date' => ['required', 'date'],
            'renewal_date' => ['required', 'date', 'after_or_equal:registration_date'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

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
        $renewalDate = Carbon::parse($validated['renewal_date']);
        $years = max(1, (int)ceil($registrationDate->diffInDays($renewalDate) / 365));

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
            return back()
                ->withInput()
                ->withErrors(['registrar' => $message]);
        }

        $domain->update([
            'registrar' => $validated['registrar'],
            'registration_date' => $registrationDate->toDateString(),
            'renewal_date' => $renewalDate->toDateString(),
            'status' => $validated['status'],
        ]);

        // @todo store $validated['notes'] in activity log or ticketing system once available.

        return redirect()
            ->route('dashboard.domains.index')
            ->with('success', __('Domain registered successfully via :provider.', ['provider' => Str::title($provider->type)]));
    }

    /**
     * Show the renewal workflow for the given domain.
     */
    public function editRenew(Domain $domain)
    {
        $currentRenewal = $domain->renewal_date ? Carbon::parse($domain->renewal_date) : null;
        $suggestedRenewal = ($currentRenewal ?? now())->copy()->addYear()->format('Y-m-d');

        $renewalDateValue = ($currentRenewal ?? now())->format('Y-m-d');

        $statusOptions = [
            'active' => __('Active'),
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

    /**
     * Handle a domain renewal request (placeholder until registrar integration is available).
     */
    public function updateRenew(Request $request, Domain $domain)
    {
        $minimumRenewalDate = $domain->renewal_date
            ? Carbon::parse($domain->renewal_date)->format('Y-m-d')
            : now()->format('Y-m-d');

        $validated = $request->validate([
            'renewal_date' => ['required', 'date', 'after_or_equal:' . $minimumRenewalDate],
            'status' => ['required', 'string', 'max:50'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $domain->update([
            'renewal_date' => $validated['renewal_date'],
            'status' => $validated['status'],
            'payment_method' => $validated['payment_method'] ?? $domain->payment_method,
        ]);

        // @todo: trigger billing/invoice generation tied to this renewal.

        return redirect()
            ->route('dashboard.domains.index')
            ->with('success', __('Domain renewal saved. Automation with registrar pending.'));
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
                        'message' => $response['message'] ?? __('Registration failed with Namecheap.'),
                    ];
                }

                return ['ok' => true];
            }

            if ($provider->type === 'enom') {
                $client = new EnomClient();
                [$sld, $tld] = $this->splitDomainParts($domain->domain_name);

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
                    return [
                        'ok' => false,
                        'message' => $response['message'] ?? __('Registration failed with Enom.'),
                    ];
                }

                return ['ok' => true];
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

    protected function splitDomainParts(string $fqdn): array
    {
        $fqdn = strtolower(trim($fqdn));
        if (!str_contains($fqdn, '.')) {
            return [null, null];
        }

        $parts = explode('.', $fqdn, 2);

        $sld = isset($parts[0]) ? Str::of($parts[0])->ascii()->trim()->value() : null;
        $tld = isset($parts[1]) ? Str::of($parts[1])->ascii()->trim()->value() : null;

        return [$sld ?: null, $tld ?: null];
    }

    /**
     * Show the DNS management screen for the given domain.
     */
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

    /**
     * Persist DNS changes for the given domain.
     * (Placeholder implementation until provider integration is available.)
     */
    public function updateDns(Request $request, Domain $domain)
    {
        $validated = $request->validate([
            'nameservers' => ['nullable', 'array'],
            'nameservers.*' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $requestedNameservers = array_values(array_filter(
            $validated['nameservers'] ?? [],
            fn($value) => filled($value)
        ));

        // @todo: Integrate with registrar API / persistence layer.
        return back()->with('success', __('DNS update request captured. Please complete the integration to push changes.'));
    }
}
