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
            $templateId = null;
            if ($order->invoices && $order->invoices->count()) {
                $firstInvoice = $order->invoices->first();
                $firstItem = $firstInvoice->items->first();
                if ($firstItem && $firstItem->item_type === 'subscription') {
                    $templateId = $firstItem->reference_id;
                }
            }
            if ($templateId) {
                $template = \App\Models\Template::find($templateId);
                if ($template && $template->plan_id) {
                    // حساب مدة الاشتراك حسب الخطة (شهرية أو سنوية)
                    $duration = 'month';
                    if (isset($template->plan) && method_exists($template->plan, 'getDurationUnit')) {
                        $duration = $template->plan->getDurationUnit(); // يجب أن تعيد 'month' أو 'year'
                    } elseif (isset($template->plan) && isset($template->plan->duration_unit)) {
                        $duration = $template->plan->duration_unit; // عمود في جدول plans
                    }
                    $startsAt = now();
                    $endsAt = $duration === 'year' ? $startsAt->copy()->addYear() : $startsAt->copy()->addMonth();

                    // أحصل على server_id من الخطة إن وُجد، وإلا استخدم قيمة موجودة في الطلب إن كانت متوفرة
                    $serverId = null;
                    if (isset($template->plan) && isset($template->plan->server_id)) {
                        $serverId = $template->plan->server_id;
                    } elseif (isset($order->server_id)) {
                        $serverId = $order->server_id;
                    }

                    // تحقق أولاً إن كان هناك اشتراك موجود لنفس العميل والخطة (وبدلاً من إنشائه مرتين نحدّثه)
                    $existingSubQuery = \App\Models\Subscription::where('client_id', $order->client_id)
                        ->where('plan_id', $template->plan_id);
                    if (!empty($order->domain_name)) {
                        $existingSubQuery->where('domain_name', $order->domain_name);
                    }
                    $existingSub = $existingSubQuery->first();

                    // مولّد اسم مستخدم فريد
                    $generateUsername = function () use ($order) {
                        $username = null;
                        // إذا حُدد دومين في الطلب، استخدمه كأساس للاسم
                        if (!empty($order->domain_name)) {
                            $base = str_replace('.', '', $order->domain_name);
                            $base = strtolower(preg_replace('/[^a-z0-9]/i', '', $base));
                            $base = substr($base, 0, 12);
                            $username = $base;
                        }

                        // ثم انظر إن هناك قيمة username ضمن بيانات إضافية
                        if (empty($username) && isset($order->extra) && is_array($order->extra) && !empty($order->extra['username'] ?? null)) {
                            $username = preg_replace('/[^a-z0-9]/i', '', $order->extra['username']);
                        }

                        // أخيراً استعمل بيانات العميل كحل احتياطي
                        if (empty($username)) {
                            $client = \App\Models\Client::find($order->client_id);
                            $base = null;
                            if ($client) {
                                if (!empty($client->email) && strpos($client->email, '@') !== false) {
                                    $base = explode('@', $client->email)[0];
                                } else {
                                    $base = ($client->first_name ?? '') . ($client->last_name ?? '');
                                }
                            }
                            $base = strtolower(preg_replace('/[^a-z0-9]/i', '', $base ?? 'user'));
                            $base = substr($base, 0, 12);
                            $username = $base;
                        }

                        // تأكد من التفرد داخل جدول الاشتراكات، أضف لاحقة متزايدة عند التصادم
                        $candidate = $username;
                        $suffix = 0;
                        while (\App\Models\Subscription::where('username', $candidate)->exists()) {
                            $suffix++;
                            $candidate = $username . $suffix;
                            if ($suffix > 1000) break; // أمان لعدم الدخول في حلقة لانهائية
                        }
                        return $candidate;
                    };

                    if ($existingSub) {
                        // حدّث الاشتراك بدل إنشاء واحد جديد
                        $updateData = [
                            'status' => 'active',
                            'starts_at' => $startsAt,
                            'ends_at' => $endsAt,
                            'domain_option' => $order->domain_option,
                            'domain_name' => $order->domain_name,
                        ];
                        if (empty($existingSub->server_id) && $serverId) {
                            $updateData['server_id'] = $serverId;
                        }
                        if (empty($existingSub->username)) {
                            $updateData['username'] = $generateUsername();
                        }
                        $existingSub->update($updateData);
                    } else {
                        // لم يوجد اشتراك سابق، ننشئ واحداً جديداً
                        \App\Models\Subscription::create([
                            'client_id' => $order->client_id,
                            'plan_id' => $template->plan_id,
                            'status' => 'active',
                            'price' => $template->price,
                            'starts_at' => $startsAt,
                            'ends_at' => $endsAt,
                            'server_id' => $serverId,
                            'username' => $generateUsername(),
                            'domain_option' => $order->domain_option,
                            'domain_name' => $order->domain_name,
                        ]);
                    }
                }
            }
        }

        return redirect()->route('dashboard.orders.show', $order->id)->with('success', 'تم تحديث حالة الطلب بنجاح');
    }
}
