<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;


use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function index($template_id)
    {
        $template = \App\Models\Template::find($template_id);
        // جلب الترجمة الحالية حسب اللغة
        $translation = $template?->translations()->where('locale', app()->getLocale())->first();
        return view('tamplate.checkout', compact('template_id', 'template', 'translation'));
    }

    public function process(Request $request, $template_id)
    {
        // إذا لم يكن العميل مسجل دخول، أنشئ حساب جديد وسجله دخول تلقائياً
        if (!auth('client')->check()) {
            $request->validate([
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'email' => 'required|email|max:255|unique:clients,email',
                'phone' => 'required|string|max:30',
                'password' => 'required|string|min:6|confirmed',
            ]);
            $client = new \App\Models\Client();
            $client->first_name = $request->first_name;
            $client->last_name = $request->last_name;
            $client->email = $request->email;
            $client->phone = $request->phone;
            $client->company_name = $request->company_name ?? '-';
            $client->password = bcrypt($request->password);
            $client->save();
            auth('client')->login($client);
        }
        // هنا منطق معالجة الطلب (حجز الدومين، إنشاء الاشتراك، إلخ)
        // مثال: بيانات الطلب (يجب استبدالها بالبيانات الحقيقية بعد إنشاء الطلب)
        $order_no = 'ORD-' . rand(1000, 9999); // رقم عشوائي للمعاينة
        $domain = $request->domain ?? null;
        $total = $request->total ?? null;
        $client_name = auth('client')->check() ? (auth('client')->user()->first_name ?? '') : '';

        // بيانات الفاتورة (تجريبي: يجب حسابها فعلياً من الطلب)
        $template = \App\Models\Template::find($template_id);
        $translation = $template?->translations()->where('locale', app()->getLocale())->first();
        $template_name = $translation?->name ?? $template?->name ?? '';
        // سعر القالب
        $basePrice = (float) ($template->price ?? 0);
        $discRaw   = $template->discount_price;
        $discPrice = is_null($discRaw) ? null : (float) $discRaw;
        $hasDiscount = !is_null($discPrice) && $discPrice > 0 && $discPrice < $basePrice;
        $showDiscount = $hasDiscount && (!$template->discount_ends_at || now()->lt($template->discount_ends_at));
        // إنشاء الطلب (Order) أولاً
        $order = null;
        if (auth('client')->check()) {
            $order = \App\Models\Order::create([
                'client_id' => auth('client')->id(),
                'order_number' => $order_no,
                'status' => 'pending',
                'type' => 'subscription',
                'notes' => 'طلب عبر صفحة checkout',
            ]);
        }

        // ثم إنشاء الفاتورة الإدارية (Invoice) وربطها بالطلب والعميل بعد تعريف كل المتغيرات المطلوبة
        if ($order) {
            $invoice = \App\Models\Invoice::create([
                'client_id' => $order->client_id,
                'number' => 'INV-' . $order_no,
                'status' => 'draft',
                'subtotal_cents' => (int)($basePrice * 100),
                'discount_cents' => $showDiscount ? (int)(($basePrice - $discPrice) * 100) : 0,
                'tax_cents' => 0,
                'total_cents' => $showDiscount ? (int)($discPrice * 100) : (int)($basePrice * 100),
                'currency' => 'USD',
                'due_date' => now()->addDays(3),
                'order_id' => $order->id,
            ]);
            // إضافة بند الفاتورة (القالب/الاشتراك)
            \App\Models\InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'item_type' => 'subscription',
                'reference_id' => $template_id,
                'description' => $template_name,
                'qty' => 1,
                'unit_price_cents' => $showDiscount ? (int)($discPrice * 100) : (int)($basePrice * 100),
                'total_cents' => $showDiscount ? (int)($discPrice * 100) : (int)($basePrice * 100),
            ]);
        }
        // إرسال الطلب مباشرة إلى لوحة الإدارة (الفواتير) بعد إنشائه
        try {
            \App\Models\Invoice::create([
                'order_no' => $order_no,
                'client_name' => $client_name,
                'template_name' => $template_name,
                'domain' => $domain,
                'total' => $total,
                'status' => 'pending',
                // أضف أي حقول أخرى مطلوبة في جدول الفواتير الإدارية
            ]);
        } catch (\Exception $e) {
            // يمكنك تسجيل الخطأ أو تجاهله حسب الحاجة
        }
        // سعر القالب
        $basePrice = (float) ($template->price ?? 0);
        $discRaw   = $template->discount_price;
        $discPrice = is_null($discRaw) ? null : (float) $discRaw;
        $hasDiscount = !is_null($discPrice) && $discPrice > 0 && $discPrice < $basePrice;
        $showDiscount = $hasDiscount && (!$template->discount_ends_at || now()->lt($template->discount_ends_at));
        // نفس منطق ملخص الطلب في الواجهة
        if ($showDiscount) {
            $template_price_html = '<span class="line-through text-gray-400">$' . number_format($basePrice, 2) . '</span> <span class="text-red-600 font-bold ms-2">$' . number_format($discPrice, 2) . '</span>';
        } else {
            $template_price_html = '$' . number_format($basePrice, 2);
        }
        // سعر الدومين (اجعلها ديناميكية حسب الطلب الفعلي)
        $domain_price = '$10.00';
        // الخصم (اجعلها ديناميكية حسب الطلب الفعلي)
        $discount = $showDiscount ? '$' . number_format($basePrice - $discPrice, 2) : '$0.00';
        $tax = '$0.00';

        $responseData = [
            'success' => true,
            'order_no' => $order_no,
            'domain' => $domain,
            'total' => $total,
            'client_name' => $client_name,
            'template_name' => $template_name,
            'domain_price' => $domain_price,
            'discount' => $discount,
            'tax' => $tax,
            'template_price_html' => $template_price_html,
        ];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($responseData);
        }
        // إذا لم يكن AJAX (طلب عادي)، أعد توجيه المستخدم للصفحة 3 (نجاح الطلب) مع بيانات الطلب
        return redirect()->route('checkout', array_merge([
            'template_id' => $template_id,
            'success' => 1,
            'order_no' => $order_no,
            'domain' => $domain,
            'total' => $total,
            'client_name' => $client_name,
            'template_name' => $template_name,
            'domain_price' => $domain_price,
            'discount' => $discount,
            'tax' => $tax,
            'template_price_html' => $template_price_html,
        ]));
    }
}
