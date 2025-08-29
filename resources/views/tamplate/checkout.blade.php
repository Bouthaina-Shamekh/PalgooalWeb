@php
    use Carbon\Carbon;
    $shortDesc = Str::limit(strip_tags($translation?->description ?? ''), 160);
    $basePrice = (float) ($template->price ?? 0);
    $discRaw = $template->discount_price;
    $discPrice = is_null($discRaw) ? null : (float) $discRaw;
    $hasDiscount = !is_null($discPrice) && $discPrice > 0 && $discPrice < $basePrice;
    $endsAt = $hasDiscount && !empty($template->discount_ends_at) ? Carbon::parse($template->discount_ends_at) : null;
    $discountExpired = false;
    if ($hasDiscount && $endsAt) {
        $discountExpired = $endsAt->isPast();
    }
    $showDiscount = $hasDiscount && !$discountExpired;
    $finalPrice = $showDiscount ? $discPrice : $basePrice;
    $discountPerc = $showDiscount && $basePrice > 0 ? (int) round((($basePrice - $discPrice) / $basePrice) * 100) : 0;
@endphp
<x-template.layouts.index-layouts
    title="{{ t('Frontend.Checkout', 'Checkout') }} - {{ t('Frontend.Palgoals', 'Palgoals') }}"
    description="{{ $shortDesc }}" keywords="خدمات حجز دومين , افضل شركة برمجيات , استضافة مواقع , ..."
    ogImage="{{ asset('assets/dashboard/images/logo-white.svg') }}">

    <!-- ===== شريط الخطوات (خطوتان) ===== -->
    <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-10 mt-6">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow p-4">
            <div id="globalStepper" class="flex items-center justify-between gap-2">
                <!-- Step 1 -->
                <div class="flex items-center gap-3 step" data-index="0">
                    <div
                        class="h-9 w-9 rounded-full grid place-items-center border-2 border-[#240B36] text-[#240B36] font-extrabold step-circle">
                        1</div>
                    <div class="text-sm">حجز الدومين</div>
                </div>
                <div class="h-0.5 flex-1 bg-gray-200 dark:bg-gray-700"></div>
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
                    <button data-tab="register" aria-selected="true"
                        class="px-4 py-2 rounded-xl border border-[#240B36]/30 text-[#240B36] bg-white dark:bg-gray-900 hover:bg-gray-50 hover:border-[#240B36]/50 dark:hover:bg-gray-800 transition-colors">
                        تسجيل جديد
                    </button>
                    <button data-tab="transfer"
                        class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 hover:border-[#240B36]/40 transition-colors">
                        نقل نطاق
                    </button>
                    <button data-tab="owndomain"
                        class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 hover:border-[#240B36]/40 transition-colors">
                        أمتلك نطاقاً
                    </button>
                    <button data-tab="subdomain"
                        class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 hover:border-[#240B36]/40 transition-colors">
                        Subdomain مجاني
                    </button>
                </div>

                <!-- Register -->
                <form id="tab-register" class="space-y-4" role="tabpanel" method="POST"
                    action="{{ route('checkout.process', $template_id) }}">
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
                </form>

                <!-- Transfer -->
                <form id="tab-transfer" class="space-y-4 hidden" role="tabpanel" method="POST"
                    action="{{ route('checkout.process', $template_id) }}">
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
                </form>

                <!-- Own Domain -->
                <form id="tab-owndomain" class="space-y-4 hidden" role="tabpanel" method="POST"
                    action="{{ route('checkout.process', $template_id) }}">
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
                </form>

                <!-- Subdomain (مجاني) -->
                <form id="tab-subdomain" class="space-y-4 hidden" role="tabpanel" method="POST"
                    action="{{ route('checkout.process', $template_id) }}">
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
                </form>
            </div>

            <!-- ملخص جانبي -->
            <aside
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow p-6 h-max">
                <h3 class="font-bold mb-3">ملخص سريع</h3>
                <ul class="space-y-2 text-sm">
                    <li class="flex justify-between"><span>القالب</span><span
                            class="font-semibold">{{ $translation && $translation->name ? $translation->name : ($template && $template->name ? $template->name : '—') }}</span>
                    </li>
                    <li class="flex justify-between"><span>مدة الاشتراك</span><span class="font-semibold">12
                            شهر</span></li>
                    <li class="flex justify-between"><span>سعر القالب</span><span class="font-semibold">
                            @if ($showDiscount)
                                <span class="line-through text-gray-400">${{ number_format($basePrice, 2) }}</span>
                                <span class="text-red-600 font-bold ms-2">${{ number_format($discPrice, 2) }}</span>
                            @else
                                ${{ number_format($basePrice, 2) }}
                            @endif
                        </span></li>
                    <li class="flex justify-between"><span>الدومين</span><span id="summaryDomain"
                            class="font-semibold">—</span>
                    </li>
                </ul>
                <hr class="my-4 border-gray-200 dark:border-gray-800" />
                <div class="flex justify-between font-bold"><span>الإجمالي التقديري</span><span id="summaryTotal">
                        @if ($showDiscount)
                            <span class="line-through text-gray-400">${{ number_format($basePrice, 2) }}</span>
                            <span class="text-red-600 font-bold ms-2">${{ number_format($discPrice, 2) }}</span>
                        @else
                            ${{ number_format($basePrice, 2) }}
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
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                            <tr>
                                <th class="text-right p-3">البند</th>
                                <th class="text-right p-3">المدة</th>
                                <th class="text-right p-3">السعر</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-t border-gray-200 dark:border-gray-800">
                                <td class="p-3">تسجيل نطاق <span id="reviewDomain">example.com</span></td>
                                <td class="p-3">12 شهر</td>
                                <td class="p-3" id="reviewDomainPrice">0</td>
                            </tr>
                            <tr class="border-t border-gray-200 dark:border-gray-800">
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
                            </tr>
                        </tbody>
                    </table>
                </div>

                @if (!auth('client')->check())
                    <!-- تبديل الدخول/التسجيل -->
                    <div
                        class="inline-flex rounded-xl bg-gray-50 dark:bg-gray-900 p-1 mb-6 shadow border border-gray-200 dark:border-gray-700 gap-2">
                        <button id="btn-login" type="button"
                            class="px-5 py-1.5 rounded-xl text-sm font-bold bg-white dark:bg-gray-900 text-[#240B36] border border-transparent hover:bg-[#240B36] hover:text-white focus:outline-none focus:ring-2 focus:ring-[#240B36]/30 shadow-sm">
                            دخول العميل
                        </button>
                        <button id="btn-register" type="button"
                            class="px-5 py-1.5 rounded-xl text-sm font-bold bg-white dark:bg-gray-900 text-[#240B36] border border-transparent hover:bg-[#240B36] hover:text-white focus:outline-none focus:ring-2 focus:ring-[#240B36]/30">
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
                        <form method="POST" action="{{ route('client.logout') }}" style="display:inline">
                            @csrf
                            <button type="submit"
                                class="text-sm text-red-700 underline hover:text-red-900 font-bold bg-transparent border-0 p-0 cursor-pointer">تسجيل
                                بحساب آخر</button>
                        </form>
                    </div>
                @else
                    <!-- نموذج الدخول -->

                    <form id="login-form" class=" mb-6" method="POST" action="{{ route('login.store') }}">
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
                    </form>
                    <!-- نموذج التسجيل -->
                    <form id="register-form" class="space-y-6 mb-6" onsubmit="return false;">
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
                    action="{{ route('checkout.process', ['template_id' => $template_id]) }}">
                    @csrf
                    <input type="hidden" name="domain" id="orderDomainInput" value="">
                    <input type="hidden" name="total" id="orderTotalInput" value="">
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
                        @if ($showDiscount)
                            <span class="line-through text-gray-400">${{ number_format($basePrice, 2) }}</span>
                            <span class="text-red-600 font-bold ms-2">${{ number_format($discPrice, 2) }}</span>
                        @else
                            ${{ number_format($basePrice, 2) }}
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
        // منطق جديد: أزرار المتابعة فقط تنقل للخطوة الثانية وتخزن بيانات الدومين مؤقتاً
        document.addEventListener('DOMContentLoaded', function() {
            // متغيرات لتخزين بيانات الدومين المختار مؤقتاً
            let selectedDomainOption = '';
            let selectedDomain = '';

            function updateDomainFields() {
                const finalForm = document.getElementById('checkoutForm');
                if (!finalForm) return;
                // domain_option
                let inputOption = finalForm.querySelector('input[name="domain_option"]');
                if (!inputOption) {
                    inputOption = document.createElement('input');
                    inputOption.type = 'hidden';
                    inputOption.name = 'domain_option';
                    finalForm.appendChild(inputOption);
                }
                inputOption.value = selectedDomainOption;
                // domain
                let inputDomain = finalForm.querySelector('input[name="domain"]');
                if (!inputDomain) {
                    inputDomain = document.createElement('input');
                    inputDomain.type = 'hidden';
                    inputDomain.name = 'domain';
                    finalForm.appendChild(inputDomain);
                }
                inputDomain.value = selectedDomain;
            }
            // expose for external handlers (AJAX submit) to call before FormData creation
            window.updateDomainFields = updateDomainFields;

            // تسجيل جديد
            const btnR = document.getElementById('goConfigR');
            if (btnR) {
                btnR.addEventListener('click', function(e) {
                    const form = btnR.closest('form');
                    let domain = form.querySelector('input[aria-label="اسم النطاق"]').value;
                    let tld = form.querySelector('select').value;
                    let fullDomain = domain + tld;
                    selectedDomainOption = 'register';
                    selectedDomain = fullDomain;
                    updateDomainFields(); // تحديث الحقول المخفية
                    // انتقل للخطوة الثانية فقط
                    const p = priceMap[tld] ?? 1000;
                    setReview(fullDomain, p);
                    goto(1);
                });
            }
            // نقل نطاق
            const btnT = document.getElementById('goConfigT');
            if (btnT) {
                btnT.addEventListener('click', function(e) {
                    const form = btnT.closest('form');
                    let domain = form.querySelector('input[aria-label="اسم النطاق"]').value;
                    selectedDomainOption = 'transfer';
                    selectedDomain = domain;
                    updateDomainFields();
                    const tld = domain.includes('.') ? `.${domain.split('.').pop()}` : '.com';
                    const p = priceMap[tld] ?? 1000;
                    setReview(domain, p);
                    goto(1);
                });
            }
            // أمتلك نطاقاً
            const btnO = document.getElementById('goConfigO');
            if (btnO) {
                btnO.addEventListener('click', function(e) {
                    const form = btnO.closest('form');
                    let domain = form.querySelector('input[aria-label="اسم النطاق"]').value;
                    selectedDomainOption = 'own';
                    selectedDomain = domain;
                    updateDomainFields();
                    setReview(domain, 0);
                    goto(1);
                });
            }
            // Subdomain مجاني
            const btnS = document.getElementById('goConfigS');
            if (btnS) {
                btnS.addEventListener('click', function(e) {
                    const form = btnS.closest('form');
                    let sub = form.querySelector('input[aria-label="اسم الساب-دومين"]').value;
                    let main = form.querySelector('select').value;
                    let fullDomain = sub + '.' + main;
                    selectedDomainOption = 'subdomain';
                    selectedDomain = fullDomain;
                    updateDomainFields();
                    setReview(fullDomain, 0);
                    goto(1);
                });
            }

            // عند الضغط على زر إتمام الطلب: أضف القيم المخزنة إلى الفورم النهائي قبل الإرسال
            const placeOrderReal = document.getElementById('placeOrderReal');
            if (placeOrderReal) {
                placeOrderReal.addEventListener('click', function(e) {
                    try {
                        updateDomainFields();
                    } catch (err) {}
                });

                placeOrderReal.closest('form').addEventListener('submit', function(e) {
                    updateDomainFields(); // تأكد دائماً من تحديث القيم قبل الإرسال
                });
            }
        });
        // تحسين الطباعة: إظهار الشعار وإخفاء العناصر غير الضرورية
        const printBtn = document.getElementById('sx-print');
        if (printBtn) {
            printBtn.addEventListener('click', function() {
                // إظهار الشعار مؤقتاً للطباعة
                const logo = document.querySelector('.print-logo');
                if (logo) logo.style.display = 'block';
                window.print();
                setTimeout(() => {
                    if (logo) logo.style.display = 'none';
                }, 500);
            });
        }
        // دالة تعبئة ملخص الفاتورة في شاشة النجاح
        function fillSuccessInvoice(data) {
            const body = document.getElementById('sx-invoice-body');
            if (!body) return;
            // عرض نفس بنود ملخص الطلب
            let html = '';
            if (data.order_no) {
                html +=
                    `<tr class='bg-gray-50 dark:bg-gray-800'><td class="py-3 px-4 flex items-center gap-2"><span class='inline-block w-5 text-green-600'>#</span>رقم الطلب</td><td class="py-3 px-4 font-bold">${data.order_no}</td></tr>`;
            }
            if (data.template_name) {
                html +=
                    `<tr><td class="py-3 px-4 flex items-center gap-2"><span class='inline-block w-5 text-blue-500'>🎨</span>القالب</td><td class="py-3 px-4">${data.template_name}</td></tr>`;
            }
            html +=
                `<tr><td class="py-3 px-4 flex items-center gap-2"><span class='inline-block w-5 text-yellow-500'>⏳</span>مدة الاشتراك</td><td class="py-3 px-4">12 شهر</td></tr>`;
            // إذا كان هناك template_price_html من الباكند استخدمه مباشرة
            if (data.template_price_html) {
                html +=
                    `<tr><td class="py-3 px-4 flex items-center gap-2"><span class='inline-block w-5 text-purple-500'>💼</span>سعر القالب</td><td class="py-3 px-4">${data.template_price_html}</td></tr>`;
            } else if (data.template_base_price && data.template_discount_price) {
                html +=
                    `<tr><td class="py-3 px-4 flex items-center gap-2"><span class='inline-block w-5 text-purple-500'>💼</span>سعر القالب</td><td class="py-3 px-4"><span class='line-through text-gray-400'>${data.template_base_price}</span> <span class='text-red-600 font-bold ms-2'>${data.template_discount_price}</span></td></tr>`;
            } else if (data.template_price) {
                html +=
                    `<tr><td class="py-3 px-4 flex items-center gap-2"><span class='inline-block w-5 text-purple-500'>💼</span>سعر القالب</td><td class="py-3 px-4">${data.template_price}</td></tr>`;
            } else if (data.template_base_price) {
                html +=
                    `<tr><td class="py-3 px-4 flex items-center gap-2"><span class='inline-block w-5 text-purple-500'>💼</span>سعر القالب</td><td class="py-3 px-4">${data.template_base_price}</td></tr>`;
            } else if (data.template_discount_price) {
                html +=
                    `<tr><td class="py-3 px-4 flex items-center gap-2"><span class='inline-block w-5 text-purple-500'>💼</span>سعر القالب</td><td class="py-3 px-4">${data.template_discount_price}</td></tr>`;
            } else {
                html +=
                    `<tr><td class="py-3 px-4 flex items-center gap-2"><span class='inline-block w-5 text-purple-500'>💼</span>سعر القالب</td><td class="py-3 px-4">—</td></tr>`;
            }
            if (data.domain) html +=
                `<tr><td class="py-3 px-4 flex items-center gap-2"><span class='inline-block w-5 text-indigo-500'>🌐</span>الدومين</td><td class="py-3 px-4">${data.domain}</td></tr>`;
            if (data.domain_price) html +=
                `<tr><td class="py-3 px-4 flex items-center gap-2"><span class='inline-block w-5 text-pink-500'>💲</span>سعر الدومين</td><td class="py-3 px-4">${data.domain_price}</td></tr>`;
            if (data.discount) html +=
                `<tr><td class="py-3 px-4 flex items-center gap-2"><span class='inline-block w-5 text-green-700'>🏷️</span>الخصم</td><td class="py-3 px-4 text-green-700">-${data.discount}</td></tr>`;
            if (data.tax) html +=
                `<tr><td class="py-3 px-4 flex items-center gap-2"><span class='inline-block w-5 text-orange-500'>🧾</span>الضريبة</td><td class="py-3 px-4">${data.tax}</td></tr>`;
            if (data.total) html +=
                `<tr class='bg-green-50 dark:bg-green-900 font-extrabold text-lg'><td class="py-4 px-4 flex items-center gap-2"><span class='inline-block w-5 text-green-700'>✔️</span>الإجمالي المستحق</td><td class="py-4 px-4 text-green-700">${data.total}</td></tr>`;
            body.innerHTML = html;
        }
        // إرسال الطلب عبر AJAX ليظهر شاشة النجاح مباشرة مع حماية إضافية
        document.getElementById('checkoutForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            // ensure hidden domain fields are up-to-date before building FormData
            try {
                if (window.updateDomainFields) window.updateDomainFields();
            } catch (err) {}
            const data = new FormData(form);
            fetch(form.action, {
                    method: 'POST',
                    body: data,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(r => {
                    return r.json();
                })
                .then(response => {
                    if (response.success) {
                        // ضع hash في الرابط ليبقى المستخدم في شاشة النجاح حتى لو أعاد تحميل الصفحة
                        window.location.hash = '#view-success';
                        showSuccess();
                        // تعبئة ملخص الفاتورة الاحترافي
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
                        // إظهار اسم العميل في الرسالة
                        if (response.client_name) {
                            document.getElementById('sx-success-msg').textContent = 'تم إنشاء الطلب بنجاح يا ' +
                                response.client_name;
                        }
                    } else if (response.errors) {
                        alert('حدث خطأ: ' + (Array.isArray(response.errors) ? response.errors.join('\n') :
                            response.errors));
                    }
                })
                .catch((err) => {
                    alert('حدث خطأ أثناء معالجة الطلب. حاول مرة أخرى.');
                });
        });

        // عند تحميل الصفحة: إذا كان success=1 في الرابط أو hash=#view-success، أظهر شاشة النجاح تلقائياً
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success') === '1' || window.location.hash === '#view-success') {
                showSuccess();
                // إذا كان هناك بيانات الطلب في الرابط، عبئها
                const orderNo = urlParams.get('order_no');
                const domain = urlParams.get('domain');
                const total = urlParams.get('total');
                const clientName = urlParams.get('client_name');
                // تعبئة ملخص الفاتورة الاحترافي عند إعادة تحميل الصفحة
                fillSuccessInvoice({
                    order_no: orderNo || '—',
                    domain: domain || '—',
                    template_name: urlParams.get('template_name') || '',
                    domain_price: urlParams.get('domain_price') || '',
                    template_price: urlParams.get('template_price') || '',
                    discount: urlParams.get('discount') || '',
                    tax: urlParams.get('tax') || '',
                    total: total ? decodeURIComponent(total) : '—'
                });
                if (clientName) document.getElementById('sx-success-msg').textContent = 'تم إنشاء الطلب بنجاح يا ' +
                    decodeURIComponent(clientName);
            }
        });
        // عند اختيار زر "إنشاء حساب جديد"، انسخ الحقول من نموذج التسجيل إلى فورم الطلب
        document.getElementById('btn-register')?.addEventListener('click', function() {
            const regForm = document.getElementById('register-form');
            const box = document.getElementById('registerFieldsBox');
            if (!regForm || !box) return;
            box.innerHTML = '';
            // انسخ الحقول مع القيم
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
        });

        // عند تغيير أي حقل في نموذج التسجيل، حدث الحقل المنسوخ في فورم الطلب
        document.querySelectorAll('#register-form input').forEach(function(input) {
            input.addEventListener('input', function() {
                const box = document.getElementById('registerFieldsBox');
                if (!box) return;
                const hidden = box.querySelector(`[name="${input.name}"]`);
                if (hidden) hidden.value = input.value;
            });
        });
        const USD = true;
        const priceMap = {
            '.com': 1000,
            '.net': 1200,
            '.org': 1100
        };
        const fmt = c => (USD ? `$${(c / 100).toFixed(2)}` : `${(c / 100).toFixed(2)} ر.س`);

        // تبويبات الدومين
        const tabs = document.querySelectorAll('[data-tab]');
        const panels = {
            register: document.getElementById('tab-register'),
            transfer: document.getElementById('tab-transfer'),
            owndomain: document.getElementById('tab-owndomain'),
            subdomain: document.getElementById('tab-subdomain')
        };

        tabs.forEach(btn => {
            // hover + cursor
            btn.classList.add('cursor-pointer', 'hover:bg-gray-50', 'dark:hover:bg-gray-800',
                'hover:border-[#240B36]/40', 'transition-colors');

            btn.addEventListener('click', () => {
                // reset all tabs styles
                tabs.forEach(b => {
                    b.classList.remove('border-[#240B36]', 'text-[#240B36]');
                    b.classList.add('border-gray-300', 'dark:border-gray-700', 'text-gray-700',
                        'dark:text-gray-200');
                });
                // activate clicked tab
                btn.classList.add('border-[#240B36]', 'text-[#240B36]');
                // switch panels safely
                Object.values(panels).forEach(p => p && p.classList.add('hidden'));
                const panel = panels[btn.dataset.tab];
                if (panel) panel.classList.remove('hidden');

                // عند تغيير التبويب: إذا كان نقل نطاق أو أمتلك نطاق أو ساب دومين، صفر سعر الدومين في الملخص
                if (btn.dataset.tab === 'transfer' || btn.dataset.tab === 'owndomain' || btn.dataset.tab ===
                    'subdomain') {
                    setReview('—', 0);
                } else if (btn.dataset.tab === 'register') {
                    // أعد السعر الافتراضي
                    const p = priceMap[regTld.value] ?? 1000;
                    setReview('—', p);
                }
            });
        });

        // عناصر وأسعار
        const regSld = document.querySelector('#tab-register input[aria-label="اسم النطاق"]');
        const regTld = document.querySelector('#tab-register select[aria-label="الامتداد"]');
        const tldPrice = document.getElementById('tldPrice');
        const btnCheck = document.getElementById('btnCheck');
        const checkResult = document.getElementById('checkResult');
        const summaryDomain = document.getElementById('summaryDomain');
        const summaryTotal = document.getElementById('summaryTotal');
        const reviewDomain = document.getElementById('reviewDomain');
        const reviewDomainPrice = document.getElementById('reviewDomainPrice');
        const sumSub = document.getElementById('sumSub');
        const sumTax = document.getElementById('sumTax');
        const sumTotal2 = document.getElementById('sumTotal2');

        // تحديث الإجمالي ليشمل دومين + القالب دائماً
        function updateTotals(domainCents) {
            // استخدم سعر القالب بعد الخصم (finalPrice)
            const templateFinalPrice = {{ (int) (($finalPrice ?? 0) * 100) }};
            const subtotal = templateFinalPrice + (domainCents | 0);
            const tax = 0;
            const total = subtotal + tax;
            sumSub.textContent = fmt(subtotal);
            sumTax.textContent = fmt(tax);
            sumTotal2.textContent = fmt(total);
            summaryTotal.textContent = fmt(total);
        }

        function setReview(domain, cents) {
            summaryDomain.textContent = domain || '—';
            reviewDomain.textContent = domain || '—';
            reviewDomainPrice.textContent = fmt(cents);
            updateTotals(cents);
        }

        // تحديث السعر عند تغيير الامتداد
        regTld?.addEventListener('change', () => {
            const p = priceMap[regTld.value] ?? 1000;
            tldPrice.textContent = `${fmt(p)}/سنة`;
        });
        // set initial price on load
        if (tldPrice && regTld) {
            const p0 = priceMap[regTld.value] ?? 1000;
            tldPrice.textContent = `${fmt(p0)}/سنة`;
            // عند أول تحميل: أضف دومين + القالب للإجمالي
            updateTotals(p0);
        }
        btnCheck?.addEventListener('click', () => {
            const sld = (regSld?.value || '').trim();
            const tld = (regTld?.value || '.com').trim();
            if (!sld) {
                checkResult.textContent = 'رجاءً أدخل اسم النطاق أولاً';
                return;
            }
            const fqdn = `${sld}${tld}`;
            const price = priceMap[tld] ?? 1000;
            checkResult.textContent = 'الدومين متاح 🎉';
            tldPrice.textContent = `${fmt(price)}/سنة`;
            setReview(fqdn, price);
        });

        // التنقّل بين الصفحات
        const views = ['view-domain', 'view-review'];
        const stepper = document.getElementById('globalStepper');

        function goto(stepIndex) {
            views.forEach((id, i) => document.getElementById(id).classList.toggle('hidden', i !== stepIndex));
            const circles = stepper.querySelectorAll('.step-circle');
            circles.forEach((c, i) => {
                c.classList.remove('border-[#240B36]', 'text-[#240B36]', 'bg-[#240B36]', 'text-white');
                if (i < stepIndex) {
                    c.classList.add('bg-[#240B36]', 'text-white', 'border-[#240B36]');
                } else if (i === stepIndex) {
                    c.classList.add('border-[#240B36]', 'text-[#240B36]');
                } else {
                    c.classList.add('border-gray-200', 'dark:border-gray-700', 'text-gray-500');
                }
            });
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Duplicate goConfig handlers removed — original handlers are inside DOMContentLoaded above

        // تبديل نماذج الدخول/التسجيل
        const btnLogin = document.getElementById('btn-login');
        const btnRegister = document.getElementById('btn-register');
        const frmLogin = document.getElementById('login-form');
        const frmRegister = document.getElementById('register-form');
        btnLogin?.addEventListener('click', () => {
            frmLogin.classList.remove('hidden');
            frmRegister.classList.add('hidden');
            btnLogin.classList.add('bg-primary', 'text-primary');
            btnRegister.classList.remove('bg-primary', 'text-primary');
            btnRegister.classList.add('bg-white', 'dark:bg-gray-900', 'text-primary');
        });
        btnRegister?.addEventListener('click', () => {
            frmRegister.classList.remove('hidden');
            frmLogin.classList.add('hidden');
            btnRegister.classList.add('bg-primary', 'text-primary');
            btnLogin.classList.remove('bg-primary', 'text-primary');
            btnLogin.classList.add('bg-white', 'dark:bg-gray-900', 'text-primary');
        });

        // إذا كان هناك باراميتر review=1 في الرابط، انتقل تلقائياً للخطوة الثانية
        if (window.location.search.includes('review=1')) {
            goto(1);
        } else {
            btnRegister?.click();
        }

        document.getElementById('backToDomain2')?.addEventListener('click', () => goto(0));
        // تفعيل زر الطلب الحقيقي عند تحقق الشروط
        const placeOrderReal = document.getElementById('placeOrderReal');

        function enableOrderIfValid() {
            if (!placeOrderReal) return;
            const agreeTos = document.getElementById('agreeTos');
            const domain = (document.getElementById('reviewDomain')?.textContent || '').trim();
            const total = (document.getElementById('sumTotal2')?.textContent || '').trim();
            placeOrderReal.disabled = !(agreeTos && agreeTos.checked && domain && total);
            placeOrderReal.classList.toggle('opacity-50', placeOrderReal.disabled);
            placeOrderReal.classList.toggle('cursor-not-allowed', placeOrderReal.disabled);
            // تعبئة القيم المخفية
            document.getElementById('orderDomainInput').value = domain;
            document.getElementById('orderTotalInput').value = total;
        }
        document.getElementById('agreeTos')?.addEventListener('input', enableOrderIfValid);
        document.getElementById('reviewDomain')?.addEventListener('DOMSubtreeModified', enableOrderIfValid);
        document.getElementById('sumTotal2')?.addEventListener('DOMSubtreeModified', enableOrderIfValid);
        enableOrderIfValid();

        // إظهار شاشة النجاح وجعل الخطوتين مكتملتين بصرياً
        function showSuccess() {
            ['view-domain', 'view-review'].forEach(id => document.getElementById(id).classList.add('hidden'));
            document.getElementById('view-success').classList.remove('hidden');

            const circles = document.querySelectorAll('#globalStepper .step-circle');
            circles.forEach(c => {
                c.classList.remove('border-[#240B36]', 'text-[#240B36]');
                c.classList.add('bg-[#240B36]', 'text-white', 'border-[#240B36]');
            });

            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // طباعة وذهاب للوحة التحكم (معاينة)
        document.getElementById('sx-print')?.addEventListener('click', () => window.print());
        document.getElementById('sx-dashboard')?.addEventListener('click', () => {
            window.location.href = '/client/home';
        });


        // كوبون تجريبي
        (function() {
            window.__couponDiscountCents = 0;
            const sumSub = document.getElementById('sumSub');
            const sumTax = document.getElementById('sumTax');
            const sumTotal2 = document.getElementById('sumTotal2');
            const sumDiscount = document.getElementById('sumDiscount');
            const summaryTotal = document.getElementById('summaryTotal');
            const reviewDomainPrice = document.getElementById('reviewDomainPrice');
            const fmt2 = fmt;
            window.updateTotals = function(domainCents) {
                // دومين + القالب دائماً
                const templateFinalPrice = {{ (int) (($finalPrice ?? 0) * 100) }};
                const subtotal = templateFinalPrice + Math.max(0, domainCents | 0);
                const tax = 0;
                const discount = Math.min(window.__couponDiscountCents | 0, subtotal);
                const total = Math.max(0, subtotal - discount + tax);
                sumSub.textContent = fmt2(subtotal);
                sumTax.textContent = fmt2(tax);
                sumTotal2.textContent = fmt2(total);
                summaryTotal.textContent = fmt2(total);
                sumDiscount.textContent = `-${fmt2(discount)}`;
            }

            function computeDiscount(code, base) {
                const c = (code || '').trim().toUpperCase();
                if (!c) return 0;
                if (c === 'PROMO10') return Math.round(base * 0.10);
                if (c === 'WELCOME20') return 2000;
                if (c === 'FREE') return base;
                return 0;
            }
            const applyBtn = document.getElementById('applyCoupon');
            const couponInput = document.getElementById('couponInput');
            const couponMsg = document.getElementById('couponMsg');
            applyBtn?.addEventListener('click', () => {
                const baseCents = Math.round(parseFloat((reviewDomainPrice.textContent || '0').replace(
                    /[^0-9\.]/g, '')) * 100) || 0;
                const d = computeDiscount(couponInput.value, baseCents);
                window.__couponDiscountCents = Math.min(d, baseCents);
                couponMsg.textContent = d > 0 ? 'تم تطبيق الخصم بنجاح ✅' : 'الكود غير صالح أو منتهي ❌';
                window.updateTotals(baseCents);
            });
        })();
        (function() {
            const gwRadios = document.querySelectorAll('input[name="gateway"]');
            const cardForm = document.getElementById('cardForm');
            const bankForm = document.getElementById('bankForm');
            const placeOrder = document.getElementById('placeOrder');
            const agreeTos = document.getElementById('agreeTos');

            function setGateway(v) {
                if (v === 'card') {
                    cardForm.classList.remove('hidden');
                    bankForm.classList.add('hidden');
                } else {
                    bankForm.classList.remove('hidden');
                    cardForm.classList.add('hidden');
                }
                if (typeof validate === 'function') validate();
            }
            gwRadios.forEach(r => r.addEventListener('change', () => setGateway(document.querySelector(
                'input[name="gateway"]:checked').value)));

            // تحقق بسيط (عرض فقط)
            const ccNumber = document.getElementById('ccNumber');
            const ccName = document.getElementById('ccName');
            const ccExp = document.getElementById('ccExp');
            const ccCvv = document.getElementById('ccCvv');
            const bankRef = document.getElementById('bankRef');

            function validCard() {
                if (cardForm.classList.contains('hidden')) return true;
                const num = (ccNumber?.value || '').replace(/\s+/g, '');
                const nameOk = (ccName?.value || '').trim().length > 2;
                const exp = (ccExp?.value || '').trim();
                const cvv = (ccCvv?.value || '').trim();
                const numOk = /^[0-9]{13,19}$/.test(num);
                const expOk = /^(0[1-9]|1[0-2])\/(\d{2})$/.test(exp);
                const cvvOk = /^\d{3,4}$/.test(cvv);
                return numOk && nameOk && expOk && cvvOk;
            }

            function validBank() {
                if (bankForm.classList.contains('hidden')) return true;
                return (bankRef?.value || '').trim().length >= 6;
            }

            function validate() {
                if (!placeOrder) return; // أمان: لا تعمل إذا لم يوجد الزر
                const ok = agreeTos.checked && validCard() && validBank();
                placeOrder.disabled = !ok;
                placeOrder.classList.toggle('opacity-50', !ok);
                placeOrder.classList.toggle('cursor-not-allowed', !ok);
            }

            [ccNumber, ccName, ccExp, ccCvv, bankRef, agreeTos].forEach(el => el && el.addEventListener('input',
                validate));
            setGateway('card');

            // رسالة المعاينة
            document.getElementById('placeOrder')?.addEventListener('click', () => {
                alert('🚀 تم إرسال الطلب (معاينة). سيتم توليد الفاتورة وإكمال الربط بعد المعالجة.');
            });
        })();
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
