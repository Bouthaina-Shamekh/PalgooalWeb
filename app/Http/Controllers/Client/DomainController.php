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
use App\Services\Billing\DomainInvoiceItemBuilder;
use App\Services\Domains\DomainAvailabilityService;
use App\Services\Domains\DomainPricingService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
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

        return redirect()->route('client.domains.index')->with('ok', t('client.Domain_Created', 'تمت إضافة النطاق بنجاح.'));
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

        return redirect()->route('client.domains.index')->with('ok', t('client.Domain_Updated', 'تم تعديل النطاق بنجاح.'));
    }

    /**
     * Remove the specified domain from storage.
     */
    public function destroy(Domain $domain)
    {
        $domain = $this->ownedDomain($domain);
        $domain->delete();
        return redirect()->route('client.domains.index')->with('ok', t('client.Domain_Deleted', 'تم حذف النطاق بنجاح.'));
    }
 
    public function toggleAutoRenew(Domain $domain)
    {
        $domain = $this->ownedDomain($domain);

        $domain->update([
            'auto_renew' => !$domain->auto_renew,
        ]);

        return redirect()
            ->route('client.domains.index')
            ->with('ok', $domain->auto_renew
                ? strtr(t('client.Auto_Renew_Enabled', 'تم تفعيل التجديد التلقائي للنطاق :domain.'), [':domain' => $domain->domain_name])
                : strtr(t('client.Auto_Renew_Disabled', 'تم تعطيل التجديد التلقائي للنطاق :domain.'), [':domain' => $domain->domain_name]));
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

        // ADR — تسعير موثوق: لا يُعتمَد أي سعر ثابت/افتراضي. المصدر الوحيد الآن هو
        // DomainPricingService (domain_tld_prices)؛ في حال عدم وجود سعر موثوق تتوقف
        // العملية هنا قبل أي إنشاء لأي سجل (لا Order ولا Invoice ولا Domain).
        $trustedQuote = $this->resolveTrustedRegistrationQuote($domain);

        if ($trustedQuote === null) {
            return redirect()->route('client.domains.search')
                ->with('error', 'تعذر تحديد سعر هذا الدومين حاليًا.');
        }

        $registrationDate = Carbon::today();
        $renewalDate = (clone $registrationDate)->addYear();

        $domainData = [
            'client_id' => $client->id,
            'domain_name' => $domain,
            'registrar' => $trustedQuote['provider_type'],
            'registration_date' => $registrationDate->format('Y-m-d'),
            'renewal_date' => $renewalDate->format('Y-m-d'),
            'status' => 'pending',
            'term_years' => 1,
            'price_cents' => $trustedQuote['price_cents'],
            'currency' => $trustedQuote['currency'],
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

        $domainName = $this->normalizePurchaseDomain($request->domain_name);

        // ADR — تسعير موثوق: لا يُعتمَد أي سعر ثابت/افتراضي. المصدر الوحيد الآن هو
        // DomainPricingService (domain_tld_prices)؛ في حال عدم وجود سعر موثوق تتوقف
        // العملية هنا قبل أي كتابة لقاعدة البيانات (لا Order ولا Invoice ولا order_items).
        $trustedQuote = $this->resolveTrustedRegistrationQuote($domainName);

        if ($trustedQuote === null) {
            return redirect()->route('client.domains.search')
                ->with('error', 'تعذر تحديد سعر هذا الدومين حاليًا.');
        }

        $provider = DomainProvider::query()
            ->active()
            ->whereKey($trustedQuote['provider_id'])
            ->where('type', $trustedQuote['provider_type'])
            ->where('mode', $trustedQuote['provider_mode'])
            ->first();

        if (!$provider instanceof DomainProvider) {
            return redirect()->route('client.domains.search')
                ->with('error', 'تعذر استخدام مزوّد الدومين المحدد حاليًا. حاول مرة أخرى لاحقًا.');
        }

        $quote = $this->resolveDomainQuote($domainName);

        // ADR — إعادة تحقق فعلية للتوفر لدى المزوّد مباشرة قبل الدخول إلى DB::transaction()؛
        // لا يُعتمَد available من نتيجة بحث سابقة (حتى لو كانت من resolveDomainQuote() أعلاه).
        // هذه عملية تسجيل جديد دائماً (item_option = register)، لذا تخضع للفحص دائماً.
        $availMap = app(DomainAvailabilityService::class)
            ->verifyRegistrationAvailabilityBatch([$domainName], $provider);

        if ($availMap === null) {
            Log::error('Client domain purchase: provider availability check failed', ['domain' => $domainName]);
            return redirect()->route('client.domains.search')
                ->with('error', 'تعذر التحقق من توفر الدومين حاليًا. حاول مرة أخرى لاحقًا.');
        }

        if (!($availMap[$domainName] ?? false)) {
            return redirect()->route('client.domains.search')
                ->with('error', 'الدومين ' . $domainName . ' لم يعد متاحًا للتسجيل.');
        }

        $checkoutFingerprint = $this->buildClientDomainPurchaseFingerprint(
            $client->id,
            $domainName,
            $trustedQuote
        );
        $existingOrder = Order::query()
            ->where('checkout_fingerprint', $checkoutFingerprint)
            ->first();

        if ($existingOrder instanceof Order) {
            return $this->clientDomainPurchaseOrderResponse($existingOrder, $client->id, $domainName);
        }

        $existingDomain = Domain::where('domain_name', $domainName)->first();

        if ($existingDomain) {
            return redirect()->route('client.domains.index')
                ->with('error', 'Domain already exists in your account.');
        }

        try {
            $registrationDate = Carbon::today();
            $renewalDate = (clone $registrationDate)->addYear();
            $currency = $trustedQuote['currency'];
            $priceCents = $trustedQuote['price_cents'];

            $invoiceItemBuilder = app(DomainInvoiceItemBuilder::class);

            $invoice = DB::transaction(function () use (
                $client,
                $domainName,
                $registrationDate,
                $renewalDate,
                $priceCents,
                $currency,
                $quote,
                $trustedQuote,
                $invoiceItemBuilder,
                $checkoutFingerprint
            ) {
                $order = Order::create([
                    'client_id' => $client->id,
                    'status' => Order::STATUS_PENDING,
                    'type' => 'domain',
                    'checkout_fingerprint' => $checkoutFingerprint,
                    'notes' => 'Domain order for ' . $domainName,
                ]);

                $orderItem = $order->items()->create([
                    'domain' => $domainName,
                    'item_option' => 'register',
                    'price_cents' => $priceCents,
                    'meta' => [
                        'currency' => $currency,
                        'registrar' => $trustedQuote['provider_type'],
                        'provider_id' => $trustedQuote['provider_id'],
                        'provider_type' => $trustedQuote['provider_type'],
                        'provider_mode' => $trustedQuote['provider_mode'],
                        'domain_tld_id' => $trustedQuote['domain_tld_id'],
                        'years' => 1,
                        'registration_date' => $registrationDate->format('Y-m-d'),
                        'renewal_date' => $renewalDate->format('Y-m-d'),
                        'term_years' => 1,
                        'quote' => [
                            'price' => $trustedQuote['price'],
                            'is_premium' => (bool) ($quote['is_premium'] ?? false),
                        ],
                    ],
                ]);

                // ADR — Phase 2: توحيد InvoiceItems لفواتير الدومينات. نستبدل الإنشاء اليدوي
                // لبند الفاتورة بنفس DomainInvoiceItemBuilder المستخدم في CheckoutController،
                // ونشتق subtotal_cents من خصائص البند المُجهَّزة (مبنية من price_cents الموثوق
                // لنفس OrderItem أعلاه) بدلاً من الاعتماد المباشر على متغيّر $priceCents.
                $invoiceItemAttributes = $invoiceItemBuilder->buildAttributesForItems(collect([$orderItem]));

                if ($invoiceItemAttributes->isEmpty()) {
                    throw new \RuntimeException('تعذّر تجهيز بند الفاتورة لهذا الدومين.');
                }

                if ($invoiceItemAttributes->count() !== 1) {
                    throw new \RuntimeException('عدد بنود الفاتورة المُجهَّزة لا يطابق عنصر الطلب (order_items).');
                }

                $subtotalCents = (int) $invoiceItemAttributes->sum('total_cents');
                // لا كوبون ولا ضريبة في هذا المسار حالياً (نفس السياسة المالية السابقة تمامًا:
                // subtotal_cents === total_cents) — فقط أصبح subtotal مشتقًا من بند الفاتورة
                // نفسه بدلاً من $priceCents مباشرة.
                $discountCents = 0;
                $taxCents = 0;
                $totalCents = max(0, $subtotalCents - $discountCents + $taxCents);

                $invoice = Invoice::create([
                    'client_id' => $client->id,
                    'order_id' => $order->id,
                    'number' => $this->generateUniqueInvoiceNumber(),
                    'status' => 'unpaid',
                    'subtotal_cents' => $subtotalCents,
                    'discount_cents' => $discountCents,
                    'tax_cents' => $taxCents,
                    'total_cents' => $totalCents,
                    'currency' => $currency,
                    'due_date' => now()->addDays(7),
                ]);

                $createdInvoiceItems = $invoiceItemBuilder->persistItems($invoice, $invoiceItemAttributes);

                // تحقق صريح بعد الإنشاء — لا اعتماد على افتراض تطابق المصادر:
                if ($createdInvoiceItems->count() !== 1) {
                    throw new \RuntimeException('لم يتم إنشاء بند الفاتورة فعليًا بشكل صحيح.');
                }

                $persistedItemsTotal = (int) $createdInvoiceItems->sum('total_cents');
                if ($persistedItemsTotal !== (int) $invoice->subtotal_cents) {
                    throw new \RuntimeException('مجموع بند الفاتورة المُنشأ لا يطابق subtotal_cents الخاص بالفاتورة.');
                }

                $expectedTotalCents = max(0, (int) $invoice->subtotal_cents - (int) $invoice->discount_cents + (int) $invoice->tax_cents);
                if ((int) $invoice->total_cents !== $expectedTotalCents) {
                    throw new \RuntimeException('total_cents الخاص بالفاتورة لا يطابق subtotal - discount + tax وفق السياسة الحالية.');
                }

                return $invoice;
            });

            return redirect()->route('client.invoices.checkout', $invoice)
                ->with('ok', t('client.Domain_Order_Created', 'تم إنشاء طلبك بنجاح. تابع إلى صفحة الدفع.'));

        } catch (QueryException $e) {
            if ($this->isClientDomainPurchaseFingerprintCollision($e)) {
                $winningOrder = Order::query()
                    ->where('checkout_fingerprint', $checkoutFingerprint)
                    ->first();

                if ($winningOrder instanceof Order) {
                    return $this->clientDomainPurchaseOrderResponse($winningOrder, $client->id, $domainName);
                }
            }

            Log::error('Domain purchase checkout creation failed', [
                'client_id' => $client->id,
                'domain' => $domainName,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to create the order. Please try again.')
                ->withInput();
        } catch (\Throwable $e) {
            Log::error('Domain purchase checkout creation failed', [
                'client_id' => $client->id,
                'domain' => $domainName,
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
            // TLD-3A — sale فقط. لا يُستخدم cost (تكلفة داخلية) كسعر معروض للعميل في كتالوج
            // العرض/autocomplete هذا؛ مسار الشراء الفعلي أصلاً موثوق حصراً عبر
            // resolveTrustedRegistrationQuote() (DomainPricingService)، ولم يُلمَس هنا.
            $rawSale = $priceRow?->sale;
            $price = (is_numeric($rawSale) && (float) $rawSale > 0) ? (float) $rawSale : null;

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

        // ADR — لا يُستخدم أي سعر بديل/ثابت هنا. إن لم يُرجع فحص التوفر سعرًا حقيقيًا،
        // يبقى $quote['price'] = null، ويُترك تحديد السعر الموثوق النهائي حصريًا لـ
        // resolveTrustedRegistrationQuote() عبر DomainPricingService (domain_tld_prices).
        return $quote;
    }

    /**
     * عرض السعر الموثوق الكامل لتسجيل دومين، ويتضمن هوية المزوّد المختار.
     * لا يوجد أي سعر أو مزوّد افتراضي هنا؛ يُعاد null إن لم يوجد Quote موثوق،
     * وعندها يجب على المستدعي إيقاف العملية بالكامل قبل أي كتابة لقاعدة البيانات.
     */
    protected function resolveTrustedRegistrationQuote(string $domain): ?array
    {
        return app(DomainPricingService::class)->registrationQuoteForDomain($domain);
    }

    protected function normalizePurchaseDomain(string $domain): string
    {
        $domain = strtolower(trim(rtrim($domain, '.')));

        if (function_exists('idn_to_ascii')) {
            $ascii = @idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($ascii) {
                $domain = strtolower($ascii);
            }
        }

        return $domain;
    }

    protected function buildClientDomainPurchaseFingerprint(int $clientId, string $domain, array $quote): string
    {
        $payload = [
            'namespace' => 'client-domain-purchase',
            'client_id' => $clientId,
            'domain' => $domain,
            'item_option' => 'register',
            'years' => 1,
            'price_cents' => (int) $quote['price_cents'],
            'currency' => strtoupper(trim((string) $quote['currency'])),
            'provider_id' => (int) $quote['provider_id'],
            'provider_type' => strtolower(trim((string) $quote['provider_type'])),
            'provider_mode' => strtolower(trim((string) $quote['provider_mode'])),
            'domain_tld_id' => (int) $quote['domain_tld_id'],
        ];

        return hash('sha256', 'client-domain-purchase|' . json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    protected function clientDomainPurchaseOrderResponse(Order $order, int $clientId, string $domain)
    {
        $invoice = $this->completedClientDomainPurchaseInvoice($order, $clientId, $domain);

        if (!$invoice instanceof Invoice) {
            Log::error('Client domain purchase fingerprint matched an incomplete order.', [
                'order_id' => $order->getKey(),
                'client_id' => $clientId,
                'domain' => $domain,
            ]);

            return redirect()->back()->with('error', t(
                'client.Domain_Purchase_Incomplete_Order',
                'تعذر استعادة طلب الدومين السابق لأنه غير مكتمل. يرجى التواصل مع الدعم الفني.'
            ));
        }

        if ($invoice->status === 'paid') {
            return redirect()->route('client.invoices.checkout', [
                'invoice' => $invoice,
                'state' => 'paid',
            ])->with('ok', t('client.Domain_Invoice_Already_Paid', 'تم دفع فاتورة هذا الطلب مسبقًا.'));
        }

        if (in_array($invoice->status, ['draft', 'unpaid'], true)) {
            return redirect()->route('client.invoices.checkout', $invoice)
                ->with('ok', t('client.Domain_Invoice_Exists', 'توجد فاتورة غير مدفوعة لهذا النطاق. تابع عملية الدفع.'));
        }

        return redirect()->route('client.invoices.checkout', [
            'invoice' => $invoice,
            'state' => strtolower((string) $invoice->status),
        ])->with('error', t(
            'client.Domain_Invoice_Not_Payable',
            'فاتورة طلب الدومين السابق غير قابلة للدفع بحالتها الحالية.'
        ));
    }

    protected function completedClientDomainPurchaseInvoice(Order $order, int $clientId, string $domain): ?Invoice
    {
        if ((int) $order->client_id !== $clientId || $order->type !== 'domain') {
            return null;
        }

        if (!$order->items()
            ->where('domain', $domain)
            ->where('item_option', 'register')
            ->exists()
        ) {
            return null;
        }

        $invoice = $order->invoices()
            ->where('client_id', $clientId)
            ->latest('id')
            ->first();

        if (!$invoice instanceof Invoice || !$invoice->items()->exists()) {
            return null;
        }

        return $invoice;
    }

    protected function isClientDomainPurchaseFingerprintCollision(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, '23000')
            && (str_contains($message, 'orders_checkout_fingerprint_unique')
                || str_contains($message, 'checkout_fingerprint'));
    }

    protected function generateUniqueInvoiceNumber(): string
    {
        do {
            $number = 'INV-' . Str::upper(Str::random(6));
        } while (Invoice::where('invoice_number', $number)->exists());
        return $number;
    }
}
