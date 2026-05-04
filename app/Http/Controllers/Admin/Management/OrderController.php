<?php

namespace App\Http\Controllers\Admin\Management;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Billing\OrderActivationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function __construct(
        protected OrderActivationService $activationService,
    ) {}

    // عرض جميع الطلبات مع بيانات العميل
    public function index(Request $request)
    {
        $q = $request->get('q');
        $status = $request->get('status');
        $type = $request->get('type');
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');

        $query = Order::with('client'); // إن احتجت الدومين في الجدول، أضف 'items'
        if ($q) {
            $query->where(function ($qr) use ($q) {
                $qr->where('order_number', 'like', "%$q%")
                    ->orWhereHas('client', function ($qc) use ($q) {
                        $qc->where('first_name', 'like', "%$q%")
                            ->orWhere('last_name', 'like', "%$q%")
                            ->orWhere('email', 'like', "%$q%");
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
        $data = $request->validate([
            'ids'    => 'required|array|min:1',
            'action' => 'required|string',
        ]);

        $ids = $data['ids'];
        $action = $data['action'];
        $affected = 0;

        if ($action === 'delete') {
            $affected = Order::whereIn('id', $ids)->delete(); // سيحذف البنود تباعًا بسبب FK cascade
        } elseif (in_array($action, ['pending', 'active', 'cancelled', 'fraud'])) {
            $affected = Order::whereIn('id', $ids)->update(['status' => $action]);

            if ($action === 'active') {
                $orders = Order::with(['invoices.items', 'items'])->whereIn('id', $ids)->get();
                foreach ($orders as $order) {
                    try {
                        $this->processActivation($order);
                    } catch (\Exception $e) {
                        Log::error('Bulk activation failed for order ' . $order->id . ': ' . $e->getMessage());
                    }
                }
            }
        }

        return redirect()->back()->with('ok', "تم تنفيذ الإجراء على {$affected} طلب(ات)");
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
        $order = \App\Models\Order::with(['client', 'items'])->findOrFail($id);
        return view('dashboard.management.orders.show', compact('order'));
    }

    // تغيير حالة الطلب
    public function updateStatus($id, Request $request)
    {
        $order = \App\Models\Order::findOrFail($id);
        $request->validate([
            'status' => 'required|in:pending,active,cancelled,fraud',
        ]);

        $order->status = $request->status;
        $order->save();

        if ($order->status === 'active') {
            // حمّل العلاقات اللازمة لتقليل الاستعلامات داخل processActivation
            $order->loadMissing(['invoices.items', 'items']);
            $this->processActivation($order);
        }

        return redirect()->route('dashboard.orders.show', $order->id)->with('success', 'تم تحديث حالة الطلب بنجاح');
    }
}

