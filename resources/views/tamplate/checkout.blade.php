@php
    use Carbon\Carbon;
    // safe access: template may be null when rendering cart-based checkout
    $shortDesc = Str::limit(strip_tags($translation?->description ?? ''), 160);
    $basePrice = (float) ($template?->price ?? 0);
    $discRaw = $template?->discount_price ?? null;
    $discPrice = is_null($discRaw) ? null : (float) $discRaw;
    $hasDiscount = !is_null($discPrice) && $discPrice > 0 && $discPrice < $basePrice;
    $endsAt = $hasDiscount && !empty($template?->discount_ends_at) ? Carbon::parse($template->discount_ends_at) : null;
    $discountExpired = false;
    if ($hasDiscount && $endsAt) {
        $discountExpired = $endsAt->isPast();
    }
    $showDiscount = $hasDiscount && !$discountExpired;
    $finalPrice = $showDiscount ? $discPrice : $basePrice;
    $discountPerc = $showDiscount && $basePrice > 0 ? (int) round((($basePrice - $discPrice) / $basePrice) * 100) : 0;

    // safe access: plan may be null when rendering cart-based checkout
    $basePricePlan =
        (float) ($plan_sub_type === 'monthly' ? $plan?->monthly_price_cents ?? 0 : $plan?->annual_price_cents ?? 0);
@endphp
<x-template.layouts.index-layouts
    title="{{ t('Frontend.Checkout', 'Checkout') }} - {{ t('Frontend.Palgoals', 'Palgoals') }}"
    description="{{ $shortDesc }}" keywords="خدمات حجز دومين , افضل شركة برمجيات , استضافة مواقع , ..."
    ogImage="{{ asset('assets/dashboard/images/logo-white.svg') }}">

    <!-- ===== شريط الخطوات (خطوتان) ===== -->
    <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-4 mt-6">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow p-4">
            <div id="globalStepper" class="flex items-center justify-between gap-2">
                <!-- Step 1 -->
                <div class="flex items-center gap-3 step" data-index="0">
                    <div
                        class="h-9 w-9 rounded-full grid place-items-center border-2 border-[#240B36] text-[#240B36] font-extrabold step-circle">
                        1</div>
                    <div class="text-sm">حجز الدومين</div>
                </div>
                <div class="relative h-0.5 flex-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div id="stepperProgress"
                        class="absolute inset-y-0 right-0 bg-[#240B36] transition-all duration-300" style="width:0%">
                    </div>
                </div>
                <!-- Step 2 -->
                <div class="flex items-center gap-3 step" data-index="1">
                    <div
                        class="h-9 w-9 rounded-full grid place-items-center border-2 border-gray-200 dark:border-gray-700 text-gray-500 font-extrabold step-circle">
                        2</div>
                    <div class="text-sm">المراجعة والدفع</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== الصفحة 1: الدومين ===== -->
    <main id="view-domain" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-10 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- العمود الرئيسي -->
            <div
                class="lg:col-span-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow p-6">
                <h1 class="text-2xl font-extrabold mb-1">احجز اسم النطاق</h1>
                <p class="text-gray-600 dark:text-gray-300 mb-6">ابدأ باختيار طريقة ربط اسم النطاق بموقعك الجديد.</p>

                <!-- Tabs -->
                <div role="tablist" aria-label="طرق الدومين" class="flex gap-2 mb-6">
                    <button data-tab="register" role="tab" aria-controls="tab-register" aria-selected="true"
                        class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-900 hover:bg-primary/80 hover:text-white dark:hover:bg-gray-800 hover:border-[#240B36]/40 transition-colors aria-selected:bg-primary/80 aria-selected:text-white aria-selected:border-[#240B36]/40 aria-selected:ring-2 aria-selected:ring-[#240B36]/30 aria-selected:shadow-sm">
                        تسجيل جديد
                    </button>
                    <button data-tab="transfer" role="tab" aria-controls="tab-transfer" aria-selected="false"
                        class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-900 hover:bg-primary/80 hover:text-white dark:hover:bg-gray-800 hover:border-[#240B36]/40 transition-colors aria-selected:bg-primary/80 aria-selected:text-white aria-selected:border-[#240B36]/40 aria-selected:ring-2 aria-selected:ring-[#240B36]/30 aria-selected:shadow-sm">
                        نقل نطاق
                    </button>
                    <button data-tab="owndomain" role="tab" aria-controls="tab-owndomain" aria-selected="false"
                        class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-900 hover:bg-primary/80 hover:text-white dark:hover:bg-gray-800 hover:border-[#240B36]/40 transition-colors aria-selected:bg-primary/80 aria-selected:text-white aria-selected:border-[#240B36]/40 aria-selected:ring-2 aria-selected:ring-[#240B36]/30 aria-selected:shadow-sm">
                        أمتلك نطاقاً
                    </button>
                    <button data-tab="subdomain" role="tab" aria-controls="tab-subdomain" aria-selected="false"
                        class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-900 hover:bg-primary/80 hover:text-white dark:hover:bg-gray-800 hover:border-[#240B36]/40 transition-colors aria-selected:bg-primary/80 aria-selected:text-white aria-selected:border-[#240B36]/40 aria-selected:ring-2 aria-selected:ring-[#240B36]/30 aria-selected:shadow-sm">
                        Subdomain مجاني
                    </button>
                </div>

                <!-- Register -->
                <form id="tab-register" class="space-y-4" role="tabpanel" method="POST"
                    action="@if (empty($template_id) && empty($plan_id)) {{ route('checkout.cart.process') }}@elseif(!empty($template_id)){{ route('checkout.process', $template_id) }}@else{{ route('checkout.process',['template_id'=>null,'plan_id'=>$plan_id]) }} @endif">
                    @csrf
                    <div class="flex gap-2">
                        <input aria-label="اسم النطاق" placeholder="example"
                            class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20" />
                        <select aria-label="الامتداد"
                            class="w-40 rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-3 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20">
                            <option>.com</option>
                            <option>.net</option>
                            <option>.org</option>
                        </select>
                        <button type="button" id="btnCheck"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2 font-semibold border border-gray-300 dark:border-gray-700 text-gray-800 dark:text-gray-100 bg-white dark:bg-gray-900 hover:bg-gray-50 active:scale-95 transition">
                            تحقق
                        </button>
                    </div>
                    <div id="checkResult" class="min-h-6 text-sm"></div>
                    <div class="flex items-center justify-between pt-2">
                        <div class="text-xs text-gray-500">سعر التسجيل السنوي: <span id="tldPrice"
                                class="font-semibold">—</span>
                        </div>
                        <button type="button" id="goConfigR"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2 font-semibold text-white bg-[#240B36] hover:opacity-95 active:scale-95 transition shadow-sm">
                            متابعة
                        </button>
                    </div>
                    <p id="hintR" class="mt-2 text-xs text-amber-600 hidden">يرجى إدخال اسم النطاق أولاً قبل
                        المتابعة.</p>
                </form>

                <!-- Transfer -->
                <form id="tab-transfer" class="space-y-4 hidden" role="tabpanel" method="POST"
                    action="@if (empty($template_id) && empty($plan_id)) {{ route('checkout.cart.process') }}@elseif(!empty($template_id)){{ route('checkout.process', $template_id) }}@else{{ route('checkout.process',['template_id'=>null,'plan_id'=>$plan_id]) }} @endif">
                    @csrf
                    <div class="flex gap-2">
                        <input aria-label="اسم النطاق" placeholder="example.com"
                            class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20" />
                        <input aria-label="رمز النقل" placeholder="Auth Code"
                            class="w-48 rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20" />
                    </div>
                    <div class="flex items-center justify-end pt-2">
                        <button type="button" id="goConfigT"
                            class="rounded-xl px-4 py-2 font-semibold text-white bg-[#240B36] hover:opacity-95 active:scale-95 transition shadow-sm">متابعة</button>
                    </div>
                    <p id="hintT" class="mt-2 text-xs text-amber-600 hidden">يرجى إدخال اسم النطاق المراد نقله قبل
                        المتابعة.</p>
                </form>

                <!-- Own Domain -->
                <form id="tab-owndomain" class="space-y-4 hidden" role="tabpanel" method="POST"
                    action="@if (empty($template_id) && empty($plan_id)) {{ route('checkout.cart.process') }}@elseif(!empty($template_id)){{ route('checkout.process', $template_id) }}@else{{ route('checkout.process',['template_id'=>null,'plan_id'=>$plan_id]) }} @endif">
                    @csrf
                    <div class="flex gap-2">
                        <input aria-label="اسم النطاق" placeholder="example.com"
                            class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20" />
                    </div>
                    <p class="text-xs text-gray-500">سنوفر لك سجلات DNS لتوجيه نطاقك إلى خوادمنا بعد الدفع.</p>
                    <div class="flex items-center justify-end pt-2">
                        <button type="button" id="goConfigO"
                            class="rounded-xl px-4 py-2 font-semibold text-white bg-[#240B36] hover:opacity-95 active:scale-95 transition shadow-sm">متابعة</button>
                    </div>
                    <p id="hintO" class="mt-2 text-xs text-amber-600 hidden">يرجى إدخال اسم النطاق الذي تملكه قبل
                        المتابعة.</p>
                </form>

                <!-- Subdomain (مجاني) -->
                <form id="tab-subdomain" class="space-y-4 hidden" role="tabpanel" method="POST"
                    action="@if (empty($template_id) && empty($plan_id)) {{ route('checkout.cart.process') }}@elseif(!empty($template_id)){{ route('checkout.process', $template_id) }}@else{{ route('checkout.process',['template_id'=>null,'plan_id'=>$plan_id]) }} @endif">
                    @csrf
                    <div class="flex gap-2 items-stretch">
                        <input aria-label="اسم الساب-دومين" placeholder="myshop"
                            class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20" />
                        <div class="flex items-center text-gray-500 px-2">.</div>
                        <select aria-label="الدومين الأساسي"
                            class="w-56 rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-3 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20">
                            <option>palgoals.com</option>
                            <option>palgoals.store</option>
                            <option>palgoals.site</option>
                            <option>wpgoals.com</option>
                        </select>
                    </div>
                    <p class="text-xs text-gray-500">سنوفر لك Subdomain مجاني لبدء مشروعك بسرعة (يمكن الترقيه لاحقاً
                        لدومين
                        مستقل).</p>
                    <div class="flex items-center justify-between pt-2">
                        <div class="text-xs text-gray-500">التكلفة: <span class="font-semibold">$0.00</span></div>
                        <button type="button" id="goConfigS"
                            class="rounded-xl px-4 py-2 font-semibold text-white bg-[#240B36] hover:opacity-95 active:scale-95 transition shadow-sm">متابعة</button>
                    </div>
                    <p id="hintS" class="mt-2 text-xs text-amber-600 hidden">يرجى إدخال اسم الساب-دومين قبل
                        المتابعة.</p>
                </form>
            </div>

            <!-- ملخص جانبي -->
            <aside
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow p-6 h-max">
                <h3 class="font-bold mb-3">ملخص سريع</h3>
                <ul class="space-y-2 text-sm">
                    @if ($template)
                        <li class="flex justify-between rv-template-info"><span>القالب</span><span
                                class="font-semibold">{{ $translation && $translation->name ? $translation->name : ($template && $template->name ? $template->name : '—') }}</span>
                        </li>
                        <li class="flex justify-between rv-template-info"><span>مدة الاشتراك</span><span
                                class="font-semibold">12
                                شهر</span></li>
                        <li class="flex justify-between rv-template-info"><span>سعر القالب</span><span
                                class="font-semibold">
                                @if ($showDiscount)
                                    <span class="line-through text-gray-400">${{ number_format($basePrice, 2) }}</span>
                                    <span
                                        class="text-red-600 font-bold ms-2">${{ number_format($discPrice, 2) }}</span>
                                @else
                                    ${{ number_format($basePrice, 2) }}
                                @endif
                            </span></li>
                    @endif
                    @if ($plan)
                        <li class="flex justify-between rv-template-info"><span>الخطة</span><span
                                class="font-semibold">{{ $plan && $plan->name ? $plan->name : '—' }}</span>
                        </li>
                        <li class="flex justify-between rv-template-info"><span>مدة الاشتراك</span><span
                                class="font-semibold">{{ $plan_sub_type === 'monthly' ? 'شهري' : 'سنوي' }}</span></li>
                        <li class="flex justify-between rv-template-info"><span>سعر الخطة</span><span
                                class="font-semibold">
                                ${{ number_format($basePricePlan, 2) }}
                            </span></li>
                    @endif
                    <li class="flex justify-between"><span>الدومين</span><span id="summaryDomain"
                            class="font-semibold">—</span>
                    </li>
                </ul>
                <hr class="my-4 border-gray-200 dark:border-gray-800" />
                <div class="flex justify-between font-bold"><span>الإجمالي التقديري</span><span id="summaryTotal">
                        @if ($template)
                            @if ($showDiscount)
                                <span class="line-through text-gray-400">${{ number_format($basePrice, 2) }}</span>
                                <span class="text-red-600 font-bold ms-2">${{ number_format($discPrice, 2) }}</span>
                            @else
                                ${{ number_format($basePrice, 2) }}
                            @endif
                        @else
                            $0.00
                        @endif
                        @if ($plan)
                            ${{ number_format($basePricePlan, 2) }}
                        @else
                            $0.00
                        @endif

                    </span></div>
            </aside>
        </div>
    </main>

    <!-- ===== الصفحة 2: المراجعة والدفع ===== -->
    <section id="view-review" class="hidden max-w-6xl mx-auto px-4 sm:px-6 lg:px-10 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div
                class="lg:col-span-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow p-6">
                <h2 class="text-2xl font-extrabold mb-1">المراجعة والدفع</h2>
                <p class="text-gray-600 dark:text-gray-300 mb-6">راجع التفاصيل واكمل إنشاء الحساب/الدخول ثم اختر طريقة
                    الدفع.
                </p>

                <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800 mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-gray-500">يمكنك إزالة عنصر أو إفراغ السلة قبل الدفع.</div>
                        <button type="button" id="btnClearCart"
                            class="px-3 py-1.5 rounded-lg text-sm font-semibold border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800">إفراغ
                            السلة</button>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                            <tr>
                                <th class="text-right p-3">البند</th>
                                <th class="text-right p-3">المدة</th>
                                <th class="text-right p-3">السعر</th>
                                <th class="text-right p-3">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody id="reviewDomainsBody">
                            <!-- يُملأ ديناميكياً من العناصر المتعددة -->
                            <tr class="border-t border-gray-200 dark:border-gray-800 hidden rv-domain-row"
                                id="reviewDomainProto">
                                <td class="p-3">تسجيل نطاق <span class="rv-domain">—</span></td>
                                <td class="p-3">12 شهر</td>
                                <td class="p-3 rv-price">0</td>
                                <td class="p-3">
                                    <button type="button"
                                        class="rv-remove px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-100 text-red-700 hover:bg-red-200"
                                        data-domain="">حذف</button>
                                </td>
                            </tr>
                            @if ($template)
                                <tr class="border-t border-gray-200 dark:border-gray-800 rv-template-row">
                                    <td class="p-3">القالب: <span
                                            class="font-semibold">{{ $translation && $translation->name ? $translation->name : ($template && $template->name ? $template->name : '—') }}</span>
                                    </td>
                                    <td class="p-3">12 شهر</td>
                                    <td class="p-3">
                                        @if ($showDiscount)
                                            <span
                                                class="line-through text-gray-400">${{ number_format($basePrice, 2) }}</span>
                                            <span
                                                class="text-red-600 font-bold ms-2">${{ number_format($discPrice, 2) }}</span>
                                        @else
                                            ${{ number_format($basePrice, 2) }}
                                        @endif
                                    </td>
                                    <td class="p-3">
                                        <button type="button" id="btnRemoveTemplate"
                                            class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-100 text-red-700 hover:bg-red-200">حذف
                                            القالب</button>
                                    </td>
                                </tr>
                            @endif
                            @if ($plan)
                                <tr class="border-t border-gray-200 dark:border-gray-800 rv-template-row">
                                    <td class="p-3">الخطة: <span
                                            class="font-semibold">{{ $plan_translation && $plan_translation->name ? $plan_translation->name : ($plan && $plan->name ? $plan->name : '—') }}</span>
                                    </td>
                                    <td class="p-3">
                                        {{ $plan_sub_type === 'monthly' ? 'شهري' : 'سنوي' }}
                                    </td>
                                    <td class="p-3">
                                        ${{ number_format($basePricePlan, 2) }}
                                    </td>
                                    <td class="p-3">
                                        <button type="button" id="btnRemovePlan"
                                            class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-100 text-red-700 hover:bg-red-200">حذف
                                            الخطة</button>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                @if (!$template && !$plan)
                    <div
                        class="rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700 p-4 mb-6">
                        <div class="font-bold text-amber-800 dark:text-amber-200 mb-1">تحجز دومين فقط؟</div>
                        <p class="text-sm text-amber-700 dark:text-amber-300">يمكنك إتمام الحجز الآن أو اختيار قالب
                            لبدء موقعك بسرعة. او خطة</p>
                        <div class="mt-3 flex gap-2">
                            <a id="chooseTemplateLink" href="/templates"
                                class="px-4 py-2 rounded-xl text-sm font-semibold bg-[#240B36] text-white hover:opacity-95">اختيار
                                قالب</a>
                            <button type="button"
                                class="px-4 py-2 rounded-xl text-sm font-semibold border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900">إكمال
                                بدون قالب</button>
                            <button type="button" id="btnChoosePlan"
                                class="px-4 py-2 rounded-xl text-sm font-semibold border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900">إكمال
                                بدون خطة</button>
                        </div>
                    </div>
                @endif

                <!-- يظهر بعد حذف القالب أثناء الجلسة الحالية -->
                <div id="chooseTemplateAfterRemove"
                    class="hidden rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700 p-4 mb-6">
                    <div class="font-bold text-amber-800 dark:text-amber-200 mb-1">تم حذف القالب والخطة.</div>
                    <p class="text-sm text-amber-700 dark:text-amber-300">هل تريد اختيار قالب او خطة للموقع قبل الدفع؟</p>
                    <div class="mt-3 flex gap-2">
                        <a id="chooseTemplateLink2" href="/templates"
                            class="px-4 py-2 rounded-xl text-sm font-semibold bg-[#240B36] text-white hover:opacity-95">اختيار
                            قالب</a>
                        <a id="chooseTemplateLink2" href="/plans"
                            class="px-4 py-2 rounded-xl text-sm font-semibold bg-[#240B36] text-white hover:opacity-95">اختيار
                            خطة</a>
                        <button type="button"
                            class="px-4 py-2 rounded-xl text-sm font-semibold border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900">إكمال
                            بدون قالب</button>
                    </div>
                </div>

                @if (!auth('client')->check())
                    <!-- تبديل الدخول/التسجيل (Tabs) -->
                    <div role="tablist" aria-label="حساب العميل"
                        class="inline-flex rounded-xl bg-gray-50 dark:bg-gray-900 p-1 mb-6 shadow border border-gray-200 dark:border-gray-700 gap-2">
                        <button id="btn-login" type="button" role="tab" aria-controls="login-form"
                            aria-selected="true" data-auth-tab="login"
                            class="px-5 py-1.5 rounded-xl text-sm font-bold bg-white dark:bg-gray-900 text-[#240B36] border border-transparent hover:bg-[#240B36] hover:text-white focus:outline-none aria-selected:bg-[#240B36] aria-selected:text-white aria-selected:shadow-sm aria-selected:ring-2 aria-selected:ring-[#240B36]/30">
                            دخول العميل
                        </button>
                        <button id="btn-register" type="button" role="tab" aria-controls="register-form"
                            aria-selected="false" data-auth-tab="register"
                            class="px-5 py-1.5 rounded-xl text-sm font-bold bg-white dark:bg-gray-900 text-[#240B36] border border-transparent hover:bg-[#240B36] hover:text-white focus:outline-none aria-selected:bg-[#240B36] aria-selected:text-white aria-selected:shadow-sm aria-selected:ring-2 aria-selected:ring-[#240B36]/30">
                            إنشاء حساب جديد
                        </button>
                    </div>
                @endif

                <!-- رسائل الخطأ والنجاح -->
                {{-- لا تظهر رسالة النجاح إذا كان الطلب عبر AJAX (شاشة النجاح ستظهر تلقائياً) --}}
                @if (session('success') && !request()->ajax() && !request()->wantsJson())
                    <div
                        class="mb-4 p-3 rounded-xl bg-green-100 border border-green-300 text-green-800 font-bold text-center">
                        {{ session('success') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 p-3 rounded-xl bg-red-100 border border-red-300 text-red-800">
                        <ul class="list-disc ps-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (auth('client')->check())
                    <!-- بيانات العميل بعد تسجيل الدخول -->
                    <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-green-900">
                        <div class="font-bold mb-1">مرحباً، {{ auth('client')->user()->first_name }}
                            {{ auth('client')->user()->last_name }}</div>
                        <div class="text-sm mb-2">البريد: {{ auth('client')->user()->email }}</div>
                        <button type="button" id="toggleLogout"
                            class="text-xs text-gray-700 underline hover:text-gray-900">تبديل الحساب</button>
                        <form id="logoutInline" class="hidden" method="POST" action="{{ route('client.logout') }}"
                            style="display:inline">
                            @csrf
                            <button type="submit"
                                class="text-sm text-red-700 underline hover:text-red-900 font-bold bg-transparent border-0 p-0 cursor-pointer">تسجيل
                                بحساب آخر</button>
                        </form>
                    </div>
                @else
                    <!-- نموذج الدخول -->

                    <form id="login-form" class=" mb-2" role="tabpanel" method="POST"
                        action="{{ route('login.store') }}">
                        @csrf
                        <div class="grid md:grid-cols-3 gap-4 items-end">
                            <div>
                                <label class="text-sm font-medium mb-1 block">البريد الإلكتروني *</label>
                                <input type="email" name="email"
                                    class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-3 h-12 outline-none focus:ring-4 focus:ring-[#240B36]/20"
                                    placeholder="example@domain.com" required />
                            </div>
                            <div>
                                <label class="text-sm font-medium mb-1 block">كلمة المرور *</label>
                                <input type="password" name="password"
                                    class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-3 h-12 outline-none focus:ring-4 focus:ring-[#240B36]/20"
                                    placeholder="••••••" required />
                            </div>
                            <div class="pt-6">
                                <button type="submit"
                                    class="rounded-xl px-4 py-2 font-semibold text-white bg-[#240B36] hover:opacity-95 active:scale-95 transition shadow-sm h-12">تسجيل
                                    الدخول
                                </button>
                            </div>
                        </div>
                        <p id="loginMsg" class="mt-2 text-xs text-amber-600"></p>
                    </form>
                    <!-- يظهر بعد نجاح تسجيل الدخول عبر AJAX -->
                    <div id="clientInfoAjax"
                        class="hidden mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-green-900">
                        <div class="font-bold mb-1">مرحباً، <span id="clientFirst"></span> <span
                                id="clientLast"></span></div>
                        <div class="text-sm mb-2">البريد: <span id="clientEmail"></span></div>
                    </div>
                    <!-- نموذج التسجيل -->
                    <form id="register-form" class="space-y-6 mb-6 hidden" role="tabpanel" onsubmit="return false;">
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">الاسم الأول *</label>
                                <input name="first_name"
                                    class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20"
                                    placeholder="محمد" required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">الاسم الأخير *</label>
                                <input name="last_name"
                                    class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20"
                                    placeholder="أحمد" required />
                            </div>
                        </div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">رقم الجوال *</label>
                                <input name="phone"
                                    class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20"
                                    placeholder="590000000" required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">البريد الإلكتروني *</label>
                                <input type="email" name="email"
                                    class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20"
                                    placeholder="you@example.com" required />
                            </div>
                        </div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">كلمة المرور *</label>
                                <input type="password" name="password"
                                    class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20"
                                    placeholder="••••••" required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">تأكيد كلمة المرور *</label>
                                <input type="password" name="password_confirmation"
                                    class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20"
                                    placeholder="••••••" required />
                            </div>
                        </div>
                    </form>
                @endif

                <!-- الدفع (مُحسَّن) -->
                <div class="border border-gray-200 dark:border-gray-800 rounded-xl p-4" id="paymentBox">
                    <h3 class="font-bold mb-3">طريقة الدفع</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                        <label
                            class="border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex items-center gap-3 cursor-pointer">
                            <input type="radio" name="gateway" value="card" class="scale-110" checked>
                            <span>بطاقة ائتمانية</span>
                            <span class="ms-auto text-xs text-gray-500">Visa / MasterCard</span>
                        </label>
                        <label
                            class="border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex items-center gap-3 cursor-pointer">
                            <input type="radio" name="gateway" value="bank" class="scale-110">
                            <span>تحويل بنكي</span>
                            <span class="ms-auto text-xs text-gray-500">تأكيد يدوي</span>
                        </label>
                    </div>

                    <!-- نموذج بطاقة ائتمانية -->
                    <form id="cardForm" class="space-y-4">
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">رقم البطاقة *</label>
                                <input id="ccNumber" inputmode="numeric" placeholder="4242 4242 4242 4242"
                                    class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">اسم حامل البطاقة *</label>
                                <input id="ccName" placeholder="Mohammed A."
                                    class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20">
                            </div>
                        </div>
                        <div class="grid md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">تاريخ الانتهاء *</label>
                                <input id="ccExp" inputmode="numeric" placeholder="MM/YY"
                                    class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">CVV *</label>
                                <input id="ccCvv" inputmode="numeric" placeholder="123"
                                    class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20">
                            </div>
                            <div class="flex items-end">
                                <div id="ccHint" class="text-xs text-gray-500">يتم التحقق محليًا لأغراض العرض.
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- نموذج تحويل بنكي -->
                    <form id="bankForm" class="space-y-4 hidden">
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">البنك المحوَّل إليه</label>
                                <input value="Bank of Palestine - IBAN: PS00 PALS 0000 0000 0000 0000" readonly
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 dark:bg-gray-800 dark:border-gray-800 px-4 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">رقم المعاملة *</label>
                                <input id="bankRef" placeholder="TRX-123456"
                                    class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">ملاحظة (اختياري)</label>
                            <textarea id="bankNote" rows="3"
                                class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20"
                                placeholder="ارفق أي تفاصيل مهمة عن التحويل..."></textarea>
                        </div>
                    </form>

                    <div class="mt-4 flex items-start gap-2">
                        <input id="agreeTos" type="checkbox" class="mt-1">
                        <label for="agreeTos" class="text-sm text-gray-700 dark:text-gray-300">أوافق على <a
                                href="#" class="underline">الشروط والأحكام</a> وسياسة الخصوصية.</label>
                    </div>
                </div>

                <form id="checkoutForm" method="POST"
                    action="@if (empty($template_id) && empty($plan_id)) {{ route('checkout.cart.process') }}@elseif(!empty($template_id)){{ route('checkout.process', $template_id) }}@else{{ route('checkout.process',['template_id'=>null,'plan_id'=>$plan_id]) }} @endif">
                    {{-- action="{{ $template_id ? route('checkout.process', ['template_id' => $template_id]) : route('checkout.cart.process') }}"> --}}
                    @csrf
                    <input type="hidden" name="domain" id="orderDomainInput" value="">
                    <input type="hidden" name="total" id="orderTotalInput" value="">
                    <input type="hidden" name="total_cents" id="orderTotalCents" value="">
                    <!-- حقول التسجيل ستنسخ هنا عند اختيار إنشاء حساب جديد -->
                    <div id="registerFieldsBox"></div>
                    <div class="flex items-center justify-end gap-3 mt-6">
                        <button id="backToDomain2" type="button"
                            class="rounded-xl px-4 py-2 font-semibold border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 hover:bg-gray-50 active:scale-95 transition">رجوع</button>
                        <button id="placeOrderReal" type="submit" disabled
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2 font-semibold text-white bg-[#240B36] opacity-50 cursor-not-allowed transition shadow-sm">إتمام
                            الطلب</button>
                    </div>
                </form>

            </div>

            <aside
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow p-6 h-max">
                <h3 class="font-bold mb-3">الإجمالي</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span>المجموع</span><span id="sumSub">0.00</span></div>
                    <div class="flex justify-between"><span>الخصم</span><span id="sumDiscount">$0.00</span></div>
                    <div class="flex justify-between"><span>الضريبة</span><span id="sumTax">$0.00</span></div>
                </div>
                <hr class="my-4 border-gray-200 dark:border-gray-800" />
                <div class="space-y-3">
                    <div class="flex gap-2">
                        <input id="couponInput"
                            class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20"
                            placeholder="كود الخصم (إن وجد)">
                        <button id="applyCoupon"
                            class="rounded-xl px-4 py-2 font-semibold border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 hover:bg-gray-50 active:scale-95 transition">تطبيق</button>
                    </div>
                    <p id="couponMsg" class="text-xs text-gray-500"></p>
                </div>
                <hr class="my-4 border-gray-200 dark:border-gray-800" />
                <div class="flex justify-between font-bold text-lg"><span>الإجمالي المستحق</span><span id="sumTotal2">
                    @if($template)
                        @if ($showDiscount)
                            <span class="line-through text-gray-400">${{ number_format($basePrice, 2) }}</span>
                            <span class="text-red-600 font-bold ms-2">${{ number_format($discPrice, 2) }}</span>
                        @else
                            ${{ number_format($basePrice, 2) }}
                        @endif
                    @else
                        ${{ number_format($basePricePlan, 2) }}
                    @endif
                    </span></div>
            </aside>
        </div>
    </section>
    <!-- ===== الصفحة 3: نجاح الطلب ===== -->
    <section id="view-success" class="hidden max-w-6xl mx-auto px-4 sm:px-6 lg:px-10 py-16">

        <div
            class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow p-8 text-center invoice-print-area">
            <!-- شعار الشركة للطباعة -->
            <div class="print-logo mb-6" style="display:none">
                <img src="/assets/dashboard/images/logo-white.svg" alt="Palgoals Logo"
                    style="height:60px; margin:auto;">
            </div>
            <div
                class="mx-auto w-16 h-16 rounded-full grid place-items-center bg-green-100 text-green-700 mb-4 not-print">
                ✓</div>
            <h2 class="text-2xl font-extrabold mb-2" id="sx-success-msg">تم إنشاء الطلب بنجاح</h2>
            <p class="text-gray-600 dark:text-gray-300 mb-6 not-print">سنرسل إليك فاتورة عبر البريد الإلكتروني. يمكنك
                إدارة موقعك من لوحة التحكم.</p>
            <!-- ملخص الفاتورة الاحترافي -->
            <div class="max-w-2xl mx-auto mb-8">
                <table
                    class="w-full text-base border rounded-2xl overflow-hidden bg-white dark:bg-gray-900 shadow-lg invoice-table-print">
                    <thead
                        class="bg-gradient-to-l from-[#f3f4f6] to-[#e9eaf0] dark:from-gray-800 dark:to-gray-900 text-[#240B36] dark:text-gray-100">
                        <tr>
                            <th class="p-4 text-right font-extrabold text-lg w-1/2">البند</th>
                            <th class="p-4 text-right font-extrabold text-lg w-1/2">القيمة</th>
                        </tr>
                    </thead>
                    <tbody id="sx-invoice-body" class="divide-y divide-gray-200 dark:divide-gray-800">
                        <!-- سيتم تعبئتها ديناميكياً -->
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-3 not-print">
                <button id="sx-dashboard"
                    class="inline-flex items-center justify-center rounded-xl px-5 py-2.5 font-semibold text-white bg-[#240B36] hover:opacity-95 active:scale-95 transition shadow-sm">
                    الذهاب للوحة التحكم
                </button>
                <button id="sx-print"
                    class="rounded-xl px-5 py-2.5 font-semibold border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 hover:bg-gray-50 active:scale-95 transition">
                    طباعة الفاتورة
                </button>
            </div>
            <div id="sx-hint" class="text-xs text-gray-500 mt-6 not-print">
                إن كنت قد اخترت ربط نطاق تملكه، سنعرض لك سجلات DNS في صفحة الإعداد لاحقًا.
            </div>
        </div>
    </section>


    <!-- ===== منطق التبويبات والتنقّل ===== -->
    <script>
        /* ========================== سلة موحّدة + أدوات عامّة ========================== */
        const UNIFIED_CART_KEY = 'palgoals_cart';
        const LEGACY_CART_KEY = 'palgoals_cart_domains'; // استيراد مرّة واحدة عند أول قراءة

        function safeParse(json, fb) {
            try {
                const v = JSON.parse(json);
                return Array.isArray(v) ? v : fb;
            } catch {
                return fb;
            }
        }

        function normalizeDomain(raw) {
            if (!raw) return null;
            try {
                let host = (new URL(raw.includes('://') ? raw : ('http://' + raw))).hostname;
                host = host.toLowerCase().replace(/^www\./, '').replace(/\.$/, '');
                return host || null;
            } catch {
                return String(raw).toLowerCase().replace(/^www\./, '').replace(/\.$/, '') || null;
            }
        }

        function readUnifiedCart() {
            let items = safeParse(localStorage.getItem(UNIFIED_CART_KEY), []);
            // استيراد القديم مرّة واحدة
            if (!localStorage.getItem(UNIFIED_CART_KEY)) {
                const legacy = safeParse(localStorage.getItem(LEGACY_CART_KEY), []);
                if (legacy.length) {
                    items = items.concat(legacy.map(it => ({
                        kind: 'domain',
                        domain: String(it.domain || '').toLowerCase().trim(),
                        item_option: it.item_option ?? it.option ?? 'register',
                        price_cents: Number(it.price_cents) || 0,
                        meta: it.meta ?? null,
                    })));
                    localStorage.setItem(UNIFIED_CART_KEY, JSON.stringify(items));
                }
            }
            return items;
        }

        function writeUnifiedCart(items) {
            localStorage.setItem(UNIFIED_CART_KEY, JSON.stringify(items || []));
        }

        // مرشّحات/ديدوب
        function domainOnly(items) {
            return (items || []).filter(it => it && typeof it === 'object' && (
                it.kind === 'domain' || (it.kind == null && typeof it.domain === 'string' && it.domain.trim() !==
                    '')
            ));
        }

        function dedupeDomains(domains) {
            const seen = new Set(),
                out = [];
            for (const it of domains) {
                const d = normalizeDomain(it.domain);
                if (!d || seen.has(d)) continue;
                seen.add(d);
                out.push({
                    kind: 'domain',
                    domain: d,
                    item_option: it.item_option ?? it.option ?? 'register',
                    price_cents: Number(it.price_cents) || 0,
                    meta: it.meta ?? null,
                });
            }
            return out;
        }

        function upsertDomain(items, {
            domain,
            item_option,
            price_cents,
            meta
        }) {
            const d = normalizeDomain(domain);
            if (!d) return items || [];
            let exists = false;
            const next = (items || []).map(it => {
                if (it?.kind === 'domain' && normalizeDomain(it.domain) === d) {
                    exists = true;
                    return {
                        ...it,
                        item_option: item_option || it.item_option || 'register',
                        price_cents: Number(price_cents ?? it.price_cents) || 0,
                        meta: meta ?? it.meta ?? null
                    };
                }
                return it;
            });
            if (!exists) next.push({
                kind: 'domain',
                domain: d,
                item_option: item_option || 'register',
                price_cents: Number(price_cents) || 0,
                meta: meta ?? null
            });
            return next;
        }

        // أسعار احتياطية محلّية + Formatter
        const USD = true;
        // عناصر قادمة من الخادم عند /checkout/cart (جلسة السيرفر)
        const SERVER_CART_ITEMS = @json($items ?? []);
        const fallbackPriceMap = {
            '.com': 1000,
            '.net': 1200,
            '.org': 1100
        };

        function getFallbackCents(tld) {
            try {
                if (window.priceMap && (tld in window.priceMap)) return Number(window.priceMap[tld]) || 0;
            } catch {}
            return Number(fallbackPriceMap[tld] ?? 1000);
        }
        const fmt = c => (USD ? `$${(c/100).toFixed(2)}` : `${(c/100).toFixed(2)} ر.س`);

        // تحويل أي قيمة سعر إلى سنت
        function toCents(x) {
            if (x == null) return null;
            const n = Number(String(x).replace(/[^0-9.]/g, ''));
            if (!Number.isFinite(n)) return null;
            if (n >= 100000) return Math.round(n); // يبدو أنها سنت أصلًا
            if (n <= 1000) return Math.round(n * 100); // دولار -> سنت
            return Math.round(n); // قيمة وسطية: اعتبرها سنت
        }

        // استخراج السعر من ردّ الخادم حسب نوع العملية
        function extractPriceCents(row, option) {
            const r = row || {};
            const prefer = option === 'transfer' ? ['transfer_price_cents', 'transferPriceCents', 'transfer_price'] : [
                'register_price_cents', 'registration_price_cents', 'price_cents', 'register_price'
            ];

            for (const key of prefer) {
                if (r[key] != null) {
                    const v = toCents(r[key]);
                    if (v != null) return v;
                }
            }
            // حقول عامة/احتياطية
            for (const key of ['sale_price_cents', 'promo_price_cents', 'price']) {
                if (r[key] != null) {
                    const v = toCents(r[key]);
                    if (v != null) return v;
                }
            }
            // ابحث في meta/details لو موجود
            const m = r.meta || r.details || {};
            for (const key of [...prefer, 'price_cents', 'price']) {
                if (m && m[key] != null) {
                    const v = toCents(m[key]);
                    if (v != null) return v;
                }
            }
            return null;
        }

        // جلب السعر الصحيح من الخادم (domains.check)
        const routeCheckSingle = (domain) =>
            `{{ route('domains.check') }}?domains=${encodeURIComponent(domain)}&t=${Date.now()}`;

        async function fetchServerPriceCents(domain, option) {
            const tld = '.' + (domain.split('.').pop() || 'com').toLowerCase();
            try {
                const res = await fetch(routeCheckSingle(domain), {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json().catch(() => null);
                const row = (data?.results || []).find(x => (x.domain || '').toLowerCase() === domain.toLowerCase());
                const cents = extractPriceCents(row, option);
                if (Number.isFinite(cents) && cents >= 0) return cents;
            } catch {
                /* ignore */
            }
            // احتياطي: سعر محلي
            if (option === 'register' || option === 'transfer') return getFallbackCents(tld);
            return 0;
        }

        // سعر القالب (بالسنت)
        const HAS_TEMPLATE = {{ $template ? 'true' : 'false' }};
        const USE_AJAX_LOGIN = false; // رجوع للسلوك السابق: تحديث الصفحة عند تسجيل الدخول
        const TEMPLATE_FINAL_CENTS = {{ (int) (($finalPrice ?? 0) * 100) }};
        let TEMPLATE_CENTS = TEMPLATE_FINAL_CENTS; // متغير قابل للتغيير عند إزالة القالب

        // عناصر UI مشتركة
        const summaryDomain = document.getElementById('summaryDomain');
        const summaryTotal = document.getElementById('summaryTotal');
        const reviewDomain = document.getElementById('reviewDomain');
        const reviewDomainPrice = document.getElementById('reviewDomainPrice');
        const sumSub = document.getElementById('sumSub');
        const sumTax = document.getElementById('sumTax');
        const sumDiscount = document.getElementById('sumDiscount');
        const sumTotal2 = document.getElementById('sumTotal2');
        const orderTotalCentsInp = document.getElementById('orderTotalCents');
        const orderTotalInp = document.getElementById('orderTotalInput');

        // تخزين محلي لاختيار الدومين الأساسي (للعودة إلى الخطوة 2 بعد تسجيل الدخول)
        const PRIMARY_KEY = 'palgoals_checkout_primary';

        function savePrimarySelection(item) {
            try {
                localStorage.setItem(PRIMARY_KEY, JSON.stringify(item || {}));
            } catch {}
        }

        function readPrimarySelection() {
            try {
                const v = JSON.parse(localStorage.getItem(PRIMARY_KEY) || 'null');
                return v && v.domain ? v : null;
            } catch {
                return null;
            }
        }

        function clearPrimarySelection() {
            try {
                localStorage.removeItem(PRIMARY_KEY);
            } catch {}
        }

        // خصم (كوبون) — افتراضي 0
        window.__couponDiscountCents = 0;

        // حساب الإجماليات (دومين + القالب - الخصم + ضريبة)
        function updateTotals(domainCents) {
            const subtotal = TEMPLATE_CENTS + Math.max(0, domainCents | 0);
            const tax = 0;
            const discount = Math.min(window.__couponDiscountCents | 0, subtotal);
            const total = Math.max(0, subtotal - discount + tax);

            if (sumSub) sumSub.textContent = fmt(subtotal);
            if (sumTax) sumTax.textContent = fmt(tax);
            if (sumDiscount) sumDiscount.textContent = `-${fmt(discount)}`;
            if (sumTotal2) sumTotal2.textContent = fmt(total);
            if (summaryTotal) summaryTotal.textContent = fmt(total);
            if (orderTotalCentsInp) orderTotalCentsInp.value = String(total);
            if (orderTotalInp) orderTotalInp.value = fmt(total);
        }

        function setReview(domain, cents) {
            if (summaryDomain) summaryDomain.textContent = domain || '—';
            // التوافق السابق: اجعل خانة السعر تعكس قيمة واحدة عند الحاجة
            const priceCell = document.getElementById('reviewDomainPrice');
            if (priceCell) priceCell.textContent = fmt(cents || 0);
            updateTotals(cents || 0);
            try {
                updateChooseTemplateLink();
            } catch {}
        }

        // عرض متعدد الدومينات + بناء الحقول المخفية
        function setCartDomains(items) {
            const list = Array.isArray(items) ? items.filter(x => x && x.domain) : [];
            const tbody = document.getElementById('reviewDomainsBody');
            const proto = document.getElementById('reviewDomainProto');
            if (!tbody || !proto) return;
            // امسح صفوف الدومينات فقط (اترك صف القالب إن وُجد)
            Array.from(tbody.querySelectorAll('tr.rv-domain-row')).forEach(tr => tr.remove());

            let totalCents = 0;
            list.forEach((it) => {
                const tr = proto.cloneNode(true);
                tr.id = '';
                tr.classList.remove('hidden');
                tr.classList.add('rv-domain-row');
                tr.querySelector('.rv-domain').textContent = it.domain;
                const cents = Number(it.price_cents) || 0;
                tr.querySelector('.rv-price').textContent = fmt(cents);
                const btn = tr.querySelector('.rv-remove');
                if (btn) btn.setAttribute('data-domain', it.domain);
                totalCents += cents;
                tbody.appendChild(tr);
            });

            // ملخص جانبي وخانة السعر الإجمالية
            if (summaryDomain) {
                if (list.length > 1) summaryDomain.textContent = `${list[0]?.domain || '—'} (+${list.length - 1})`;
                else summaryDomain.textContent = list[0]?.domain || '—';
            }
            const priceCell = document.getElementById('reviewDomainPrice');
            if (priceCell) priceCell.textContent = fmt(totalCents);
            updateTotals(totalCents);

            // إبراز الرابط لاختيار القالب بالدومين الأول
            try {
                updateChooseTemplateLink();
            } catch {}

            // بناء الحقول المخفية للإرسال
            const form = document.getElementById('checkoutForm');
            if (form) {
                form.querySelectorAll('input[name^="items["]').forEach(n => n.remove());
                list.forEach((it, i) => {
                    [
                        ['domain', it.domain],
                        ['option', it.item_option || it.option || 'register'],
                        ['price_cents', Number(it.price_cents) || 0]
                    ].forEach(([k, v]) => {
                        const inp = document.createElement('input');
                        inp.type = 'hidden';
                        inp.name = `items[${i}][${k}]`;
                        inp.value = v;
                        form.appendChild(inp);
                    });
                });
                const orderDomainInput = document.getElementById('orderDomainInput');
                if (orderDomainInput) orderDomainInput.value = list[0]?.domain || '';
            }

            // أظهر/أخفِ حالة السلة الفارغة
            try {
                showEmptyNotice(list.length === 0);
            } catch {}

            // اربط أزرار الحذف لكل صف
            tbody.querySelectorAll('.rv-remove').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const d = btn.getAttribute('data-domain') || '';
                    if (!d) return;
                    const remaining = list.filter(x => (x.domain || '').toLowerCase() !== d
                        .toLowerCase());
                    // حدّث LocalStorage (إزالة هذا الدومين فقط)
                    try {
                        const unified = readUnifiedCart() || [];
                        const leftovers = unified.filter(it => !(it && (it.kind === 'domain' || (it
                                .kind == null && it.domain)) && String(it.domain)
                            .toLowerCase() === d.toLowerCase()));
                        writeUnifiedCart(leftovers);
                    } catch {}
                    // حدّث جلسة السيرفر إن تبقى عناصر
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                            'content') || '';
                        if (remaining.length > 0) {
                            await fetch(`{{ route('cart.store') }}`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': token,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    items: remaining
                                })
                            });
                        } else {
                            await fetch(`{{ url('/cart/clear') }}`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': token,
                                    'Accept': 'application/json'
                                }
                            });
                        }
                    } catch {}
                    setCartDomains(remaining);
                });
            });
        }

        // Stepper
        const views = ['view-domain', 'view-review'];
        const stepper = document.getElementById('globalStepper');

        function goto(stepIndex) {
            // تبديل الشاشات
            views.forEach((id, i) => document.getElementById(id)?.classList.toggle('hidden', i !== stepIndex));
            const circles = stepper?.querySelectorAll('.step-circle') || [];
            circles.forEach((c, i) => {
                // نظّف جميع الحالات قبل التفعيل
                c.classList.remove(
                    'border-[#240B36]', 'text-[#240B36]', 'bg-[#240B36]', 'text-white',
                    'border-gray-200', 'dark:border-gray-700', 'text-gray-500'
                );
                if (i < stepIndex) {
                    // مكتملة
                    c.classList.add('bg-[#240B36]', 'text-white', 'border-[#240B36]');
                } else if (i === stepIndex) {
                    // الحالية
                    c.classList.add('border-[#240B36]', 'text-[#240B36]');
                } else {
                    // القادمة
                    c.classList.add('border-gray-200', 'dark:border-gray-700', 'text-gray-500');
                }
            });
            // تقدّم الخط بين الخطوتين (RTL: من اليمين لليسار)
            const bar = document.getElementById('stepperProgress');
            if (bar) bar.style.width = stepIndex === 0 ? '0%' : '100%';
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // شاشة النجاح
        function showSuccess() {
            ['view-domain', 'view-review'].forEach(id => document.getElementById(id)?.classList.add('hidden'));
            document.getElementById('view-success')?.classList.remove('hidden');
            const circles = document.querySelectorAll('#globalStepper .step-circle');
            circles.forEach(c => {
                c.classList.remove('border-[#240B36]', 'text-[#240B36]');
                c.classList.add('bg-[#240B36]', 'text-white', 'border-[#240B36]');
            });
            const bar = document.getElementById('stepperProgress');
            if (bar) bar.style.width = '100%';
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // تعبئة فاتورة النجاح
        function fillSuccessInvoice(data) {
            const body = document.getElementById('sx-invoice-body');
            if (!body) return;
            let html = '';
            if (data.order_no) html +=
                `<tr class='bg-gray-50 dark:bg-gray-800'><td class="py-3 px-4">رقم الطلب</td><td class="py-3 px-4 font-bold">${data.order_no}</td></tr>`;
            if (data.template_name) html +=
                `<tr><td class="py-3 px-4">القالب</td><td class="py-3 px-4">${data.template_name}</td></tr>`;
            html += `<tr><td class="py-3 px-4">مدة الاشتراك</td><td class="py-3 px-4">12 شهر</td></tr>`;
            if (data.template_price_html) html +=
                `<tr><td class="py-3 px-4">سعر القالب</td><td class="py-3 px-4">${data.template_price_html}</td></tr>`;
            else if (data.template_price) html +=
                `<tr><td class="py-3 px-4">سعر القالب</td><td class="py-3 px-4">${data.template_price}</td></tr>`;
            if (data.domain) html += `<tr><td class="py-3 px-4">الدومين</td><td class="py-3 px-4">${data.domain}</td></tr>`;
            if (data.domain_price) html +=
                `<tr><td class="py-3 px-4">سعر الدومين</td><td class="py-3 px-4">${data.domain_price}</td></tr>`;
            if (data.discount) html +=
                `<tr><td class="py-3 px-4">الخصم</td><td class="py-3 px-4 text-green-700">-${data.discount}</td></tr>`;
            if (data.tax) html += `<tr><td class="py-3 px-4">الضريبة</td><td class="py-3 px-4">${data.tax}</td></tr>`;
            if (data.total) html +=
                `<tr class='bg-green-50 dark:bg-green-900 font-extrabold text-lg'><td class="py-4 px-4">الإجمالي المستحق</td><td class="py-4 px-4 text-green-700">${data.total}</td></tr>`;
            body.innerHTML = html;
        }

        /* ========================== منطق الصفحة ========================== */
        document.addEventListener('DOMContentLoaded', function() {
            // رابط اختيار القالب يحمل الدومين الحالي إن وُجد
            function updateChooseTemplateLink() {
                const anchors = ['chooseTemplateLink', 'chooseTemplateLink2']
                    .map(id => document.getElementById(id))
                    .filter(Boolean);
                if (!anchors.length) return;
                const d = (document.getElementById('orderDomainInput')?.value || '').trim();
                const base = '/templates';
                anchors.forEach(a => {
                    if (!d || d === '—') a.href = base + '?origin=checkout';
                    else a.href = base + '?origin=checkout&domain=' + encodeURIComponent(d);
                });
            }
            updateChooseTemplateLink();

            // عناصر تبويب "تسجيل جديد"
            const regSld = document.querySelector('#tab-register input[aria-label="اسم النطاق"]');
            const regTld = document.querySelector('#tab-register select[aria-label="الامتداد"]');
            const tldPrice = document.getElementById('tldPrice');
            const btnCheck = document.getElementById('btnCheck');

            // تبويبات
            const tabs = document.querySelectorAll('[data-tab]');
            const panels = {
                register: document.getElementById('tab-register'),
                transfer: document.getElementById('tab-transfer'),
                owndomain: document.getElementById('tab-owndomain'),
                subdomain: document.getElementById('tab-subdomain')
            };

            // تحديث السعر الأولي (احتياطي)
            if (tldPrice && regTld) {
                const p0 = getFallbackCents(regTld.value);
                tldPrice.textContent = `${fmt(p0)}/سنة`;
                updateTotals(p0);
            }

            // تغيير الامتداد (سعر احتياطي فقط)
            regTld?.addEventListener('change', () => {
                const cents = getFallbackCents(regTld.value);
                tldPrice.textContent = `${fmt(cents)}/سنة`;
            });

            // فحص توافر + جلب سعر صحيح
            btnCheck?.addEventListener('click', async () => {
                const sld = (regSld?.value || '').trim().toLowerCase();
                const tld = (regTld?.value || '.com').trim().toLowerCase();
                const checkResult = document.getElementById('checkResult');
                if (!sld) {
                    if (checkResult) checkResult.textContent = 'رجاءً أدخل اسم النطاق أولاً';
                    return;
                }
                const fqdn = `${sld}${tld}`;
                if (checkResult) checkResult.textContent = 'جارٍ الفحص…';
                try {
                    const cents = await fetchServerPriceCents(fqdn, 'register');
                    if (tldPrice) tldPrice.textContent = `${fmt(cents)}/سنة`;
                    // متاح/محجوز
                    const res = await fetch(routeCheckSingle(fqdn), {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json().catch(() => null);
                    const r = (data?.results || []).find(x => (x.domain || '').toLowerCase() === fqdn
                        .toLowerCase());
                    if (r?.available) {
                        if (checkResult) checkResult.innerHTML = `✅ متاح — <strong>${fqdn}</strong>`;
                        setReview(fqdn, cents);
                    } else {
                        if (checkResult) checkResult.innerHTML = `❌ محجوز — جرّب امتدادًا آخر`;
                        setReview(fqdn, 0);
                    }
                } catch {
                    if (checkResult) checkResult.textContent = 'خطأ في الاتصال ❌';
                }
            });

            // منع الانتقال قبل اختيار دومين للقالب: تعطيل أزرار المتابعة حتى إدخال صالح
            function setBtnDisabled(btn, disabled) {
                if (!btn) return;
                btn.disabled = !!disabled;
                btn.classList.toggle('opacity-50', !!disabled);
                btn.classList.toggle('cursor-not-allowed', !!disabled);
            }

            if (HAS_TEMPLATE) {
                const btns = {
                    register: document.getElementById('goConfigR'),
                    transfer: document.getElementById('goConfigT'),
                    own: document.getElementById('goConfigO'),
                    sub: document.getElementById('goConfigS'),
                };

                // دوال تحقق سريعة لكل تبويب
                const canRegister = () => !!(regSld?.value || '').trim();
                const transferDomainInp = document.querySelector('#tab-transfer input[aria-label="اسم النطاق"]');
                const canTransfer = () => !!(transferDomainInp?.value || '').trim();
                const ownDomainInp = document.querySelector('#tab-owndomain input[aria-label="اسم النطاق"]');
                const canOwn = () => !!(ownDomainInp?.value || '').trim();
                const subNameInp = document.querySelector('#tab-subdomain input[aria-label="اسم الساب-دومين"]');
                const canSub = () => !!(subNameInp?.value || '').trim();

                // محدّث حالة التعطيل
                const refreshGuards = () => {
                    setBtnDisabled(btns.register, !canRegister());
                    setBtnDisabled(btns.transfer, !canTransfer());
                    setBtnDisabled(btns.own, !canOwn());
                    setBtnDisabled(btns.sub, !canSub());
                    // إظهار تلميح بسيط عند التعطيل
                    const toggleHint = (id, show) => {
                        const el = document.getElementById(id);
                        if (el) el.classList.toggle('hidden', !show);
                    };
                    toggleHint('hintR', !canRegister());
                    toggleHint('hintT', !canTransfer());
                    toggleHint('hintO', !canOwn());
                    toggleHint('hintS', !canSub());
                };

                // اربط الأحداث على إدخال المستخدم
                regSld?.addEventListener('input', refreshGuards);
                transferDomainInp?.addEventListener('input', refreshGuards);
                ownDomainInp?.addEventListener('input', refreshGuards);
                subNameInp?.addEventListener('input', refreshGuards);

                // تفعيل أولي
                refreshGuards();
            }

            // تخزين القيم في الفورم النهائي عند الانتقال
            function updateDomainFieldsFromSelection(option, domain, cents) {
                const finalForm = document.getElementById('checkoutForm');
                if (!finalForm) return;
                const ensure = (name, val) => {
                    let inp = finalForm.querySelector(`input[name="${name}"]`);
                    if (!inp) {
                        inp = document.createElement('input');
                        inp.type = 'hidden';
                        inp.name = name;
                        finalForm.appendChild(inp);
                    }
                    inp.value = val;
                };
                ensure('domain_option', option);
                ensure('domain', domain);
                ensure('domain_price_cents', String(cents));

                // items[0] للباك إند
                finalForm.querySelectorAll('input[name^="items["]').forEach(n => n.remove());
                const itemFields = {
                    domain,
                    option: option,
                    price_cents: String(cents)
                };
                Object.entries(itemFields).forEach(([k, v]) => {
                    const inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = `items[0][${k}]`;
                    inp.value = v;
                    finalForm.appendChild(inp);
                });
            }

            // أزرار المتابعة
            const btnR = document.getElementById('goConfigR');
            const btnT = document.getElementById('goConfigT');
            const btnO = document.getElementById('goConfigO');
            const btnS = document.getElementById('goConfigS');

            // تسجيل جديد — السعر من الخادم
            btnR?.addEventListener('click', async () => {
                const sld = (regSld?.value || '').trim().toLowerCase();
                const tld = (regTld?.value || '.com').trim().toLowerCase();
                const checkResult = document.getElementById('checkResult');
                if (!sld) {
                    if (checkResult) checkResult.textContent = 'رجاءً أدخل اسم النطاق أولاً';
                    return;
                }
                const fqdn = `${sld}${tld}`;

                // تأكّد من التوافر واجلب السعر الصحيح
                let cents = await fetchServerPriceCents(fqdn, 'register');
                try {
                    const res = await fetch(routeCheckSingle(fqdn), {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json().catch(() => null);
                    const r = (data?.results || []).find(x => (x.domain || '').toLowerCase() === fqdn
                        .toLowerCase());
                    if (r?.available !== true) {
                        if (checkResult) checkResult.textContent = '❌ محجوز — اختر اسمًا/امتدادًا آخر';
                        return;
                    }
                    // لو كان عنده سعر أدق داخل الرد استخدمه
                    const fromRow = extractPriceCents(r, 'register');
                    if (fromRow != null) cents = fromRow;
                } catch {}

                // أضف للسلة
                writeUnifiedCart(upsertDomain(readUnifiedCart(), {
                    domain: fqdn,
                    item_option: 'register',
                    price_cents: cents
                }));

                // UI
                if (tldPrice) tldPrice.textContent = `${fmt(cents)}/سنة`;
                updateDomainFieldsFromSelection('register', fqdn, cents);
                setReview(fqdn, cents);
                const sel = {
                    domain: fqdn,
                    item_option: 'register',
                    price_cents: cents
                };
                try {
                    setCartDomains([sel]);
                } catch {}
                savePrimarySelection(sel);
                goto(1);
            });

            // نقل نطاق — يفضّل transfer_price_cents من الخادم
            btnT?.addEventListener('click', async () => {
                const form = btnT.closest('form');
                const domain = (form?.querySelector('input[aria-label="اسم النطاق"]')?.value || '')
                    .trim().toLowerCase();
                if (!domain) {
                    alert('رجاءً أدخل اسم النطاق');
                    return;
                }
                const cents = await fetchServerPriceCents(domain, 'transfer');
                writeUnifiedCart(upsertDomain(readUnifiedCart(), {
                    domain,
                    item_option: 'transfer',
                    price_cents: cents
                }));
                updateDomainFieldsFromSelection('transfer', domain, cents);
                setReview(domain, cents);
                const sel = {
                    domain,
                    item_option: 'transfer',
                    price_cents: cents
                };
                try {
                    setCartDomains([sel]);
                } catch {}
                savePrimarySelection(sel);
                goto(1);
            });

            // أمتلك نطاقًا — 0$
            btnO?.addEventListener('click', () => {
                const form = btnO.closest('form');
                const domain = (form?.querySelector('input[aria-label="اسم النطاق"]')?.value || '').trim()
                    .toLowerCase();
                if (!domain) {
                    alert('رجاءً أدخل اسم النطاق');
                    return;
                }
                writeUnifiedCart(upsertDomain(readUnifiedCart(), {
                    domain,
                    item_option: 'own',
                    price_cents: 0
                }));
                updateDomainFieldsFromSelection('own', domain, 0);
                setReview(domain, 0);
                const sel = {
                    domain,
                    item_option: 'own',
                    price_cents: 0
                };
                try {
                    setCartDomains([sel]);
                } catch {}
                savePrimarySelection(sel);
                goto(1);
            });

            // Subdomain مجاني — 0$
            btnS?.addEventListener('click', () => {
                const form = btnS.closest('form');
                const sub = (form?.querySelector('input[aria-label="اسم الساب-دومين"]')?.value || '').trim()
                    .toLowerCase();
                const main = (form?.querySelector('select[aria-label="الدومين الأساسي"]')?.value || '')
                    .trim().toLowerCase();
                if (!sub) {
                    alert('رجاءً أدخل اسم الساب-دومين');
                    return;
                }
                const fqdn = `${sub}.${main}`;
                writeUnifiedCart(upsertDomain(readUnifiedCart(), {
                    domain: fqdn,
                    item_option: 'subdomain',
                    price_cents: 0
                }));
                updateDomainFieldsFromSelection('subdomain', fqdn, 0);
                setReview(fqdn, 0);
                const sel = {
                    domain: fqdn,
                    item_option: 'subdomain',
                    price_cents: 0
                };
                try {
                    setCartDomains([sel]);
                } catch {}
                savePrimarySelection(sel);
                goto(1);
            });

            // تبديل التبويبات (اعتمادًا على aria-selected + Tailwind aria-variant)
            const activateTab = (name, opts = {}) => {
                tabs.forEach(b => {
                    const active = b.dataset.tab === name;
                    b.setAttribute('aria-selected', active ? 'true' : 'false');
                    b.tabIndex = active ? 0 : -1;
                });
                Object.values(panels).forEach(p => p?.classList.add('hidden'));
                panels[name]?.classList.remove('hidden');
                if (name === 'register') {
                    const cents = getFallbackCents(regTld?.value || '.com');
                    setReview('—', cents);
                } else {
                    setReview('—', 0);
                }
                if (opts.focus) {
                    const btn = Array.from(tabs).find(b => b.dataset.tab === name);
                    btn?.focus();
                }
            };
            const mapOptionToTab = (opt) => {
                const x = String(opt || '').toLowerCase();
                if (x === 'register' || x === 'new') return 'register';
                if (x === 'transfer') return 'transfer';
                if (x === 'subdomain') return 'subdomain';
                if (x === 'own' || x === 'existing') return 'owndomain';
                return 'register';
            }
            tabs.forEach(btn => {
                btn.classList.add('cursor-pointer', 'transition-colors');
                btn.addEventListener('click', () => activateTab(btn.dataset.tab));
            });
            // تنقّل لوحي/كيبورد داخل التبويبات
            const tablist = document.querySelector('[role="tablist"]');
            tablist?.addEventListener('keydown', (e) => {
                const keys = ['ArrowLeft', 'ArrowRight', 'Home', 'End'];
                if (!keys.includes(e.key)) return;
                e.preventDefault();
                const arr = Array.from(tabs);
                let idx = arr.findIndex(b => b === document.activeElement);
                if (idx === -1) idx = arr.findIndex(b => b.getAttribute('aria-selected') === 'true');
                if (e.key === 'Home') return activateTab(arr[0].dataset.tab, {
                    focus: true
                });
                if (e.key === 'End') return activateTab(arr[arr.length - 1].dataset.tab, {
                    focus: true
                });
                const dir = e.key === 'ArrowRight' ? 1 : -1;
                const next = (idx + dir + arr.length) % arr.length;
                activateTab(arr[next].dataset.tab, {
                    focus: true
                });
            });
            // تفعيل الحالة الابتدائية حسب الزر المحدد
            const initiallyActive = document.querySelector('[data-tab][aria-selected="true"]')?.dataset.tab ||
                'register';
            activateTab(initiallyActive);

            // ===== تبويبات الدخول/التسجيل =====
            const authTabs = document.querySelectorAll('[data-auth-tab]');
            const loginFormEl = document.getElementById('login-form');
            const registerFormEl = document.getElementById('register-form');
            const loginMsg = document.getElementById('loginMsg');

            function ensureRegisterFields() {
                const regForm = registerFormEl;
                const box = document.getElementById('registerFieldsBox');
                if (!regForm || !box) return;
                box.innerHTML = '';
                regForm.querySelectorAll('input').forEach(function(input) {
                    const clone = input.cloneNode();
                    clone.value = input.value;
                    clone.type = input.type;
                    clone.name = input.name;
                    clone.required = input.required;
                    clone.placeholder = input.placeholder;
                    clone.className = 'hidden';
                    box.appendChild(clone);
                });
            }

            function activateAuthTab(name) {
                authTabs.forEach(b => b.setAttribute('aria-selected', b.dataset.authTab === name ? 'true' :
                    'false'));
                if (name === 'login') {
                    loginFormEl?.classList.remove('hidden');
                    registerFormEl?.classList.add('hidden');
                } else {
                    registerFormEl?.classList.remove('hidden');
                    loginFormEl?.classList.add('hidden');
                    ensureRegisterFields();
                }
            }
            authTabs.forEach(b => b.addEventListener('click', () => activateAuthTab(b.dataset.authTab)));
            activateAuthTab(document.querySelector('[data-auth-tab][aria-selected="true"]')?.dataset.authTab ||
                'login');

            // إخفاء رابط "تسجيل بحساب آخر" بشكل افتراضي وإظهاره عند الطلب
            document.getElementById('toggleLogout')?.addEventListener('click', () => {
                const f = document.getElementById('logoutInline');
                if (f) f.classList.toggle('hidden');
            });

            // تسجيل الدخول عبر AJAX لتجنّب إعادة التحميل
            if (loginFormEl && USE_AJAX_LOGIN) {
                loginFormEl.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    if (loginMsg) loginMsg.textContent = '';
                    const btn = loginFormEl.querySelector('button[type="submit"]');
                    const inputs = loginFormEl.querySelectorAll('input');
                    btn?.classList.add('opacity-50', 'cursor-not-allowed');
                    btn.disabled = true;
                    inputs.forEach(i => i.readOnly = true);
                    try {
                        const fd = new FormData(loginFormEl);
                        const res = await fetch(loginFormEl.action, {
                            method: 'POST',
                            body: fd,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            credentials: 'same-origin'
                        });
                        const data = await res.json().catch(() => null);
                        if (!res.ok || !data || data.ok === false) {
                            let msg = (data && data.message) ? data.message :
                                'تعذّر تسجيل الدخول. تأكد من البيانات.';
                            if (loginMsg) loginMsg.textContent = msg;
                            return;
                        }
                        // نجاح: أخفِ نموذج الدخول، وأظهر معلومات العميل، وابقَ في الخطوة الثانية
                        if (loginMsg) {
                            loginMsg.textContent = 'تم تسجيل الدخول بنجاح.';
                            loginMsg.classList.remove('text-amber-600');
                            loginMsg.classList.add('text-green-700');
                        }
                        const box = document.getElementById('clientInfoAjax');
                        if (box) {
                            const u = data.user || {};
                            document.getElementById('clientFirst')?.append(document.createTextNode(u
                                .first_name || ''));
                            document.getElementById('clientLast')?.append(document.createTextNode(u
                                .last_name || ''));
                            document.getElementById('clientEmail')?.append(document.createTextNode(u
                                .email || ''));
                            box.classList.remove('hidden');
                        }
                        loginFormEl.classList.add('hidden');
                        // تأكد من عدم إظهار رابط/زر تبديل الحساب في وضع AJAX
                        document.getElementById('toggleLogout')?.classList.add('hidden');
                        document.getElementById('logoutInline')?.classList.add('hidden');
                        enableOrderIfValid();
                    } catch {
                        if (loginMsg) loginMsg.textContent = 'خطأ في الاتصال بالخادم.';
                    } finally {
                        btn?.classList.remove('opacity-50', 'cursor-not-allowed');
                        btn.disabled = false;
                        inputs.forEach(i => i.readOnly = false);
                    }
                });
            }

            // اضبط الشريط على الخطوة الأولى افتراضيًا
            goto(0);

            // إذا تم تمرير domain عبر الاستعلام أو محفوظ محلياً (لتجربة تسجيل الدخول)، فعّل المراجعة مباشرة
            (async () => {
                const qp = new URLSearchParams(window.location.search);
                const qDomain = (qp.get('domain') || '').trim().toLowerCase();
                const qOpt = (qp.get('domain_option') || 'register').toLowerCase();
                const saved = HAS_TEMPLATE ? readPrimarySelection() : null;
                if (qDomain) {
                    try {
                        const cents = await fetchServerPriceCents(qDomain, qOpt);
                        activateTab(mapOptionToTab(qOpt));
                        setReview(qDomain, cents);
                        updateDomainFieldsFromSelection(qOpt, qDomain, cents);
                        try {
                            setCartDomains([{
                                domain: qDomain,
                                item_option: qOpt,
                                price_cents: cents
                            }]);
                        } catch {}
                        savePrimarySelection({
                            domain: qDomain,
                            item_option: qOpt,
                            price_cents: cents
                        });
                        goto(1);
                    } catch {}
                } else if (saved) {
                    // استعادة الاختيار بعد تسجيل الدخول أو تحديث الصفحة
                    setReview(saved.domain, Number(saved.price_cents || 0));
                    updateDomainFieldsFromSelection(saved.item_option || 'register', saved.domain, Number(
                        saved.price_cents || 0));
                    try {
                        setCartDomains([saved]);
                    } catch {}
                    goto(1);
                } else if (!HAS_TEMPLATE && window.location.search.includes('review=1')) {
                    goto(1);
                }
            })();

            // استيراد أي دومين محفوظ مسبقًا من السلة (لـ تدفّق الدومينات فقط)
            if (!HAS_TEMPLATE) try {
                const srv = Array.isArray(SERVER_CART_ITEMS) ? SERVER_CART_ITEMS : [];
                const srvMapped = srv.map(it => ({
                    domain: String((it && (it.domain || '')) || '').toLowerCase(),
                    item_option: it?.item_option ?? it?.option ?? 'register',
                    price_cents: Number(it?.price_cents) || 0,
                })).filter(it => it.domain);

                const localList = dedupeDomains(domainOnly(readUnifiedCart()));
                // دمج: نعطي أولوية للأحدث عبر اختيار آخر عنصر من المصفوفة المدموجة
                const merged = [...srvMapped, ...(Array.isArray(localList) ? localList : [])];
                if (merged.length) {
                    // فعّل تبويب حسب أول عنصر، واعرض الجميع
                    const first = merged[0];
                    activateTab(mapOptionToTab(first.item_option));
                    setCartDomains(merged);
                    goto(1);
                }
            } catch {}

            // زر إفراغ السلة
            document.getElementById('btnClearCart')?.addEventListener('click', async () => {
                if (!confirm('هل تريد إفراغ السلة؟')) return;
                try {
                    // نظّف التخزين المحلي لعناصر الدومين فقط
                    try {
                        const unified = readUnifiedCart() || [];
                        const leftovers = unified.filter(it => !(it && (it.kind === 'domain' || (it
                            .kind == null && it.domain))));
                        writeUnifiedCart(leftovers);
                    } catch {}
                    // نظّف جلسة السيرفر
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                        'content') || '';
                    await fetch(`{{ url('/cart/clear') }}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        }
                    });
                } catch {}
                // امسح أي اختيار محفوظ للدومين الأساسي (في تدفّق القالب)
                try {
                    clearPrimarySelection();
                } catch {}
                // أفرغ عرض الدومينات تمامًا
                setCartDomains([]);
                // صفّر الحقول المخفية والملخص
                try {
                    const form = document.getElementById('checkoutForm');
                    form?.querySelectorAll('input[name^="items["]').forEach(n => n.remove());
                    const od = document.getElementById('orderDomainInput');
                    if (od) od.value = '';
                    const op = form?.querySelector('input[name="domain_option"]');
                    if (op) op.value = '';
                    const pc = form?.querySelector('input[name="domain_price_cents"]');
                    if (pc) pc.value = '';
                    if (summaryDomain) summaryDomain.textContent = '—';
                    if (reviewDomain) reviewDomain.textContent = '—';
                    const priceCell = document.getElementById('reviewDomainPrice');
                    if (priceCell) priceCell.textContent = fmt(0);
                    updateTotals(0);
                    enableOrderIfValid();
                } catch {}
                // وجّه المستخدم بحسب السياق السابق
                try {
                    const ref = document.referrer || '';
                    if (ref && /\/templates\//.test(ref)) {
                        window.location.href = ref; // العودة لصفحة القالب السابقة إن وُجدت
                        return;
                    }
                } catch {}
                if (HAS_TEMPLATE) {
                    // كان في تدفّق القالب: وجّه لقائمة القوالب
                    window.location.href = '/templates';
                } else {
                    // تدفّق الدومينات فقط: وجّه للصفحة الرئيسية
                    window.location.href = '{{ url('/') }}';
                }
            });

            // زر حذف القالب: يخفي صف القالب ويجعل إجمالي القالب = 0 ويُحدّث الإجماليات، ويحوّل مسار الإرسال لدومينات فقط
            document.getElementById('btnRemoveTemplate')?.addEventListener('click', () => {
                document.querySelectorAll('.rv-template-info').forEach(el => el.classList.add('hidden'));
                document.querySelector('.rv-template-row')?.remove();
                TEMPLATE_CENTS = 0;
                // عدّل مسار الفورم إلى معالجة سلة الدومينات فقط
                const form = document.getElementById('checkoutForm');
                if (form) form.action = "{{ route('checkout.cart.process') }}";
                // أعِد حساب الإجماليات وفق الدومينات المعروضة
                try {
                    const rows = Array.from(document.querySelectorAll(
                        '#reviewDomainsBody .rv-domain-row .rv-price'));
                    const sum = rows.reduce((t, cell) => t + Math.round(Number((cell.textContent || '0')
                        .replace(/[^0-9.]/g, '') * 100)), 0);
                    updateTotals(sum);
                } catch {
                    /* ignore */
                }
                // أظهر دعوة اختيار قالب بعد الحذف وحدث الرابط بالدومين الحالي
                const box = document.getElementById('chooseTemplateAfterRemove');
                if (box) box.classList.remove('hidden');
                try {
                    updateChooseTemplateLink();
                } catch {}
            });

            // زر رجوع
            document.getElementById('backToDomain2')?.addEventListener('click', () => goto(0));

            // جعل الدوائر (الستبر) قابلة للنقر مع حماية تجربة المستخدم
            stepper?.querySelectorAll('.step').forEach((s, i) => {
                s.classList.add('cursor-pointer');
                s.addEventListener('click', () => {
                    if (i === 0) return goto(0);
                    const domainPicked = (reviewDomain?.textContent || '').trim();
                    if (domainPicked && domainPicked !== '—') {
                        goto(1);
                    } else {
                        // بدون تنبيه مزعج؛ أبقِه على الخطوة الأولى
                        goto(0);
                    }
                });
            });

            // تمكين زر إتمام الطلب عند تحقق الشروط
            const placeOrderReal = document.getElementById('placeOrderReal');

            function enableOrderIfValid() {
                if (!placeOrderReal) return;
                const agree = document.getElementById('agreeTos');
                const domain = (document.getElementById('orderDomainInput')?.value || '').trim();
                // في حالة وجود قالب: يجب اختيار دومين أساسي
                const needPrimary = HAS_TEMPLATE;
                const domainOk = !needPrimary || (domain && domain !== '—');
                const total = (document.getElementById('sumTotal2')?.textContent || '').trim();
                placeOrderReal.disabled = !(agree && agree.checked && domainOk && total);
                placeOrderReal.classList.toggle('opacity-50', placeOrderReal.disabled);
                placeOrderReal.classList.toggle('cursor-not-allowed', placeOrderReal.disabled);
                const orderDomainInput = document.getElementById('orderDomainInput');
                if (orderDomainInput) orderDomainInput.value = domain;
                if (orderTotalInp) orderTotalInp.value = total;
            }
            document.getElementById('agreeTos')?.addEventListener('input', enableOrderIfValid);
            document.getElementById('reviewDomain')?.addEventListener('DOMSubtreeModified', enableOrderIfValid);
            document.getElementById('sumTotal2')?.addEventListener('DOMSubtreeModified', enableOrderIfValid);
            enableOrderIfValid();

            // عند الضغط على تبويب التسجيل، تأكد من استنساخ الحقول للفورم النهائي
            document.getElementById('btn-register')?.addEventListener('click', function() {
                try {
                    ensureRegisterFields();
                } catch {}
            });
            document.querySelectorAll('#register-form input').forEach(function(input) {
                input.addEventListener('input', function() {
                    const box = document.getElementById('registerFieldsBox');
                    if (!box) return;
                    const hidden = box.querySelector(`[name="${input.name}"]`);
                    if (hidden) hidden.value = input.value;
                });
            });

            // إرسال الطلب عبر AJAX
            document.getElementById('checkoutForm')?.addEventListener('submit', function(e) {
                e.preventDefault();
                const form = this;
                try {
                    ensureRegisterFields();
                } catch {}
                try {
                    updateDomainFieldsFromSelection(
                        form.querySelector('input[name="domain_option"]')?.value || 'register',
                        form.querySelector('input[name="domain"]')?.value || '',
                        Number(form.querySelector('input[name="domain_price_cents"]')?.value || 0)
                    );
                } catch {}
                const data = new FormData(form);
                fetch(form.action, {
                        method: 'POST',
                        body: data,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(r => r.json())
                    .then(response => {
                        if (response.success) {
                            // تنظيف سلة الدومينات فقط
                            try {
                                const unified = readUnifiedCart();
                                const leftovers = unified.filter(it => !(it && (it.kind === 'domain' ||
                                    (it.kind == null && it.domain))));
                                writeUnifiedCart(leftovers);
                                localStorage.removeItem('palgoals_cart_domains'); // القديم
                                clearPrimarySelection();
                            } catch {}
                            if (response.redirect) {
                                window.location.href = response.redirect;
                                return;
                            }
                            window.location.hash = '#view-success';
                            showSuccess();
                            fillSuccessInvoice({
                                order_no: response.order_no || '—',
                                domain: response.domain || '—',
                                template_name: response.template_name || '',
                                domain_price: response.domain_price || '',
                                template_price_html: response.template_price_html || '',
                                discount: response.discount || '',
                                tax: response.tax || '',
                                total: response.total || '—'
                            });
                            if (response.client_name) {
                                const m = document.getElementById('sx-success-msg');
                                if (m) m.textContent = 'تم إنشاء الطلب بنجاح يا ' + response
                                    .client_name;
                            }
                        } else if (response.errors) {
                            alert('حدث خطأ: ' + (Array.isArray(response.errors) ? response.errors.join(
                                '\n') : response.errors));
                        }
                    })
                    .catch(() => alert('حدث خطأ أثناء معالجة الطلب. حاول مرة أخرى.'));
            });

            // كوبونات (تجريبيًا)
            (function() {
                const applyBtn = document.getElementById('applyCoupon');
                const couponInput = document.getElementById('couponInput');
                const couponMsg = document.getElementById('couponMsg');

                function computeDiscount(code, base) {
                    const c = (code || '').trim().toUpperCase();
                    if (!c) return 0;
                    if (c === 'PROMO10') return Math.round(base * 0.10);
                    if (c === 'WELCOME20') return 2000;
                    if (c === 'FREE') return base;
                    return 0;
                }
                applyBtn?.addEventListener('click', () => {
                    const baseCents = Math.round(parseFloat((reviewDomainPrice?.textContent || '0')
                        .replace(/[^0-9.]/g, '')) * 100) || 0;
                    const d = computeDiscount(couponInput?.value, baseCents);
                    window.__couponDiscountCents = Math.min(d, baseCents);
                    if (couponMsg) couponMsg.textContent = d > 0 ? 'تم تطبيق الخصم بنجاح ✅' :
                        'الكود غير صالح أو منتهي ❌';
                    updateTotals(baseCents);
                });
            })();

            // تبديل بوابة الدفع (عرض فقط)
            (function() {
                const gwRadios = document.querySelectorAll('input[name="gateway"]');
                const cardForm = document.getElementById('cardForm');
                const bankForm = document.getElementById('bankForm');
                const agreeTos = document.getElementById('agreeTos');

                function setGateway(v) {
                    if (v === 'card') {
                        cardForm?.classList.remove('hidden');
                        bankForm?.classList.add('hidden');
                    } else {
                        bankForm?.classList.remove('hidden');
                        cardForm?.classList.add('hidden');
                    }
                }
                gwRadios.forEach(r => r.addEventListener('change', () => setGateway(document.querySelector(
                    'input[name="gateway"]:checked')?.value)));
                setGateway('card');
                agreeTos?.addEventListener('input', () => {
                    /* keep validation on */
                });
            })();
        });

        // طباعة/نجاح على إعادة التحميل
        document.getElementById('sx-print')?.addEventListener('click', () => {
            const logo = document.querySelector('.print-logo');
            if (logo) logo.style.display = 'block';
            window.print();
            setTimeout(() => {
                if (logo) logo.style.display = 'none';
            }, 500);
        });
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success') === '1' || window.location.hash === '#view-success') {
                showSuccess();
                fillSuccessInvoice({
                    order_no: urlParams.get('order_no') || '—',
                    domain: urlParams.get('domain') || '—',
                    template_name: urlParams.get('template_name') || '',
                    domain_price: urlParams.get('domain_price') || '',
                    template_price: urlParams.get('template_price') || '',
                    discount: urlParams.get('discount') || '',
                    tax: urlParams.get('tax') || '',
                    total: (urlParams.get('total') ? decodeURIComponent(urlParams.get('total')) : '—')
                });
                const clientName = urlParams.get('client_name');
                if (clientName) {
                    const m = document.getElementById('sx-success-msg');
                    if (m) m.textContent = 'تم إنشاء الطلب بنجاح يا ' + decodeURIComponent(clientName);
                }
            }
        });
    </script>

    <style>
        @media print {
            body * {
                visibility: hidden !important;
            }

            .invoice-print-area,
            .invoice-print-area * {
                visibility: visible !important;
            }

            .invoice-print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100vw;
                min-height: 100vh;
                background: #fff !important;
                box-shadow: none !important;
            }

            .not-print {
                display: none !important;
            }

            .invoice-table-print {
                box-shadow: none !important;
                border: 2px solid #240B36 !important;
            }

            .print-logo {
                display: block !important;
                margin-bottom: 2rem !important;
            }

            .invoice-print-area h2 {
                margin-top: 0 !important;
            }
        }
    </style>


    {{-- <livewire:checkout-client :template_id="$template_id" /> --}}
</x-template.layouts.index-layouts>
