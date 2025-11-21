<?php

namespace App\Http\Controllers\Admin\Management;

use App\Http\Controllers\Controller;
use App\Models\{Invoice, InvoiceItem, Client, Subscription, Domain, Order};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log, Mail};
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $q      = trim((string) $request->get('q', ''));
        $status = $request->get('status');

        $invoices = Invoice::query()
            ->with(['client:id,first_name,last_name,email'])
            ->when($status, fn($qb) => $qb->where('status', $status))
            ->when($q, function ($qb) use ($q) {
                $qb->where(function ($qInner) use ($q) {
                    $qInner->where('number', 'like', "%{$q}%")
                        ->orWhereHas('client', function ($qClient) use ($q) {
                            $qClient->where('first_name', 'like', "%{$q}%")
                                ->orWhere('last_name', 'like', "%{$q}%");
                        });
                });
            })
            ->latest('created_at')
            ->paginate(20)
            ->appends($request->only('q', 'status'));

        return view('dashboard.management.invoices.index', compact('invoices'));
    }

    public function create()
    {
        return view('dashboard.management.invoices.create', [
            'clients'       => Client::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']),
            'subscriptions' => Subscription::with('client:id,first_name,last_name')
                ->get(['id', 'plan_id', 'client_id']),
            'domains'       => Domain::select('id', 'domain_name', 'status')->get(),
            'invoice'       => new Invoice(),
        ]);
    }

    public function store(Request $request)
    {
        $allowedItemTypes = $this->allowedItemTypes();

        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'status'    => ['required', Rule::in(['draft', 'unpaid', 'paid', 'cancelled'])],
            'due_date'  => ['nullable', 'date'],
            'paid_date' => ['nullable', 'date'],
            'items'     => ['required', 'array', 'min:1'],
            'items.*.item_type'        => ['required', Rule::in($allowedItemTypes)],
            'items.*.reference_id'     => ['required', 'integer'],
            'items.*.description'      => ['required', 'string', 'max:255'],
            'items.*.qty'              => ['required', 'integer', 'min:1'],
            'items.*.unit_price_cents' => ['required', 'integer', 'min:0'],
        ]);

        // طھط­ظ‚ظ‚ ط´ط±ط·ظٹ ظ…ظ† ط§ظ„ظ…ط±ط¬ط¹ ط­ط³ط¨ ظ†ظˆط¹ ط§ظ„ط¨ظ†ط¯
        $this->validateReferenceIds($data['items']);

        // ط¶ط¨ط· ط§ظ„طھظˆط§ط±ظٹط®
        $due      = $data['due_date'] ? Carbon::parse($data['due_date']) : Carbon::now()->addDays(7);
        $paidDate = $data['paid_date'] ? Carbon::parse($data['paid_date']) : null;

        if ($data['status'] !== 'paid') {
            $paidDate = null;
        } elseif ($paidDate && $paidDate->isBefore(Carbon::now()->subYears(5))) {
            // ط­ط§ط±ط³ ظ…ظ†ط·ظ‚ظٹ ظ„ظ…ظ†ط¹ طھط§ط±ظٹط® ط؛ظٹط± ظ…ط¹ظ‚ظˆظ„
            $paidDate = Carbon::now();
        }

        return DB::transaction(function () use ($data, $due, $paidDate) {
            $number  = $this->generateUniqueNumber();
            $totals  = $this->computeTotals($data['items']);

            $invoice = Invoice::create([
                'client_id'       => $data['client_id'],
                'number'          => $number,
                'status'          => $data['status'],
                'subtotal_cents'  => $totals['subtotal_cents'],
                'discount_cents'  => $totals['discount_cents'],
                'tax_cents'       => $totals['tax_cents'],
                'total_cents'     => $totals['total_cents'],
                'currency'        => 'USD',
                'due_date'        => $due,
                'paid_date'       => $paidDate,
            ]);

            foreach ($data['items'] as $item) {
                $invoice->items()->create([
                    'item_type'         => $item['item_type'],
                    'reference_id'      => $item['reference_id'],
                    'description'       => $item['description'],
                    'qty'               => $item['qty'],
                    'unit_price_cents'  => $item['unit_price_cents'],
                    'total_cents'       => $item['unit_price_cents'] * $item['qty'],
                ]);
            }

            // طھظپط¹ظٹظ„ ط§ظ„ط·ظ„ط¨ ط§ظ„ظ…ط±طھط¨ط· ظ„ظˆ ط§ظ„ط­ط§ظ„ط© Paid
            $this->maybeActivateRelatedOrder($invoice);

            return redirect()->route('dashboard.invoices.index')->with('ok', 'طھظ… ط¥ظ†ط´ط§ط، ط§ظ„ظپط§طھظˆط±ط©');
        });
    }

    public function edit(Invoice $invoice)
    {
        return view('dashboard.management.invoices.edit', [
            'invoice'       => $invoice->load('items', 'client'),
            'clients'       => Client::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']),
            'subscriptions' => Subscription::with('client:id,first_name,last_name')
                ->get(['id', 'plan_id', 'client_id']),
            'domains'       => Domain::select('id', 'domain_name', 'status')->get(),
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $allowedItemTypes = $this->allowedItemTypes();

        $data = $request->validate([
            'status'    => ['required', Rule::in(['draft', 'unpaid', 'paid', 'cancelled'])],
            'due_date'  => ['nullable', 'date'],
            'paid_date' => ['nullable', 'date'],
            'items'     => ['required', 'array', 'min:1'],
            'items.*.item_type'        => ['required', Rule::in($allowedItemTypes)],
            'items.*.reference_id'     => ['required', 'integer'],
            'items.*.description'      => ['required', 'string', 'max:255'],
            'items.*.qty'              => ['required', 'integer', 'min:1'],
            'items.*.unit_price_cents' => ['required', 'integer', 'min:0'],
        ]);

        $this->validateReferenceIds($data['items']);

        $due      = $data['due_date'] ? Carbon::parse($data['due_date']) : null;
        $paidDate = $data['paid_date'] ? Carbon::parse($data['paid_date']) : null;
        if ($data['status'] !== 'paid') {
            $paidDate = null;
        }

        return DB::transaction(function () use ($invoice, $data, $due, $paidDate) {
            $totals = $this->computeTotals($data['items']);

            $invoice->update([
                'status'          => $data['status'],
                'due_date'        => $due,
                'paid_date'       => $paidDate,
                'subtotal_cents'  => $totals['subtotal_cents'],
                'discount_cents'  => $totals['discount_cents'],
                'tax_cents'       => $totals['tax_cents'],
                'total_cents'     => $totals['total_cents'],
            ]);

            // طھط­ط¯ظٹط« ط§ظ„ط¨ظ†ظˆط¯ (ط¥ط¹ط§ط¯ط© ط¥ط¯ط®ط§ظ„ ط¨ط³ظٹط·ط© ظˆط¢ظ…ظ†ط©)
            $invoice->items()->delete();
            foreach ($data['items'] as $item) {
                $invoice->items()->create([
                    'item_type'         => $item['item_type'],
                    'reference_id'      => $item['reference_id'],
                    'description'       => $item['description'],
                    'qty'               => $item['qty'],
                    'unit_price_cents'  => $item['unit_price_cents'],
                    'total_cents'       => $item['unit_price_cents'] * $item['qty'],
                ]);
            }

            $this->maybeActivateRelatedOrder($invoice);

            return redirect()->route('dashboard.invoices.index')->with('ok', 'طھظ… طھط­ط¯ظٹط« ط§ظ„ظپط§طھظˆط±ط©');
        });
    }

    public function destroy(Request $request, Invoice $invoice)
    {
        DB::transaction(function () use ($invoice) {
            $invoice->items()->delete();
            $invoice->delete();
        });

        if ($request->ajax()) {
            return response()->json(['ok' => true]);
        }
        return redirect()->route('dashboard.invoices.index')->with('ok', 'طھظ… ط­ط°ظپ ط§ظ„ظپط§طھظˆط±ط©');
    }

    // ط¥ط¬ط±ط§ط، ط¬ظ…ط§ط¹ظٹ ط¹ظ„ظ‰ ط§ظ„ظپظˆط§طھظٹط±
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer|exists:invoices,id',
            'action' => 'required|string',
        ]);

        $ids     = $data['ids'];
        $action  = $data['action'];
        $affected = 0;

        DB::transaction(function () use (&$affected, $ids, $action) {
            if ($action === 'delete') {
                $invoices = Invoice::whereIn('id', $ids)->get();
                foreach ($invoices as $inv) {
                    $inv->items()->delete();
                    $inv->delete();
                }
                $affected = $invoices->count();
                return;
            }

            if (in_array($action, ['draft', 'unpaid', 'paid', 'cancelled'], true)) {
                Invoice::whereIn('id', $ids)->update([
                    'status'    => $action,
                    'paid_date' => $action === 'paid' ? now() : null,
                ]);

                if ($action === 'paid') {
                    $invoices = Invoice::whereIn('id', $ids)->get(['id', 'order_id', 'status']);
                    foreach ($invoices as $inv) {
                        $this->maybeActivateRelatedOrder($inv);
                    }
                }
                $affected = count($ids);
                return;
            }

            if ($action === 'duplicate') {
                $invoices = Invoice::with('items')->whereIn('id', $ids)->get();
                foreach ($invoices as $inv) {
                    $clone = $inv->replicate();
                    $clone->number     = $this->generateUniqueNumber();
                    $clone->status     = 'draft';
                    $clone->paid_date  = null;
                    $clone->due_date   = now()->addDays(7);
                    $clone->created_at = now();
                    $clone->updated_at = now();
                    $clone->save();

                    foreach ($inv->items as $item) {
                        $newItem = $item->replicate();
                        $newItem->invoice_id = $clone->id;
                        $newItem->created_at = now();
                        $newItem->updated_at = now();
                        $newItem->save();
                    }
                }
                $affected = $invoices->count();
                return;
            }

            if ($action === 'reminder') {
                $invoices = Invoice::with('client')->whereIn('id', $ids)->get();
                foreach ($invoices as $inv) {
                    $client = $inv->client;
                    $email  = $client?->email;
                    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        continue;
                    }
                    try {
                        $amount   = number_format(($inv->total_cents ?? 0) / 100, 2);
                        $currency = $inv->currency ?? 'USD';
                        $bodyLines = [
                            "ظ…ط±ط­ط¨ط§ظ‹ {$client->first_name},",
                            '',
                            "ظ‡ط°ظ‡ ط±ط³ط§ظ„ط© طھط°ظƒظٹط± ط¨ظپط§طھظˆط±ط© ط±ظ‚ظ… {$inv->number}.",
                            "ظ‚ظٹظ…ط© ط§ظ„ظپط§طھظˆط±ط©: {$amount} {$currency}.",
                            $inv->due_date ? "طھط§ط±ظٹط® ط§ظ„ط§ط³طھط­ظ‚ط§ظ‚: {$inv->due_date->format('Y-m-d')}." : '',
                            '',
                            'ظٹط±ط¬ظ‰ ط§ظ„طھظˆط§طµظ„ ظ…ط¹ظ†ط§ ظپظٹ ط­ط§ظ„ ظˆط¬ظˆط¯ ط£ظٹ ط§ط³طھظپط³ط§ط±.',
                            'ظپط±ظٹظ‚ Palgoals',
                        ];
                        // ط§ط³طھط®ط¯ظ… Queue ط¥ظ† ظ…طھط§ط­: Mail::to($email)->queue(new YourMailable($inv));
                        Mail::raw(implode(PHP_EOL, array_filter($bodyLines)), function ($message) use ($email, $inv) {
                            $message->to($email)->subject('طھط°ظƒظٹط± ط¨ط§ظ„ط¯ظپط¹ - ' . $inv->number);
                        });
                        $affected++;
                    } catch (\Throwable $e) {
                        Log::error('Failed to send invoice reminder for invoice ' . $inv->id . ': ' . $e->getMessage());
                    }
                }
                return;
            }
        });

        if ($request->ajax()) {
            return response()->json(['affected' => $affected]);
        }
        return redirect()->back()->with('ok', "طھظ… طھظ†ظپظٹط° ط§ظ„ط¥ط¬ط±ط§ط، ط¹ظ„ظ‰ {$affected} ظپط§طھظˆط±ط©(ط§طھ)");
    }

    // ط¹ط±ط¶ ظپط§طھظˆط±ط© ظˆط§ط­ط¯ط©
    public function show(Invoice $invoice)
    {
        $invoice->load(['items.subscription.plan', 'items.domain', 'client']);
        return view('dashboard.management.invoices.show', compact('invoice'));
    }

    protected function allowedItemTypes(): array
    {
        $types = array_keys(config('invoices.item_types', []));
        return !empty($types) ? $types : ['subscription', 'domain'];
    }

    /**
     * طھط­ظ‚ظ‚ ط£ظ† reference_id ظ…ظˆط¬ظˆط¯ ظپط¹ظ„ظٹظ‹ط§ ط­ط³ط¨ ظ†ظˆط¹ ط§ظ„ط¨ظ†ط¯.
     */
    protected function validateReferenceIds(array $items): void
    {
        foreach ($items as $i => $item) {
            if ($item['item_type'] === 'subscription' && !Subscription::where('id', $item['reference_id'])->exists()) {
                abort(422, "Invalid subscription reference at item #" . ($i + 1));
            }
            if ($item['item_type'] === 'domain' && !Domain::where('id', $item['reference_id'])->exists()) {
                abort(422, "Invalid domain reference at item #" . ($i + 1));
            }
        }
    }

    /**
     * ط­ط³ط§ط¨ ط§ظ„ط¥ط¬ظ…ط§ظ„ظٹط§طھ ظ…ط±ط© ظˆط§ط­ط¯ط© (ط¬ط§ظ‡ط²ط© ظ„ط¥ط¶ط§ظپط© ط®طµظˆظ…ط§طھ/ط¶ط±ط§ط¦ط¨ ظ…ط³طھظ‚ط¨ظ„ظ‹ط§).
     */
    protected function computeTotals(array $items): array
    {
        $subtotal = 0;
        foreach ($items as $i) {
            $subtotal += ((int)$i['unit_price_cents']) * ((int)$i['qty']);
        }

        $discount = 0; // ط§ط¯ظ…ط¬ ظƒظˆط¨ظˆظ†ط§طھ/ط®طµظˆظ…ط§طھ ظ‡ظ†ط§ ظ„ط§ط­ظ‚ظ‹ط§
        $tax      = 0; // ط§ط­طھط³ط¨ ط§ظ„ط¶ط±ظٹط¨ط© ظ‡ظ†ط§ ط¹ظ†ط¯ ط§ظ„ط­ط§ط¬ط©

        return [
            'subtotal_cents' => $subtotal,
            'discount_cents' => $discount,
            'tax_cents'      => $tax,
            'total_cents'    => max(0, $subtotal - $discount + $tax),
        ];
    }

    /**
     * طھظˆظ„ظٹط¯ ط±ظ‚ظ… ظپط§طھظˆط±ط© ظپط±ظٹط¯ ظ…ط¹ طھط­ظ‚ظ‘ظ‚ ظ…ظ† ط§ظ„طھط¹ط§ط±ط¶.
     */
    protected function generateUniqueNumber(): string
    {
        do {
            $number = 'INV-' . Str::upper(Str::random(6));
        } while (Invoice::where('number', $number)->exists());

        return $number;
    }

    /**
     * طھظپط¹ظٹظ„ ط§ظ„ط·ظ„ط¨ ط§ظ„ظ…ط±طھط¨ط· ط¨ط§ظ„ظپط§طھظˆط±ط© ط¹ظ†ط¯ظ…ط§ طھطµط¨ط­ ظ…ط¯ظپظˆط¹ط©.
     */
    protected function maybeActivateRelatedOrder(Invoice $invoice): void
    {
        try {
            if ($invoice->status !== 'paid' || !$invoice->order_id) {
                return;
            }
            $order = Order::find($invoice->order_id);
            if (!$order) return;

            if ($order->status !== 'active') {
                $order->status = 'active';
                $order->save();

                // ط¥ظ† ظƒط§ظ† ظ„ط¯ظٹظƒ OrderController::processActivation
                if (class_exists(\App\Http\Controllers\Admin\Management\OrderController::class)) {
                    app(\App\Http\Controllers\Admin\Management\OrderController::class)
                        ->processActivation($order);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed to activate order from invoice ' . $invoice->id . ': ' . $e->getMessage());
        }
    }
}

