<?php

namespace App\Http\Controllers\Admin\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\DomainProviderRequest;
use App\Models\DomainProvider;
use App\Services\Domains\Clients\EnomClient;
use App\Services\Domains\Clients\NamecheapClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class DomainProviderController extends Controller
{
    /**
     * ط¹ط±ط¶ ط¬ظ…ظٹط¹ ط§ظ„ظ…ط²ظˆط¯ظٹظ†
     */
    public function index()
    {
        $providers = DomainProvider::orderBy('name')->get();
        return view('dashboard.management.domain_providers.index', compact('providers'));
    }

    /**
     * ط¹ط±ط¶ ظ†ظ…ظˆط°ط¬ ط¥ط¶ط§ظپط© ظ…ط²ظˆط¯ ط¬ط¯ظٹط¯
     */
    public function create()
    {
        return view('dashboard.management.domain_providers.create');
    }

    /**
     * ط­ظپط¸ ظ…ط²ظˆط¯ ط¬ط¯ظٹط¯
     */
    public function store(DomainProviderRequest $request)
    {
        $data = $request->validated();

        // ط¥ظ† ظ„ظ… ظٹظڈط±ط³ظ„ mode ط£ظˆ ظƒط§ظ† ظپط§ط¶ظٹظ‹ط§طŒ ظ†ط³طھظ†طھط¬ظ‡ ظ…ظ† endpoint
        if (!isset($data['mode']) || blank($data['mode'])) {
            $ep = $data['endpoint'] ?? '';
            $data['mode'] = (str_contains($ep, 'sandbox') || str_contains($ep, 'resellertest')) ? 'test' : 'live';
        }

        // طھط·ط¨ظٹط¹ ط¨ط³ظٹط·
        $data['name']      = trim($data['name']);
        $data['username']  = $data['username'] ?? null;
        $data['endpoint']  = $data['endpoint'] ?? null;
        $data['client_ip'] = $data['client_ip'] ?? null;

        DomainProvider::create($data);

        return redirect()
            ->route('dashboard.domain_providers.index')
            ->with('ok', 'طھظ… ط¥ط¶ط§ظپط© ط§ظ„ظ…ط²ظˆط¯ ط¨ظ†ط¬ط§ط­');
    }

    /**
     * ط¹ط±ط¶ ظ†ظ…ظˆط°ط¬ طھط¹ط¯ظٹظ„ ظ…ط²ظˆط¯
     */
    public function edit(DomainProvider $domainProvider)
    {
        return view('dashboard.management.domain_providers.edit', compact('domainProvider'));
    }

    /**
     * طھط­ط¯ظٹط« ط¨ظٹط§ظ†ط§طھ ظ…ط²ظˆط¯
     */
    public function update(DomainProviderRequest $request, DomainProvider $domainProvider)
    {
        $data = $request->validated();

        // ظ„ط§ طھط³طھط¨ط¯ظ„ ط§ظ„ط­ظ‚ظˆظ„ ط§ظ„ط­ط³ظ‘ط§ط³ط© ط¥ظ† ظƒط§ظ†طھ ظپط§ط±ط؛ط©
        foreach (['password', 'api_token', 'api_key'] as $secret) {
            if (array_key_exists($secret, $data) && blank($data[$secret])) {
                unset($data[$secret]);
            }
        }

        // ط¥ظ† ظ„ظ… ظٹظڈط±ط³ظ„ mode ط£ظˆ ظƒط§ظ† ظپط§ط¶ظٹظ‹ط§طŒ ظ†ط³طھظ†طھط¬ظ‡ ظ…ظ† endpoint ط§ظ„ط­ط§ظ„ظٹ/ط§ظ„ط¬ط¯ظٹط¯
        if (!isset($data['mode']) || blank($data['mode'])) {
            $ep = $data['endpoint'] ?? $domainProvider->endpoint ?? '';
            $data['mode'] = (str_contains($ep, 'sandbox') || str_contains($ep, 'resellertest')) ? 'test' : 'live';
        }

        // طھط·ط¨ظٹط¹ ط¨ط³ظٹط·
        if (isset($data['name']))      $data['name']      = trim($data['name']);
        if (isset($data['username']))  $data['username']  = trim((string) $data['username']);
        if (isset($data['endpoint']))  $data['endpoint']  = trim((string) $data['endpoint']);
        if (isset($data['client_ip'])) $data['client_ip'] = trim((string) $data['client_ip']);

        $domainProvider->update($data);

        return redirect()
            ->route('dashboard.domain_providers.index')
            ->with('ok', 'طھظ… طھط­ط¯ظٹط« ط§ظ„ظ…ط²ظˆط¯ ط¨ظ†ط¬ط§ط­');
    }

    /**
     * ط­ط°ظپ ظ…ط²ظˆط¯
     */
    public function destroy(DomainProvider $domainProvider)
    {
        $domainProvider->delete();
        return redirect()
            ->route('dashboard.domain_providers.index')
            ->with('ok', 'طھظ… ط­ط°ظپ ط§ظ„ظ…ط²ظˆط¯ ط¨ظ†ط¬ط§ط­');
    }

    /**
     * ط§ط®طھط¨ط§ط± ط§ظ„ط§طھطµط§ظ„ ط¨ط§ظ„ظ…ط²ظˆط¯
     */
    public function testConnection(DomainProvider $domainProvider)
    {
        try {
            if (!$domainProvider->is_active) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'ط§ظ„ظ…ط²ظˆظ‘ط¯ ط؛ظٹط± ظ…ظپط¹ظ‘ظ„. ظپط¹ظ‘ظ„ ط«ظ… ط¬ط±ظ‘ط¨ ظ…ط¬ط¯ط¯ظ‹ط§.',
                ], 422);
            }

            // âœ… ظپط­طµ ط­ظ‚ظˆظ„ ظ…ط·ظ„ظˆط¨ط© ط­ط³ط¨ ط§ظ„ظ†ظˆط¹
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
                        $missing[] = 'password/api_token/api_key (ظˆط§ط­ط¯ ط¹ظ„ظ‰ ط§ظ„ط£ظ‚ظ„)';
                    }
                    break;

                default:
                    return response()->json([
                        'ok'      => false,
                        'message' => 'ظ†ظˆط¹ ط§ظ„ظ…ط²ظˆط¯ ط؛ظٹط± ظ…ط¯ط¹ظˆظ… ظ„ظ„ط§ط®طھط¨ط§ط± ط§ظ„ط¢ظ„ظٹ ط­ط§ظ„ظٹظ‹ط§.',
                    ], 422);
            }

            if (!empty($missing)) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'ط­ظ‚ظˆظ„ ظ†ط§ظ‚طµط©: ' . implode(', ', $missing),
                ], 422);
            }

            // âœ… ظƒط§ط´ ط§ط®طھظٹط§ط±ظٹ + ط¯ط¹ظ… fresh=1
            $forceFresh = request()->boolean('fresh') || request()->boolean('bypass_cache');
            $cacheKey   = "dp:balance:{$domainProvider->id}";
            $ttlSeconds = 60; // ط؛ظٹظ‘ط±ظ‡ط§ ط­ط³ط¨ ط±ط؛ط¨طھظƒ

            if (!$forceFresh) {
                if ($cached = cache()->get($cacheKey)) {
                    // cached payload ظٹط­طھظˆظٹ ط¹ظ„ظ‰ cache => 'hit'
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
                        /** @var \App\Services\Domains\Clients\EnomClient $client */
                        $client   = app(\App\Services\Domains\Clients\EnomClient::class);
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
                            $msg = $ok ? 'طھظ… ط§ظ„ط§طھطµط§ظ„ ط¨ظ†ط¬ط§ط­.' : ($fallbackErr ?: 'طھط¹ط°ظ‘ط± ط§ظ„ط§طھطµط§ظ„ ط£ظˆ ط¨ظٹط§ظ†ط§طھ ط§ظ„ط§ط¹طھظ…ط§ط¯ ط؛ظٹط± طµط­ظٹط­ط©.');
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

                        // ط®ط²ظ‘ظ† ظ†ط³ط®ط© ظ„ظ„ظƒط§ط´ (ظ†ط¹ظ„ظ‘ظ…ظ‡ط§ hit ظ„ط§ط³طھط®ط¯ط§ظ…ظ‡ط§ ظ„ط§ط­ظ‚ظ‹ط§ ظƒظ‚ط±ط§ط،ط© ط³ط±ظٹط¹ط©)
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
                        $client   = new \App\Services\Domains\Clients\NamecheapClient($domainProvider);
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
                            $msg = $ok ? 'طھظ… ط§ظ„ط§طھطµط§ظ„ ط¨ظ†ط¬ط§ط­.' : ($fallbackErr ?: 'طھط¹ط°ظ‘ط± ط§ظ„ط§طھطµط§ظ„ ط£ظˆ ط¨ظٹط§ظ†ط§طھ ط§ظ„ط§ط¹طھظ…ط§ط¯ ط؛ظٹط± طµط­ظٹط­ط©.');
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
                'message' => 'ط­ط¯ط« ط®ط·ط£ ط£ط«ظ†ط§ط، ط§ظ„ط§ط®طھط¨ط§ط±. ط±ط§ط¬ط¹ ط§ظ„ط³ط¬ظ„ط§طھ.',
            ], 500);
        }
    }
}


