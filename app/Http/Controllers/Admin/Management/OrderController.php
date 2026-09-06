<?php

namespace App\Http\Controllers\Admin\Management;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Billing\OrderActivationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log};

class OrderController extends Controller
{
    public function __construct(
        protected OrderActivationService $activationService,
    ) {}

    // عرض جميع الطلبات مع بيانات العميل
    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);
        $q = $request->get('q');
        $status = $request->get('status');
        $type = $request->get('type');
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');

        $query = Order::with('client'); // إن احتجت الدومين في الجدول، أضف 'items'
        if ($q) {
            // Escape LIKE wildcards so searching for "%" or "_" is literal.
            $qLike = '%' . addcslashes($q, '%_\\') . '%';
            $query->where(function ($qr) use ($qLike) {
                $qr->where('order_number', 'like', $qLike)
                    ->orWhereHas('client', function ($qc) use ($qLike) {
                        $qc->where('first_name', 'like', $qLike)
                            ->orWhere('last_name', 'like', $qLike)
                            ->orWhere('email', 'like', $qLike);
                    });
            });
        }
        if ($status) $query->where('status', $status);
        if ($type) $query->where('type', $type);

        // safe sort whitelist
        $allowed = ['created_at', 'order_number', 'status'];
        if (!in_array($sort, $allowed)) $sort = 'created_at';
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        $orders = $query->orderBy($sort, $direction)->paginate(20)->withQueryString();
        return view('dashboard.management.orders.index', compact('orders'));
    }

    // إجراء جماعي على الطلبات (تغيير الحالة أو حذف)
    public function bulk(Request $request)
    {
        $this->authorize('bulk', Order::class);

        $data = $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer',
            'action' => 'required|string|in:pending,active,cancelled,fraud,delete',
        ]);

        $ids    = $data['ids'];
        $action = $data['action'];
        $affected = 0;

        if ($action === 'delete') {
            // Soft delete — recoverable via restore()
            $affected = Order::whereIn('id', $ids)->delete();
        } elseif (in_array($action, ['pending', 'active', 'cancelled', 'fraud'], true)) {
            // TLD-3H.2C — Defense-in-depth: capture each selected order's status BEFORE the
            // mass update below overwrites it. Without this, every order fetched afterward for
            // the activation loop would already read status='active' (the update already ran),
            // making it impossible to tell which ones were genuinely transitioning into active
            // versus already active and merely re-selected. The provisioning layer (3H.2A) is
            // already at-most-once for domains, but OrderActivationService unconditionally
            // re-extends subscription end dates and re-dispatches provisioning on every call —
            // so re-running it for an already-active order is a real, distinct hazard this
            // guard exists to prevent. Non-'active' actions are entirely unaffected.
            $originalStatuses = $action === 'active'
                ? Order::whereIn('id', $ids)->pluck('status', 'id')
                : collect();

            $affected = Order::whereIn('id', $ids)->update(['status' => $action]);

            if ($action === 'active') {
                $orders = Order::with(['invoices.items', 'items'])->whereIn('id', $ids)->get();
                foreach ($orders as $order) {
                    if (($originalStatuses[$order->id] ?? null) === Order::STATUS_ACTIVE) {
                        // Already active before this bulk action — never re-run activation.
                        // Processed independently: this never prevents any other selected
                        // (non-active) order from being activated below.
                        continue;
                    }

                    // Each order activation is wrapped in its own transaction so one failure
                    // doesn't prevent the others from being processed.
                    try {
                        DB::transaction(function () use ($order) {
                            $this->activationService->activate($order);
                        });
                    } catch (\Throwable $e) {
                        Log::error('Bulk activation failed for order ' . $order->id . ': ' . $e->getMessage());
                    }
                }
            }
        }

        return redirect()->back()->with('ok', strtr(t('dashboard.Orders_Updated', ':count order(s) updated.'), [':count' => $affected]));
    }

    /**
     * @deprecated  Delegates to OrderActivationService::activate().
     *              Kept for backwards compatibility — call the service directly instead.
     */
    public function processActivation(\App\Models\Order $order, ?string $paymentMethod = null): array
    {
        return $this->activationService->activate($order, $paymentMethod);
    }

    public function show($id)
    {
        $order = \App\Models\Order::with(['client', 'items', 'invoices.items'])->findOrFail($id);
        $this->authorize('view', $order);
        return view('dashboard.management.orders.show', compact('order'));
    }

    // تغيير حالة الطلب
    public function updateStatus($id, Request $request)
    {
        $order = \App\Models\Order::findOrFail($id);
        $this->authorize('update', $order);

        $request->validate([
            'status' => 'required|in:pending,active,cancelled,fraud',
        ]);

        $newStatus = $request->status;

        DB::transaction(function () use ($order, $newStatus) {
            // TLD-3H.2C — Defense-in-depth: capture the ORIGINAL status before mutating it.
            // An active->active "transition" (re-saving the same status, e.g. re-submitting the
            // same status form) must never re-invoke OrderActivationService::activate() — the
            // provisioning layer is already the real at-most-once guard for domains, but
            // activate() itself unconditionally re-extends subscription end dates and
            // re-dispatches provisioning on every call. A genuine transition INTO active from
            // any other status is completely unaffected.
            $originalStatus = $order->status;

            $order->status = $newStatus;
            $order->save();

            if ($newStatus === Order::STATUS_ACTIVE && $originalStatus !== Order::STATUS_ACTIVE) {
                // Load relations before activation to avoid lazy-loading inside the service.
                $order->loadMissing(['invoices.items', 'items']);
                $this->activationService->activate($order);
            }
        });

        return redirect()
            ->route('dashboard.orders.show', $order->id)
            ->with('ok', t('dashboard.Order_Status_Updated', 'Order status updated successfully.'));
    }
}

