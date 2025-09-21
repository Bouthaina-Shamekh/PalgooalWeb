<?php

namespace App\Http\Controllers\Dashboard\Management;

use App\Http\Controllers\Controller;
use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

    /**
     * Return available packages from the server (WHM) as JSON.
     */
    public function packages(Server $server)
    {
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

        // If no packages found and app debug enabled, include a debugSample of the raw response
        if (empty($packages) && config('app.debug')) {
            $debugSample = isset($response) ? substr($response, 0, 2000) : null;
            return response()->json(['packages' => $packages, 'debugSample' => $debugSample]);
        }

        return response()->json(['packages' => $packages]);
    }
}
