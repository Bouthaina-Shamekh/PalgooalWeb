<?php

namespace App\Http\Controllers\Dashboard\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\DomainProviderRequest;
use App\Models\DomainProvider;
use App\Services\DomainProviders\EnomClient;
use App\Services\DomainProviders\NamecheapClient;
use Illuminate\Support\Facades\Log;

class DomainProviderController extends Controller
{
    /**
     * عرض جميع المزودين
     */
    public function index()
    {
        $providers = DomainProvider::orderBy('name')->get();
        return view('dashboard.management.domain_providers.index', compact('providers'));
    }

    /**
     * عرض نموذج إضافة مزود جديد
     */
    public function create()
    {
        return view('dashboard.management.domain_providers.create');
    }

    /**
     * حفظ مزود جديد
     */
    public function store(DomainProviderRequest $request)
    {
        $data = $request->validated();

        // إن لم يُرسل mode أو كان فاضيًا، نستنتجه من endpoint
        if (!isset($data['mode']) || blank($data['mode'])) {
            $ep = $data['endpoint'] ?? '';
            $data['mode'] = (str_contains($ep, 'sandbox') || str_contains($ep, 'resellertest')) ? 'test' : 'live';
        }

        // تطبيع بسيط
        $data['name']      = trim($data['name']);
        $data['username']  = $data['username'] ?? null;
        $data['endpoint']  = $data['endpoint'] ?? null;
        $data['client_ip'] = $data['client_ip'] ?? null;

        DomainProvider::create($data);

        return redirect()
            ->route('dashboard.domain_providers.index')
            ->with('ok', 'تم إضافة المزود بنجاح');
    }
    /**
     * عرض نموذج تعديل مزود
     */
    public function edit(DomainProvider $domainProvider)
    {
        return view('dashboard.management.domain_providers.edit', compact('domainProvider'));
    }

    /**
     * تحديث بيانات مزود
     */
    public function update(DomainProviderRequest $request, DomainProvider $domainProvider)
    {
        $data = $request->validated();

        // لا تستبدل الحقول الحسّاسة إن كانت فارغة
        foreach (['password', 'api_token', 'api_key'] as $secret) {
            if (array_key_exists($secret, $data) && blank($data[$secret])) {
                unset($data[$secret]);
            }
        }

        // إن لم يُرسل mode أو كان فاضيًا، نستنتجه من endpoint الحالي/الجديد
        if (!isset($data['mode']) || blank($data['mode'])) {
            $ep = $data['endpoint'] ?? $domainProvider->endpoint ?? '';
            $data['mode'] = (str_contains($ep, 'sandbox') || str_contains($ep, 'resellertest')) ? 'test' : 'live';
        }

        // تطبيع بسيط
        if (isset($data['name']))      $data['name']      = trim($data['name']);
        if (isset($data['username']))  $data['username']  = trim((string) $data['username']);
        if (isset($data['endpoint']))  $data['endpoint']  = trim((string) $data['endpoint']);
        if (isset($data['client_ip'])) $data['client_ip'] = trim((string) $data['client_ip']);

        $domainProvider->update($data);

        return redirect()
            ->route('dashboard.domain_providers.index')
            ->with('ok', 'تم تحديث المزود بنجاح');
    }

    /**
     * حذف مزود
     */
    public function destroy(DomainProvider $domainProvider)
    {
        $domainProvider->delete();
        return redirect()
            ->route('dashboard.domain_providers.index')
            ->with('ok', 'تم حذف المزود بنجاح');
    }

    /**
     * اختبار الاتصال بالمزود
     */
    public function testConnection(DomainProvider $domainProvider)
    {
        try {
            if (!$domainProvider->is_active) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'المزوّد غير مفعّل. فعّل ثم جرّب مجددًا.'
                ], 422);
            }

            switch ($domainProvider->type) {
                case 'enom': {
                        // يتوقّع: username + (password أو api_token) + endpoint (من الواجهة)
                        $enom   = app(EnomClient::class);
                        $result = $enom->getBalance($domainProvider);
                        return response()->json($result, $result['ok'] ? 200 : 422);
                    }

                case 'namecheap': {
                        // يتوقّع: username + api_key + client_ip + endpoint
                        $nc     = new NamecheapClient($domainProvider);
                        $result = $nc->getBalance();
                        return response()->json($result, $result['ok'] ? 200 : 422);
                    }

                default:
                    return response()->json([
                        'ok'      => false,
                        'message' => 'نوع المزود غير مدعوم للاختبار الآلي حاليًا.'
                    ], 422);
            }
        } catch (\Throwable $e) {
            Log::error('Provider test failed', [
                'provider_id' => $domainProvider->id ?? null,
                'type'        => $domainProvider->type ?? null,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);

            return response()->json([
                'ok'      => false,
                'message' => 'حدث خطأ أثناء الاختبار. راجع السجلات.'
            ], 500);
        }
    }
}
