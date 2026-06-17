<?php

namespace App\Http\Controllers\Admin\Management;

use App\Http\Controllers\Controller;
use App\Jobs\ProvisionSubscription;
use App\Models\Tenancy\Subscription;
use App\Models\Client;
use App\Models\Plan;
use App\Services\Tenancy\DomainVerificationService;
use App\Services\Tenancy\SubscriptionSyncService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    // -------------------------------------------------------------------------
    // Index / CRUD
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        $this->authorize('viewAny', Subscription::class);

        $q         = $request->get('q');
        $domain    = $request->get('domain');
        $status    = $request->get('status');
        $sort      = $request->get('sort');
        $direction = $request->get('direction', 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage   = in_array((int) $request->get('per_page'), [10, 20, 50])
            ? (int) $request->get('per_page') : 20;

        $query = Subscription::with(['client', 'plan']);

        // domain filter
        if ($domain) {
            $domainLike = '%' . addcslashes($domain, '%_\\') . '%';
            $query->where('domain_name', 'like', $domainLike);
        }

        // generic q search
        if ($q) {
            $qLike = '%' . addcslashes($q, '%_\\') . '%';
            $query->where(function ($qry) use ($qLike) {
                $qry->whereHas('client', function ($c) use ($qLike) {
                    $c->where('first_name', 'like', $qLike)
                      ->orWhere('last_name',  'like', $qLike)
                      ->orWhere('email',       'like', $qLike);
                })->orWhere('domain_name', 'like', $qLike);
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        // sorting
        if ($sort && in_array($sort, ['id', 'domain_name', 'status', 'starts_at'])) {
            $query->orderBy($sort, $direction);
        } else {
            $query->latest();
        }

        $subscriptions = $query->paginate($perPage)->withQueryString();

        return view('dashboard.management.subscriptions.index', compact(
            'subscriptions', 'q', 'domain', 'status', 'sort', 'direction', 'perPage'
        ));
    }

    /**
     * Show sync logs (last_sync_message) for subscriptions.
     */
    public function syncLogs(Request $request)
    {
        $this->authorize('viewAny', Subscription::class);

        $query = Subscription::with(['client', 'plan', 'server']);

        // P11: escape LIKE wildcards
        if ($q = $request->get('q')) {
            $qLike = '%' . addcslashes($q, '%_\\') . '%';
            $query->where(function ($qr) use ($qLike) {
                $qr->where('domain_name', 'like', $qLike)
                   ->orWhereHas('client', function ($c) use ($qLike) {
                       $c->where('first_name', 'like', $qLike)
                         ->orWhere('last_name',  'like', $qLike)
                         ->orWhere('email',       'like', $qLike);
                   });
            });
        }

        if ($serverId = $request->get('server_id')) {
            $query->where('server_id', $serverId);
        }

        // date range filters (on updated_at)
        try {
            if ($from = $request->get('from')) {
                $query->where('updated_at', '>=', \Carbon\Carbon::parse($from)->startOfDay());
            }
            if ($to = $request->get('to')) {
                $query->where('updated_at', '<=', \Carbon\Carbon::parse($to)->endOfDay());
            }
        } catch (\Exception $e) {
            // ignore parse errors
        }

        $subscriptions = $query->whereNotNull('last_sync_message')
            ->orderBy('updated_at', 'desc')
            ->paginate(30)
            ->withQueryString();

        $servers = \App\Models\Server::orderBy('name')->get();

        return view('dashboard.management.subscriptions.sync_logs', compact('subscriptions', 'servers'));
    }

    public function create()
    {
        $this->authorize('create', Subscription::class);

        // P9: select only the columns needed for the dropdowns
        $clients = Client::orderBy('first_name')->select(['id', 'first_name', 'last_name', 'email'])->get();
        $plans   = Plan::orderBy('name')->select(['id', 'name', 'server_package'])->get();
        $servers = \App\Models\Server::where('is_active', 1)->select(['id', 'name'])->get();

        return view('dashboard.management.subscriptions.create', compact('clients', 'plans', 'servers'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Subscription::class);

        $data = $request->validate([
            'client_id'     => ['required', 'exists:clients,id'],
            'plan_id'       => ['required', 'exists:plans,id'],
            'status'        => ['required', Rule::in(['pending', 'active', 'suspended', 'cancelled'])],
            'price'         => ['required', 'numeric', 'min:0'],
            'username'      => ['nullable', 'string', 'max:255'],
            'server_id'     => ['nullable', 'integer', 'exists:servers,id'],
            'next_due_date' => ['nullable', 'date'],
            'starts_at'     => ['required', 'date'],
            'ends_at'       => ['nullable', 'date', 'after_or_equal:starts_at'],
            'domain_option' => ['required', Rule::in(['new', 'subdomain', 'existing'])],
            'domain_name'   => ['required_if:domain_option,new,existing'],
        ]);

        // Always derive server_package from the chosen plan (never trust the request)
        $plan = Plan::find($data['plan_id']);
        $data['server_package'] = $plan ? ($plan->server_package ?? $plan->name ?? null) : null;

        // Convert dollar input to cents; unset legacy column (dropped in ADR-003 Phase 3)
        $data['price_cents'] = (int) round((float) $data['price'] * 100);
        unset($data['price']);

        $subscription = Subscription::create($data);
        app(DomainVerificationService::class)->reset($subscription);

        return redirect()->route('dashboard.subscriptions.index')
            ->with('ok', t('dashboard.Subscription_Created', 'Subscription created successfully.'));
    }

    public function edit(Subscription $subscription)
    {
        $this->authorize('update', $subscription);

        // P9: select only the columns needed for the dropdowns
        $clients = Client::orderBy('first_name')->select(['id', 'first_name', 'last_name', 'email'])->get();
        $plans   = Plan::orderBy('name')->select(['id', 'name', 'server_package'])->get();
        $servers = \App\Models\Server::where('is_active', 1)->select(['id', 'name'])->get();

        return view('dashboard.management.subscriptions.edit', compact('subscription', 'clients', 'plans', 'servers'));
    }

    public function update(Request $request, Subscription $subscription)
    {
        $this->authorize('update', $subscription);

        $data = $request->validate([
            'client_id'     => ['required', 'exists:clients,id'],
            'plan_id'       => ['required', 'exists:plans,id'],
            'status'        => ['required', Rule::in(['pending', 'active', 'suspended', 'cancelled'])],
            'price'         => ['required', 'numeric', 'min:0'],
            'username'      => ['nullable', 'string', 'max:255'],
            'server_id'     => ['nullable', 'integer', 'exists:servers,id'],
            'next_due_date' => ['nullable', 'date'],
            'starts_at'     => ['required', 'date'],
            'ends_at'       => ['nullable', 'date', 'after_or_equal:starts_at'],
            'domain_option' => ['required', Rule::in(['new', 'subdomain', 'existing'])],
            'domain_name'   => ['required_if:domain_option,new,existing'],
        ]);

        // Ensure server_package remains derived from the plan selection
        $plan = Plan::find($data['plan_id']);
        $data['server_package'] = $plan ? ($plan->server_package ?? $plan->name ?? null) : null;

        // Convert dollar input to cents; unset legacy column (dropped in ADR-003 Phase 3)
        $data['price_cents'] = (int) round((float) $data['price'] * 100);
        unset($data['price']);

        $subscription->update($data);
        app(DomainVerificationService::class)->reset($subscription->fresh());

        return redirect()->route('dashboard.subscriptions.index')
            ->with('ok', t('dashboard.Subscription_Updated', 'Subscription updated successfully.'));
    }

    public function destroy(Subscription $subscription)
    {
        $this->authorize('delete', $subscription);

        // Soft-delete — recoverable via restore()
        $subscription->delete();

        return redirect()->route('dashboard.subscriptions.index')
            ->with('ok', t('dashboard.Subscription_Deleted', 'Subscription deleted.'));
    }

    // -------------------------------------------------------------------------
    // Bulk Actions
    // -------------------------------------------------------------------------

    /**
     * Bulk actions handler for subscriptions.
     * Accepts: ids[] (array), action (string): suspend|unsuspend|sync|terminate|delete
     */
    public function bulk(Request $request)
    {
        $this->authorize('bulk', Subscription::class);

        $data = $request->validate([
            'ids'    => ['required', 'array'],
            'ids.*'  => ['integer', 'exists:subscriptions,id'],
            'action' => ['required', Rule::in(['suspend', 'unsuspend', 'sync', 'terminate', 'delete'])],
        ]);

        $ids    = $data['ids'];
        $action = $data['action'];

        $subs = Subscription::whereIn('id', $ids)->get();

        $dispatchedCount = 0;
        foreach ($subs as $sub) {
            try {
                switch ($action) {
                    case 'suspend':
                        $sub->update(['status' => 'suspended']);
                        break;
                    case 'unsuspend':
                        $sub->update(['status' => 'active']);
                        break;
                    case 'sync':
                        if (class_exists(\App\Jobs\SyncSubscriptionToProvider::class)) {
                            \App\Jobs\SyncSubscriptionToProvider::dispatch($sub->id);
                            $dispatchedCount++;
                        }
                        break;
                    case 'terminate':
                        if (class_exists(\App\Jobs\TerminateSubscriptionOnProvider::class)) {
                            \App\Jobs\TerminateSubscriptionOnProvider::dispatch($sub->id);
                            $dispatchedCount++;
                        } else {
                            $sub->update(['status' => 'cancelled']);
                        }
                        break;
                    case 'delete':
                        // Soft-delete — recoverable
                        $sub->delete();
                        break;
                }
            } catch (\Exception $e) {
                logger()->error('Bulk action failed for subscription ' . $sub->id . ': ' . $e->getMessage());
            }
        }

        $message = t('dashboard.Bulk_Operation_Applied', 'Operation applied to selected subscriptions.');
        if ($dispatchedCount > 0) {
            $message .= ' ' . strtr(t('dashboard.Jobs_Queued', ':count job(s) queued for background processing.'), [':count' => $dispatchedCount]);
        }

        return redirect()->route('dashboard.subscriptions.index')->with('ok', $message);
    }

    // -------------------------------------------------------------------------
    // Provider Actions — WHM API calls
    // -------------------------------------------------------------------------

    /**
     * Sync (create) subscription account on WHM provider.
     * Delegates to SubscriptionSyncService for retry logic and username candidates.
     *
     * P8: replaced inline cURL with service delegation.
     */
    public function syncWithProvider(Subscription $subscription, SubscriptionSyncService $syncService)
    {
        $this->authorize('manage', $subscription);

        $message = $syncService->sync($subscription);

        return back()->with('connection_result', $message);
    }

    public function provision(Subscription $subscription)
    {
        $this->authorize('manage', $subscription);

        ProvisionSubscription::dispatch($subscription->id, true);

        return back()->with('ok', t('dashboard.Provisioning_Queued', 'Subscription provisioning request has been queued.'));
    }

    public function verifyDomain(Subscription $subscription, DomainVerificationService $verification)
    {
        $this->authorize('manage', $subscription);

        $details = $verification->verify($subscription);
        $message = $details['label'];

        if (! empty($details['error'])) {
            $message .= ': ' . $details['error'];
        }

        return back()->with(
            $details['status'] === Subscription::DOMAIN_VERIFICATION_ACTIVE ? 'success' : 'info',
            $message
        );
    }

    public function cpanelLogin(Subscription $subscription)
    {
        $this->authorize('manage', $subscription);

        $server = $subscription->server;
        if (! $server) {
            return back()->with('connection_result', 'لا يوجد سيرفر مرتبط بهذا الاشتراك.');
        }

        $host     = $this->resolveHost($server);
        $whmUser  = $server->username;
        $apiToken = $server->api_token;
        $cpUser   = $subscription->username;

        if (! $host || ! $whmUser || ! $apiToken || ! $cpUser) {
            return back()->with('connection_result', 'بيانات السيرفر أو اسم المستخدم غير مكتملة.');
        }

        $apiUrl = "https://{$host}:2087/json-api/create_user_session?api.version=1"
            . '&user=' . urlencode($cpUser)
            . '&service=cpaneld';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, config('services.whm.ssl_verify', true));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, config('services.whm.ssl_verify', true) ? 2 : 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: whm ' . $whmUser . ':' . $apiToken,
        ]);

        $response = curl_exec($ch);
        $curlError = curl_errno($ch) ? curl_error($ch) : null;
        curl_close($ch);

        if ($curlError) {
            return back()->with('connection_result', 'فشل إنشاء رابط الدخول: ' . $curlError);
        }

        $data = json_decode($response, true);
        if (isset($data['data']['url'])) {
            return redirect()->away($data['data']['url']);
        }

        $reason = trim(
            ($data['error'] ?? '') . ' ' .
            ($data['metadata']['reason'] ?? '')
        );
        return back()->with('connection_result', 'فشل إنشاء رابط الدخول: ' . $reason);
    }

    public function suspendToProvider(Subscription $subscription)
    {
        $this->authorize('manage', $subscription);

        $result = $this->whmCall($subscription, 'suspendacct', [
            'user'   => $subscription->username,
            'reason' => 'Suspended from dashboard',
        ]);

        if ($result['ok']) {
            $subscription->update(['status' => 'suspended']);
            return back()->with('connection_result', 'تم تعليق الموقع بنجاح على السيرفر.');
        }

        return back()->with('connection_result', $result['error']);
    }

    public function unsuspendToProvider(Subscription $subscription)
    {
        $this->authorize('manage', $subscription);

        $result = $this->whmCall($subscription, 'unsuspendacct', [
            'user' => $subscription->username,
        ]);

        if ($result['ok']) {
            $subscription->update(['status' => 'active']);
            return back()->with('connection_result', 'تم إلغاء تعليق الموقع بنجاح على السيرفر.');
        }

        return back()->with('connection_result', $result['error']);
    }

    public function terminateToProvider(Subscription $subscription)
    {
        $this->authorize('manage', $subscription);

        $result = $this->whmCall($subscription, 'removeacct', [
            'user' => $subscription->username,
        ]);

        if ($result['ok']) {
            $subscription->update(['status' => 'cancelled']);
            return back()->with('connection_result', 'تم حذف الموقع (Terminate) بنجاح من السيرفر.');
        }

        return back()->with('connection_result', $result['error']);
    }

    /**
     * تنصيب ووردبريس عبر wp-cli على السيرفر.
     *
     * P2 FIX: All user-controlled values passed to exec() are wrapped in escapeshellarg().
     * P6 FIX: Removed dead/unreachable code that existed after the first return statement.
     */
    public function installWordPressManual(Subscription $subscription)
    {
        $this->authorize('manage', $subscription);

        $server = $subscription->server;
        if (! $server) {
            return back()->with('connection_result', 'لا يوجد سيرفر مرتبط بهذا الاشتراك.');
        }

        // P2: sanitize every value that touches the shell
        $user   = preg_replace('/[^a-z0-9]/', '', strtolower((string) $subscription->username));
        $domain = $subscription->domain_name;

        if (! $user) {
            return back()->with('connection_result', 'اسم المستخدم غير صالح لتنفيذ الأمر.');
        }

        $wpPath     = escapeshellarg('/home/' . $user . '/public_html');
        $themeZip   = escapeshellarg('/home/' . $user . '/public_html/theme.zip');
        $wpcli      = '/usr/local/bin/wp';
        $adminUser  = 'wpadmin';
        $adminPass  = Str::random(16) . '!A1'; // random — never hardcoded
        $adminEmail = 'admin@' . escapeshellarg($domain);
        $dbName     = escapeshellarg($user . '_wp');
        $dbUser     = escapeshellarg($user . '_wp');

        $cmds = [
            "cd {$wpPath} && HOME={$wpPath} " . escapeshellarg($wpcli) . " core download --force",
            "cd {$wpPath} && HOME={$wpPath} " . escapeshellarg($wpcli)
                . " config create --dbname={$dbName} --dbuser={$dbUser}"
                . " --dbpass=" . escapeshellarg('StrongDBPass!') . " --dbhost=localhost --skip-check --force",
            "cd {$wpPath} && HOME={$wpPath} " . escapeshellarg($wpcli) . " db create",
            "cd {$wpPath} && HOME={$wpPath} " . escapeshellarg($wpcli)
                . " core install --url=" . escapeshellarg($domain)
                . " --title=" . escapeshellarg('SiteTitle')
                . " --admin_user=" . escapeshellarg($adminUser)
                . " --admin_password=" . escapeshellarg($adminPass)
                . " --admin_email=" . escapeshellarg('admin@' . $domain),
            "cd {$wpPath} && HOME={$wpPath} " . escapeshellarg($wpcli)
                . " theme install {$themeZip} --activate",
        ];

        $output = [];
        foreach ($cmds as $cmd) {
            $out    = [];
            $status = 0;
            exec($cmd . ' 2>&1', $out, $status);
            $output[] = ['cmd' => $cmd, 'output' => $out, 'status' => $status];
            if ($status !== 0) {
                return back()->with(
                    'connection_result',
                    'فشل تنفيذ الأمر. رمز الخروج: ' . $status . "\n" . implode("\n", $out)
                );
            }
        }

        return back()->with('connection_result', 'تم تنصيب ووردبريس وتفعيل القالب بنجاح.');
    }

    // -------------------------------------------------------------------------
    // AJAX helpers
    // -------------------------------------------------------------------------

    /**
     * AJAX: suggest a unique username based on domain, client or preferred value.
     */
    public function suggestUsername(Request $request)
    {
        $this->authorize('create', Subscription::class);

        $data = $request->validate([
            'domain_name'        => ['nullable', 'string'],
            'client_id'          => ['nullable', 'integer', 'exists:clients,id'],
            'preferred_username' => ['nullable', 'string'],
        ]);

        $makeBase = function (string $s): string {
            $s = strtolower($s);
            $s = str_replace('.', '', $s);
            $s = preg_replace('/[^a-z0-9]/', '', $s);
            $s = trim($s);
            return $s !== '' ? substr($s, 0, 12) : 'user';
        };

        $base = null;
        if (! empty($data['domain_name'])) {
            $base = $makeBase($data['domain_name']);
        }
        if (! $base && ! empty($data['preferred_username'])) {
            $base = $makeBase($data['preferred_username']);
        }
        if (! $base && ! empty($data['client_id'])) {
            $client = Client::find($data['client_id']);
            if ($client) {
                $base = ! empty($client->email) && str_contains($client->email, '@')
                    ? $makeBase(explode('@', $client->email)[0])
                    : $makeBase(($client->first_name ?? '') . ($client->last_name ?? ''));
            }
        }
        if (! $base) {
            $base = 'user';
        }

        $candidate = $base;
        $suffix    = 0;
        while (Subscription::where('username', $candidate)->exists()) {
            $suffix++;
            $candidate = $base . $suffix;
            if ($suffix > 1000) {
                break;
            }
        }

        return response()->json(['username' => $candidate, 'unique' => true]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve the most appropriate hostname/IP to connect to for a server.
     */
    private function resolveHost(\App\Models\Server $server): ?string
    {
        $hostname = trim((string) ($server->hostname ?? ''));
        return $hostname !== '' ? $hostname : ($server->ip ?? null);
    }

    /**
     * Shared WHM JSON-API caller — eliminates the cURL boilerplate duplicated
     * across suspendToProvider / unsuspendToProvider / terminateToProvider.
     *
     * P12 partial fix: remaining cURL duplication reduced to one private method.
     * P7: SSL verification is now configurable via config('services.whm.ssl_verify').
     *
     * @return array{ok: bool, data: array|null, error: string}
     */
    private function whmCall(Subscription $subscription, string $endpoint, array $params): array
    {
        $server = $subscription->server;
        if (! $server) {
            return ['ok' => false, 'data' => null, 'error' => 'لا يوجد سيرفر مرتبط بهذا الاشتراك.'];
        }

        $host     = $this->resolveHost($server);
        $whmUser  = $server->username;
        $apiToken = $server->api_token;

        if (! $host || ! $whmUser || ! $apiToken) {
            return ['ok' => false, 'data' => null, 'error' => 'بيانات السيرفر غير مكتملة.'];
        }

        $sslVerify = config('services.whm.ssl_verify', true);
        $apiUrl    = "https://{$host}:2087/json-api/{$endpoint}?api.version=1&" . http_build_query($params);

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: whm ' . $whmUser . ':' . $apiToken,
            ]);

            $response  = curl_exec($ch);
            $curlError = curl_errno($ch) ? curl_error($ch) : null;
            curl_close($ch);

            if ($curlError) {
                return ['ok' => false, 'data' => null, 'error' => 'cURL error: ' . $curlError];
            }

            $data = json_decode($response, true);
            if (isset($data['metadata']['result']) && $data['metadata']['result'] == 1) {
                return ['ok' => true, 'data' => $data, 'error' => ''];
            }

            $reason = $data['metadata']['reason'] ?? $data['reason'] ?? 'فشل تنفيذ الإجراء على السيرفر.';
            return ['ok' => false, 'data' => $data, 'error' => $reason];

        } catch (\Exception $e) {
            return ['ok' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }
}
