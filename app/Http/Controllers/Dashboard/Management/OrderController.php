<?php

namespace App\Http\Controllers\Dashboard\Management;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    // عرض جميع الطلبات مع بيانات العميل
    public function index(Request $request)
    {
        $q = $request->get('q');
        $status = $request->get('status');
        $type = $request->get('type');
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');

        $query = Order::with('client'); // إن احتجت الدومين في الجدول، أضف 'items'
        if ($q) {
            $query->where(function ($qr) use ($q) {
                $qr->where('order_number', 'like', "%$q%")
                    ->orWhereHas('client', function ($qc) use ($q) {
                        $qc->where('first_name', 'like', "%$q%")
                            ->orWhere('last_name', 'like', "%$q%")
                            ->orWhere('email', 'like', "%$q%");
                    });
            });
        }
        if ($status) $query->where('status', $status);
        if ($type) $query->where('type', $type);

        // safe sort whitelist
        $allowed = ['created_at', 'order_number', 'status'];
        if (!in_array($sort, $allowed)) $sort = 'created_at';
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        $orders = $query->orderBy($sort, $direction)->paginate(20)->withQueryString();
        return view('dashboard.management.orders.index', compact('orders'));
    }

    // إجراء جماعي على الطلبات (تغيير الحالة أو حذف)
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids'    => 'required|array|min:1',
            'action' => 'required|string',
        ]);

        $ids = $data['ids'];
        $action = $data['action'];
        $affected = 0;

        if ($action === 'delete') {
            $affected = Order::whereIn('id', $ids)->delete(); // سيحذف البنود تباعًا بسبب FK cascade
        } elseif (in_array($action, ['pending', 'active', 'cancelled', 'fraud'])) {
            $affected = Order::whereIn('id', $ids)->update(['status' => $action]);

            if ($action === 'active') {
                $orders = Order::with(['invoices.items', 'items'])->whereIn('id', $ids)->get();
                foreach ($orders as $order) {
                    try {
                        $this->processActivation($order);
                    } catch (\Exception $e) {
                        Log::error('Bulk activation failed for order ' . $order->id . ': ' . $e->getMessage());
                    }
                }
            }
        }

        return redirect()->back()->with('ok', "تم تنفيذ الإجراء على {$affected} طلب(ات)");
    }

    /**
     * استخرج بيانات الدومين من بنود الطلب (أول بند يملك دومين)
     */
    protected function extractDomainData(Order $order): array
    {
        // إن لم تكن محمّلة، لا بأس سيتم جلبها كسولياً
        $item = $order->items()
            ->whereNotNull('domain')
            ->where('domain', '<>', '')
            ->orderBy('id')
            ->first();

        return [
            'domain_name'   => $item->domain ?? null,
            'domain_option' => $item->item_option ?? null,
        ];
    }

    /**
     * Process order activation: update invoices, create/update subscription and sync with provider
     */
    public function processActivation(\App\Models\Order $order)
    {
        // فعّل الفواتير المرتبطة (اجعل status = unpaid)
        foreach ($order->invoices as $invoice) {
            if ($invoice->status === 'draft') {
                $invoice->status = 'unpaid';
                $invoice->save();
            }
        }
        // استخرج بيانات الدومين من البنود (نحتاجها لاحقًا أو قد نحتاجها مباشرة)
        $domain = $this->extractDomainData($order);
        $domainName   = $domain['domain_name'];
        $domainOption = $domain['domain_option'];

        // إذا كان الطلب يتعلق بدومين للـ "register" فقط، أنشئ أو حدّث سجل الدومين في جدول domains
        if (!empty($domainName) && strtolower($domainOption) === 'register') {
            try {
                $existingDomain = \App\Models\Domain::where('domain_name', $domainName)->first();
                if ($existingDomain) {
                    // حدّث حالة وربط العميل إن لزم
                    $existingDomain->update([
                        'client_id' => $order->client_id ?? $existingDomain->client_id,
                        'status' => 'active',
                    ]);
                } else {
                    \App\Models\Domain::create([
                        'client_id' => $order->client_id,
                        'domain_name' => $domainName,
                        'registrar' => 'order-' . $order->id,
                        'registration_date' => now()->toDateString(),
                        'status' => 'active',
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to create/update domain for order ' . $order->id . ': ' . $e->getMessage());
            }
        }

        // حدد إن كان الطلب يخص اشتراكًا (من أول عنصر فاتورة من نوع subscription)
        $templateId = null;
        if ($order->invoices && $order->invoices->count()) {
            $firstInvoice = $order->invoices->first();
            $firstItem = $firstInvoice->items->first();
            if ($firstItem && $firstItem->item_type === 'subscription') {
                $templateId = $firstItem->reference_id;
            }
        }
        if (!$templateId) return;

        $template = \App\Models\Template::find($templateId);
        if (!($template && $template->plan_id)) return;

        // حساب مدة الاشتراك حسب الخطة (شهرية أو سنوية)
        $duration = 'month';
        if (isset($template->plan) && method_exists($template->plan, 'getDurationUnit')) {
            $duration = $template->plan->getDurationUnit();
        } elseif (isset($template->plan) && isset($template->plan->duration_unit)) {
            $duration = $template->plan->duration_unit;
        }
        $startsAt = now();
        $endsAt = $duration === 'year' ? $startsAt->copy()->addYear() : $startsAt->copy()->addMonth();

        // server_id من الخطة أو من الطلب (إن وُجد في الطلب)
        $serverId = null;
        if (isset($template->plan) && isset($template->plan->server_id)) {
            $serverId = $template->plan->server_id;
        } elseif (isset($order->server_id)) {
            $serverId = $order->server_id;
        }

        // استخرج بيانات الدومين من البنود
        $domain = $this->extractDomainData($order);
        $domainName   = $domain['domain_name'];
        $domainOption = $domain['domain_option'];

        // إذا كان الطلب يتعلق بدومين للـ "register" فقط، أنشئ أو حدّث سجل الدومين في جدول domains
        if (!empty($domainName) && strtolower($domainOption) === 'register') {
            try {
                $existingDomain = \App\Models\Domain::where('domain_name', $domainName)->first();
                if ($existingDomain) {
                    // حدّث حالة وربط العميل إن لزم
                    $existingDomain->update([
                        'client_id' => $order->client_id ?? $existingDomain->client_id,
                        'status' => 'active',
                    ]);
                } else {
                    \App\Models\Domain::create([
                        'client_id' => $order->client_id,
                        'domain_name' => $domainName,
                        'registrar' => 'order-' . $order->id,
                        'registration_date' => now()->toDateString(),
                        'status' => 'active',
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to create/update domain for order ' . $order->id . ': ' . $e->getMessage());
            }
        }

        // تحقق من وجود اشتراك مسبق لنفس العميل والخطة وربما نفس الدومين
        $existingSubQuery = \App\Models\Subscription::where('client_id', $order->client_id)
            ->where('plan_id', $template->plan_id);

        if (!empty($domainName)) {
            $existingSubQuery->where('domain_name', $domainName);
        }

        $existingSub = $existingSubQuery->first();

        // مولّد اسم مستخدم فريد
        $generateUsername = function () use ($order, $domainName) {
            $username = null;

            if (!empty($domainName)) {
                $base = str_replace('.', '', $domainName);
                $base = strtolower(preg_replace('/[^a-z0-9]/i', '', $base));
                $base = substr($base, 0, 12);
                $username = $base;
            }

            if (empty($username) && isset($order->extra) && is_array($order->extra) && !empty($order->extra['username'] ?? null)) {
                $username = preg_replace('/[^a-z0-9]/i', '', $order->extra['username']);
            }

            if (empty($username)) {
                $client = \App\Models\Client::find($order->client_id);
                $base = null;
                if ($client) {
                    if (!empty($client->email) && strpos($client->email, '@') !== false) {
                        $base = explode('@', $client->email)[0];
                    } else {
                        $base = ($client->first_name ?? '') . ($client->last_name ?? '');
                    }
                }
                $base = strtolower(preg_replace('/[^a-z0-9]/i', '', $base ?? 'user'));
                $base = substr($base, 0, 12);
                $username = $base;
            }

            $candidate = $username;
            $suffix = 0;
            while (\App\Models\Subscription::where('username', $candidate)->exists()) {
                $suffix++;
                $candidate = $username . $suffix;
                if ($suffix > 1000) break;
            }
            return $candidate;
        };

        if ($existingSub) {
            $updateData = [
                'status'        => 'active',
                'starts_at'     => $startsAt,
                'ends_at'       => $endsAt,
                'domain_option' => $domainOption,
                'domain_name'   => $domainName,
            ];
            if (empty($existingSub->server_id) && $serverId) {
                $updateData['server_id'] = $serverId;
            }
            if (empty($existingSub->username)) {
                $updateData['username'] = $generateUsername();
            }
            $existingSub->update($updateData);
            $subscriptionModel = $existingSub;
        } else {
            $subscriptionModel = \App\Models\Subscription::create([
                'client_id'     => $order->client_id,
                'plan_id'       => $template->plan_id,
                'status'        => 'active',
                'price'         => $template->price,
                'starts_at'     => $startsAt,
                'ends_at'       => $endsAt,
                'server_id'     => $serverId,
                'server_package' => $template->plan?->server_package ?? ($template->plan?->name ?? null),
                'username'      => $generateUsername(),
                'domain_option' => $domainOption,
                'domain_name'   => $domainName,
            ]);
        }

        // مزامنة الاشتراك مع مزوّد السيرفر
        try {
            if (!empty($subscriptionModel) && $subscriptionModel instanceof \App\Models\Subscription) {
                \App\Jobs\SyncSubscriptionToProvider::dispatch($subscriptionModel->id);
            }
        } catch (\Exception $e) {
            Log::error('Failed to dispatch sync job for order ' . $order->id . ': ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $order = \App\Models\Order::with(['client', 'items'])->findOrFail($id);
        return view('dashboard.management.orders.show', compact('order'));
    }

    // تغيير حالة الطلب
    public function updateStatus($id, Request $request)
    {
        $order = \App\Models\Order::findOrFail($id);
        $request->validate([
            'status' => 'required|in:pending,active,cancelled,fraud',
        ]);

        $order->status = $request->status;
        $order->save();

        if ($order->status === 'active') {
            // حمّل العلاقات اللازمة لتقليل الاستعلامات داخل processActivation
            $order->loadMissing(['invoices.items', 'items']);
            $this->processActivation($order);
        }

        return redirect()->route('dashboard.orders.show', $order->id)->with('success', 'تم تحديث حالة الطلب بنجاح');
    }
}
