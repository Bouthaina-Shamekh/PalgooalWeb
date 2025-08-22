<?php
namespace App\Http\Controllers\Dashboard\Management;

use App\Http\Controllers\Controller;
use App\Models\Server;
use Illuminate\Http\Request;

class ServerController extends Controller
{
    public function accounts(Server $server)
    {
        $host = (!empty($server->hostname) && trim($server->hostname) !== '') ? $server->hostname : $server->ip;
        $port = 2087;
        $username = $server->username;
        $apiToken = $server->api_token;
        $accounts = [];
        $error = null;
        if ($host && $username && $apiToken) {
            $apiUrl = "https://{$host}:{$port}/json-api/listaccts?api.version=1";
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
                    if (isset($data['acct']) && is_array($data['acct'])) {
                        $accounts = $data['acct'];
                    } else {
                        $error = $data['metadata']['reason'] ?? $data['reason'] ?? 'لم يتم العثور على بيانات.';
                    }
                }
                curl_close($ch);
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'يجب توفر hostname أو IP واسم المستخدم وAPI Token.';
        }
        return view('dashboard.management.servers.accounts', compact('server', 'accounts', 'error'));
    }
    public function index()
    {
        $servers = Server::latest()->paginate(20);
        return view('dashboard.management.servers.index', compact('servers'));
    }

    public function create()
    {
        return view('dashboard.management.servers.create');
    }

    public function store(Request $request)
    {
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
        return view('dashboard.management.servers.edit', compact('server'));
    }

    public function update(Request $request, Server $server)
    {
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
        $server->delete();
        return redirect()->route('dashboard.servers.index')->with('ok', 'تم حذف السيرفر');
    }

        public function testConnection(Server $server)
        {
            // اختبار الاتصال الفعلي بـ cPanel API
    $host = (!empty($server->hostname) && trim($server->hostname) !== '') ? $server->hostname : $server->ip;
            $port = 2087;
            $username = $server->username;
            $password = $server->password;
            $apiToken = $server->api_token;
            $error = null;
            $success = false;
            if ($host && $username && ($password || $apiToken)) {
                if ($apiToken) {
                    // جرب الاتصال باستخدام API Token (WHM API)
                    $apiUrl = "https://{$host}:{$port}/json-api/listaccts?api.version=1";
                    try {
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $apiUrl);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                        $header = [
                            'Authorization: whm ' . $username . ':' . $apiToken
                        ];
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        if (curl_errno($ch)) {
                            $error = curl_error($ch);
                        } else {
                            $data = json_decode($response, true);
                            if ($httpCode == 200 && isset($data['metadata']['result']) && $data['metadata']['result'] == 1) {
                                $success = true;
                            } elseif ($httpCode == 200 && isset($data['status']) && $data['status'] == 1) {
                                $success = true;
                            } else {
                                $error = $data['metadata']['reason'] ?? $data['reason'] ?? 'فشل التحقق من التوكن';
                            }
                        }
                        curl_close($ch);
                    } catch (\Exception $e) {
                        $error = $e->getMessage();
                    }
                } elseif ($password) {
                    // جرب الاتصال باستخدام الباسورد (cPanel API)
                    $url = "https://{$host}:{$port}/login/?login_only=1";
                    $postFields = http_build_query([
                        'user' => $username,
                        'pass' => $password,
                    ]);
                    try {
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        if (curl_errno($ch)) {
                            $error = curl_error($ch);
                        } else {
                            $data = json_decode($response, true);
                            if ($httpCode == 200 && isset($data['status']) && $data['status'] == 1) {
                                $success = true;
                            } else {
                                $error = $data['reason'] ?? 'بيانات الدخول غير صحيحة أو لا يمكن الاتصال';
                            }
                        }
                        curl_close($ch);
                    } catch (\Exception $e) {
                        $error = $e->getMessage();
                    }
                }
            } else {
                $error = 'يجب تحديد IP/Hostname واسم المستخدم وكلمة المرور أو API Token';
            }
            return back()->with('connection_result', $success ? 'تم الاتصال وتوثيق الدخول بنجاح (WHM/cPanel API)' : 'فشل الاتصال: ' . $error);
        }

            public function ssoWhm(Server $server)
            {
                $host = (!empty($server->hostname) && trim($server->hostname) !== '') ? $server->hostname : $server->ip;
                $port = 2087;
                $username = $server->username;
                $password = $server->password;
                $apiToken = $server->api_token;
                $error = null;
                $loginUrl = null;
                if ($host && $username && ($password || $apiToken)) {
                    $apiUrl = "https://{$host}:{$port}/json-api/create_user_session?api.version=1&user={$username}&service=whostmgrd";
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $apiUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    if ($apiToken) {
                        $header = [
                            'Authorization: whm ' . $username . ':' . $apiToken
                        ];
                    } else {
                        $header = [
                            'Authorization: Basic ' . base64_encode($username . ':' . $password)
                        ];
                    }
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
                    $error = 'يجب تحديد IP/Hostname واسم المستخدم وكلمة المرور أو API Token';
                }
                if ($loginUrl) {
                    return redirect()->away($loginUrl);
                }
                return back()->with('connection_result', 'فشل إنشاء رابط الدخول: ' . $error);
            }
}
