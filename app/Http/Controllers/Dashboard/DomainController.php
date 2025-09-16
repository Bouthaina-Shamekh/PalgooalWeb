<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DomainController extends Controller
{
    /**
     * Display a listing of the domains.
     */
    public function index()
    {
        $domains = Domain::latest()->paginate(10);
        return view('dashboard.domains.index', compact('domains'));
    }

    /**
     * Show the form for creating a new domain.
     */
    public function create()
    {
        $clients = Client::all();
        $templates = Template::all();
        $domain = new Domain();
        return view('dashboard.domains.create', compact('clients', 'templates', 'domain'));
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

        Domain::create($validated);

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
            'reference_id' => $validated['id'],
            'description' => 'تسجيل دومين ' . $validated['domain_name'],
            'qty' => 1,
            'unit_price_cents' => $price_cents,
            'total_cents' => $price_cents,
        ]);

        return redirect()->route('dashboard.domains.index')->with('success', 'تمت إضافة النطاق بنجاح');
    }

    /**
     * Show the form for editing the specified domain.
     */
    public function edit(Domain $domain)
    {
        $clients = Client::all();
        $templates = Template::all();
        return view('dashboard.domains.edit', compact('domain', 'clients', 'templates'));
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
                'description' => 'تحديث دومين: ' . $domain->domain_name,
            ]);
        }

        return redirect()->route('dashboard.domains.index')->with('success', 'تم تعديل النطاق بنجاح');
    }

    /**
     * Remove the specified domain from storage.
     */
    public function destroy(Domain $domain)
    {
        $domain->delete();
        return redirect()->route('dashboard.domains.index')->with('success', 'تم حذف النطاق بنجاح');
    }
}
