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
            $affected = Order::whereIn('id', $ids)->update(['status' => $action]);

            if ($action === 'active') {
                $orders = Order::with(['invoices.items', 'items'])->whereIn('id', $ids)->get();
                foreach ($orders as $order) {
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
            $order->status = $newStatus;
            $order->save();

            if ($newStatus === Order::STATUS_ACTIVE) {
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

