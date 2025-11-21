<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Template;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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
        $domains = Domain::where('client_id', Auth::guard('client')->user()->id)->latest()->paginate(10);
        return view('client.domains.index', compact('domains'));
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
            'registrar' => 'required',
            'registration_date' => 'required',
            'renewal_date' => 'required',
            'status' => 'required',
        ]);

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
        $clients = Client::all();
        $templates = Template::all();
        return view('client.domains.edit', compact('domain', 'clients', 'templates'));
    }

    /**
     * Update the specified domain in storage.
     */
    public function update(Request $request, Domain $domain)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'domain_name' => 'required|unique:domains,domain_name,' . $domain->id,
            'registrar' => 'required',
            'registration_date' => 'required',
            'renewal_date' => 'required',
            'status' => 'required',
        ]);

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
        $domain->delete();
        return redirect()->route('client.domains.index')->with('success', 'تم حذف النطاق بنجاح');
    }


    /**
     * Show domain search form
     */
    public function search()
    {
        return view('client.domains.search', [
            'domain_extensions' => $this->domainExtensions
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

        $domainName = $request->domain_name;
        $domainExtension = $request->domain_extension;
        $fullDomain = $domainName . $domainExtension;

        // Check main domain availability
        $domainAvailable = $this->checkDomainAvailability($fullDomain);

        $alternativeExtensions = [];
        $alternativeNames = [];

        if (!$domainAvailable) {
            // Check alternative extensions
            foreach ($this->domainExtensions as $extension => $price) {
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
            'domain_extensions' => $this->domainExtensions
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

        // Check if domain is still available
        if (!$this->checkDomainAvailability($domain)) {
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

        $domainData = [
            'client_id' => $client->id,
            'domain_name' => $domain,
            'registrar' => 'enom',
            'registration_date' => Carbon::now()->format('Y-m-d'),
            'renewal_date' => Carbon::now()->addYears(1)->format('Y-m-d'),
            'status' => 'pending',
        ];

        return view('client.domains.buy', [
            'domain_data' => $domainData,
            'domain' => $domain
        ]);
    }

    /**
     * Process domain purchase
     */
    public function purchase(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'domain_name' => 'required|string|unique:domains,domain_name',
            'registrar' => 'required|string',
            'registration_date' => 'required|date',
            'renewal_date' => 'required|date|after:registration_date',
            'status' => 'required|in:active,expired,pending',
        ]);

        $client = Client::findOrFail(Auth::guard('client')->user()->id);

        // Verify client_id matches authenticated client
        if ($request->client_id != $client->id) {
            return redirect()->route('client.domains.search')
                ->with('error', 'Unauthorized access.');
        }

        // Final availability check
        if (!$this->checkDomainAvailability($request->domain_name)) {
            return redirect()->route('client.domains.search')
                ->with('error', 'Domain is no longer available.');
        }

        try {
            // Create domain record
            $domain = Domain::create([
                'client_id' => $request->client_id,
                'domain_name' => $request->domain_name,
                'registrar' => $request->registrar,
                'registration_date' => $request->registration_date,
                'renewal_date' => $request->renewal_date,
                'status' => $request->status,
            ]);

            // Get domain price
            $domainExtension = '.' . pathinfo($request->domain_name, PATHINFO_EXTENSION);
            if (empty($domainExtension) || $domainExtension === '.') {
                // Extract extension from domain name
                preg_match('/(\.[a-z]+)$/i', $request->domain_name, $matches);
                $domainExtension = $matches[1] ?? '.com';
            }

            $priceCents = ($this->domainExtensions[$domainExtension] ?? 10) * 100; // Convert to cents

            // Create invoice
            $invoice = Invoice::create([
                'client_id' => $domain->client_id,
                'number' => 'INV-' . strtoupper(Str::random(6)),
                'status' => 'unpaid',
                'subtotal_cents' => $priceCents,
                'total_cents' => $priceCents,
                'currency' => 'USD',
                'due_date' => $domain->renewal_date ?? now()->addDays(7),
            ]);

            // Create invoice item
            $invoice->items()->create([
                'item_type' => 'domain',
                'reference_id' => $domain->id,
                'description' => 'Domain Registration: ' . $domain->domain_name,
                'qty' => 1,
                'unit_price_cents' => $priceCents,
                'total_cents' => $priceCents,
            ]);

            return redirect()->route('client.domains.index')
                ->with('success', 'Domain purchased successfully! Invoice #' . $invoice->number . ' has been created.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to purchase domain. Please try again.')
                ->withInput();
        }
    }

    /**
     * Check domain availability using RapidAPI
     */
    protected function checkDomainAvailability($domain)
    {
        try {
            // $response = Http::withHeaders([
            //     'x-rapidapi-host' => 'domainr.p.rapidapi.com',
            //     'x-rapidapi-key' => 'b33685147cmshf3a6f5c32c46eb1p13e1f9jsna1975004573c',
            // ])->get('https://domainr.p.rapidapi.com/v2/status', [
            //     'domain' => $domain,
            // ]);

            // if ($response->successful()) {
            //     $result = $response->json();

            //     if (isset($result['status'][0]['status'])) {
            //         $status = $result['status'][0]['status'];
            //         // Domain is available only if status is not 'active'
            //         return $status !== 'active';
            //     }
            // }
            return true;
        } catch (\Exception $e) {
            // Log error if needed
            Log::error('Domain check failed: ' . $e->getMessage());
        }

        return false;
    }
}
