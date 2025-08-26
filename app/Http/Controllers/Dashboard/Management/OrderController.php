<?php

namespace App\Http\Controllers\Dashboard\Management;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    // عرض جميع الطلبات مع بيانات العميل
    public function index(Request $request)
    {
        $orders = Order::with('client')->latest()->paginate(20);
        return view('dashboard.management.orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = \App\Models\Order::with('client')->findOrFail($id);
        return view('dashboard.management.orders.show', compact('order'));
    }


    // تغيير حالة الطلب
    public function updateStatus($id, \Illuminate\Http\Request $request)
    {
        $order = \App\Models\Order::findOrFail($id);
        $request->validate([
            'status' => 'required|in:pending,active,cancelled,fraud',
        ]);
        $order->status = $request->status;
        $order->save();


        // عند تفعيل الطلب، فعّل الفواتير المرتبطة (اجعل status = unpaid)
        if ($order->status === 'active') {
            foreach ($order->invoices as $invoice) {
                if ($invoice->status === 'draft') {
                    $invoice->status = 'unpaid';
                    $invoice->save();
                }
            }

            // إنشاء اشتراك جديد للعميل بناءً على الطلب
            // نفترض أن الطلب من نوع "subscription" ويحتوي على قالب (template_id)
            $templateId = null;
            if ($order->invoices && $order->invoices->count()) {
                // جلب أول بند مرتبط بالقالب
                $firstInvoice = $order->invoices->first();
                $firstItem = $firstInvoice->items->first();
                if ($firstItem && $firstItem->item_type === 'subscription') {
                    $templateId = $firstItem->reference_id;
                }
            }
            if ($templateId) {
                $template = \App\Models\Template::find($templateId);
                if ($template && $template->plan_id) {
                    \App\Models\Subscription::create([
                        'client_id' => $order->client_id,
                        'plan_id' => $template->plan_id,
                        'status' => 'active',
                        'price' => $template->price,
                        'starts_at' => now(),
                        'domain_name' => $order->notes, // أو أي قيمة مناسبة
                    ]);
                }
            }
        }

        return redirect()->route('dashboard.orders.show', $order->id)->with('success', 'تم تحديث حالة الطلب بنجاح');
    }
}
