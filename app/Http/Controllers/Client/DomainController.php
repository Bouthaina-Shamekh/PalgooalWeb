<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Admin\Management\DomainSearchController as RegistrarDomainSearchController;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\DomainTld;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Template;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DomainController extends Controller
{
    protected $domainExtensions = [
        '.com' => 9, '.net' => 10, '.org' => 12, '.io' => 15, '.co' => 18,
    ];

    /**
     * Display a listing of the domains.
     */
    public function index()
    {
        $clientId = Auth::guard('client')->id();
        $domainsQuery = Domain::query()->where('client_id', $clientId);

        $domainStats = [
            'total' => (clone $domainsQuery)->count(),
            'active' => (clone $domainsQuery)->where('status', 'active')->count(),
            'pending' => (clone $domainsQuery)->where('status', 'pending')->count(),
            'expired' => (clone $domainsQuery)->where('status', 'expired')->count(),
        ];

        $domains = (clone $domainsQuery)
            ->with('template')
            ->latest()
            ->paginate(10);

        return view('client.domains.index', compact('domains', 'domainStats'));
    }

    /**
     * Show the form for creating a new domain.
     */
    public function create()
    {
        $clients = Client::all();
        $templates = Template::all();
        $domain = new Domain();
        return view('client.domains.create', compact('clients', 'templates', 'domain'));
    }

    /**
     * Store a newly created domain in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'domain_name' => 'required|unique:domains,domain_name',
            'registrar' => 'nullable|string',
            'registration_date' => 'required',
            'renewal_date' => 'required',
            'status' => 'required',
        ]);

        $validated['registrar'] = $this->defaultRegistrar();
        $domain = Domain::create($validated);

        $price_cents = 0;

        $invoice = Invoice::create([
            'client_id' => $validated['client_id'],
            'number' => 'INV-' . strtoupper(Str::random(6)),
            'status' => 'unpaid',
            'subtotal_cents' => $price_cents,
            'total_cents' => $price_cents,
            'currency' => 'USD',
            'due_date' => $validated['renewal_date'] ?? now()->addDays(7),
        ]);

        $invoice->items()->create([
            'item_type' => 'domain',
            'reference_id' => $domain->id,
            'description' => 'Domain registration: ' . $domain->domain_name,
            'qty' => 1,
            'unit_price_cents' => $price_cents,
            'total_cents' => $price_cents,
        ]);

        return redirect()->route('client.domains.index')->with('success', 'تمت إضافة النطاق بنجاح');
    }

    /**
     * Show the form for editing the specified domain.
     */
    public function edit(Domain $domain)
    {
        $domain = $this->ownedDomain($domain);
        $clients = Client::all();
        $templates = Template::all();
        return view('client.domains.edit', compact('domain', 'clients', 'templates'));
    }

    /**
     * Update the specified domain in storage.
     */
    public function update(Request $request, Domain $domain)
    {
        $domain = $this->ownedDomain($domain);

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'domain_name' => 'required|unique:domains,domain_name,' . $domain->id,
            'registrar' => 'nullable|string',
            'registration_date' => 'required',
            'renewal_date' => 'required',
            'status' => 'required',
        ]);

        $validated['registrar'] = $domain->registrar ?: $this->defaultRegistrar();
        $domain->update($validated);

        // تحديث الفاتورة إن وجدت
        $invoiceItem = $domain->invoiceItems()->first();

        if ($invoiceItem && $invoiceItem->invoice) {
            $invoiceItem->update([
                'description' => 'تحديث النطاق: ' . $domain->domain_name,
            ]);
        }

        return redirect()->route('client.domains.index')->with('success', 'تم تعديل النطاق بنجاح');
    }

    /**
     * Remove the specified domain from storage.
     */
    public function destroy(Domain $domain)
    {
        $domain = $this->ownedDomain($domain);
        $domain->delete();
        return redirect()->route('client.domains.index')->with('success', 'تم حذف النطاق بنجاح');
    }
 
    public function toggleAutoRenew(Domain $domain)
    {
        $domain = $this->ownedDomain($domain);

        $domain->update([
            'auto_renew' => !$domain->auto_renew,
        ]);

        return redirect()
            ->route('client.domains.index')
            ->with('success', 'Auto-renew ' . ($domain->auto_renew ? 'enabled' : 'disabled') . ' for ' . $domain->domain_name . '.');
    }

    /**
     * Show domain search form
     */
    public function search()
    {
        $catalog = $this->buildSearchCatalog();

        return view('client.domains.search', [
            'domain_extensions' => $catalog['legacy_extensions'],
            'provider_names' => $catalog['provider_names'],
            'default_tlds' => $catalog['default_tlds'],
            'all_tlds' => $catalog['all_tlds'],
            'fallback_prices' => $catalog['fallback_prices'],
            'catalog_stats' => $catalog['stats'],
            'has_registrar_setup' => !empty($catalog['provider_names']),
        ]);
    }

    /**
     * Process domain search
     */
    public function processSearch(Request $request)
    {
        $request->validate([
            'domain_name' => 'required|string|max:255',
            'domain_extension' => 'required|string'
        ]);

        $catalog = $this->buildSearchCatalog();
        $domainExtensions = $catalog['legacy_extensions'];
        $domainName = $request->domain_name;
        $domainExtension = $request->domain_extension;
        $fullDomain = $domainName . $domainExtension;

        // Check main domain availability
        $domainAvailable = $this->checkDomainAvailability($fullDomain);

        $alternativeExtensions = [];
        $alternativeNames = [];

        if (!$domainAvailable) {
            // Check alternative extensions
            foreach ($domainExtensions as $extension => $price) {
                $testDomain = $domainName . $extension;
                if ($this->checkDomainAvailability($testDomain)) {
                    $alternativeExtensions[] = [
                        'extension' => $extension,
                        'price' => $price,
                        'domain' => $testDomain
                    ];
                }
            }

            // If no alternative extensions available, generate alternative names
            if (empty($alternativeExtensions)) {
                $found = 0;
                while ($found < 3) {
                    $randomString = substr(str_shuffle(md5(microtime())), 0, 3);
                    $testDomain = $domainName . $randomString . $domainExtension;
                    if ($this->checkDomainAvailability($testDomain)) {
                        $alternativeNames[] = $testDomain;
                        $found++;
                    }
                }
            }
        }

        return view('client.domains.search-results', [
            'domain' => $fullDomain,
            'domain_name' => $domainName,
            'domain_extension' => $domainExtension,
            'domain_available' => $domainAvailable,
            'alternative_extensions' => $alternativeExtensions,
            'alternative_names' => $alternativeNames,
            'domain_extensions' => $domainExtensions
        ]);
    }

    /**
     * Show buy form for available domain
     */
    public function buy(Request $request)
    {
        $request->validate([
            'domain' => 'required|string'
        ]);

        $domain = $request->domain;
        $client = Client::findOrFail(Auth::guard('client')->user()->id);
        $defaultRegistrar = $this->defaultRegistrar();
        $quote = $this->resolveDomainQuote($domain);

        // Check if domain is still available
        if (!($quote['available'] ?? false)) {
            return redirect()->route('client.domains.search')
                ->with('error', 'Domain is no longer available.');
        }

        // Check if domain already exists for this client
        $existingDomain = Domain::where('client_id', $client->id)
            ->where('domain_name', $domain)
            ->first();

        if ($existingDomain) {
            return redirect()->route('client.domains.index')
                ->with('error', 'Domain already exists in your account.');
        }

        $registrationDate = Carbon::today();
        $renewalDate = (clone $registrationDate)->addYear();
        $priceCents = $this->resolveQuotePriceCents($quote, $domain);

        $domainData = [
            'client_id' => $client->id,
            'domain_name' => $domain,
            'registrar' => (string) $request->query('registrar', $defaultRegistrar),
            'registration_date' => $registrationDate->format('Y-m-d'),
            'renewal_date' => $renewalDate->format('Y-m-d'),
            'status' => 'pending',
            'term_years' => 1,
            'price_cents' => $priceCents,
            'currency' => $quote['currency'] ?? 'USD',
        ];

        return view('client.domains.buy', [
            'domain_data' => $domainData,
            'domain' => $domain,
            'quote' => $quote,
        ]);
    }

    /**
     * Process domain purchase
     */
    public function purchase(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'domain_name' => 'required|string|max:255',
        ]);

        $client = Client::findOrFail(Auth::guard('client')->user()->id);

        // Verify client_id matches authenticated client
        if ($request->client_id != $client->id) {
            return redirect()->route('client.domains.search')
                ->with('error', 'Unauthorized access.');
        }

        $quote = $this->resolveDomainQuote($request->domain_name);

        // Final availability check
        if (!($quote['available'] ?? false)) {
            return redirect()->route('client.domains.search')
                ->with('error', 'Domain is no longer available.');
        }

        $existingDomain = Domain::where('domain_name', $request->domain_name)->first();

        if ($existingDomain) {
            return redirect()->route('client.domains.index')
                ->with('error', 'Domain already exists in your account.');
        }

        $existingInvoice = $this->findPendingDomainInvoice($client->id, $request->domain_name);

        if ($existingInvoice) {
            return redirect()->route('client.invoices.checkout', $existingInvoice)
                ->with('success', 'An existing unpaid invoice was found for this domain. Continue with payment.');
        }

        try {
            $registrar = $this->defaultRegistrar();
            $registrationDate = Carbon::today();
            $renewalDate = (clone $registrationDate)->addYear();
            $priceCents = $this->resolveQuotePriceCents($quote, $request->domain_name);
            $currency = $quote['currency'] ?? 'USD';

            $invoice = DB::transaction(function () use (
                $client,
                $request,
                $registrar,
                $registrationDate,
                $renewalDate,
                $priceCents,
                $currency,
                $quote
            ) {
                $order = Order::create([
                    'client_id' => $client->id,
                    'status' => Order::STATUS_PENDING,
                    'type' => 'domain',
                    'notes' => 'Domain order for ' . $request->domain_name,
                ]);

                $order->items()->create([
                    'domain' => $request->domain_name,
                    'item_option' => 'register',
                    'price_cents' => $priceCents,
                    'meta' => [
                        'currency' => $currency,
                        'registrar' => $registrar,
                        'registration_date' => $registrationDate->format('Y-m-d'),
                        'renewal_date' => $renewalDate->format('Y-m-d'),
                        'term_years' => 1,
                        'quote' => [
                            'price' => $quote['price'] ?? null,
                            'is_premium' => (bool) ($quote['is_premium'] ?? false),
                        ],
                    ],
                ]);

                $invoice = Invoice::create([
                    'client_id' => $client->id,
                    'order_id' => $order->id,
                    'number' => $this->generateUniqueInvoiceNumber(),
                    'status' => 'unpaid',
                    'subtotal_cents' => $priceCents,
                    'total_cents' => $priceCents,
                    'currency' => $currency,
                    'due_date' => now()->addDays(7),
                ]);

                $invoice->items()->create([
                    'item_type' => 'domain',
                    'reference_id' => null,
                    'description' => 'Domain Registration: ' . $request->domain_name,
                    'qty' => 1,
                    'unit_price_cents' => $priceCents,
                    'total_cents' => $priceCents,
                ]);

                return $invoice;
            });

            return redirect()->route('client.invoices.checkout', $invoice)
                ->with('success', 'Your order has been created. Continue to the demo payment page.');

        } catch (\Throwable $e) {
            Log::error('Domain purchase checkout creation failed', [
                'client_id' => $client->id,
                'domain' => $request->domain_name,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to create the order. Please try again.')
                ->withInput();
        }
    }

    /**
     * Check domain availability using RapidAPI
     */
    protected function checkDomainAvailability($domain)
    {
        return (bool) ($this->resolveDomainQuote($domain)['available'] ?? false);
    }

    protected function buildSearchCatalog(): array
    {
        $providerNames = DomainProvider::query()
            ->active()
            ->whereIn('type', ['namecheap', 'enom'])
            ->orderBy('name')
            ->pluck('name')
            ->filter()
            ->values()
            ->all();

        $catalogRows = DomainTld::query()
            ->with([
                'prices' => fn($query) => $query
                    ->where('action', 'register')
                    ->where('years', 1)
                    ->select('id', 'domain_tld_id', 'sale', 'cost'),
            ])
            ->where('enabled', true)
            ->whereHas('provider', fn($query) => $query->active()->whereIn('type', ['namecheap', 'enom']))
            ->get(['id', 'provider_id', 'tld', 'currency', 'in_catalog']);

        $catalog = [];

        foreach ($catalogRows as $row) {
            $tld = ltrim(strtolower((string) $row->tld), '.');
            if ($tld === '') {
                continue;
            }

            $priceRow = $row->prices->first();
            $price = $priceRow?->sale ?? $priceRow?->cost;

            if (
                !isset($catalog[$tld])
                || ((bool) $row->in_catalog && !($catalog[$tld]['in_catalog'] ?? false))
                || ($price !== null && (($catalog[$tld]['price'] ?? null) === null || (float) $price < (float) $catalog[$tld]['price']))
            ) {
                $catalog[$tld] = [
                    'price' => $price !== null ? (float) $price : null,
                    'currency' => $row->currency ?: 'USD',
                    'in_catalog' => (bool) $row->in_catalog,
                ];
            }
        }

        if (empty($catalog)) {
            foreach ($this->domainExtensions as $extension => $price) {
                $catalog[ltrim($extension, '.')] = [
                    'price' => (float) $price,
                    'currency' => 'USD',
                    'in_catalog' => true,
                ];
            }
        }

        $allTlds = $this->sortSearchTlds(array_keys($catalog));
        $defaultTlds = array_slice($allTlds, 0, 12);
        $fallbackPrices = [];
        $legacyExtensions = [];

        foreach ($catalog as $tld => $meta) {
            if ($meta['price'] !== null) {
                $fallbackPrices[$tld] = (float) $meta['price'];
                $legacyExtensions['.' . $tld] = (float) $meta['price'];
            } else {
                $legacyExtensions['.' . $tld] = (float) ($this->domainExtensions['.' . $tld] ?? 10);
            }
        }

        return [
            'provider_names' => $providerNames,
            'default_tlds' => $defaultTlds,
            'all_tlds' => $allTlds,
            'fallback_prices' => $fallbackPrices,
            'legacy_extensions' => $legacyExtensions,
            'stats' => [
                'provider_count' => count($providerNames),
                'tld_count' => count($allTlds),
            ],
        ];
    }

    protected function sortSearchTlds(array $tlds): array
    {
        $priority = ['com', 'net', 'org', 'io', 'co', 'app', 'dev', 'shop', 'online', 'store', 'xyz', 'info'];
        $order = array_flip($priority);

        $normalized = array_values(array_unique(array_filter(array_map(
            fn($tld) => strtolower(ltrim((string) $tld, '.')),
            $tlds
        ))));

        usort($normalized, function (string $left, string $right) use ($order) {
            $leftRank = $order[$left] ?? 999;
            $rightRank = $order[$right] ?? 999;

            if ($leftRank !== $rightRank) {
                return $leftRank <=> $rightRank;
            }

            return strcmp($left, $right);
        });

        return $normalized;
    }

    protected function defaultRegistrar(): string
    {
        return DomainProvider::query()
            ->active()
            ->whereIn('type', ['namecheap', 'enom'])
            ->orderByRaw("CASE WHEN type = 'namecheap' THEN 0 WHEN type = 'enom' THEN 1 ELSE 2 END")
            ->value('type') ?? 'namecheap';
    }

    protected function resolveDomainQuote(string $domain): array
    {
        $quote = [
            'domain' => $domain,
            'available' => false,
            'is_premium' => false,
            'price' => null,
            'currency' => 'USD',
        ];

        try {
            $response = app(RegistrarDomainSearchController::class)->check(
                Request::create('/api/domains/check', 'GET', ['domains' => $domain])
            );

            $payload = $response->getData(true);
            $results = is_array($payload['results'] ?? null) ? $payload['results'] : [];

            foreach ($results as $result) {
                if (strtolower((string) ($result['domain'] ?? '')) !== strtolower($domain)) {
                    continue;
                }

                $quote = [
                    'domain' => (string) ($result['domain'] ?? $domain),
                    'available' => (bool) ($result['available'] ?? false),
                    'is_premium' => (bool) ($result['is_premium'] ?? false),
                    'price' => is_numeric($result['price'] ?? null) ? (float) $result['price'] : null,
                    'currency' => (string) ($result['currency'] ?? 'USD'),
                ];

                break;
            }
        } catch (\Throwable $e) {
            Log::error('Domain quote lookup failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);
        }

        if ($quote['price'] === null) {
            $catalog = $this->buildSearchCatalog();
            $extension = ltrim(strtolower((string) pathinfo($domain, PATHINFO_EXTENSION)), '.');

            if ($extension !== '' && isset($catalog['fallback_prices'][$extension])) {
                $quote['price'] = (float) $catalog['fallback_prices'][$extension];
            }
        }

        return $quote;
    }

    protected function resolveQuotePriceCents(array $quote, string $domain): int
    {
        if (is_numeric($quote['price'] ?? null)) {
            return (int) round(((float) $quote['price']) * 100);
        }

        $extension = ltrim(strtolower((string) pathinfo($domain, PATHINFO_EXTENSION)), '.');
        $catalog = $this->buildSearchCatalog();
        $price = $catalog['fallback_prices'][$extension] ?? 10;

        return (int) round(((float) $price) * 100);
    }

    protected function findPendingDomainInvoice(int $clientId, string $domain): ?Invoice
    {
        return Invoice::query()
            ->where('client_id', $clientId)
            ->where('status', 'unpaid')
            ->where(function ($query) use ($domain) {
                $query->whereHas('order.items', function ($orderItems) use ($domain) {
                    $orderItems->where('domain', $domain)
                        ->where('item_option', 'register');
                })->orWhereHas('items', function ($items) use ($domain) {
                    $items->where('item_type', 'domain')
                        ->where('description', 'like', '%' . $domain . '%');
                });
            })
            ->latest('id')
            ->first();
    }

    protected function generateUniqueInvoiceNumber(): string
    {
        do {
            $number = 'INV-' . Str::upper(Str::random(6));
        } while (Invoice::where('number', $number)->exists());

        return $number;
    }

    protected function ownedDomain(Domain $domain): Domain
    {
        abort_if((int) $domain->client_id !== (int) Auth::guard('client')->id(), 404);

        return $domain;
    }
}
