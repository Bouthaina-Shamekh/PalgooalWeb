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
        return redirect()->route('dashboard.orders.show', $order->id)->with('success', 'تم تحديث حالة الطلب بنجاح');
    }
}
