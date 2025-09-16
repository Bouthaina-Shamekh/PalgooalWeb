<?php

namespace App\Http\Controllers\Dashboard\Management;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Domain;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
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
}

