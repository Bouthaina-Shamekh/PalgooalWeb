<?php

namespace App\Http\Controllers\Dashboard\Management;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Client;
use App\Models\Subscription;
use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with(['client', 'items'])->latest()->paginate(20);
        return view('dashboard.management.invoices.index', compact('invoices'));
    }

    public function create()
    {
        return view('dashboard.management.invoices.create', [
            'clients' => Client::orderBy('first_name')->get(),
            'subscriptions' => Subscription::all(),
            'domains' => Domain::all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'status'    => ['required', Rule::in(['draft', 'unpaid', 'paid', 'cancelled'])],
            'due_date'  => ['nullable', 'date'],
            'paid_date' => ['nullable', 'date'],
            'items'     => ['required', 'array', 'min:1'],
            'items.*.item_type'   => ['required', Rule::in(['subscription', 'domain'])],
            'items.*.reference_id' => ['required', 'integer'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty'         => ['required', 'integer', 'min:1'],
            'items.*.unit_price_cents' => ['required', 'integer', 'min:0'],
        ]);

        // إنشاء رقم فاتورة فريد
        $number = 'INV-' . Str::upper(Str::random(6));

        $invoice = Invoice::create([
            'client_id' => $data['client_id'],
            'number'    => $number,
            'status'    => $data['status'],
            'subtotal_cents' => collect($data['items'])->sum(fn($i) => $i['unit_price_cents'] * $i['qty']),
            'discount_cents' => 0,
            'tax_cents'      => 0,
            'total_cents'    => collect($data['items'])->sum(fn($i) => $i['unit_price_cents'] * $i['qty']),
            'currency'       => 'USD',
            'due_date'       => $data['due_date'] ?? now()->addDays(7),
            'paid_date'      => $data['paid_date'] ?? null,
        ]);

        foreach ($data['items'] as $item) {
            $invoice->items()->create([
                'item_type'   => $item['item_type'],
                'reference_id' => $item['reference_id'],
                'description' => $item['description'],
                'qty'         => $item['qty'],
                'unit_price_cents' => $item['unit_price_cents'],
                'total_cents' => $item['unit_price_cents'] * $item['qty'],
            ]);
        }

        return redirect()->route('dashboard.invoices.index')->with('ok', 'تم إنشاء الفاتورة');
    }

    public function edit(Invoice $invoice)
    {
        return view('dashboard.management.invoices.edit', [
            'invoice' => $invoice->load('items', 'client'),
            'clients' => Client::orderBy('first_name')->get(),
            'subscriptions' => Subscription::all(),
            'domains' => Domain::all(),
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'status'    => ['required', Rule::in(['draft', 'unpaid', 'paid', 'cancelled'])],
            'due_date'  => ['nullable', 'date'],
            'paid_date' => ['nullable', 'date'],
            'items'     => ['required', 'array', 'min:1'],
            'items.*.item_type'   => ['required', Rule::in(['subscription', 'domain'])],
            'items.*.reference_id' => ['required', 'integer'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty'         => ['required', 'integer', 'min:1'],
            'items.*.unit_price_cents' => ['required', 'integer', 'min:0'],
        ]);

        $invoice->update([
            'status'   => $data['status'],
            'due_date' => $data['due_date'] ?? null,
            'paid_date' => $data['paid_date'] ?? null,
            'subtotal_cents' => collect($data['items'])->sum(fn($i) => $i['unit_price_cents'] * $i['qty']),
            'total_cents'    => collect($data['items'])->sum(fn($i) => $i['unit_price_cents'] * $i['qty']),
        ]);

        // إذا أصبحت الفاتورة مدفوعة، فعّل الطلب المرتبط وأنشئ/حدّث الاشتراك
        if ($invoice->status === 'paid' && $invoice->order_id) {
            try {
                $order = \App\Models\Order::find($invoice->order_id);
                if ($order && $order->status !== 'active') {
                    $order->status = 'active';
                    $order->save();
                    // استدعاء عملية المعالجة في OrderController
                    $orderController = new \App\Http\Controllers\Dashboard\Management\OrderController();
                    $orderController->processActivation($order);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to activate order from invoice ' . $invoice->id . ': ' . $e->getMessage());
            }
        }

        // تحديث البنود
        $invoice->items()->delete();
        foreach ($data['items'] as $item) {
            $invoice->items()->create([
                'item_type'   => $item['item_type'],
                'reference_id' => $item['reference_id'],
                'description' => $item['description'],
                'qty'         => $item['qty'],
                'unit_price_cents' => $item['unit_price_cents'],
                'total_cents' => $item['unit_price_cents'] * $item['qty'],
            ]);
        }

        return redirect()->route('dashboard.invoices.index')->with('ok', 'تم تحديث الفاتورة');
    }


    public function destroy(Invoice $invoice)
    {
        $invoice->items()->delete();
        $invoice->delete();
        return redirect()->route('dashboard.invoices.index')->with('ok', 'تم حذف الفاتورة');
    }
}
