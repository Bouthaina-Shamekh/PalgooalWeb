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
        return redirect()->route('checkout', ['template_id' => $template_id])
            ->with('success', 'تم استلام طلبك بنجاح! وتم إنشاء حسابك تلقائياً. سيتم معالجة الطلب قريباً.');
    }
}
