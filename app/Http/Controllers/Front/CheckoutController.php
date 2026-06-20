<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Jobs\ProvisionSubscription;
use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\Tenancy\Subscription;
use App\Services\Billing\InvoiceSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function index($template_id)
    {
        $template = \App\Models\Template::find($template_id);
        $translation = $template?->translations()->where('locale', app()->getLocale())->first();
        $items = session('palgoals_cart_domains', []);
        $plan_id = null;
        $plan = null;
        $plan_translation = null;
        $plan_sub_type = null;
        $checkout_mode = 'template';
        $requires_domain_selection = false;

        return view('front.pages.checkout', compact(
            'template_id',
            'template',
            'translation',
            'items',
            'plan_id',
            'plan',
            'plan_translation',
            'plan_sub_type',
            'checkout_mode',
            'requires_domain_selection'
        ));
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
        $plan_sub_type = in_array($request->query('plan_sub_type'), ['monthly', 'annual'])
            ? $request->query('plan_sub_type')
            : 'monthly'; // default to monthly when not specified
        $plan = null;
        $plan_translation = null;
        if (!empty($plan_id)) {
            $plan = \App\Models\Plan::find($plan_id);
            $plan_translation = $plan?->translations()->where('locale', app()->getLocale())->first();
        }
        $checkout_mode = !empty($template_id) ? 'template' : 'hosting';
        // Hosting: require domain only if the selected plan has requires_domain = true.
        // Template checkout never needs domain selection here (handled upstream).
        $requires_domain_selection = $checkout_mode === 'hosting'
            ? (bool) ($plan?->requires_domain ?? true)
            : false;

        return view('front.pages.checkout', compact(
            'template_id',
            'template',
            'translation',
            'items',
            'plan_id',
            'plan',
            'plan_translation',
            'plan_sub_type',
            'checkout_mode',
            'requires_domain_selection'
        ));
    }

    /**
     * Checkout handler for both template checkout and domain-only checkout (when $template_id is null).
     */
    public function process(Request $request, $template_id = null, $plan_id = null)
    {
        // ADR-007 Phase 1 — Payment gateway feature flag
        // Set PAYMENT_GATEWAY_ENABLED=false in .env to block public checkout
        // without affecting admin bulk-mark-paid or auto-renewal jobs.
        if (!app(\App\Payments\PaymentManager::class)->isEnabled()) {
            $message = t('site.Payment_Not_Available', 'خدمة الدفع غير متاحة حالياً. يرجى المحاولة لاحقاً.');
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 503);
            }
            return redirect()->back()->with('error', $message);
        }

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
            $client->can_login   = true;
            $client->password    = bcrypt($request->password);
            $client->save();

            auth('client')->login($client);
        }

        $isDomainOnly = empty($template_id) && empty($plan_id);
        $isNotTemplate = empty($template_id);
        $isNotPlan = empty($plan_id);
        $isTemplateCheckout = !$isNotTemplate;
        $requiresDomainSelection = !$isTemplateCheckout;

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

        $basePriceCents = $template ? $template->resolvedPriceCents() : 0;
        $discPriceCents = $template ? $template->resolvedDiscountPriceCents() : null;
        $basePrice      = $basePriceCents / 100;   // float — display only
        $discPrice      = $discPriceCents !== null ? $discPriceCents / 100 : null;  // float — display only
        $hasDiscount    = $discPriceCents !== null && $discPriceCents > 0 && $discPriceCents < $basePriceCents;
        $showDiscount   = $hasDiscount && (!$template?->discount_ends_at || now()->lt($template->discount_ends_at));

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

        if ($isTemplateCheckout && blank($normalizedOption)) {
            $normalizedOption = 'subdomain';
        }

        // إجمالي سلة الدومينات (في حالة الدومين فقط)
        $domainsTotalCents = array_reduce($items, fn($c, $it) => $c + ((int) ($it['price_cents'] ?? 0)), 0);

        // تحقق خاص بتدفّق القالب: يجب وجود دومين أساسي
        if ($requiresDomainSelection && !$isDomainOnly) {
            $request->validate([
                'domain'        => 'required|string|min:1',
                'domain_option' => 'required|string|min:1',
            ]);
        }

        // ADR-008 Phase 3 — Coupon resolution (server-side; never trust frontend discount)
        // The frontend only sends the code; we re-validate and re-compute here.
        $couponCode = strtoupper(trim((string) $request->input('coupon_code', '')));
        $coupon     = null;
        if ($couponCode !== '') {
            $coupon = Coupon::usable()->where('code', $couponCode)->first();
            // If the code is invalid/expired/exhausted, we silently proceed with no discount
            // (the frontend already showed the user an error via the validation API).
        }

        try {
            $provisionQueue = [];
            $createdSubscriptionIds = [];

            $result = DB::transaction(function () use (
                &$provisionQueue,
                &$createdSubscriptionIds,
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
                $basePriceCents,
                $discPrice,
                $discPriceCents,
                $showDiscount,
                $basePricePlan,
                $discPricePlan,
                $showDiscountPlan,
                $normalizedOption,
                $coupon,
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

                    // ADR-008 Phase 3 — coupon discount (server-side, re-validated above)
                    $couponDiscount = ($coupon && $coupon->isUsableForSubtotal($subtotalCents))
                        ? $coupon->computeDiscountCents($subtotalCents)
                        : 0;

                    $invoice = \App\Models\Invoice::create([
                        'client_id'      => $order->client_id,
                        'number'         => 'INV-' . $order->order_number,
                        'status'         => 'draft',
                        'subtotal_cents' => $subtotalCents,
                        'discount_cents' => $couponDiscount,
                        'tax_cents'      => 0,
                        'total_cents'    => max(0, $subtotalCents - $couponDiscount),
                        'currency'       => 'USD',
                        'due_date'       => now()->addDays(3),
                        'order_id'       => $order->id,
                        'coupon_id'      => $coupon?->id,
                    ]);
                    // بإمكانك لاحقًا إضافة invoice_items لكل دومين إن رغبت
                } else {
                    // ADR-003 Phase 1 — use integer cents directly (no float * 100 rounding risk)
                    $unitCents     = $showDiscount ? ($discPriceCents ?? $basePriceCents) : $basePriceCents;
                    $unitCentsPlan = $showDiscountPlan ? (int) ($discPricePlan * 100) : (int) ($basePricePlan * 100);
                    $subscriptionLineConfigs = [];

                    if (!$isNotTemplate) {
                        $planTemplate = $template?->plan;
                        $subscriptionLineConfigs[] = [
                            'description'   => $template_name ?: ($planTemplate?->name ?? ''),
                            'unit_cents'    => $unitCents,
                            'base_cents'    => $basePriceCents,
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

                    // Template/plan discount (price vs discount_price from DB)
                    $templatePlanDiscount = max(0, $baseSubtotal - ($subscriptionTotalSum + $domainLineTotal));

                    // ADR-008 Phase 3 — coupon discount applied on top of plan discounts
                    // Subtotal for coupon purposes = what the customer actually pays before coupon
                    $preCouponTotal = $subscriptionTotalSum + $domainLineTotal;
                    $couponDiscount = ($coupon && $coupon->isUsableForSubtotal($preCouponTotal))
                        ? $coupon->computeDiscountCents($preCouponTotal)
                        : 0;

                    $discountCentsTotal = $templatePlanDiscount + $couponDiscount;
                    $invoiceTotal       = max(0, $baseSubtotal - $discountCentsTotal);

                    $invoice = \App\Models\Invoice::create([
                        'client_id'      => $order->client_id,
                        'number'         => 'INV-' . $order->order_number,
                        'status'         => 'draft',
                        'subtotal_cents' => $baseSubtotal,
                        'discount_cents' => $discountCentsTotal,
                        'tax_cents'      => 0,
                        'total_cents'    => $invoiceTotal,
                        'currency'       => 'USD',
                        'due_date'       => now()->addDays(3),
                        'order_id'       => $order->id,
                        'coupon_id'      => $coupon?->id,
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
                            $billingCycle = !empty($config['billing_cycle'])
                                ? (string) $config['billing_cycle']
                                : 'monthly';
                            $nextDueDate = str_contains(strtolower($billingCycle), 'month')
                                ? now()->addMonth()
                                : now()->addYear();
                            $subscriptionData = [
                                'client_id'     => $order->client_id,
                                'plan_id'       => $planModel->id,
                                'template_id'   => !$isNotTemplate && $template && $planModel->id === (int) $template->plan_id
                                    ? $template->id
                                    : null,
                                'status'        => 'pending',
                                'provisioning_status' => \App\Models\Tenancy\Subscription::PROVISIONING_PENDING,
                                'price_cents'   => (int) $config['unit_cents'],
                                'server_id'     => $planModel->server_id ?? null,
                                'domain_option' => $normalizedOption ?? 'subdomain',
                                'domain_name'   => $firstDomainItem->domain ?? $request->input('domain'),
                                'starts_at'     => now(),
                                'next_due_date' => $nextDueDate,
                            ];

                            $subscriptionData['billing_cycle'] = $billingCycle;

                            $subscription = \App\Models\Tenancy\Subscription::create($subscriptionData);
                            if ($subscription) {
                                $createdSubscriptionIds[] = $subscription->id;

                                if ($isNotTemplate) {
                                    $provisionQueue[] = $subscription;
                                }
                            }
                        }

                        \App\Models\InvoiceItem::create([
                            'invoice_id'       => $invoice->id,
                            'item_type'        => 'subscription',
                            'reference_id'     => $subscription?->id,
                            'description'      => trim($config['description']) !== ''
                                ? $config['description']
                                : ($subscription ? 'اشتراك #' . $subscription->id : 'اشتراك'),
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
                                ? 'تسجيل نطاق: ' . $domainName
                                : 'تسجيل نطاق',
                            'qty'              => 1,
                            'unit_price_cents' => $unitCentsDomain,
                            'total_cents'      => $unitCentsDomain,
                        ]);
                    }
                }

                return [
                    'order_id' => $order->id,
                    'invoice_id' => $invoice->id,
                    'subscription_ids' => $createdSubscriptionIds,
                ];
            });

            $order = \App\Models\Order::findOrFail($result['order_id']);
            $invoice = Invoice::query()->findOrFail($result['invoice_id']);
            $subscriptionIds = array_values(array_filter($result['subscription_ids'] ?? []));

            // ADR-008 Phase 3 — Coupon usage tracking is intentionally deferred to
            // InvoiceSettlementService::markPaid(), which fires only after real payment.
            // Tracking here would consume the coupon even if the customer never pays.

            if (!$isNotTemplate) {
                app(InvoiceSettlementService::class)->markPaid($invoice, app(\App\Payments\PaymentManager::class)->gateway()->name());
            } else {
                foreach ($provisionQueue as $subscription) {
                    ProvisionSubscription::dispatch($subscription->id);
                }
            }

            $tenantRedirectUrl = !$isNotTemplate
                ? $this->resolveTenantRedirectUrl($subscriptionIds)
                : null;
            $subscriptions = $this->loadCheckoutSubscriptions($subscriptionIds);
            $primarySubscription = $subscriptions->first();

            // احفظ مرجعًا في الجلسة
            session([
                'palgoals_reserved'      => $items,
                'palgoals_last_order_id' => $order->id,
            ]);

            // البيانات المرجعة للواجهة (تأكد من تعريفها لجميع السيناريوهات)
            $client_name  = auth('client')->user()->first_name ?? '';
            $domainPicked = $items[0]['domain'] ?? $request->input('domain');
            $totalCents   = $isDomainOnly ? $domainsTotalCents : 0;
            $totalCentsPlan = $isDomainOnly ? $domainsTotalCents : 0;
            $successState = $this->buildSuccessState(
                $invoice,
                $primarySubscription,
                $domainPicked,
                $normalizedOption,
                $isTemplateCheckout,
                $isDomainOnly,
                $tenantRedirectUrl
            );

            // Default response (covers domain-only case)
            $responseData = [
                'success'     => true,
                'order_no'    => $order->order_number,
                'order_id'    => $order->id,
                'subscription_id' => $primarySubscription?->id,
                'domain'      => $successState['domain'] ?? $domainPicked,
                'total_cents' => $totalCents,
                'total'       => '$' . number_format(($invoice->total_cents ?? 0) / 100, 2),
                'client_name' => $client_name,
                'checkout_mode' => $isTemplateCheckout ? 'template' : 'hosting',
                'dashboard_url' => route('client.subscriptions'),
            ];

            $responseData = array_merge($responseData, $successState);

            // Template-specific override
            if (!$isNotTemplate) {
                // ADR-003 Phase 1 — use integer cents directly
                $totalCents = $isDomainOnly
                    ? $domainsTotalCents
                    : ($showDiscount ? ($discPriceCents ?? $basePriceCents) : $basePriceCents);

                $responseData = array_merge($responseData, [
                    'total_cents'   => $totalCents,
                    'template_name' => $template_name,
                    'total'         => '$' . number_format($totalCents / 100, 2),
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
                    'total'       => '$' . number_format($totalCentsPlan / 100, 2),
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
                    'order_no'      => $order->order_number,
                    'subscription_id' => $primarySubscription?->id,
                    'domain'        => $responseData['domain'] ?? $domainPicked,
                    'total'         => $totalCents / 100,
                    'client_name'   => $client_name,
                    'template_name' => $template_name,
                    'checkout_mode' => 'template',
                    'success_title' => $responseData['success_title'] ?? null,
                    'success_message' => $responseData['success_message'] ?? null,
                    'payment_status_label' => $responseData['payment_status_label'] ?? null,
                    'payment_status_tone' => $responseData['payment_status_tone'] ?? null,
                    'provisioning_status_label' => $responseData['provisioning_status_label'] ?? null,
                    'provisioning_status_tone' => $responseData['provisioning_status_tone'] ?? null,
                    'domain_status_label' => $responseData['domain_status_label'] ?? null,
                    'domain_status_tone' => $responseData['domain_status_tone'] ?? null,
                    'site_url' => $responseData['site_url'] ?? null,
                ]);
            }

            if (!$isNotPlan) {
                // Redirect to cart-based checkout view with plan context
                return redirect()->route('checkout.cart', [
                    'plan_id'     => $plan_id,
                    'plan_name'   => $plan_name,
                    'success'     => 1,
                    'order_no'    => $order->order_number,
                    'subscription_id' => $primarySubscription?->id,
                    'domain'      => $responseData['domain'] ?? $domainPicked,
                    'total'       => $totalCentsPlan / 100,
                    'client_name' => $client_name,
                    'checkout_mode' => 'hosting',
                    'success_title' => $responseData['success_title'] ?? null,
                    'success_message' => $responseData['success_message'] ?? null,
                    'payment_status_label' => $responseData['payment_status_label'] ?? null,
                    'payment_status_tone' => $responseData['payment_status_tone'] ?? null,
                    'provisioning_status_label' => $responseData['provisioning_status_label'] ?? null,
                    'provisioning_status_tone' => $responseData['provisioning_status_tone'] ?? null,
                    'domain_status_label' => $responseData['domain_status_label'] ?? null,
                    'domain_status_tone' => $responseData['domain_status_tone'] ?? null,
                    'site_url' => $responseData['site_url'] ?? null,
                ]);
            }

            // Domain-only fallback → cart-based checkout
            return redirect()->route('checkout.cart', [
                'success'     => 1,
                'order_no'    => $order->order_number,
                'subscription_id' => $primarySubscription?->id,
                'domain'      => $responseData['domain'] ?? $domainPicked,
                'total'       => $totalCents / 100,
                'client_name' => $client_name,
                'checkout_mode' => 'hosting',
                'success_title' => $responseData['success_title'] ?? null,
                'success_message' => $responseData['success_message'] ?? null,
                'payment_status_label' => $responseData['payment_status_label'] ?? null,
                'payment_status_tone' => $responseData['payment_status_tone'] ?? null,
                'provisioning_status_label' => $responseData['provisioning_status_label'] ?? null,
                'provisioning_status_tone' => $responseData['provisioning_status_tone'] ?? null,
                'domain_status_label' => $responseData['domain_status_label'] ?? null,
                'domain_status_tone' => $responseData['domain_status_tone'] ?? null,
                'site_url' => $responseData['site_url'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('CheckoutController::process failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'تعذر إتمام عملية الدفع الآن.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST entry for cart checkout (domain-only). Validates items then forwards to process().
     */
    protected function resolveTenantRedirectUrl(array $subscriptionIds): ?string
    {
        if ($subscriptionIds === []) {
            return null;
        }

        $subscriptions = Subscription::query()
            ->whereIn('id', $subscriptionIds)
            ->get()
            ->keyBy('id');

        foreach ($subscriptionIds as $subscriptionId) {
            $subscription = $subscriptions->get($subscriptionId);

            if (! $subscription || blank($subscription->activeSiteHost())) {
                continue;
            }

            return $this->tenantUrl($subscription->activeSiteHost());
        }

        return null;
    }

    protected function tenantUrl(string $domain): string
    {
        return tenant_url($domain);
    }

    protected function loadCheckoutSubscriptions(array $subscriptionIds)
    {
        if ($subscriptionIds === []) {
            return collect();
        }

        return Subscription::query()
            ->with(['plan', 'template'])
            ->whereIn('id', $subscriptionIds)
            ->get()
            ->sortBy(fn (Subscription $subscription) => array_search($subscription->id, $subscriptionIds, true))
            ->values();
    }

    protected function buildSuccessState(
        Invoice $invoice,
        ?Subscription $subscription,
        ?string $domainPicked,
        ?string $normalizedOption,
        bool $isTemplateCheckout,
        bool $isDomainOnly,
        ?string $tenantRedirectUrl
    ): array {
        $paymentMeta = $this->paymentStatusMeta((string) ($invoice->status ?? 'draft'));
        $provisioningMeta = $this->provisioningStatusMeta(
            $subscription?->provisioning_status,
            $subscription !== null && ! $isDomainOnly
        );
        $domainMeta = $this->domainStatusMeta($subscription, $domainPicked, $normalizedOption, $isTemplateCheckout);

        $siteReady = $isTemplateCheckout
            && $subscription?->status === 'active'
            && $subscription?->provisioning_status === Subscription::PROVISIONING_ACTIVE
            && filled($subscription?->activeSiteHost());

        if ($isTemplateCheckout) {
            $title = $siteReady ? 'موقعك جاهز الآن! 🎉' : 'جارٍ إعداد موقعك...';
            $message = $siteReady
                ? 'تم إعداد موقعك بنجاح وهو جاهز للزيارة الآن.'
                : 'تم استلام الدفع وبدأ إعداد الموقع. سنُنهي تجهيز موقعك قريباً.';
        } else {
            $title = 'تم استلام طلبك بنجاح 🎉';
            $message = 'راجع حالة الدفع والإعداد والنطاق أدناه.';
        }

        return [
            'success_title' => $title,
            'success_message' => $message,
            'post_checkout_state' => $siteReady ? 'ready' : 'preparing',
            'payment_status' => $paymentMeta['value'],
            'payment_status_label' => $paymentMeta['label'],
            'payment_status_tone' => $paymentMeta['tone'],
            'provisioning_status' => $provisioningMeta['value'],
            'provisioning_status_label' => $provisioningMeta['label'],
            'provisioning_status_tone' => $provisioningMeta['tone'],
            'domain_status' => $domainMeta['value'],
            'domain_status_label' => $domainMeta['label'],
            'domain_status_tone' => $domainMeta['tone'],
            'domain' => $subscription?->domain_name ?: $domainPicked,
            'site_url' => $siteReady
                ? ($tenantRedirectUrl ?: $subscription?->activeSiteUrl())
                : null,
        ];
    }

    protected function paymentStatusMeta(string $status): array
    {
        return match ($status) {
            'paid'   => ['value' => 'paid',   'label' => 'تم تأكيد الدفع',          'tone' => 'emerald'],
            'unpaid' => ['value' => 'unpaid', 'label' => 'في انتظار تأكيد الدفع',   'tone' => 'amber'],
            default  => ['value' => $status ?: 'draft', 'label' => 'تم إرسال الدفع', 'tone' => 'sky'],
        };
    }

    protected function provisioningStatusMeta(?string $status, bool $hasProvisioning): array
    {
        if (! $hasProvisioning) {
            return ['value' => 'not_applicable', 'label' => 'لا يلزم إعداد موقع', 'tone' => 'slate'];
        }

        return match ($status) {
            Subscription::PROVISIONING_ACTIVE      => ['value' => Subscription::PROVISIONING_ACTIVE,      'label' => 'تم إعداد الموقع',       'tone' => 'emerald'],
            Subscription::PROVISIONING_IN_PROGRESS => ['value' => Subscription::PROVISIONING_IN_PROGRESS, 'label' => 'جارٍ إعداد الموقع',     'tone' => 'sky'],
            Subscription::PROVISIONING_FAILED      => ['value' => Subscription::PROVISIONING_FAILED,      'label' => 'فشل إعداد الموقع',      'tone' => 'red'],
            default                                => ['value' => $status ?: Subscription::PROVISIONING_PENDING, 'label' => 'في طابور الإعداد', 'tone' => 'amber'],
        };
    }

    protected function domainStatusMeta(
        ?Subscription $subscription,
        ?string $domainPicked,
        ?string $normalizedOption,
        bool $isTemplateCheckout
    ): array {
        $resolvedDomain = $subscription?->domain_name ?: $domainPicked;

        if (filled($resolvedDomain)) {
            if (($subscription?->domain_option ?: $normalizedOption) === 'subdomain' && $isTemplateCheckout) {
                return ['value' => 'auto_subdomain', 'label' => 'تم تعيين Subdomain تلقائي', 'tone' => 'emerald'];
            }

            if ($subscription?->requiresDomainVerification()) {
                return match ($subscription->effectiveDomainVerificationStatus()) {
                    Subscription::DOMAIN_VERIFICATION_ACTIVE      => ['value' => 'custom_domain_active',      'label' => 'النطاق المخصص نشط',                  'tone' => 'emerald'],
                    Subscription::DOMAIN_VERIFICATION_SSL_PENDING => ['value' => 'custom_domain_ssl_pending', 'label' => 'في انتظار HTTPS (SSL قيد الإعداد)',   'tone' => 'sky'],
                    Subscription::DOMAIN_VERIFICATION_DNS_PENDING => ['value' => 'custom_domain_dns_pending', 'label' => 'في انتظار التحقق (DNS لم يُكتشف بعد)', 'tone' => 'amber'],
                    Subscription::DOMAIN_VERIFICATION_FAILED      => ['value' => 'custom_domain_failed',      'label' => 'فشل التحقق من النطاق المخصص',         'tone' => 'red'],
                    default                                        => ['value' => 'custom_domain_pending',     'label' => 'في انتظار التحقق (DNS لم يُكتشف بعد)', 'tone' => 'amber'],
                };
            }

            return ['value' => 'selected', 'label' => 'تم اختيار النطاق', 'tone' => 'emerald'];
        }

        if ($isTemplateCheckout) {
            return ['value' => 'auto_subdomain_pending', 'label' => 'سيتم تعيين Subdomain تلقائياً', 'tone' => 'sky'];
        }

        return ['value' => 'pending', 'label' => 'حالة النطاق قيد الانتظار', 'tone' => 'amber'];
    }

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

