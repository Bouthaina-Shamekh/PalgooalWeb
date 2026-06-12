<?php

namespace App\Http\Controllers\Admin\Management;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Server;
use App\Models\Tenancy\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ServerController extends Controller
{
    public function accounts(Server $server)
    {
        $this->authorize('viewAny', Server::class);

        $host     = (!empty($server->hostname) && trim($server->hostname) !== '') ? $server->hostname : $server->ip;
        $username = $server->username;
        $apiToken = $server->api_token;
        $accounts = [];
        $error    = null;

        if ($host && $username && $apiToken) {
            $apiUrl = "https://{$host}:2087/json-api/listaccts?api.version=1";
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: whm ' . $username . ':' . $apiToken]);
                $response = curl_exec($ch);
                if (curl_errno($ch)) {
                    $error = curl_error($ch);
                } else {
                    $data = json_decode($response, true);
                    // WHM API v1 يرجع data.acct، بعض الإصدارات ترجع acct مباشرة
                    if (isset($data['data']['acct']) && is_array($data['data']['acct'])) {
                        $accounts = $data['data']['acct'];
                    } elseif (isset($data['acct']) && is_array($data['acct'])) {
                        $accounts = $data['acct'];
                    } else {
                        $error = $data['metadata']['reason'] ?? $data['data']['reason'] ?? $data['reason'] ?? 'لم يتم العثور على بيانات.';
                    }
                }
                curl_close($ch);
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'يجب توفر hostname أو IP واسم المستخدم وAPI Token.';
        }

        // جلب الاشتراكات الموجودة لهذا السيرفر ومقارنتها بالحسابات
        $linkedUsernames = Subscription::where('server_id', $server->id)
            ->whereNotNull('cpanel_username')
            ->pluck('cpanel_username')
            ->map(fn($u) => strtolower(trim($u)))
            ->toArray();

        $linkedDomains = Subscription::where('server_id', $server->id)
            ->whereNotNull('domain_name')
            ->pluck('domain_name')
            ->map(fn($d) => strtolower(trim($d)))
            ->toArray();

        // إضافة حقل is_linked لكل حساب
        foreach ($accounts as &$acc) {
            $accUser   = strtolower(trim($acc['user'] ?? ''));
            $accDomain = strtolower(trim($acc['domain'] ?? ''));
            $acc['is_linked'] = in_array($accUser, $linkedUsernames)
                             || in_array($accDomain, $linkedDomains);
        }
        unset($acc);

        // جلب الباقات لعرضها في modal الإنشاء
        $plans = Plan::orderBy('name')->get(['id', 'name']);

        $linkedCount   = count(array_filter($accounts, fn($a) => $a['is_linked']));
        $unlinkedCount = count($accounts) - $linkedCount;

        return view('dashboard.management.servers.accounts',
            compact('server', 'accounts', 'error', 'plans', 'linkedCount', 'unlinkedCount'));
    }

    /**
     * إنشاء عميل + اشتراك من حساب WHM
     */
    public function importAccount(Request $request, Server $server)
    {
        $this->authorize('update', $server);

        $request->validate([
            'cpanel_username' => 'required|string|max:100',
            'domain_name'     => 'required|string|max:255',
            'email'           => 'required|email|max:255',
            'plan_id'         => 'required|exists:plans,id',
            'billing_cycle'   => 'required|in:monthly,annually',
            'starts_at'       => 'nullable|date',
            'server_package'  => 'nullable|string|max:100',
        ]);

        // إيجاد أو إنشاء العميل بناءً على الإيميل
        $client = Client::firstOrCreate(
            ['email' => $request->email],
            [
                'first_name' => $request->cpanel_username,
                'last_name'  => '',
                'status'     => 'active',
                'can_login'  => true,
                'password'   => Hash::make(Str::random(16)),
            ]
        );

        // إنشاء الاشتراك
        $subscription = Subscription::create([
            'client_id'       => $client->id,
            'plan_id'         => $request->plan_id,
            'server_id'       => $server->id,
            'status'          => 'active',
            'billing_cycle'   => $request->billing_cycle,
            'price'           => 0,
            'username'        => $request->cpanel_username,
            'cpanel_username' => $request->cpanel_username,
            'domain_name'     => $request->domain_name,
            'domain_option'   => 'existing',
            'server_package'  => $request->server_package,
            'starts_at'       => $request->starts_at ?: now(),
        ]);

        return response()->json([
            'ok'              => true,
            'subscription_id' => $subscription->id,
            'client_id'       => $client->id,
            'message'         => 'تم إنشاء الاشتراك بنجاح',
        ]);
    }
    public function index(Request $request)
    {
        $this->authorize('viewAny', Server::class);
        $search  = $request->get('search');
        $perPage = in_array((int) $request->get('per_page'), [10, 25, 50])
            ? (int) $request->get('per_page')
            : 20;

        $servers = Server::latest()
            ->when($search, fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('ip', 'like', "%{$search}%")
                ->orWhere('hostname', 'like', "%{$search}%")
            )
            ->paginate($perPage)
            ->withQueryString();

        return view('dashboard.management.servers.index', compact('servers', 'search', 'perPage'));
    }

    public function create()
    {
        $this->authorize('create', Server::class);
        return view('dashboard.management.servers.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Server::class);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'ip' => 'nullable|string|max:255',
            'hostname' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'api_token' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);
        Server::create($data);
        return redirect()->route('dashboard.servers.index')->with('ok', 'تم إضافة السيرفر بنجاح');
    }

    public function edit(Server $server)
    {
        $this->authorize('update', $server);
        return view('dashboard.management.servers.edit', compact('server'));
    }

    public function update(Request $request, Server $server)
    {
        $this->authorize('update', $server);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'ip' => 'nullable|string|max:255',
            'hostname' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'api_token' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);
        // حماية api_token: لا تحدثه إذا كان الحقل فارغًا
        if (empty($data['api_token'])) {
            unset($data['api_token']);
        }
        $server->update($data);
        return redirect()->route('dashboard.servers.index')->with('ok', 'تم تحديث السيرفر بنجاح');
    }

    public function destroy(Server $server)
    {
        $this->authorize('delete', $server);
        $server->delete();
        return redirect()->route('dashboard.servers.index')->with('ok', 'تم حذف السيرفر');
    }

    public function testConnection(Server $server)
    {
        $this->authorize('update', $server);

        $host     = (!empty($server->hostname) && trim($server->hostname) !== '') ? $server->hostname : $server->ip;
        $username = $server->username;
        $password = $server->password;
        $apiToken = $server->api_token;
        $error    = null;
        $success  = false;

        if (!$host || !$username) {
            return back()->with('connection_result', 'فشل الاتصال: يجب تحديد IP/Hostname واسم المستخدم');
        }

        if ($apiToken) {
            // ── WHM API token: استخدم /json-api/version — يعمل مع root والـ reseller بدون صلاحيات خاصة
            $apiUrl = "https://{$host}:2087/json-api/version?api.version=1";
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: whm ' . $username . ':' . $apiToken,
                ]);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErr  = curl_errno($ch) ? curl_error($ch) : null;
                curl_close($ch);

                if ($curlErr) {
                    $error = $curlErr;
                } else {
                    $data = json_decode($response, true);

                    // نجاح: الـ version endpoint يرجع metadata.result=1 عند التوثيق الصحيح
                    if ($httpCode === 200 && isset($data['metadata']['result']) && $data['metadata']['result'] == 1) {
                        $success = true;
                    } elseif ($httpCode === 200 && isset($data['data']['version'])) {
                        // بعض الإصدارات ترجع data.version مباشرة بدون metadata
                        $success = true;
                    } else {
                        $reason = $data['metadata']['reason']
                                ?? $data['cpanelresult']['error']
                                ?? $data['reason']
                                ?? null;

                        if ($reason) {
                            $error = $reason;
                            // تلميح مفيد إذا كان الخطأ Access denied
                            if (str_contains(strtolower($reason), 'access denied') || str_contains($reason, '1014')) {
                                $error .= ' — تأكد أن التوكن نوعه WHM API Token (يُنشأ من WHM → Manage API Tokens) وليس cPanel API Token';
                            }
                        } else {
                            $error = 'استجابة غير متوقعة من السيرفر (HTTP ' . $httpCode . '): ' . substr($response, 0, 200);
                        }
                    }
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }

        } elseif ($password) {
            // ── كلمة مرور: تسجيل دخول عبر WHM login endpoint
            $url = "https://{$host}:2087/login/?login_only=1";
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['user' => $username, 'pass' => $password]));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErr  = curl_errno($ch) ? curl_error($ch) : null;
                curl_close($ch);

                if ($curlErr) {
                    $error = $curlErr;
                } else {
                    $data = json_decode($response, true);
                    if ($httpCode === 200 && isset($data['status']) && $data['status'] == 1) {
                        $success = true;
                    } else {
                        $error = $data['reason'] ?? 'بيانات الدخول غير صحيحة';
                    }
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'يجب إدخال API Token أو كلمة المرور';
        }

        return back()->with(
            'connection_result',
            $success
                ? 'تم الاتصال وتوثيق الدخول بنجاح ✓'
                : 'فشل الاتصال: ' . $error
        );
    }

    public function ssoWhm(Server $server)
    {
        $this->authorize('update', $server);

        $host     = (!empty($server->hostname) && trim($server->hostname) !== '') ? $server->hostname : $server->ip;
        $username = $server->username;
        $password = $server->password;
        $apiToken = $server->api_token;

        if (!$host || !$username || (!$apiToken && !$password)) {
            return back()->with('connection_result', 'يجب تحديد IP/Hostname واسم المستخدم وAPI Token أو كلمة المرور');
        }

        $authHeader = $apiToken
            ? 'Authorization: whm ' . $username . ':' . $apiToken
            : 'Authorization: Basic ' . base64_encode($username . ':' . $password);

        // ── محاولة ١: WHM session (root أو reseller مع صلاحية create-user-session)
        $loginUrl = $this->tryCreateSession($host, 2087, $username, $authHeader, 'whostmgrd');

        // ── محاولة ٢: cPanel session (reseller يملك cPanel account بنفس اسم المستخدم)
        if (!$loginUrl) {
            $loginUrl = $this->tryCreateSession($host, 2087, $username, $authHeader, 'cpaneld');
        }

        if ($loginUrl) {
            return redirect()->away($loginUrl);
        }

        // ── Fallback: فتح صفحة WHM مباشرة (المستخدم يُدخل بياناته يدوياً)
        $directUrl = "https://{$host}:2087/";
        return back()->with(
            'connection_result',
            'تعذّر إنشاء رابط دخول تلقائي (الحساب reseller). ' .
            'يمكنك <a href="' . e($directUrl) . '" target="_blank" class="alert-link">فتح WHM مباشرة</a> ' .
            'وتسجيل الدخول يدوياً. ' .
            'ملاحظة: التوكن يجب أن يكون WHM API Token (من WHM → Manage API Tokens) وليس cPanel API Token.'
        );
    }

    /**
     * محاولة إنشاء session عبر WHM create_user_session API.
     * ترجع الـ URL عند النجاح، أو null عند الفشل.
     */
    private function tryCreateSession(string $host, int $port, string $username, string $authHeader, string $service): ?string
    {
        $apiUrl = "https://{$host}:{$port}/json-api/create_user_session?api.version=1"
                . "&user=" . urlencode($username)
                . "&service=" . urlencode($service);
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [$authHeader]);
            $response = curl_exec($ch);
            $curlErr  = curl_errno($ch) ? curl_error($ch) : null;
            curl_close($ch);

            if ($curlErr || !$response) {
                return null;
            }
            $data = json_decode($response, true);
            return $data['data']['url'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Return available packages from the server (WHM) as JSON.
     */
    public function packages(Server $server)
    {
        $this->authorize('update', $server);
        $host = (!empty($server->hostname) && trim($server->hostname) !== '') ? $server->hostname : $server->ip;
        $port = 2087;
        $username = $server->username;
        $apiToken = $server->api_token;
        $error = null;
        $packages = [];

        if ($host && $username && $apiToken) {
            $apiUrl = "https://{$host}:{$port}/json-api/listpkgs?api.version=1";
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                $header = [
                    'Authorization: whm ' . $username . ':' . $apiToken
                ];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                $response = curl_exec($ch);
                if (curl_errno($ch)) {
                    $error = curl_error($ch);
                } else {
                    $data = json_decode($response, true);
                    // Try to extract package names from common response shapes
                    if (is_array($data)) {
                        $itemsSources = [];

                        // If WHM returns data.pkg (common): dive into that
                        if (!empty($data['data']) && is_array($data['data'])) {
                            $d = $data['data'];
                            // direct arrays inside data (pkg, packages)
                            if (!empty($d['pkg']) && is_array($d['pkg'])) {
                                $itemsSources[] = $d['pkg'];
                            } elseif (!empty($d['packages']) && is_array($d['packages'])) {
                                $itemsSources[] = $d['packages'];
                            } elseif (array_values($d) === $d) {
                                // data is itself a numeric array of items
                                $itemsSources[] = $d;
                            } else {
                                // find first numeric-indexed child inside data
                                foreach ($d as $child) {
                                    if (is_array($child) && array_values($child) === $child) {
                                        $itemsSources[] = $child;
                                    }
                                }
                            }
                        }

                        // Top-level alternatives
                        if (!empty($data['pkg']) && is_array($data['pkg'])) $itemsSources[] = $data['pkg'];
                        if (!empty($data['packages']) && is_array($data['packages'])) $itemsSources[] = $data['packages'];
                        if (!empty($data['cpanelresult']['data']) && is_array($data['cpanelresult']['data'])) {
                            $cr = $data['cpanelresult']['data'];
                            if (array_values($cr) === $cr) {
                                $itemsSources[] = $cr;
                            } else {
                                foreach ($cr as $child) {
                                    if (is_array($child) && array_values($child) === $child) $itemsSources[] = $child;
                                }
                            }
                        }
                        if (array_values($data) === $data) $itemsSources[] = $data;

                        // Flatten candidate sources and extract names
                        foreach ($itemsSources as $src) {
                            if (!is_array($src)) continue;
                            // If the source is associative mapping (e.g. ['pkg' => [...]]), skip — we want numeric lists
                            if (array_values($src) !== $src) continue;
                            foreach ($src as $item) {
                                $name = null;
                                if (is_string($item)) {
                                    $name = $item;
                                } elseif (is_array($item)) {
                                    if (!empty($item['name'])) $name = $item['name'];
                                    elseif (!empty($item['pkg'])) $name = $item['pkg'];
                                    elseif (!empty($item['package'])) $name = $item['package'];
                                    elseif (!empty($item['package_name'])) $name = $item['package_name'];
                                }
                                if ($name) $packages[] = trim($name);
                            }
                        }

                        // Remove empties and duplicates while preserving order
                        $packages = array_values(array_unique(array_filter($packages, function ($v) {
                            return is_string($v) && $v !== '';
                        })));

                        // فلترة: أظهر فقط باقات الرسيلر (المسبوقة باسم المستخدم_)
                        // مثال: username=palgooal → يُظهر palgooal_plu, palgooal_abutair فقط
                        $prefix = $username . '_';
                        $filtered = array_values(array_filter($packages, function ($name) use ($prefix) {
                            return str_starts_with($name, $prefix);
                        }));

                        // إذا وُجدت باقات مفلترة استخدمها، وإلا أظهر الكل (fallback للحسابات التي لا تتبع نمط الـ prefix)
                        if (!empty($filtered)) {
                            $packages = $filtered;
                        }

                        // Development helper: if debug and packages empty, include a small sample of the raw response for diagnostics
                        if (empty($packages) && config('app.debug')) {
                            Log::debug('Server packages raw response for server ' . $server->id . ': ' . substr($response ?? '', 0, 1000));
                            $debugSample = substr($response ?? '', 0, 2000);
                        }
                    }
                }
                curl_close($ch);
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'Missing server hostname/IP or credentials (username + API token).';
        }

        if ($error) {
            return response()->json(['error' => $error, 'packages' => $packages], 400);
        }

        if (empty($packages)) {
            return response()->json([
                'packages' => [],
                'warning'  => 'لا توجد باقات لهذا السيرفر. أنشئ الباقات من WHM → Packages → Add a Package بتسجيل الدخول كـ reseller.',
            ]);
        }

        return response()->json(['packages' => $packages]);
    }
}

