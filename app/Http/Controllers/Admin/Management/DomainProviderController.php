<?php

namespace App\Http\Controllers\Admin\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\DomainProviderRequest;
use App\Models\DomainProvider;
use App\Services\DomainProviders\EnomClient;
use App\Services\DomainProviders\NamecheapClient;
use Illuminate\Support\Arr;
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
                    'message' => 'المزوّد غير مفعّل. فعّل ثم جرّب مجددًا.',
                ], 422);
            }

            // ✅ فحص حقول مطلوبة حسب النوع
            $missing = [];
            if (blank($domainProvider->username)) {
                $missing[] = 'username';
            }
            switch ($domainProvider->type) {
                case 'namecheap':
                    foreach (['api_key', 'client_ip'] as $req) {
                        if (blank($domainProvider->{$req})) $missing[] = $req;
                    }
                    break;

                case 'enom':
                    if (
                        blank($domainProvider->password)
                        && blank($domainProvider->api_token)
                        && blank($domainProvider->api_key)
                    ) {
                        $missing[] = 'password/api_token/api_key (واحد على الأقل)';
                    }
                    break;

                default:
                    return response()->json([
                        'ok'      => false,
                        'message' => 'نوع المزود غير مدعوم للاختبار الآلي حاليًا.',
                    ], 422);
            }

            if (!empty($missing)) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'حقول ناقصة: ' . implode(', ', $missing),
                ], 422);
            }

            // ✅ كاش اختياري + دعم fresh=1
            $forceFresh = request()->boolean('fresh') || request()->boolean('bypass_cache');
            $cacheKey   = "dp:balance:{$domainProvider->id}";
            $ttlSeconds = 60; // غيّرها حسب رغبتك

            if (!$forceFresh) {
                if ($cached = cache()->get($cacheKey)) {
                    // cached payload يحتوي على cache => 'hit'
                    return response()
                        ->json($cached, (!empty($cached['ok'])) ? 200 : 422)
                        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                        ->header('Pragma', 'no-cache')
                        ->header('Expires', '0');
                }
            }

            $started = microtime(true);

            switch ($domainProvider->type) {
                case 'enom': {
                        /** @var \App\Services\DomainProviders\EnomClient $client */
                        $client   = app(\App\Services\DomainProviders\EnomClient::class);
                        $r        = $client->getBalance($domainProvider);

                        $ok       = (bool)($r['ok'] ?? false);
                        $balance  = $r['balance'] ?? $r['available'] ?? null;
                        $currency = $r['currency'] ?? null;

                        $msg = $r['message'] ?? null;
                        if (blank($msg)) {
                            $fallbackErr = $r['error']
                                ?? \Illuminate\Support\Arr::get($r, 'errors.0.text')
                                ?? \Illuminate\Support\Arr::get($r, 'errors.0.message')
                                ?? \Illuminate\Support\Arr::get($r, 'Errors.0');
                            $msg = $ok ? 'تم الاتصال بنجاح.' : ($fallbackErr ?: 'تعذّر الاتصال أو بيانات الاعتماد غير صحيحة.');
                        }

                        $durationMs = $r['duration_ms'] ?? (int) round((microtime(true) - $started) * 1000);
                        $payload = [
                            'ok'         => $ok,
                            'reason'     => $r['reason'] ?? ($ok ? 'ok' : 'provider_error'),
                            'message'    => $msg,
                            'currency'   => $currency,
                            'balance'    => $balance,
                            'duration_ms' => $durationMs,
                            'fetched_at' => now()->toIso8601String(),
                            'cache'      => 'miss',
                        ];

                        // خزّن نسخة للكاش (نعلّمها hit لاستخدامها لاحقًا كقراءة سريعة)
                        cache()->put($cacheKey, array_merge($payload, ['cache' => 'hit']), $ttlSeconds);

                        Log::info('Enom provider test summary', [
                            'provider_id' => $domainProvider->id,
                            'ok'          => $ok,
                            'currency'    => $currency,
                            'has_balance' => !is_null($balance),
                            'duration_ms' => $durationMs,
                            'cache'       => 'miss',
                        ]);

                        return response()
                            ->json($payload, $ok ? 200 : 422)
                            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                            ->header('Pragma', 'no-cache')
                            ->header('Expires', '0');
                    }

                case 'namecheap': {
                        $client   = new \App\Services\DomainProviders\NamecheapClient($domainProvider);
                        $r        = $client->getBalance();

                        $ok       = (bool)($r['ok'] ?? false);
                        $balance  = $r['balance'] ?? null;
                        $currency = $r['currency'] ?? null;

                        $msg = $r['message'] ?? null;
                        if (blank($msg)) {
                            $fallbackErr = $r['error']
                                ?? \Illuminate\Support\Arr::get($r, 'errors.0.text')
                                ?? \Illuminate\Support\Arr::get($r, 'errors.0.message')
                                ?? \Illuminate\Support\Arr::get($r, 'Errors.0');
                            $msg = $ok ? 'تم الاتصال بنجاح.' : ($fallbackErr ?: 'تعذّر الاتصال أو بيانات الاعتماد غير صحيحة.');
                        }

                        $durationMs = $r['duration_ms'] ?? (int) round((microtime(true) - $started) * 1000);
                        $payload = [
                            'ok'         => $ok,
                            'reason'     => $r['reason'] ?? ($ok ? 'ok' : 'provider_error'),
                            'message'    => $msg,
                            'currency'   => $currency,
                            'balance'    => $balance,
                            'duration_ms' => $durationMs,
                            'fetched_at' => now()->toIso8601String(),
                            'cache'      => 'miss',
                        ];

                        cache()->put($cacheKey, array_merge($payload, ['cache' => 'hit']), $ttlSeconds);

                        Log::info('Namecheap provider test summary', [
                            'provider_id' => $domainProvider->id,
                            'ok'          => $ok,
                            'currency'    => $currency,
                            'has_balance' => !is_null($balance),
                            'duration_ms' => $durationMs,
                            'cache'       => 'miss',
                        ]);

                        return response()
                            ->json($payload, $ok ? 200 : 422)
                            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                            ->header('Pragma', 'no-cache')
                            ->header('Expires', '0');
                    }
            }
        } catch (\Throwable $e) {
            Log::error('Provider test failed', [
                'provider_id' => $domainProvider->id ?? null,
                'type'        => $domainProvider->type ?? null,
                'error'       => $e->getMessage(),
            ]);
            return response()->json([
                'ok'      => false,
                'message' => 'حدث خطأ أثناء الاختبار. راجع السجلات.',
            ], 500);
        }
    }
}

