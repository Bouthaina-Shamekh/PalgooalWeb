<?php

namespace App\Http\Controllers\Dashboard\Management;

use App\Http\Controllers\Controller;
use App\Models\DomainProvider;
use App\Http\Requests\DomainProviderRequest;
use App\Services\DomainProviders\EnomClient;
use Illuminate\Support\Facades\Log;

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
    public function store(DomainProviderRequest $request)
    {
        DomainProvider::create($request->validated());
        return redirect()->route('dashboard.domain_providers.index')->with('ok', 'تم إضافة المزود بنجاح');
    }

    // عرض نموذج تعديل مزود
    public function edit(DomainProvider $domainProvider)
    {
        return view('dashboard.management.domain_providers.edit', compact('domainProvider'));
    }

    // تحديث بيانات مزود
    public function update(DomainProviderRequest $request, DomainProvider $domainProvider)
    {
        $domainProvider->update($request->validated());
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
        try {
            if (!$domainProvider->is_active) {
                return response()->json(['ok' => false, 'message' => 'المزوّد غير مفعّل. فعّل ثم جرّب مجددًا.']);
            }

            switch ($domainProvider->type) {
                case 'enom':
                    $enom = app(EnomClient::class);
                    $result = $enom->getBalance($domainProvider);
                    return response()->json($result);
                default:
                    return response()->json(['ok' => false, 'message' => 'نوع المزود غير مدعوم للاختبار الآلي حاليًا.']);
            }
        } catch (\Throwable $e) {
            Log::error('Provider test failed', [
                'provider_id' => $domainProvider->id,
                'type'        => $domainProvider->type,
                'error'       => $e->getMessage(),
            ]);
            return response()->json(['ok' => false, 'message' => 'حدث خطأ أثناء الاختبار. راجع السجلات.']);
        }
    }
}
