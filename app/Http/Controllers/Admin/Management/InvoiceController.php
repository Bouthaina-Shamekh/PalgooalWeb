<?php

namespace App\Http\Controllers\Admin\Management;

use App\Http\Controllers\Controller;
use App\Models\{Invoice, Client, Domain};
use App\Models\Tenancy\Subscription;
use App\Services\Billing\InvoiceSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log, Mail};
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    protected const ADMIN_MANUAL_PAYMENT_METHOD = 'admin_manual';

    public function __construct(
        protected InvoiceSettlementService $settlementService,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

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
        $this->authorize('create', Invoice::class);

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
        $this->authorize('create', Invoice::class);

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
        $shouldMarkPaid = $data['status'] === 'paid';

        $invoice = DB::transaction(function () use ($data, $due, $shouldMarkPaid) {
            $totals  = $this->computeTotals($data['items']);

            $invoice = $this->createInvoiceRecord([
                'client_id'       => $data['client_id'],
                'status'          => $shouldMarkPaid ? 'unpaid' : $data['status'],
                'subtotal_cents'  => $totals['subtotal_cents'],
                'discount_cents'  => $totals['discount_cents'],
                'tax_cents'       => $totals['tax_cents'],
                'total_cents'     => $totals['total_cents'],
                'currency'        => 'USD',
                'due_date'        => $due,
                'paid_date'       => null,
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

            return $invoice;
        });

        if ($shouldMarkPaid && !$this->settleManualPayment($invoice)) {
            return redirect()->route('dashboard.invoices.edit', $invoice)->with(
                'error',
                $this->manualSettlementErrorMessage($invoice),
            );
        }

        return redirect()->route('dashboard.invoices.index')->with(
            'ok',
            t('dashboard.Invoice_Created', 'Invoice created successfully.'),
        );
    }

    public function edit(Invoice $invoice)
    {
        $this->authorize('update', $invoice);

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
        $this->authorize('update', $invoice);

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
        $shouldMarkPaid = false;
        $paidUpdateBlocked = false;
        $activeSessionUpdateBlocked = false;

        DB::transaction(function () use (
            $invoice,
            $data,
            $due,
            &$shouldMarkPaid,
            &$paidUpdateBlocked,
            &$activeSessionUpdateBlocked,
        ) {
            $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if ($lockedInvoice->status === 'paid') {
                $paidUpdateBlocked = true;

                return;
            }

            if ($this->hasActiveHostedPaymentSession($lockedInvoice)) {
                $activeSessionUpdateBlocked = true;

                return;
            }

            $totals = $this->computeTotals($data['items']);
            $requestedPaid = $data['status'] === 'paid';
            $shouldMarkPaid = $requestedPaid;

            $invoiceUpdate = [
                'due_date'        => $due,
                'subtotal_cents'  => $totals['subtotal_cents'],
                'discount_cents'  => $totals['discount_cents'],
                'tax_cents'       => $totals['tax_cents'],
                'total_cents'     => $totals['total_cents'],
            ];

            if (!$requestedPaid) {
                $invoiceUpdate['status'] = $data['status'];
                $invoiceUpdate['paid_date'] = null;
            }

            $lockedInvoice->update($invoiceUpdate);

            // طھط­ط¯ظٹط« ط§ظ„ط¨ظ†ظˆط¯ (ط¥ط¹ط§ط¯ط© ط¥ط¯ط®ط§ظ„ ط¨ط³ظٹط·ط© ظˆط¢ظ…ظ†ط©)
            $lockedInvoice->items()->delete();
            foreach ($data['items'] as $item) {
                $lockedInvoice->items()->create([
                    'item_type'         => $item['item_type'],
                    'reference_id'      => $item['reference_id'],
                    'description'       => $item['description'],
                    'qty'               => $item['qty'],
                    'unit_price_cents'  => $item['unit_price_cents'],
                    'total_cents'       => $item['unit_price_cents'] * $item['qty'],
                ]);
            }

        });

        if ($paidUpdateBlocked) {
            return redirect()->back()->with('error', $this->paidInvoiceImmutableMessage());
        }

        if ($activeSessionUpdateBlocked) {
            return redirect()->back()->with('error', $this->activePaymentSessionImmutableMessage());
        }

        if ($shouldMarkPaid && !$this->settleManualPayment($invoice)) {
            return redirect()->back()->withInput()->with('error', $this->manualSettlementErrorMessage($invoice));
        }

        return redirect()->route('dashboard.invoices.index')->with(
            'ok',
            t('dashboard.Invoice_Updated', 'Invoice updated successfully.'),
        );
    }

    public function destroy(Request $request, Invoice $invoice)
    {
        $this->authorize('delete', $invoice);

        $deleteBlockedBy = DB::transaction(function () use ($invoice): ?string {
            $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if ($lockedInvoice->status === 'paid') {
                return 'paid';
            }

            if ($this->hasActiveHostedPaymentSession($lockedInvoice)) {
                return 'payment_session';
            }

            $lockedInvoice->items()->delete();
            $lockedInvoice->delete();

            return null;
        });

        if ($deleteBlockedBy !== null) {
            $message = $deleteBlockedBy === 'paid'
                ? $this->paidInvoiceImmutableMessage()
                : $this->activePaymentSessionImmutableMessage();

            if ($request->ajax()) {
                return response()->json(['ok' => false, 'message' => $message], 422);
            }

            return redirect()->back()->with('error', $message);
        }

        if ($request->ajax()) {
            return response()->json(['ok' => true]);
        }
        return redirect()->route('dashboard.invoices.index')->with('ok', __('Invoice deleted successfully.'));
    }

    // ط¥ط¬ط±ط§ط، ط¬ظ…ط§ط¹ظٹ ط¹ظ„ظ‰ ط§ظ„ظپظˆط§طھظٹط±
    public function bulk(Request $request)
    {
        $this->authorize('bulk', Invoice::class);

        $data = $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer|exists:invoices,id',
            'action' => 'required|string',
        ]);

        $ids          = $data['ids'];
        $action       = $data['action'];
        $affected     = 0;
        $failed       = 0;
        $skipped      = 0;
        $pendingEmails = []; // جمع بيانات الإيميلات هنا وإرسالها بعد commit

        if ($action === 'paid') {
            $invoices = Invoice::query()->whereIn('id', $ids)->get();

            foreach ($invoices as $invoice) {
                $alreadyPaid = $invoice->status === 'paid';

                if ($this->settleManualPayment($invoice)) {
                    $alreadyPaid ? $skipped++ : $affected++;
                } else {
                    $failed++;
                }
            }

            return $this->bulkResultResponse($request, $affected, $failed, $skipped);
        }

        DB::transaction(function () use (&$affected, &$failed, &$skipped, &$pendingEmails, $ids, $action) {
            if ($action === 'delete') {
                $invoices = Invoice::query()->whereIn('id', $ids)->lockForUpdate()->get();
                foreach ($invoices as $inv) {
                    if ($inv->status === 'paid' || $this->hasActiveHostedPaymentSession($inv)) {
                        $skipped++;
                        continue;
                    }

                    $inv->items()->delete();
                    $inv->delete();
                    $affected++;
                }
                return;
            }

            if (in_array($action, ['draft', 'unpaid', 'cancelled'], true)) {
                $invoices = Invoice::query()
                    ->whereIn('id', $ids)
                    ->lockForUpdate()
                    ->get(['id', 'status', 'payment_session_status']);
                $mutableIds = $invoices
                    ->reject(fn (Invoice $invoice) =>
                        $invoice->status === 'paid' || $this->hasActiveHostedPaymentSession($invoice)
                    )
                    ->pluck('id');

                $skipped = $invoices->count() - $mutableIds->count();

                if ($mutableIds->isNotEmpty()) {
                    $affected = Invoice::query()->whereIn('id', $mutableIds->all())->update([
                        'status'    => $action,
                        'paid_date' => null,
                    ]);
                }

                return;
            }

            if ($action === 'duplicate') {
                $invoices = Invoice::with('items')->whereIn('id', $ids)->lockForUpdate()->get();
                foreach ($invoices as $inv) {
                    if ($inv->items->contains('item_type', 'domain')) {
                        $skipped++;
                        continue;
                    }

                    $clone = $inv->replicate();
                    $clone->order_id = null;
                    $clone->coupon_id = null;
                    $clone->status     = 'draft';
                    $clone->paid_date  = null;
                    $clone->payment_attempt_id = null;
                    $clone->payment_session_attempt_id = null;
                    $clone->payment_session_status = Invoice::PAYMENT_SESSION_IDLE;
                    $clone->due_date   = now()->addDays(7);
                    $clone->created_at = now();
                    $clone->updated_at = now();

                    // Retry on unique-number collision (same TOCTOU protection as createInvoiceRecord).
                    $saved = false;
                    for ($attempt = 0; $attempt < 5; $attempt++) {
                        try {
                            $clone->number = $this->generateUniqueNumber();
                            $clone->save();
                            $saved = true;
                            break;
                        } catch (\Illuminate\Database\QueryException $e) {
                            if ($attempt < 4 && str_contains($e->getMessage(), '23000')) {
                                continue;
                            }
                            throw $e;
                        }
                    }

                    foreach ($inv->items as $item) {
                        $newItem = $item->replicate();
                        $newItem->invoice_id = $clone->id;
                        $newItem->created_at = now();
                        $newItem->updated_at = now();
                        $newItem->save();
                    }

                    $affected++;
                }
                return;
            }

            if ($action === 'reminder') {
                // لا نُرسل الإيميل هنا — نجمع البيانات فقط ليُرسل بعد نجاح الـ commit
                $invoices = Invoice::with('client')->whereIn('id', $ids)->get();
                foreach ($invoices as $inv) {
                    $client = $inv->client;
                    $email  = $client?->email;
                    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        continue;
                    }
                    $pendingEmails[] = [
                        'to'      => $email,
                        'subject' => __('Payment Reminder') . ' - ' . $inv->number,
                        'name'    => $client->first_name,
                        'number'  => $inv->number,
                        'amount'  => number_format(($inv->total_cents ?? 0) / 100, 2),
                        'currency'=> $inv->currency ?? 'USD',
                        'due_date'=> $inv->due_date?->format('Y-m-d'),
                        'inv_id'  => $inv->id,
                    ];
                }
                return;
            }
        });

        // إرسال الإيميلات بعد نجاح الـ commit — لا خطر من rollback هنا
        foreach ($pendingEmails as $mail) {
            try {
                $bodyLines = [
                    __('Hello :name,', ['name' => $mail['name']]),
                    '',
                    __('This is a payment reminder for invoice :number.', ['number' => $mail['number']]),
                    __('Invoice amount: :amount :currency.', ['amount' => $mail['amount'], 'currency' => $mail['currency']]),
                    $mail['due_date'] ? __('Due date: :date.', ['date' => $mail['due_date']]) : '',
                    '',
                    __('Please contact us if you have any questions.'),
                    __('Palgoals Team'),
                ];
                Mail::raw(implode(PHP_EOL, array_filter($bodyLines)), function ($message) use ($mail) {
                    $message->to($mail['to'])->subject($mail['subject']);
                });
                $affected++;
            } catch (\Throwable $e) {
                Log::error('Failed to send invoice reminder for invoice ' . $mail['inv_id'] . ': ' . $e->getMessage());
            }
        }

        if ($request->ajax()) {
            return response()->json([
                'affected' => $affected,
                'failed' => $failed,
                'skipped' => $skipped,
            ]);
        }

        if ($action === 'duplicate' && $skipped > 0) {
            $message = $affected === 0
                ? t(
                    'dashboard.Invoice_Domain_Duplicate_Blocked',
                    'This invoice contains domain items and cannot be duplicated safely.',
                )
                : strtr(
                    t(
                        'dashboard.Invoice_Duplicate_Partial',
                        ':affected invoice(s) duplicated; :skipped domain invoice(s) skipped because they cannot be duplicated safely.',
                    ),
                    [':affected' => $affected, ':skipped' => $skipped],
                );

            return redirect()->back()->with('error', $message);
        }

        if ($failed > 0 || $skipped > 0) {
            return redirect()->back()->with('error', strtr(
                t(
                    'dashboard.Invoice_Bulk_Update_Partial',
                    ':affected invoice(s) updated; :failed failed; :skipped paid invoice(s) skipped.',
                ),
                [':affected' => $affected, ':failed' => $failed, ':skipped' => $skipped],
            ));
        }

        return redirect()->back()->with('ok', strtr(
            t('dashboard.Invoices_Updated', ':count invoice(s) updated.'),
            [':count' => $affected],
        ));
    }

    // ط¹ط±ط¶ ظپط§طھظˆط±ط© ظˆط§ط­ط¯ط©
    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        // Use the raw relation names for eager loading; the type-guarded accessors
        // ($item->subscription / $item->domain) will read from these loaded relations.
        $invoice->load(['items.subscriptionRelation.plan', 'items.domainRelation', 'client']);
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
            $num = $i + 1;
            if ($item['item_type'] === 'subscription' && ! Subscription::where('id', $item['reference_id'])->exists()) {
                abort(422, __('Invalid subscription reference at item #:num.', ['num' => $num]));
            }
            if ($item['item_type'] === 'domain' && ! Domain::where('id', $item['reference_id'])->exists()) {
                abort(422, __('Invalid domain reference at item #:num.', ['num' => $num]));
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
    /**
     * Generate a candidate invoice number (no DB check — collision handled at insert time).
     * Uses 8 random chars → ~208 billion combinations, making collisions extremely rare.
     */
    protected function generateUniqueNumber(): string
    {
        return 'INV-' . Str::upper(Str::random(8));
    }

    /**
     * Create an Invoice record, retrying on unique-number constraint violations.
     * Handles the TOCTOU race that a pre-insert existence check cannot prevent.
     *
     * @param  array<string, mixed>  $attributes  All invoice attributes EXCEPT 'number'.
     * @param  int                   $maxAttempts
     * @return \App\Models\Invoice
     */
    protected function createInvoiceRecord(array $attributes, int $maxAttempts = 5): Invoice
    {
        $lastException = null;

        for ($i = 0; $i < $maxAttempts; $i++) {
            try {
                return Invoice::create(
                    array_merge($attributes, ['number' => $this->generateUniqueNumber()])
                );
            } catch (\Illuminate\Database\QueryException $e) {
                // MySQL 23000 / SQLSTATE 23000 = integrity constraint violation (duplicate key).
                if ($i < $maxAttempts - 1 && str_contains($e->getMessage(), '23000')) {
                    $lastException = $e;
                    continue;
                }
                throw $e;
            }
        }

        throw $lastException;
    }

    protected function settleManualPayment(Invoice $invoice): bool
    {
        try {
            $this->settlementService->markPaid(
                $invoice,
                self::ADMIN_MANUAL_PAYMENT_METHOD,
                null,
            );

            return true;
        } catch (\Throwable $e) {
            Log::warning('Admin manual invoice settlement failed.', [
                'invoice_id' => $invoice->id,
                'payment_method' => self::ADMIN_MANUAL_PAYMENT_METHOD,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function bulkResultResponse(
        Request $request,
        int $affected,
        int $failed,
        int $skipped,
    ) {
        if ($request->ajax()) {
            return response()->json(compact('affected', 'failed', 'skipped'));
        }

        if ($failed > 0) {
            return redirect()->back()->with('error', strtr(
                t(
                    'dashboard.Invoice_Bulk_Settlement_Partial',
                    ':affected invoice(s) marked paid; :failed failed; :skipped already paid.',
                ),
                [':affected' => $affected, ':failed' => $failed, ':skipped' => $skipped],
            ));
        }

        $message = $skipped > 0
            ? strtr(
                t(
                    'dashboard.Invoice_Bulk_Paid_Idempotent',
                    ':affected invoice(s) marked paid; :skipped already paid and unchanged.',
                ),
                [':affected' => $affected, ':skipped' => $skipped],
            )
            : strtr(
                t('dashboard.Invoices_Updated', ':count invoice(s) updated.'),
                [':count' => $affected],
            );

        return redirect()->back()->with('ok', $message);
    }

    protected function paidInvoiceImmutableMessage(): string
    {
        return t(
            'dashboard.Paid_Invoice_Immutable',
            'Paid invoices cannot be modified. Use a future refund or accounting adjustment workflow instead.',
        );
    }

    protected function hasActiveHostedPaymentSession(Invoice $invoice): bool
    {
        return in_array($invoice->payment_session_status, [
            Invoice::PAYMENT_SESSION_CREATING,
            Invoice::PAYMENT_SESSION_READY,
        ], true);
    }

    protected function activePaymentSessionImmutableMessage(): string
    {
        return t(
            'dashboard.Invoice_Active_Payment_Session_Immutable',
            'This invoice has an active hosted payment session and cannot be modified until that payment session is resolved.',
        );
    }

    protected function manualSettlementErrorMessage(Invoice $invoice): string
    {
        $sessionStatus = $invoice->fresh()?->payment_session_status ?: Invoice::PAYMENT_SESSION_IDLE;

        if (in_array($sessionStatus, [
            Invoice::PAYMENT_SESSION_CREATING,
            Invoice::PAYMENT_SESSION_READY,
        ], true)) {
            return t(
                'dashboard.Invoice_Manual_Settlement_Active_Session',
                'This invoice has an active hosted payment session. Resolve it before marking the invoice paid manually.',
            );
        }

        return t(
            'dashboard.Invoice_Manual_Settlement_Failed',
            'The invoice could not be marked paid. Review the logs and try again.',
        );
    }
}

