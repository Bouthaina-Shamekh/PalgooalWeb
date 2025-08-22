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
                    'Authorization: whm ' . $username . ':' . $apiToken
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
            'status' => ['required', Rule::in(['pending','active','suspended','cancelled'])],
            'price' => ['required', 'numeric', 'min:0'],
            'username' => ['nullable', 'string', 'max:255'],
            'server_id' => ['nullable', 'integer', 'exists:servers,id'],
            'next_due_date' => ['nullable', 'date'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'domain_option' => ['required', Rule::in(['new','subdomain','existing'])],
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
            'status' => ['required', Rule::in(['pending','active','suspended','cancelled'])],
            'price' => ['required', 'numeric', 'min:0'],
            'username' => ['nullable', 'string', 'max:255'],
            'server_id' => ['nullable', 'integer', 'exists:servers,id'],
            'next_due_date' => ['nullable', 'date'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'domain_option' => ['required', Rule::in(['new','subdomain','existing'])],
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
}

