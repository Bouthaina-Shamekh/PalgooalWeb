<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Jobs\ProvisionSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function index($template_id)
    {
        $template = \App\Models\Template::find($template_id);
        $translation = $template?->translations()->where('locale', app()->getLocale())->first();

        return view('tamplate.checkout', compact('template_id', 'template', 'translation'));
    }

    /**
     * Render checkout for domain-only cart (no template).
     * Reads items from session if available. Renders same view with null template.
     */
    public function cart(Request $request)
    {
        $items = session('palgoals_cart_domains', []);
        // دعم تمرير قالب عبر الاستعلام لعرضه داخل المراجعة الموحدة
        $template_id = $request->query('template_id') ?? $request->query('tid');
        $template = null;
        $translation = null;
        if (!empty($template_id)) {
            $template = \App\Models\Template::find($template_id);
            $translation = $template?->translations()->where('locale', app()->getLocale())->first();
        }
        $plan_id = $request->query('plan_id');
        $plan_sub_type = $request->query('plan_sub_type');
        $plan = null;
        $plan_translation = null;
        if (!empty($plan_id)) {
            $plan = \App\Models\Plan::find($plan_id);
            $plan_translation = $plan?->translations()->where('locale', app()->getLocale())->first();
        }

        return view('tamplate.checkout', compact('template_id', 'template', 'translation', 'items', 'plan_id', 'plan', 'plan_translation', 'plan_sub_type'));
    }

    /**
     * Checkout handler for both template checkout and domain-only checkout (when $template_id is null).
     */
    public function process(Request $request, $template_id = null, $plan_id = null)
    {
        // إنشاء حساب للعميل في حال عدم التسجيل
        if (!auth('client')->check()) {
            $request->validate([
                'first_name' => 'required|string|max:100',
                'last_name'  => 'required|string|max:100',
                'email'      => 'required|email|max:255|unique:clients,email',
                'phone'      => 'required|string|max:30',
                'password'   => 'required|string|min:6|confirmed',
            ]);

            $client = new \App\Models\Client();
            $client->first_name  = $request->first_name;
            $client->last_name   = $request->last_name;
            $client->email       = $request->email;
            $client->phone       = $request->phone;
            $client->company_name = $request->company_name ?? '-';
            $client->password    = bcrypt($request->password);
            $client->save();

            auth('client')->login($client);
        }

        $isDomainOnly = empty($template_id) && empty($plan_id);
        $isNotTemplate = empty($template_id);
        $isNotPlan = empty($plan_id);

        // لو في عناصر قادمة من الطلب استخدمها؛ وإلا خذها من السيشن (لسيناريو الدومين فقط)
        $rawItems = $request->input('items', session('palgoals_cart_domains', []));
        $items = array_map(function ($it) {
            return [
                'domain'      => isset($it['domain']) ? strtolower(trim($it['domain'])) : null,
                'item_option' => $it['item_option'] ?? $it['option'] ?? null, // تطبيع
                'price_cents' => isset($it['price_cents']) ? (int) $it['price_cents'] : 0,
                'meta'        => $it['meta'] ?? null,
            ];
        }, is_array($rawItems) ? $rawItems : []);

        // معلومات القالب (إن وجد)
        $template    = $isNotTemplate ? null : \App\Models\Template::find($template_id);
        $translation = $template?->translations()->where('locale', app()->getLocale())->first();
        $template_name = $translation?->name ?? $template?->name ?? '';

        $basePrice = (float) ($template->price ?? 0);
        $discRaw   = $template->discount_price ?? null;
        $discPrice = is_null($discRaw) ? null : (float) $discRaw;
        $hasDiscount  = !is_null($discPrice) && $discPrice > 0 && $discPrice < $basePrice;
        $showDiscount = $hasDiscount && (!$template?->discount_ends_at || now()->lt($template->discount_ends_at));

        // معلومات الخطة (إن وجد)
        $plan = $isNotPlan ? null : \App\Models\Plan::find($plan_id);
        $planTranslation = null;
        $plan_name = '';
        $monthlyPricePlan = 0.0;
        $annualPricePlan = 0.0;
        $plan_sub_type = $request->query('plan_sub_type');
        $basePricePlan = 0.0;
        $discRawPlan = null;
        $discPricePlan = null;
        $hasDiscountPlan = false;
        $showDiscountPlan = false;

        if ($plan) {
            $planTranslation = $plan->translations()->where('locale', app()->getLocale())->first();
            $plan_name = $planTranslation?->name ?? $plan->name ?? '';
            $monthlyPricePlan = (float) (($plan->monthly_price_cents ?? 0) / 100);
            $annualPricePlan = (float) (($plan->annual_price_cents ?? 0) / 100);
            $basePricePlan = $plan_sub_type === 'monthly' ? $monthlyPricePlan : $annualPricePlan;

            if ($basePricePlan <= 0) {
                $basePricePlan = $annualPricePlan > 0 ? $annualPricePlan : $monthlyPricePlan;
            }

            $discRawPlan = $plan->discount_price;
            $discPricePlan = !is_null($discRawPlan) && $discRawPlan > 0 ? (float) $discRawPlan : null;
            $hasDiscountPlan = $discPricePlan !== null && $basePricePlan > 0 && $discPricePlan < $basePricePlan;
            $showDiscountPlan = $hasDiscountPlan && (!$plan->discount_ends_at || now()->lt($plan->discount_ends_at));
        }

        // تطبيع اختيار الدومين القادم من الواجهة (للاشتراك)
        $rawOption = $request->input('domain_option'); // قد لا يكون موجودًا
        $optionMap = [
            'register'  => 'new',
            'subdomain' => 'subdomain',
            'own'       => 'existing',
            'transfer'  => 'existing',
        ];
        $normalizedOption = $rawOption ? ($optionMap[$rawOption] ?? $rawOption) : null;

        // إجمالي سلة الدومينات (في حالة الدومين فقط)
        $domainsTotalCents = array_reduce($items, fn($c, $it) => $c + ((int) ($it['price_cents'] ?? 0)), 0);

        // تحقق خاص بتدفّق القالب: يجب وجود دومين أساسي
        if (!$isDomainOnly) {
            $request->validate([
                'domain'        => 'required|string|min:1',
                'domain_option' => 'required|string|min:1',
            ]);
        }

        try {
            $provisionQueue = [];

            $result = DB::transaction(function () use (
                &$provisionQueue,
                $isDomainOnly,
                $isNotTemplate,
                $isNotPlan,
                $items,
                $template,
                $template_id,
                $template_name,
                $plan,
                $plan_id,
                $plan_name,
                $basePrice,
                $discPrice,
                $showDiscount,
                $basePricePlan,
                $discPricePlan,
                $showDiscountPlan,
                $normalizedOption,
                $request
            ) {
                // 1) إنشاء الطلب
                $order = \App\Models\Order::create([
                    'client_id' => auth('client')->check() ? auth('client')->id() : null,
                    'status'    => 'pending',
                    'type'      => $isDomainOnly ? 'domains' : 'subscription',
                    'notes'     => $isDomainOnly ? 'حجز دومينات من سلة الدومينات' : 'طلب عبر صفحة checkout',
                ]);

                // 2) إنشاء بنود الطلب
                if ($isDomainOnly) {
                    if (empty($items)) {
                        abort(422, 'السلة فارغة.');
                    }
                    $payload = array_map(function ($it) {
                        return [
                            'domain'      => $it['domain'],
                            'item_option' => $it['item_option'],
                            'price_cents' => (int) ($it['price_cents'] ?? 0),
                            'meta'        => $it['meta'],
                        ];
                    }, $items);
                    $order->items()->createMany($payload);
                } else {
                    // لو وصل دومين مع شراء القالب، خزّنه كبند واحد (اختياري لكنه مفيد للمراجعة/التتبع)
                    $domainFromRequest = $request->input('domain');
                    $optionFromRequest = $request->input('domain_option');
                    if (!empty($domainFromRequest) || !empty($optionFromRequest)) {
                        $order->items()->create([
                            'domain'      => $domainFromRequest ? strtolower(trim($domainFromRequest)) : null,
                            'item_option' => $optionFromRequest ?? null,
                            'price_cents' => 0,
                            'meta'        => null,
                        ]);
                    }
                }

                // 3) إنشاء الفاتورة
                if ($isDomainOnly) {
                    $subtotalCents = (int) array_reduce($items, fn($c, $it) => $c + ((int) ($it['price_cents'] ?? 0)), 0);
                    $invoice = \App\Models\Invoice::create([
                        'client_id'      => $order->client_id,
                        'number'         => 'INV-' . $order->order_number,
                        'status'         => 'draft',
                        'subtotal_cents' => $subtotalCents,
                        'discount_cents' => 0,
                        'tax_cents'      => 0,
                        'total_cents'    => $subtotalCents,
                        'currency'       => 'USD',
                        'due_date'       => now()->addDays(3),
                        'order_id'       => $order->id,
                    ]);
                    // بإمكانك لاحقًا إضافة invoice_items لكل دومين إن رغبت
                } else {
                    $unitCents = $showDiscount ? (int) ($discPrice * 100) : (int) ($basePrice * 100);
                    $unitCentsPlan = $showDiscountPlan ? (int) ($discPricePlan * 100) : (int) ($basePricePlan * 100);
                    $subscriptionLineConfigs = [];

                    if (!$isNotTemplate) {
                        $planTemplate = $template?->plan;
                        $subscriptionLineConfigs[] = [
                            'description'   => $template_name ?: ($planTemplate?->name ?? ''),
                            'unit_cents'    => $unitCents,
                            'base_cents'    => (int) (($basePrice ?? 0) * 100),
                            'plan'          => $planTemplate,
                            'billing_cycle' => $planTemplate?->billing_cycle ?? 'annually',
                        ];
                    }

                    if (!$isNotPlan && $plan) {
                        $subscriptionLineConfigs[] = [
                            'description'   => $plan_name ?? '',
                            'unit_cents'    => $unitCentsPlan,
                            'base_cents'    => (int) (($basePricePlan ?? 0) * 100),
                            'plan'          => $plan,
                            'billing_cycle' => null,
                        ];
                    }

                    $subscriptionBaseSum = array_sum(array_map(
                        fn ($config) => $config['base_cents'],
                        $subscriptionLineConfigs
                    ));
                    $subscriptionTotalSum = array_sum(array_map(
                        fn ($config) => $config['unit_cents'],
                        $subscriptionLineConfigs
                    ));
                    $domainLineTotal = array_reduce(
                        $items,
                        fn ($carry, $domainItem) => $carry + (int) ($domainItem['price_cents'] ?? 0),
                        0
                    );
                    $baseSubtotal = $subscriptionBaseSum + $domainLineTotal;
                    $lineTotals = $subscriptionTotalSum + $domainLineTotal;
                    $discountCentsTotal = max(0, $baseSubtotal - $lineTotals);

                    $invoice = \App\Models\Invoice::create([
                        'client_id'      => $order->client_id,
                        'number'         => 'INV-' . $order->order_number,
                        'status'         => 'draft',
                        'subtotal_cents' => $baseSubtotal,
                        'discount_cents' => $discountCentsTotal,
                        'tax_cents'      => 0,
                        'total_cents'    => $lineTotals,
                        'currency'       => 'USD',
                        'due_date'       => now()->addDays(3),
                        'order_id'       => $order->id,
                    ]);

                    $firstDomainItem = $order->items()
                        ->whereNotNull('domain')
                        ->where('domain', '<>', '')
                        ->orderBy('id')
                        ->first();

                    foreach ($subscriptionLineConfigs as $config) {
                        $planModel = $config['plan'];
                        $subscription = null;

                        if ($planModel) {
                            $subscriptionData = [
                                'client_id'     => $order->client_id,
                                'plan_id'       => $planModel->id,
                                'status'        => 'pending',
                                'price'         => $config['unit_cents'] / 100,
                                'server_id'     => $planModel->server_id ?? null,
                                'domain_option' => $normalizedOption ?? 'subdomain',
                                'domain_name'   => $firstDomainItem->domain ?? $request->input('domain'),
                                'starts_at'     => now(),
                                'next_due_date' => now()->addMonth(),
                            ];

                            if (!empty($config['billing_cycle'])) {
                                $subscriptionData['billing_cycle'] = $config['billing_cycle'];
                            }

                            $subscription = \App\Models\Subscription::create($subscriptionData);
                            if ($subscription) {
                                $provisionQueue[] = $subscription;
                            }
                        }

                        \App\Models\InvoiceItem::create([
                            'invoice_id'       => $invoice->id,
                            'item_type'        => 'subscription',
                            'reference_id'     => $subscription?->id,
                            'description'      => trim($config['description']) !== ''
                                ? $config['description']
                                : ($subscription ? 'Subscription #' . $subscription->id : 'Subscription'),
                            'qty'              => 1,
                            'unit_price_cents' => $config['unit_cents'],
                            'total_cents'      => $config['unit_cents'],
                        ]);
                    }

                    foreach ($items as $domainItem) {
                        $unitCentsDomain = (int) ($domainItem['price_cents'] ?? 0);
                        $domainName = $domainItem['domain'] ?? null;

                        if ($unitCentsDomain <= 0 && empty($domainName)) {
                            continue;
                        }

                        $invoice->items()->create([
                            'item_type'        => 'domain',
                            'reference_id'     => null,
                            'description'      => $domainName
                                ? 'Domain registration: ' . $domainName
                                : 'Domain registration',
                            'qty'              => 1,
                            'unit_price_cents' => $unitCentsDomain,
                            'total_cents'      => $unitCentsDomain,
                        ]);
                    }
                }

                return $order;
            });

            foreach ($provisionQueue as $subscription) {
                ProvisionSubscription::dispatch($subscription->id);
            }

            // احفظ مرجعًا في الجلسة
            session([
                'palgoals_reserved'      => $items,
                'palgoals_last_order_id' => $result->id,
            ]);

            // البيانات المرجعة للواجهة (تأكد من تعريفها لجميع السيناريوهات)
            $client_name  = auth('client')->user()->first_name ?? '';
            $domainPicked = $items[0]['domain'] ?? $request->input('domain');
            $totalCents   = $isDomainOnly ? $domainsTotalCents : 0;
            $totalCentsPlan = $isDomainOnly ? $domainsTotalCents : 0;

            // Default response (covers domain-only case)
            $responseData = [
                'success'     => true,
                'order_no'    => $result->order_number,
                'order_id'    => $result->id,
                'domain'      => $domainPicked,
                'total_cents' => $totalCents,
                'client_name' => $client_name,
                'redirect'    => route('checkout.domains.success'),
            ];

            // Template-specific override
            if (!$isNotTemplate) {
                $totalCents = $isDomainOnly
                    ? $domainsTotalCents
                    : ($showDiscount ? (int) ($discPrice * 100) : (int) ($basePrice * 100));

                $responseData = array_merge($responseData, [
                    'total_cents'   => $totalCents,
                    'template_name' => $template_name,
                ]);
            }

            // Plan-specific override
            if (!$isNotPlan) {
                $totalCentsPlan = $isDomainOnly
                    ? $domainsTotalCents
                    : ($showDiscountPlan ? (int) ($discPricePlan * 100) : (int) ($basePricePlan * 100));

                $responseData = array_merge($responseData, [
                    'total_cents' => $totalCentsPlan,
                    'plan_name'   => $plan_name,
                ]);
            }

            // If AJAX/json requested, return JSON payload
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json($responseData);
            }

            // Non-AJAX: redirect to a suitable checkout page depending on scenario
            if (!$isNotTemplate) {
                return redirect()->route('checkout', [
                    'template_id'   => $template_id,
                    'success'       => 1,
                    'order_no'      => $result->order_number,
                    'domain'        => $domainPicked,
                    'total'         => $totalCents / 100,
                    'client_name'   => $client_name,
                    'template_name' => $template_name,
                ]);
            }

            if (!$isNotPlan) {
                // Redirect to cart-based checkout view with plan context
                return redirect()->route('checkout.cart', [
                    'plan_id'     => $plan_id,
                    'plan_name'   => $plan_name,
                    'success'     => 1,
                    'order_no'    => $result->order_number,
                    'domain'      => $domainPicked,
                    'total'       => $totalCentsPlan / 100,
                    'client_name' => $client_name,
                ]);
            }

            // Domain-only fallback → cart-based checkout
            return redirect()->route('checkout.cart', [
                'success'     => 1,
                'order_no'    => $result->order_number,
                'domain'      => $domainPicked,
                'total'       => $totalCents / 100,
                'client_name' => $client_name,
            ]);
        } catch (\Throwable $e) {
            Log::error('CheckoutController::process failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'تعذر إتمام عملية الدفع الآن.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST entry for cart checkout (domain-only). Validates items then forwards to process().
     */
    public function processCart(Request $request)
    {
        $data = $request->validate([
            'items'               => 'required|array|min:1',
            'items.*.domain'      => 'required|string',
            'items.*.option'      => 'required|string', // سنطبّعها داخل process إلى item_option
            'items.*.price_cents' => 'nullable|integer|min:0',
        ]);

        session(['palgoals_cart_domains' => $data['items']]);

        // مرّر العناصر إلى process() مع template_id = null
        return $this->process($request->merge(['items' => $data['items']]), null);
    }
}
