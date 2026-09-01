<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\DomainProvider;
use App\Models\Invoice;
use App\Models\Tenancy\Subscription;
use App\Services\Billing\DomainInvoiceItemBuilder;
use App\Services\Domains\DomainAvailabilityService;
use App\Services\Domains\DomainPricingService;
use App\Services\Payments\PaymentSessionStarter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
        // Defense in depth for the public domain cart: reject forged/stale session or
        // request values before registration pricing, availability checks, or writes.
        $rawItems = $request->input('items', session('palgoals_cart_domains', []));
        $items = array_map(function ($it) {
            return [
                'domain'      => isset($it['domain']) ? strtolower(trim($it['domain'])) : null,
                'item_option' => $it['item_option'] ?? $it['option'] ?? null,
                'price_cents' => isset($it['price_cents']) ? (int) $it['price_cents'] : 0,
                'meta'        => $it['meta'] ?? null,
            ];
        }, is_array($rawItems) ? $rawItems : []);

        if (collect($items)->contains(
            fn (array $item) => ($item['item_option'] ?? null) !== 'register'
        )) {
            $message = t(
                'site.Domain_Item_Option_Unsupported',
                'نوع عملية الدومين غير مدعوم في هذا المسار.'
            );

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return response($message, 422);
        }

        foreach ($items as $index => $item) {
            $items[$index]['item_option'] = 'register';
        }

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

        $isDomainOnly = empty($template_id) && empty($plan_id);
        $isNotTemplate = empty($template_id);
        $isNotPlan = empty($plan_id);
        $isTemplateCheckout = !$isNotTemplate;
        $requiresDomainSelection = !$isTemplateCheckout;

        // ADR — لا يُنشأ حساب العميل الضيف ولا يُسجَّل دخوله هنا. سابقًا كان الإنشاء/الدخول
        // يحدثان فوراً قبل أي تحقق من السعر أو التوفر أو نجاح المعاملة، ما يترك حساب Client
        // حقيقي ومسجَّل الدخول بلا أي Order/Invoice إذا فشلت خطوة لاحقة. الآن: نتحقق فقط من
        // صحة بيانات التسجيل (فشل التحقق هنا لا ينشئ أي شيء، كما كان تمامًا)، ونؤجّل إنشاء
        // Client الفعلي إلى داخل نفس DB::transaction التي تُنشئ Order/OrderItems/Invoice/
        // InvoiceItems أدناه، ونؤجّل auth('client')->login() إلى ما بعد نجاح الـ commit فقط.
        $isGuestCheckout = !auth('client')->check();
        $guestClientData = null;

        if ($isGuestCheckout) {
            $guestClientData = $request->validate([
                'first_name' => 'required|string|max:100',
                'last_name'  => 'required|string|max:100',
                'email'      => 'required|email|max:255|unique:clients,email',
                'phone'      => 'required|string|max:30',
                'password'   => 'required|string|min:6|confirmed',
            ]);
        }

        // ADR — إعادة تحقق نهائية موثوقة قبل إنشاء أي سجل: لا نعتمد على price_cents القادم من
        // Request أو Session بأي شكل. كل دومين يُعاد تسعيره الآن من DomainPricingService
        // (domain_tld_prices)؛ فشل أي عنصر أو تغيّر سعره يوقف العملية كاملة قبل أي كتابة لقاعدة البيانات.
        $quoteCurrencies = [];

        if (!empty($items)) {
            $pricing = app(DomainPricingService::class);
            $priceChangedDomains = [];

            foreach ($items as $idx => $it) {
                $domain = $it['domain'] ?? null;

                if (!$domain) {
                    $msg = 'تعذر تحديد سعر أحد عناصر الدومين في السلة.';
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => $msg], 422);
                    }
                    return redirect()->back()->with('error', $msg);
                }

                $quote = $pricing->registrationQuoteForDomain($domain);

                if ($quote === null) {
                    Log::warning('Checkout process: no trusted registration price for domain', ['domain' => $domain]);
                    $msg = 'تعذر تحديد سعر الدومين "' . $domain . '" حالياً. تعذّر إتمام الطلب.';
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => $msg], 422);
                    }
                    return redirect()->back()->with('error', $msg);
                }

                if ((int) ($it['price_cents'] ?? 0) !== $quote['price_cents']) {
                    $priceChangedDomains[] = $domain;
                }

                // اعتماد دائم على السعر الجديد القادم من الخدمة نفسها (وليس القيمة المخزَّنة) في بناء البنود لاحقاً
                $items[$idx]['price_cents'] = $quote['price_cents'];
                $items[$idx]['price']       = $quote['price'];
                $items[$idx]['currency']    = $quote['currency'];
                $items[$idx]['provider_id']   = $quote['provider_id'];
                $items[$idx]['provider_type'] = $quote['provider_type'];
                $items[$idx]['provider_mode'] = $quote['provider_mode'];
                $items[$idx]['domain_tld_id'] = $quote['domain_tld_id'];
                $items[$idx]['meta'] = array_merge(
                    is_array($it['meta'] ?? null) ? $it['meta'] : [],
                    [
                        'provider_id' => $quote['provider_id'],
                        'provider_type' => $quote['provider_type'],
                        'provider_mode' => $quote['provider_mode'],
                        'domain_tld_id' => $quote['domain_tld_id'],
                        'currency' => $quote['currency'],
                        'years' => 1,
                    ]
                );
            }

            $quoteCurrencies = array_values(array_unique(array_map(
                fn (array $item) => strtoupper((string) ($item['currency'] ?? '')),
                $items
            )));

            if (count($quoteCurrencies) > 1) {
                $msg = t('site.Domain_Cart_Mixed_Currencies', 'لا يمكن جمع دومينات بعملات مختلفة في فاتورة واحدة.');
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return redirect()->back()->with('error', $msg);
            }

            if (!empty($priceChangedDomains)) {
                // حدّث الجلسة بالسعر الجديد للمراجعة، ولا تُكمل الطلب دون إعادة تأكيد صريحة من العميل
                session(['palgoals_cart_domains' => $items]);

                Log::info('Checkout process: domain price changed since added to cart', ['domains' => $priceChangedDomains]);

                $msg = 'تغير سعر الدومين منذ إضافته إلى السلة. راجع السعر الجديد ثم أعد تأكيد الطلب.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg, 'price_changed' => true], 409);
                }
                return redirect()->route('checkout.cart')->with('error', $msg);
            }

            // ADR — إعادة تحقق فعلية للتوفر لدى المزوّد قبل إنشاء أي Order/Invoice؛ لا يُعتمَد available
            // القادم من Request/Session أو من نتيجة بحث سابقة. يُطبَّق فقط على عمليات التسجيل الجديد
            // (register/new) — عمليات transfer/renew/subdomain/existing/own لا تخضع لهذا الفحص.
            $availabilityService = app(DomainAvailabilityService::class);
            $registerDomainsByProvider = [];
            foreach ($items as $it) {
                if ($availabilityService->isNewRegistrationOption($it['item_option'] ?? null)) {
                    $registerDomainsByProvider[(int) $it['provider_id']][] = $it['domain'];
                }
            }

            foreach ($registerDomainsByProvider as $providerId => $registerDomains) {
                $provider = DomainProvider::query()->active()->find($providerId);

                if (!$provider instanceof DomainProvider) {
                    Log::error('Checkout process: trusted quote provider is no longer active', [
                        'provider_id' => $providerId,
                        'domains' => $registerDomains,
                    ]);
                    $msg = 'تعذر استخدام مزوّد الدومين المحدد حاليًا. حاول مرة أخرى لاحقًا.';
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => $msg], 503);
                    }
                    return redirect()->back()->with('error', $msg);
                }

                $availMap = $availabilityService->verifyRegistrationAvailabilityBatch($registerDomains, $provider);

                if ($availMap === null) {
                    Log::error('Checkout process: provider availability check failed', ['domains' => $registerDomains]);
                    $msg = 'تعذر التحقق من توفر الدومين حاليًا. حاول مرة أخرى لاحقًا.';
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => $msg], 503);
                    }
                    return redirect()->back()->with('error', $msg);
                }

                foreach ($registerDomains as $d) {
                    if (!($availMap[$d] ?? false)) {
                        $msg = 'الدومين ' . $d . ' لم يعد متاحًا للتسجيل.';
                        if ($request->ajax() || $request->wantsJson()) {
                            return response()->json(['success' => false, 'message' => $msg], 422);
                        }
                        return redirect()->back()->with('error', $msg);
                    }
                }
            }
        }

        // ADR — Idempotency Phase 1 (orders.checkout_fingerprint، بدون جدول محاولات مستقل):
        // تُبنى البصمة هنا فقط — بعد إعادة التسعير الموثوقة وفحص التوفر أعلاه مباشرة، وليس
        // قبلهما — من بيانات $items النهائية الموثوقة (domain/item_option/years/price_cents/
        // currency/provider_id/domain_tld_id، كل القيم المالية منها قادمة حصرًا من
        // DomainPricingService عبر الحلقة أعلاه، وليس من Request/المتصفح بأي شكل). تُحسب فقط
        // لمسار سلة الدومينات (isDomainOnly دائمًا true عبر checkout.cart.process →
        // processCart() → هنا).
        $checkoutFingerprint = $isDomainOnly
            ? $this->buildCheckoutFingerprint($request, $items)
            : null;

        $duplicateResult = null;

        if ($checkoutFingerprint !== null) {
            $duplicateOrder = \App\Models\Order::where('checkout_fingerprint', $checkoutFingerprint)->first();

            if ($duplicateOrder) {
                if (!$this->checkoutFingerprintOrderBelongsToCurrentIdentity($duplicateOrder)) {
                    // نظريًا لا يجب أن يحدث هذا (البصمة مبنية أصلاً من هوية مقدّم الطلب)، لكن
                    // احتياطًا: لا نعرض هذا الـ Order ولا فاتورته ولا نسجّل دخول أي حساب — نتابع
                    // كأن لا تكرار موجود إطلاقًا (المسار الطبيعي أدناه سيُنشئ طلبًا جديدًا).
                    Log::warning('Checkout process: checkout_fingerprint matched an order owned by a different identity', [
                        'fingerprint'       => $checkoutFingerprint,
                        'order_id'          => $duplicateOrder->id,
                        'order_client_id'   => $duplicateOrder->client_id,
                        'current_client_id' => auth('client')->id(),
                    ]);
                } else {
                    $duplicateInvoice = $this->completedCheckoutInvoiceFor($duplicateOrder);

                    if ($duplicateInvoice === null) {
                        // ADR — لا نُخفي المشكلة: سجل بنفس البصمة موجود لكنه غير مكتمل (بلا فاتورة،
                        // أو بلا OrderItems، أو بلا InvoiceItems) — لا يمثّل عملية ناجحة مكتملة، فلا
                        // نعتبره تكرارًا آمنًا، ولا نُنشئ طلبًا جديدًا (سيتصادم مع نفس القيد الفريد
                        // على أي حال) — نوقف الطلب بخطأ صريح بدل التظاهر بالنجاح.
                        Log::error('Checkout process: checkout_fingerprint matched an incomplete order', [
                            'fingerprint' => $checkoutFingerprint,
                            'order_id'    => $duplicateOrder->id,
                        ]);

                        $msg = 'تعذر إتمام الطلب. يرجى التواصل مع الدعم الفني.';
                        if ($request->ajax() || $request->wantsJson()) {
                            return response()->json(['success' => false, 'message' => $msg], 500);
                        }
                        return redirect()->back()->with('error', $msg);
                    }

                    // نفس الجلسة/العميل حاول هذا الطلب من قبل ونجح فعليًا واكتمل بالكامل — لا
                    // تُنشئ أي شيء جديد، فقط سجّل دخول صاحب الطلب لو لم يكن مسجَّلاً بالفعل،
                    // وأعد نفس نتيجة النجاح دون بدء DB::transaction إطلاقًا.
                    if ($duplicateOrder->client_id && !auth('client')->check()) {
                        auth('client')->login($duplicateOrder->client);
                    }

                    $duplicateResult = [
                        'order_id'         => $duplicateOrder->id,
                        'invoice_id'       => $duplicateInvoice->id,
                        'subscription_ids' => [],
                        'client'           => $duplicateOrder->client,
                        'subtotal_cents'   => (int) $duplicateInvoice->subtotal_cents,
                    ];
                }
            }
        }

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
            $createdSubscriptionIds = [];

            // ADR — Idempotency Phase 1: لو اكتُشف طلب مكرر أعلاه (نفس checkout_fingerprint)،
            // لا تُنفَّذ أي DB::transaction جديدة إطلاقًا — أعد استخدام نفس نتيجة النجاح السابقة.
            $result = $duplicateResult ?? DB::transaction(function () use (
                &$createdSubscriptionIds,
                $isGuestCheckout,
                $guestClientData,
                $isDomainOnly,
                $isNotTemplate,
                $isNotPlan,
                $items,
                $quoteCurrencies,
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
                $checkoutFingerprint,
                $request
            ) {
                // ADR — إنشاء Client الضيف يحدث هنا فقط، داخل نفس المعاملة التي تُنشئ
                // Order/Invoice — لا خارجها. لو فشلت أي خطوة لاحقة (order_items/InvoiceItems/
                // التحققات)، يتراجع إنشاء هذا الـ Client أيضًا تلقائيًا (rollback كامل)، فلا يبقى
                // حساب عميل حقيقي بلا Order. لا تسجيل دخول هنا إطلاقًا — يحدث فقط بعد نجاح الـ
                // commit بالكامل خارج هذه الدالة.
                if ($isGuestCheckout) {
                    $client = new \App\Models\Client();
                    $client->first_name   = $guestClientData['first_name'];
                    $client->last_name    = $guestClientData['last_name'];
                    $client->email        = $guestClientData['email'];
                    $client->phone        = $guestClientData['phone'];
                    $client->company_name = $request->company_name ?? '-';
                    $client->can_login    = true;
                    $client->password     = bcrypt($guestClientData['password']);
                    $client->save();
                } else {
                    // مستخدم مسجَّل مسبقًا: استخدم هويته الحالية دون إنشاء أي حساب جديد.
                    $client = auth('client')->user();
                }

                // 1) إنشاء الطلب
                // ADR — Idempotency Phase 1: تمرير checkout_fingerprint هنا هو ما يجعل القيد
                // الفريد على orders.checkout_fingerprint يمنع تصادميًا أي إنشاء مزدوج فعلي في
                // قاعدة البيانات، حتى لو نجح فحصان متزامنان في تجاوز الفحص المسبق أعلاه معًا.
                $order = \App\Models\Order::create([
                    'client_id' => $client?->id,
                    'status'    => 'pending',
                    'type'      => $isDomainOnly ? 'domains' : 'subscription',
                    'notes'     => $isDomainOnly ? 'حجز دومينات من سلة الدومينات' : 'طلب عبر صفحة checkout',
                    'checkout_fingerprint' => $checkoutFingerprint,
                ]);

                // 2) إنشاء بنود الطلب
                if ($isDomainOnly) {
                    if (empty($items)) {
                        abort(422, 'السلة فارغة.');
                    }
                    $payload = array_map(function ($it) {
                        return [
                            'domain'      => $it['domain'],
                            'item_option' => 'register',
                            'price_cents' => (int) ($it['price_cents'] ?? 0),
                            'meta'        => $it['meta'],
                        ];
                    }, $items);
                    $createdDomainOrderItems = $order->items()->createMany($payload);
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
                    $invoiceItemBuilder = app(DomainInvoiceItemBuilder::class);

                    // ADR — إغلاق المرحلة الأولى: مصدر الحقيقة المحاسبي النهائي هو بنود
                    // الفاتورة نفسها، وليس مصفوفة السلة/الـ Request. نبني خصائص InvoiceItems
                    // أولاً (بلا كتابة لقاعدة البيانات) من order_items الفعلية التي أُنشئت
                    // أعلاه، ثم نشتق subtotal_cents من مجموعها مباشرة.
                    $invoiceItemAttributes = $invoiceItemBuilder->buildAttributesForItems($createdDomainOrderItems);

                    if ($invoiceItemAttributes->isEmpty()) {
                        throw new \RuntimeException('تعذّر تجهيز بنود الفاتورة: لا توجد عناصر دومين صالحة لإنشاء الفاتورة.');
                    }

                    if ($invoiceItemAttributes->count() !== $createdDomainOrderItems->count()) {
                        throw new \RuntimeException('عدد بنود الفاتورة المُجهَّزة لا يطابق عدد عناصر الطلب (order_items).');
                    }

                    $subtotalCents = (int) $invoiceItemAttributes->sum('total_cents');

                    // ADR-008 Phase 3 — coupon discount (server-side, re-validated above)
                    $couponDiscount = ($coupon && $coupon->isUsableForSubtotal($subtotalCents))
                        ? $coupon->computeDiscountCents($subtotalCents)
                        : 0;

                    $taxCents   = 0;
                    $totalCents = max(0, $subtotalCents - $couponDiscount + $taxCents);

                    $invoice = \App\Models\Invoice::create([
                        'client_id'      => $order->client_id,
                        'number'         => 'INV-' . $order->order_number,
                        'status'         => 'draft',
                        'subtotal_cents' => $subtotalCents,
                        'discount_cents' => $couponDiscount,
                        'tax_cents'      => $taxCents,
                        'total_cents'    => $totalCents,
                        'currency'       => $quoteCurrencies[0],
                        'due_date'       => now()->addDays(3),
                        'order_id'       => $order->id,
                        'coupon_id'      => $coupon?->id,
                    ]);

                    $createdInvoiceItems = $invoiceItemBuilder->persistItems($invoice, $invoiceItemAttributes);

                    // تحقق صريح بعد الإنشاء — لا اعتماد على افتراض تطابق المصادر:
                    if ($createdInvoiceItems->count() !== $createdDomainOrderItems->count()) {
                        throw new \RuntimeException('عدد بنود الفاتورة المُنشأة فعليًا لا يطابق عدد عناصر الطلب.');
                    }

                    $persistedItemsTotal = (int) $createdInvoiceItems->sum('total_cents');
                    if ($persistedItemsTotal !== (int) $invoice->subtotal_cents) {
                        throw new \RuntimeException('مجموع بنود الفاتورة المُنشأة لا يطابق subtotal_cents الخاص بالفاتورة.');
                    }

                    $expectedTotalCents = max(0, (int) $invoice->subtotal_cents - (int) $invoice->discount_cents + (int) $invoice->tax_cents);
                    if ((int) $invoice->total_cents !== $expectedTotalCents) {
                        throw new \RuntimeException('total_cents الخاص بالفاتورة لا يطابق subtotal - discount + tax وفق السياسة الحالية.');
                    }
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
                    'order_id'       => $order->id,
                    'invoice_id'     => $invoice->id,
                    'subscription_ids' => $createdSubscriptionIds,
                    'client'         => $client,
                    'subtotal_cents' => (int) ($invoice->subtotal_cents ?? 0),
                ];
            });

            $order = \App\Models\Order::findOrFail($result['order_id']);
            $invoice = Invoice::query()->findOrFail($result['invoice_id']);
            $subscriptionIds = array_values(array_filter($result['subscription_ids'] ?? []));

            // ADR — Idempotency Phase 1: اعرض المجموع الفعلي المخزَّن في الفاتورة (سواء أُنشئت
            // للتو أو أُعيد استخدامها كتكرار) بدل اشتقاقه من $items التي قد تكون غير مُعاد
            // تسعيرها في مسار التكرار (تُخطَّى إعادة التسعير عمدًا في تلك الحالة أعلاه).
            if ($isDomainOnly) {
                $domainsTotalCents = (int) ($result['subtotal_cents'] ?? $domainsTotalCents);
            }

            // ADR — تسجيل الدخول يحدث هنا فقط، بعد نجاح الـ commit بالكامل (Order/OrderItems/
            // Invoice/InvoiceItems جميعها موجودة فعليًا في قاعدة البيانات الآن). لو وصلنا لهذا
            // السطر فالمعاملة نجحت قطعًا؛ لا يوجد أي احتمال لبقاء Client مسجَّل الدخول بلا Order.
            if ($isGuestCheckout) {
                auth('client')->login($result['client']);
            }

            $paymentSession = null;
            if (!$isDomainOnly) {
                $paymentSession = app(PaymentSessionStarter::class)->start(
                    $invoice,
                    (int) $invoice->client_id,
                    route('client.invoices.checkout', ['invoice' => $invoice, 'state' => 'return']),
                    route('client.invoices.checkout', ['invoice' => $invoice, 'state' => 'cancel']),
                );

                if (!in_array($paymentSession['status'], ['ready', 'paid'], true)) {
                    $errorPayload = [
                        'success' => false,
                        'payment_session_status' => $paymentSession['status'],
                        'message' => $paymentSession['message'],
                    ];

                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json($errorPayload, $paymentSession['http_status']);
                    }

                    return redirect()->back()->with('error', $paymentSession['message']);
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
                'checkout_url' => $paymentSession['checkout_url'] ?? null,
                'payment_session_status' => $paymentSession['status'] ?? null,
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

            if (is_string($paymentSession['checkout_url'] ?? null)) {
                return redirect()->away($paymentSession['checkout_url']);
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
            // ADR — Idempotency Phase 1: تعارض متزامن (Race) على القيد الفريد
            // orders_checkout_fingerprint_unique — يعني أن طلبًا متزامنًا آخر فاز بإنشاء نفس
            // Order للتو (بين لحظة الفحص المسبق أعلاه وبدء هذه المعاملة). هذه المعاملة تراجعت
            // بالكامل (rollback) تلقائيًا مثل أي استثناء آخر يصل هنا. نتحقق من الملكية واكتمال
            // الطلب الفائز قبل اعتباره نجاحًا — أي استثناء آخر (أو طلب فائز غير مملوك/غير مكتمل)
            // يسقط إلى خطأ 500 العام أدناه دون تحويله إلى نجاح.
            if ($checkoutFingerprint !== null && $this->isCheckoutFingerprintCollision($e)) {
                $raceOrder = \App\Models\Order::where('checkout_fingerprint', $checkoutFingerprint)->first();

                if ($raceOrder && $this->checkoutFingerprintOrderBelongsToCurrentIdentity($raceOrder)) {
                    $raceInvoice = $this->completedCheckoutInvoiceFor($raceOrder);

                    if ($raceInvoice !== null) {
                        Log::info('CheckoutController::process: concurrent duplicate checkout fingerprint recovered', [
                            'fingerprint' => $checkoutFingerprint,
                            'order_id'    => $raceOrder->id,
                        ]);

                        $msg = t('site.Checkout_Already_Processed', 'تم استلام طلبك بالفعل، لا داعٍ لإعادة الإرسال.');

                        if ($request->ajax() || $request->wantsJson()) {
                            return response()->json([
                                'success'  => true,
                                'order_no' => $raceOrder->order_number,
                                'order_id' => $raceOrder->id,
                                'message'  => $msg,
                            ]);
                        }

                        return redirect()->route('checkout.cart', [
                            'success'       => 1,
                            'order_no'      => $raceOrder->order_number,
                            'checkout_mode' => 'hosting',
                        ]);
                    }
                }
            }

            Log::error('CheckoutController::process failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'تعذر إتمام عملية الدفع الآن.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * ADR — Idempotency Phase 1: بصمة حتمية لمحاولة Checkout واحدة (سلة الدومينات فقط،
     * checkout.cart.process → processCart() → process()). لا تُستخدم في أي مسار آخر.
     *
     * يجب استدعاؤها فقط بعد إعادة التسعير الموثوقة عبر DomainPricingService وبعد فحص التوفر
     * (أي بعد أن يحمل كل عنصر في $items بالفعل price_cents/price/currency/provider_id/
     * domain_tld_id الموثوقة من الخدمة — وليس القيم القادمة من Request/الجلسة/المتصفح).
     *
     * لكل عنصر تدخل في البصمة: domain (بعد trim/lowercase، مطبَّع مسبقًا)، item_option،
     * years (من meta إن كانت قيمة صحيحة موجبة، وإلا 1 — بنفس تحويل max(1,(int)($meta['years']
     * ?? 1)) المعتمد في DomainInvoiceItemBuilder)، price_cents، currency، provider_id،
     * domain_tld_id. هوية مقدّم الطلب = معرّف العميل المسجَّل أو معرّف جلسة الضيف الحالية.
     *
     * العناصر تُرتَّب ترتيبًا حتميًا حسب: domain ثم item_option ثم years ثم provider_id ثم
     * domain_tld_id، ثم تُحوَّل إلى JSON ثابت وتُمرَّر إلى SHA-256.
     *
     * تُعيد null إن كانت $items فارغة، أو إن غاب provider_id/domain_tld_id الموثوقَين عن أي
     * عنصر (لا يجب أن يحدث عمليًا لأن كل عنصر وصل إلى هنا يكون قد نجح في إعادة التسعير أعلاه؛
     * هذا مجرد ضمان دفاعي كي لا تُبنى بصمة على بيانات ناقصة).
     *
     * @param  array<int, array{domain: ?string, item_option: ?string, price_cents: int, price: float, currency: string, provider_id: int, domain_tld_id: int, meta: mixed}>  $items
     */
    protected function buildCheckoutFingerprint(Request $request, array $items): ?string
    {
        if (empty($items)) {
            return null;
        }

        $identity = auth('client')->check()
            ? 'client:' . auth('client')->id()
            : 'session:' . $request->session()->getId();

        $normalizedItems = array_map(function ($it) {
            $meta = is_array($it['meta'] ?? null) ? $it['meta'] : [];
            $years = max(1, (int) ($meta['years'] ?? 1));

            return [
                'domain'        => $it['domain'] ?? null,
                'item_option'   => $it['item_option'] ?? null,
                'years'         => $years,
                'price_cents'   => (int) ($it['price_cents'] ?? 0),
                'currency'      => $it['currency'] ?? null,
                'provider_id'   => (int) ($it['provider_id'] ?? 0),
                'domain_tld_id' => (int) ($it['domain_tld_id'] ?? 0),
            ];
        }, $items);

        foreach ($normalizedItems as $it) {
            if (empty($it['domain']) || $it['provider_id'] <= 0 || $it['domain_tld_id'] <= 0) {
                return null;
            }
        }

        usort($normalizedItems, function ($a, $b) {
            return [$a['domain'], (string) $a['item_option'], $a['years'], $a['provider_id'], $a['domain_tld_id']]
                <=> [$b['domain'], (string) $b['item_option'], $b['years'], $b['provider_id'], $b['domain_tld_id']];
        });

        return hash('sha256', $identity . '|' . json_encode($normalizedItems, JSON_UNESCAPED_UNICODE));
    }

    /**
     * هل هذا الاستثناء تصادم فعلي على القيد الفريد orders_checkout_fingerprint_unique تحديدًا؟
     * (وليس أي تصادم 23000 آخر، مثل تصادم نادر جدًا على order_number.)
     */
    protected function isCheckoutFingerprintCollision(\Throwable $e): bool
    {
        if (!($e instanceof \Illuminate\Database\QueryException)) {
            return false;
        }

        return str_contains($e->getMessage(), '23000')
            && str_contains($e->getMessage(), 'checkout_fingerprint');
    }

    /**
     * تحقق الملكية قبل اعتماد أي Order موجود بنفس checkout_fingerprint: للمستخدم المسجَّل لا
     * يُعتمَد الطلب إلا لو كان client_id مطابقًا فعليًا لهويته الحالية. للضيف، تطابق البصمة
     * نفسه كافٍ — البصمة مبنية أصلاً من session:{id} الحالية لغير المسجَّلين وقت البحث عنها،
     * فأي تطابق يعني بالضرورة نفس الجلسة الحالية.
     */
    protected function checkoutFingerprintOrderBelongsToCurrentIdentity(\App\Models\Order $order): bool
    {
        if (auth('client')->check()) {
            return $order->client_id === auth('client')->id();
        }

        return true;
    }

    /**
     * يتحقق أن Order يمثّل عملية Checkout ناجحة ومكتملة فعليًا (وليس سجلًا جزئيًا): لديه
     * Invoice، ولديه OrderItems، ولدى تلك الفاتورة InvoiceItems. يعيد الفاتورة عند الاكتمال
     * الكامل، أو null إن كان أي جزء ناقصًا — لا نعتبر سجلًا ناقصًا نتيجة idempotent ناجحة.
     */
    protected function completedCheckoutInvoiceFor(\App\Models\Order $order): ?Invoice
    {
        $invoice = $order->invoices()->latest()->first();

        if (!$invoice) {
            return null;
        }

        if ($order->items()->count() === 0) {
            return null;
        }

        if ($invoice->items()->count() === 0) {
            return null;
        }

        return $invoice;
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
        $unsupportedActionMessage = t(
            'site.Domain_Item_Option_Unsupported',
            'نوع عملية الدومين غير مدعوم في هذا المسار.'
        );

        $data = $request->validate([
            'items'               => 'required|array|min:1',
            'items.*.domain'      => 'required|string',
            'items.*.option'      => ['required', 'string', Rule::in(['register'])],
            'items.*.price_cents' => 'nullable|integer|min:0',
        ], [
            'items.*.option.required' => $unsupportedActionMessage,
            'items.*.option.string'   => $unsupportedActionMessage,
            'items.*.option.in'       => $unsupportedActionMessage,
        ]);

        $items = array_map(fn (array $item) => [
            'domain'      => $item['domain'],
            'item_option' => 'register',
            'price_cents' => $item['price_cents'] ?? null,
        ], $data['items']);

        session(['palgoals_cart_domains' => $items]);

        // مرّر العناصر إلى process() مع template_id = null
        return $this->process($request->merge(['items' => $items]), null);
    }
}
