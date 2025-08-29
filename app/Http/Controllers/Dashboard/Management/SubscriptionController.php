<?php

namespace App\Http\Controllers\Dashboard\Management;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Client;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function syncWithProvider(Subscription $subscription)
    {
        $server = $subscription->server;
        if (!$server) {
            return back()->with('connection_result', 'لا يوجد سيرفر مرتبط بهذا الاشتراك.');
        }
        $host = (!empty($server->hostname) && trim($server->hostname) !== '') ? $server->hostname : $server->ip;
        $port = 2087;
        $username = $server->username;
        $apiToken = $server->api_token;
        $error = null;
        $result = null;
        if ($host && $username && $apiToken) {
            $params = [
                'username' => $subscription->username,
                'domain' => $subscription->domain_name,
                'plan' => $subscription->plan->slug ?? $subscription->plan->name,
                'contactemail' => $subscription->client->email ?? '',
                'password' => $subscription->password ?? 'TempPass!123',
            ];
            $apiUrl = "https://{$host}:{$port}/json-api/createacct?api.version=1&" . http_build_query($params);
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                $header = [
                    'Authorization: whm ' . $username . ':' . $apiToken,
                ];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                $response = curl_exec($ch);
                if (curl_errno($ch)) {
                    $error = curl_error($ch);
                } else {
                    $data = json_decode($response, true);
                    if (isset($data['metadata']['result']) && $data['metadata']['result'] == 1) {
                        $result = 'تم إنشاء الحساب بنجاح على المزود.';
                    } else {
                        $error = ($data['metadata']['reason'] ?? $data['reason'] ?? 'فشل إنشاء الحساب.') . '<br><pre>' . print_r($data, true) . '</pre>';
                    }
                }
                curl_close($ch);
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'بيانات السيرفر غير مكتملة.';
        }
        return back()->with('connection_result', $result ?: $error);
    }
    public function index()
    {
        $subscriptions = Subscription::with(['client', 'plan'])->latest()->paginate(20);
        return view('dashboard.management.subscriptions.index', compact('subscriptions'));
    }

    /**
     * AJAX: suggest a unique username based on domain, client or preferred value
     */
    public function suggestUsername(Request $request)
    {
        $data = $request->validate([
            'domain_name' => ['nullable', 'string'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'preferred_username' => ['nullable', 'string'],
        ]);

        $makeBase = function ($s) {
            $s = (string) $s;
            $s = strtolower($s);
            // remove dots and non-alphanumeric
            $s = str_replace('.', '', $s);
            $s = preg_replace('/[^a-z0-9]/', '', $s);
            $s = trim($s);
            if ($s === '') {
                return 'user';
            }
            return substr($s, 0, 12);
        };

        $base = null;
        if (!empty($data['domain_name'])) {
            $base = $makeBase($data['domain_name']);
        }

        if (!$base && !empty($data['preferred_username'])) {
            $base = $makeBase($data['preferred_username']);
        }

        if (!$base && !empty($data['client_id'])) {
            $client = Client::find($data['client_id']);
            if ($client) {
                if (!empty($client->email) && strpos($client->email, '@') !== false) {
                    $base = $makeBase(explode('@', $client->email)[0]);
                } else {
                    $name = ($client->first_name ?? '') . ($client->last_name ?? '');
                    $base = $makeBase($name);
                }
            }
        }

        if (!$base) {
            $base = 'user';
        }

        $candidate = $base;
        $suffix = 0;
        // ensure uniqueness
        while (Subscription::where('username', $candidate)->exists()) {
            $suffix++;
            $candidate = $base . $suffix;
            // safety break
            if ($suffix > 1000) break;
        }

        return response()->json(['username' => $candidate, 'unique' => true]);
    }

    public function create()
    {
        $clients = Client::all();
        $plans = Plan::all();
        $servers = \App\Models\Server::where('is_active', 1)->get();
        return view('dashboard.management.subscriptions.create', compact('clients', 'plans', 'servers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'status' => ['required', Rule::in(['pending', 'active', 'suspended', 'cancelled'])],
            'price' => ['required', 'numeric', 'min:0'],
            'username' => ['nullable', 'string', 'max:255'],
            'server_id' => ['nullable', 'integer', 'exists:servers,id'],
            'next_due_date' => ['nullable', 'date'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'domain_option' => ['required', Rule::in(['new', 'subdomain', 'existing'])],
            'domain_name' => ['required_if:domain_option,new,existing'],
        ]);
        Subscription::create($data);
        return redirect()->route('dashboard.subscriptions.index')->with('ok', 'تم إضافة الاشتراك بنجاح');
    }

    public function edit(Subscription $subscription)
    {
        $clients = Client::all();
        $plans = Plan::all();
        $servers = \App\Models\Server::where('is_active', 1)->get();
        return view('dashboard.management.subscriptions.edit', compact('subscription', 'clients', 'plans', 'servers'));
    }

    public function update(Request $request, Subscription $subscription)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'status' => ['required', Rule::in(['pending', 'active', 'suspended', 'cancelled'])],
            'price' => ['required', 'numeric', 'min:0'],
            'username' => ['nullable', 'string', 'max:255'],
            'server_id' => ['nullable', 'integer', 'exists:servers,id'],
            'next_due_date' => ['nullable', 'date'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'domain_option' => ['required', Rule::in(['new', 'subdomain', 'existing'])],
            'domain_name' => ['required_if:domain_option,new,existing'],
        ]);
        $subscription->update($data);
        return redirect()->route('dashboard.subscriptions.index')->with('ok', 'تم تحديث الاشتراك بنجاح');
    }

    public function destroy(Subscription $subscription)
    {
        $subscription->delete();
        return redirect()->route('dashboard.subscriptions.index')->with('ok', 'تم حذف الاشتراك');
    }
    public function suspendToProvider(Subscription $subscription)
    {
        $server = $subscription->server;
        if (!$server) {
            return back()->with('connection_result', 'لا يوجد سيرفر مرتبط بهذا الاشتراك.');
        }
        $host = (!empty($server->hostname) && trim($server->hostname) !== '') ? $server->hostname : $server->ip;
        $port = 2087;
        $username = $server->username;
        $apiToken = $server->api_token;
        $error = null;
        $result = null;
        if ($host && $username && $apiToken) {
            $params = [
                'user' => $subscription->username,
                'reason' => 'Suspended from dashboard',
            ];
            $apiUrl = "https://{$host}:{$port}/json-api/suspendacct?api.version=1&" . http_build_query($params);
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                $header = [
                    'Authorization: whm ' . $username . ':' . $apiToken
                ];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                $response = curl_exec($ch);
                if (curl_errno($ch)) {
                    $error = curl_error($ch);
                } else {
                    $data = json_decode($response, true);
                    if (isset($data['metadata']['result']) && $data['metadata']['result'] == 1) {
                        $result = 'تم تعليق الموقع بنجاح على السيرفر.';
                        $subscription->update(['status' => 'suspended']);
                    } else {
                        $error = ($data['metadata']['reason'] ?? $data['reason'] ?? 'فشل تعليق الموقع.') . '<br><pre>' . print_r($data, true) . '</pre>';
                    }
                }
                curl_close($ch);
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'بيانات السيرفر غير مكتملة.';
        }
        return back()->with('connection_result', $result ?: $error);
    }

    public function unsuspendToProvider(Subscription $subscription)
    {
        $server = $subscription->server;
        if (!$server) {
            return back()->with('connection_result', 'لا يوجد سيرفر مرتبط بهذا الاشتراك.');
        }
        $host = (!empty($server->hostname) && trim($server->hostname) !== '') ? $server->hostname : $server->ip;
        $port = 2087;
        $username = $server->username;
        $apiToken = $server->api_token;
        $error = null;
        $result = null;
        if ($host && $username && $apiToken) {
            $params = [
                'user' => $subscription->username,
            ];
            $apiUrl = "https://{$host}:{$port}/json-api/unsuspendacct?api.version=1&" . http_build_query($params);
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                $header = [
                    'Authorization: whm ' . $username . ':' . $apiToken
                ];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                $response = curl_exec($ch);
                if (curl_errno($ch)) {
                    $error = curl_error($ch);
                } else {
                    $data = json_decode($response, true);
                    if (isset($data['metadata']['result']) && $data['metadata']['result'] == 1) {
                        $result = 'تم إلغاء تعليق الموقع بنجاح على السيرفر.';
                        $subscription->update(['status' => 'active']);
                    } else {
                        $error = ($data['metadata']['reason'] ?? $data['reason'] ?? 'فشل إلغاء التعليق.') . '<br><pre>' . print_r($data, true) . '</pre>';
                    }
                }
                curl_close($ch);
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'بيانات السيرفر غير مكتملة.';
        }
        return back()->with('connection_result', $result ?: $error);
    }

    public function terminateToProvider(Subscription $subscription)
    {
        $server = $subscription->server;
        if (!$server) {
            return back()->with('connection_result', 'لا يوجد سيرفر مرتبط بهذا الاشتراك.');
        }
        $host = (!empty($server->hostname) && trim($server->hostname) !== '') ? $server->hostname : $server->ip;
        $port = 2087;
        $username = $server->username;
        $apiToken = $server->api_token;
        $error = null;
        $result = null;
        if ($host && $username && $apiToken) {
            $params = [
                'user' => $subscription->username,
            ];
            $apiUrl = "https://{$host}:{$port}/json-api/removeacct?api.version=1&" . http_build_query($params);
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                $header = [
                    'Authorization: whm ' . $username . ':' . $apiToken
                ];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                $response = curl_exec($ch);
                if (curl_errno($ch)) {
                    $error = curl_error($ch);
                } else {
                    $data = json_decode($response, true);
                    if (isset($data['metadata']['result']) && $data['metadata']['result'] == 1) {
                        $result = 'تم حذف الموقع (Terminate) بنجاح من السيرفر.';
                        $subscription->update(['status' => 'cancelled']);
                    } else {
                        $error = ($data['metadata']['reason'] ?? $data['reason'] ?? 'فشل حذف الموقع.') . '<br><pre>' . print_r($data, true) . '</pre>';
                    }
                }
                curl_close($ch);
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'بيانات السيرفر غير مكتملة.';
        }
        return back()->with('connection_result', $result ?: $error);
    }

    /**
     * تسجيل دخول تلقائي إلى cPanel (SSO) لهذا الاشتراك
     */
    public function cpanelLogin(Subscription $subscription)
    {
        $server = $subscription->server;
        if (!$server) {
            return back()->with('connection_result', 'لا يوجد سيرفر مرتبط بهذا الاشتراك.');
        }
        $host = (!empty($server->hostname) && trim($server->hostname) !== '') ? $server->hostname : $server->ip;
        $port = 2087;
        $whmUser = $server->username;
        $apiToken = $server->api_token;
        $cpUser = $subscription->username;
        $error = null;
        $loginUrl = null;
        if ($host && $whmUser && $apiToken && $cpUser) {
            $apiUrl = "https://{$host}:{$port}/json-api/create_user_session?api.version=1&user={$cpUser}&service=cpaneld";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $header = [
                'Authorization: whm ' . $whmUser . ':' . $apiToken
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                $error = curl_error($ch);
            } else {
                $data = json_decode($response, true);
                if (isset($data['data']['url'])) {
                    $loginUrl = $data['data']['url'];
                } else {
                    $error = ($data['error'] ?? '') . ' ' . ($data['metadata']['reason'] ?? '') . ' ' . ($data['cpanelresult']['data'][0]['reason'] ?? '') . ' ' . json_encode($data);
                }
            }
            curl_close($ch);
        } else {
            $error = 'بيانات السيرفر أو اسم المستخدم غير مكتملة.';
        }
        if ($loginUrl) {
            return redirect()->away($loginUrl);
        }
        return back()->with('connection_result', 'فشل إنشاء رابط الدخول: ' . $error);
    }


    /**
     * تنصيب ووردبريس يدويًا عبر تحميل wordpress.zip وفك الضغط وإنشاء قاعدة البيانات وwp-config
     */
    public function installWordPressManual(Subscription $subscription)
    // --- مثال عملي: تنصيب ووردبريس عبر WP Toolkit ثم رفع وتفعيل قالب عبر wp-cli ---
    // 1. استدعاء WP Toolkit API لتنصيب ووردبريس (تحتاج بيانات السيرفر واسم المستخدم والدومين)
    // مثال (تخصيص حسب بيئتك):
    // $apiUrl = "https://{$host}:2087/json-api/cpanel?cpanel_jsonapi_user={$cpUser}&cpanel_jsonapi_apiversion=2&cpanel_jsonapi_module=WpToolkit&cpanel_jsonapi_func=install_wp";
    // $postFields = [
    //     'domain' => $domain,
    //     'path' => 'public_html',
    //     'admin_user' => 'wpadmin',
    //     'admin_pass' => 'StrongPass123',
    //     'admin_email' => 'admin@' . $domain,
    // ];
    // ... تنفيذ الطلب عبر cURL بنفس طريقة باقي الدوال ...

    // 2. بعد نجاح التنصيب، نفذ أوامر wp-cli لرفع وتفعيل القالب:
    // مثال:
    // $themeZip = '/path/to/theme.zip'; // ضع مسار القالب على السيرفر
    // $wpPath = '/home/' . $cpUser . '/public_html';
    // $cmd = "wp theme install $themeZip --activate --path=$wpPath";
    // يمكنك تنفيذ الأمر عبر SSH أو من داخل السيرفر مباشرة:
    // exec($cmd, $output, $status);
    // إذا كنت تريد تنفيذ الأمر عبر SSH من Laravel:
    // \Illuminate\Support\Facades\Process::run('ssh user@host "' . $cmd . '"');
    // --- نهاية المثال ---
    {
        $server = $subscription->server;
        if (!$server) {
            return back()->with('connection_result', 'لا يوجد سيرفر مرتبط بهذا الاشتراك.');
        }
        $host = (!empty($server->hostname) && trim($server->hostname) !== '') ? $server->hostname : $server->ip;
        $port = 2083; // cPanel port
        $cpUser = $subscription->username;
        $apiToken = $server->api_token;
        $domain = $subscription->domain_name;
        $error = null;
        $result = null;
        // --- تنصيب ووردبريس وتفعيل قالب zip تلقائيًا عبر wp-cli ---
        $user = $subscription->username;
        $domain = $subscription->domain_name;
        $wpPath = "/home/$user/public_html";
        $themeZip = "/home/$user/public_html/theme.zip";
        $adminUser = 'wpadmin';
        $adminPass = 'StrongPass123!';
        $adminEmail = 'admin@' . $domain;
        $wpcli = '/usr/local/bin/wp';

        // تشخيص: من ينفذ الأوامر وما هي بيئة wp-cli؟
        $diagnose = [];
        exec('whoami 2>&1', $diagnose['whoami']);
        exec("cd $wpPath && $wpcli --info 2>&1", $diagnose['wpinfo']);
        exec('ls -la ' . $wpPath . ' 2>&1', $diagnose['ls']);

        // 1. تحميل وتنصيب ووردبريس مع ضبط HOME
        $cmds = [
            "cd $wpPath && HOME=$wpPath $wpcli core download --force",
            "cd $wpPath && HOME=$wpPath $wpcli config create --dbname={$user}_wp --dbuser={$user}_wp --dbpass=StrongDBPass! --dbhost=localhost --skip-check --force",
            "cd $wpPath && HOME=$wpPath $wpcli db create",
            "cd $wpPath && HOME=$wpPath $wpcli core install --url=$domain --title=SiteTitle --admin_user=$adminUser --admin_password=$adminPass --admin_email=$adminEmail",
        ];

        // 2. رفع وتفعيل القالب (يجب أن يكون theme.zip موجود مسبقًا)
        $cmds[] = "cd $wpPath && HOME=$wpPath $wpcli theme install $themeZip --activate";

        $output = [];
        $status = 0;
        foreach ($cmds as $cmd) {
            $out = [];
            exec($cmd . ' 2>&1', $out, $status);
            $output[] = [
                'cmd' => $cmd,
                'output' => $out,
                'status' => $status
            ];
            if ($status !== 0) {
                return back()->with('connection_result', 'فشل تنفيذ الأمر:<br><pre>' . print_r($diagnose, true) . print_r($output, true) . '</pre>');
            }
        }
        return back()->with('connection_result', 'تم تنصيب ووردبريس وتفعيل القالب بنجاح.<br><pre>' . print_r($diagnose, true) . print_r($output, true) . '</pre>');
        // 2. فك الضغط عن wordpress.zip في public_html
        $apiUrl = "https://{$host}:{$port}/execute/Fileman/extract_archive";
        $postFields = http_build_query([
            'archive' => 'public_html/latest.zip',
            'dest' => 'public_html',
        ]);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $response = curl_exec($ch);
        $data = json_decode($response, true);
        curl_close($ch);
        if (!isset($data['status']) || $data['status'] != 1) {
            return back()->with('connection_result', 'فشل فك الضغط عن wordpress.zip: <pre>' . print_r($data, true) . '</pre>');
        }
        // 3. نقل ملفات ووردبريس من public_html/wordpress إلى public_html (اختياري)
        // يمكن تنفيذ ذلك عبر Fileman/mv_file أو Fileman/copy_file API إذا رغبت
        // 4. إنشاء قاعدة بيانات ومستخدم وwp-config.php (يمكن إضافتها لاحقًا)
        return back()->with('connection_result', 'تم تحميل وفك ضغط ووردبريس بنجاح. أكمل الإعدادات يدويًا أو أبلغني لإكمال قاعدة البيانات وwp-config تلقائيًا.');
    }
}
