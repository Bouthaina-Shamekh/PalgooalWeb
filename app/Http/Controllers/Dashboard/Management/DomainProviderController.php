<?php

namespace App\Http\Controllers\Dashboard\Management;

use App\Http\Controllers\Controller;
use App\Models\DomainProvider;
use Illuminate\Http\Request;

class DomainProviderController extends Controller
{
    // عرض جميع المزودين
    public function index()
    {
        $providers = DomainProvider::orderBy('name')->get();
        return view('dashboard.management.domain_providers.index', compact('providers'));
    }

    // عرض نموذج إضافة مزود جديد
    public function create()
    {
        return view('dashboard.management.domain_providers.create');
    }

    // حفظ مزود جديد
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'endpoint' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'api_token' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'mode' => 'required|in:live,test',
        ]);
        DomainProvider::create($data);
        return redirect()->route('dashboard.domain_providers.index')->with('ok', 'تم إضافة المزود بنجاح');
    }

    // عرض نموذج تعديل مزود
    public function edit(DomainProvider $domainProvider)
    {
        return view('dashboard.management.domain_providers.edit', compact('domainProvider'));
    }

    // تحديث بيانات مزود
    public function update(Request $request, DomainProvider $domainProvider)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'endpoint' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'api_token' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'mode' => 'required|in:live,test',
        ]);
        $domainProvider->update($data);
        return redirect()->route('dashboard.domain_providers.index')->with('ok', 'تم تحديث المزود بنجاح');
    }

    // حذف مزود
    public function destroy(DomainProvider $domainProvider)
    {
        $domainProvider->delete();
        return redirect()->route('dashboard.domain_providers.index')->with('ok', 'تم حذف المزود بنجاح');
    }

    // اختبار الاتصال بالمزود (dummy, للتوسعة لاحقاً)
    public function testConnection(DomainProvider $domainProvider)
    {
        // اختبار الاتصال الفعلي حسب نوع المزود
        try {
            if ($domainProvider->type === 'enom') {
                // اختيار endpoint تلقائياً حسب وضع الاتصال إذا لم يتم إدخال رابط مخصص
                $endpoint = $domainProvider->endpoint;
                if (empty($endpoint)) {
                    $endpoint = $domainProvider->mode === 'test'
                        ? 'https://resellertest.enom.com/interface.asp'
                        : 'https://reseller.enom.com/interface.asp';
                }
                // مثال: طلب get balance من Enom
                $client = new \GuzzleHttp\Client();
                $response = $client->request('POST', $endpoint, [
                    'form_params' => [
                        'command' => 'GetBalance',
                        'UID' => $domainProvider->username,
                        'PW' => $domainProvider->password,
                        'ApiToken' => $domainProvider->api_token,
                        'ResponseType' => 'JSON',
                    ],
                    'timeout' => 10,
                ]);
                $body = json_decode($response->getBody(), true);
                if (isset($body['ErrCount']) && $body['ErrCount'] == 0) {
                    return response()->json(['ok' => true, 'message' => 'تم الاتصال بنجاح. الرصيد: ' . ($body['Balance'] ?? '-')]);
                } else {
                    // عرض جميع الأخطاء من الاستجابة
                    $errors = [];
                    if (isset($body['errors']) && is_array($body['errors'])) {
                        foreach ($body['errors'] as $key => $error) {
                            $errors[] = $error;
                        }
                    }
                    $msg = count($errors) ? implode(' | ', $errors) : 'فشل الاتصال أو بيانات خاطئة';
                    // إضافة محتوى الاستجابة بالكامل
                    $fullBody = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                    return response()->json(['ok' => false, 'message' => $msg, 'response' => $fullBody]);
                }
            }
            // يمكن إضافة مزودين آخرين هنا
            return response()->json(['ok' => false, 'message' => 'نوع المزود غير مدعوم للاختبار الآلي حالياً']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => 'خطأ في الاتصال: ' . $e->getMessage()]);
        }
    }
}
