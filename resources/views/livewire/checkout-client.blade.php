<div style="color: white;">
    <div class="alert alert-{{ $alertType }} justify-between items-center {{ $alert === false ? 'hidden' : 'flex' }}">
        {{ $alertMessage }}
        <button type="button" class="btn-close" wire:click="closeModal">
            <span class="pc-micon">
                <i class="material-icons-two-tone pc-icon">close</i>
            </span>
        </button>
    </div>


    <!-- ===== شريط الخطوات (خطوتان) ===== -->
    <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-10 mt-6 mb-3">
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

    @if($mode === 'domain')
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
                <form id="tab-register" class="space-y-4" role="tabpanel">
                    <div class="flex gap-2">
                        <input aria-label="اسم النطاق" placeholder="example" wire:model="domain_name"
                            class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20" />
                        <select aria-label="الامتداد" wire:model="domain_extension"
                            class="w-40 rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-3 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20">
                            @foreach ($domain_extensions as $extension => $price)
                                <option value="{{ $extension }}">{{ $extension }}</option>
                            @endforeach
                        </select>
                        <button type="button" id="btnCheck" wire:click="search"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2 font-semibold border border-gray-300 dark:border-gray-700 text-gray-800 dark:text-gray-100 bg-white dark:bg-gray-900 hover:bg-gray-50 active:scale-95 transition">
                            تحقق
                        </button>
                    </div>
                    @if($domain_check)
                    <div>{{ $domain_available ? 'The domain "' . $domain . '" is available' : 'The domain "' . $domain . '" is not available' }}</div>
                    @endif
                    <div class="mt-2">
                        @if(!$domain_available && count($domain_extensions_available) > 0)
                            <span class="text-lg">But you can buy it for</span>
                            @foreach ($domain_extensions_available as $extension)
                                <span class="text-lg btn btn-outline-success">{{ $domain_extensions[$extension] }}$ {{ $extension }}</span>
                            @endforeach
                        @else
                            @if(count($domain_names_available) > 0)
                                <span class="text-lg">Please try another domain Example:</span>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($domain_names_available as $domain_name)
                                        <span class="text-sm btn btn-outline-success">{{ $domain_name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </div>
                    <div class="flex items-center justify-between pt-2">
                        <div class="text-xs text-gray-500">سعر التسجيل السنوي: <span id="tldPrice"
                                class="font-semibold">{{ $domain_price }} $</span>
                        </div>
                        <button type="button" id="goConfigR" wire:click="search"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2 font-semibold text-white bg-[#240B36] hover:opacity-95 active:scale-95 transition shadow-sm">
                            متابعة
                        </button>
                    </div>
                </form>

                <!-- Transfer -->
                <form id="tab-transfer" class="space-y-4 hidden" role="tabpanel">
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
                <form id="tab-owndomain" class="space-y-4 hidden" role="tabpanel">
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
                <form id="tab-subdomain" class="space-y-4 hidden" role="tabpanel">
                    <div class="flex gap-2 items-stretch">
                        <input aria-label="اسم الساب-دومين" placeholder="myshop"
                            class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20" />
                        <div class="flex items-center text-gray-500 px-2">.</div>
                        <select aria-label="الدومين الأساسي"
                            class="w-56 rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-3 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20">
                            <option>palgoals.com</option>
                            <option>palgoals.store</option>
                            <option>palgoals.site</option>
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
                    <li class="flex justify-between"><span>القالب</span><span class="font-semibold">{{ $template?->translation($locale)?->name }}</span>
                    </li>
                    <li class="flex justify-between"><span>الخطة</span><span class="font-semibold">سنوي</span></li>
                    <li class="flex justify-between"><span>الدومين</span><span id="summaryDomain"
                            class="font-semibold">{{ $domain }}</span>
                    </li>
                </ul>
                <hr class="my-4 border-gray-200 dark:border-gray-800" />
                <div class="flex justify-between font-bold"><span>الإجمالي التقديري</span><span
                        id="summaryTotal">{{ $sumSub }} $</span></div>
            </aside>
        </div>
    </main>
    @endif

    @if($mode === 'review')
    <!-- ===== الصفحة 2: المراجعة والدفع ===== -->
    <section id="view-review" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-10 py-8">
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
                                <td class="p-3">تسجيل نطاق <span id="reviewDomain">{{ $domain }}</span></td>
                                <td class="p-3">12 شهر</td>
                                <td class="p-3" id="reviewDomainPrice">{{ $domain_price }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- تبديل الدخول/التسجيل -->
                <div
                    class="inline-flex rounded-xl bg-gray-50 dark:bg-gray-900 p-1 mb-6 shadow border border-gray-200 dark:border-gray-700 gap-2">
                    <button id="btn-login" wire:click="setTypeLoginClient('login')" type="button"
                        class="px-5 py-1.5 rounded-xl text-sm font-bold bg-white dark:bg-gray-900 text-[#240B36] border border-transparent hover:bg-[#240B36] hover:text-white focus:outline-none focus:ring-2 focus:ring-[#240B36]/30 shadow-sm">
                        دخول العميل
                    </button>
                    <button id="btn-register" wire:click="setTypeLoginClient('register')" type="button"
                        class="px-5 py-1.5 rounded-xl text-sm font-bold bg-white dark:bg-gray-900 text-[#240B36] border border-transparent hover:bg-[#240B36] hover:text-white focus:outline-none focus:ring-2 focus:ring-[#240B36]/30">
                        إنشاء حساب جديد
                    </button>
                </div>

                <!-- نموذج الدخول -->
                @if($type_login_client === 'login')
                <form id="login-form" class="mb-6">
                    <div class="grid md:grid-cols-3 gap-4 items-end">
                        <div>
                            <label class="text-sm font-medium mb-1 block">البريد الإلكتروني *</label>
                            <input type="email" wire:model="clientData.email"
                                class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-3 h-12 outline-none focus:ring-4 focus:ring-[#240B36]/20"
                                placeholder="example@domain.com" required />
                        </div>
                        <div>
                            <label class="text-sm font-medium mb-1 block">كلمة المرور *</label>
                            <input type="password" wire:model="clientData.password"
                                class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-3 h-12 outline-none focus:ring-4 focus:ring-[#240B36]/20"
                                placeholder="••••••" required />
                        </div>
                        <div class="pt-6">
                            <button type="button" wire:click="loginClient('login')"
                                class="rounded-xl px-4 py-2 font-semibold text-white bg-[#240B36] hover:opacity-95 active:scale-95 transition shadow-sm h-12">تسجيل
                                الدخول</button></div>
                    </div>
                </form>
                @endif

                <!-- نموذج التسجيل -->
                @if($type_login_client === 'register')
                <form id="register-form" class="space-y-6 mb-6">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">الاسم الأول *</label>
                            <input
                                class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20"
                                placeholder="محمد" wire:model="clientDataRegister.first_name" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">الاسم الأخير *</label>
                            <input
                                class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20"
                                placeholder="أحمد" wire:model="clientDataRegister.last_name" required />
                        </div>
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">رقم الجوال *</label>
                            <input
                                class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20"
                                placeholder="590000000" wire:model="clientDataRegister.phone" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">البريد الإلكتروني *</label>
                            <input type="email"
                                class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20"
                                placeholder="you@example.com" wire:model="clientDataRegister.email" required />
                        </div>
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">كلمة المرور *</label>
                            <input type="password"
                                class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20"
                                placeholder="••••••" wire:model="clientDataRegister.password" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">تأكيد كلمة المرور *</label>
                            <input type="password"
                                class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20"
                                placeholder="••••••" wire:model="clientDataRegister.password_confirmation" required />
                        </div>
                    </div>
                    <div class="pt-6">
                        <button type="button" wire:click="loginClient('register')"
                            class="rounded-xl px-4 py-2 font-semibold text-white bg-[#240B36] hover:opacity-95 active:scale-95 transition shadow-sm h-12">
                            تسجيل جديد
                        </button>
                    </div>
                </form>
                @endif

                @if($check_login_client && $client)
                    <div>
                        <h2 class="text-2xl font-extrabold mb-1">بيانات الشخصية</h2>
                        <div class="client_data">
                            <p>الاسم: {{ $client->first_name . ' ' . $client->last_name }}</p>
                            <p>البريد الإلكتروني: {{ $client->email }}</p>
                            <p>الشركة: {{ $client->company_name }}</p>
                            <p>رقم الجوال: {{ $client->phone }}</p>
                        </div>
                    </div>
                @endif


                <!-- الدفع (مُحسَّن) -->
                <div class="border border-gray-200 dark:border-gray-800 rounded-xl p-4" id="paymentBox">
                    @if(!$payment_check)
                    <h3 class="font-bold mb-3">طريقة الدفع</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                        <label
                            wire:click="setPaymentMethod('card')"
                            class="border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex items-center gap-3 cursor-pointer">
                            <input type="radio" name="gateway" value="card" class="scale-110" checked>
                            <span>بطاقة ائتمانية</span>
                            <span class="ms-auto text-xs text-gray-500">Visa / MasterCard</span>
                        </label>
                        <label
                            wire:click="setPaymentMethod('bank')"
                            class="border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex items-center gap-3 cursor-pointer">
                            <input type="radio" name="gateway" value="bank" class="scale-110">
                            <span>تحويل بنكي</span>
                            <span class="ms-auto text-xs text-gray-500">تأكيد يدوي</span>
                        </label>
                    </div>

                    @if($paymentData['payment_method'] == 'card')
                    <!-- نموذج بطاقة ائتمانية -->
                    <form id="cardForm" class="space-y-4">
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">رقم البطاقة *</label>
                                <input id="ccNumber" wire:model="paymentData.ccNumber" inputmode="numeric" placeholder="4242 4242 4242 4242"
                                    class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">اسم حامل البطاقة *</label>
                                <input id="ccName" wire:model="paymentData.ccName" placeholder="Mohammed A."
                                    class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20">
                            </div>
                        </div>
                        <div class="grid md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">تاريخ الانتهاء *</label>
                                <input id="ccExp" wire:model="paymentData.ccExp" inputmode="numeric" placeholder="MM/YY"
                                    class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">CVV *</label>
                                <input id="ccCvv" wire:model="paymentData.ccCvv" inputmode="numeric" placeholder="123"
                                    class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20">
                            </div>
                            <div class="flex items-end">
                                <div id="ccHint" class="text-xs text-gray-500">
                                    يتم التحقق محليًا لأغراض العرض.
                                </div>
                                <button type="button" wire:click="validateCard" class="rounded-xl px-4 py-2 font-semibold text-white bg-[#240B36] hover:opacity-95 active:scale-95 transition shadow-sm h-12">
                                    تحقق
                                </button>
                            </div>
                        </div>
                    </form>
                    <div class="mt-4 flex items-start gap-2">
                        <input id="agreeTos" type="checkbox" class="mt-1">
                        <label for="agreeTos" class="text-sm text-gray-700 dark:text-gray-300">أوافق على <a
                                href="#" class="underline">الشروط والأحكام</a> وسياسة الخصوصية.</label>
                    </div>
                    @endif

                    @if($paymentData['payment_method'] == 'bank')
                    <!-- نموذج تحويل بنكي -->
                    <form id="bankForm" class="space-y-4">
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">البنك المحوَّل إليه</label>
                                <input wire:model="paymentData.bankName" value="Bank of Palestine - IBAN: PS00 PALS 0000 0000 0000 0000" readonly
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 dark:bg-gray-800 dark:border-gray-800 px-4 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">رقم المعاملة *</label>
                                <input id="bankRef" wire:model="paymentData.bankRef" placeholder="TRX-123456"
                                    class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">ملاحظة (اختياري)</label>
                            <textarea id="bankNote" rows="3" wire:model="paymentData.bankNote"
                                class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20"
                                placeholder="ارفق أي تفاصيل مهمة عن التحويل..."></textarea>
                        </div>

                        <button type="button" wire:click="validateBank" class="rounded-xl px-4 py-2 font-semibold text-white bg-[#240B36] hover:opacity-95 active:scale-95 transition shadow-sm h-12">
                            تحقق
                        </button>
                    </form>
                    <div class="mt-4 flex items-start gap-2">
                        <input id="agreeTos" type="checkbox" class="mt-1">
                        <label for="agreeTos" class="text-sm text-gray-700 dark:text-gray-300">أوافق على <a
                                href="#" class="underline">الشروط والأحكام</a> وسياسة الخصوصية.</label>
                    </div>
                    @endif
                    @endif

                    @if($payment_check)
                    <div>
                        <h2 class="text-2xl font-extrabold mb-1">بيانات الدفع ناجحة</h2>
                        <p>تم التحقق من البطاقة بنجاح.</p>
                    </div>
                    @endif


                </div>

                <div class="flex items-center justify-end gap-3 mt-6">
                    <button id="backToDomain2" type="button"
                        class="rounded-xl px-4 py-2 font-semibold border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 hover:bg-gray-50 active:scale-95 transition">رجوع</button>
                    <button wire:click="submit" id="placeOrder" type="button" {{ $payment_check ? 'disabled' : '' }}
                        class="inline-flex items-center justify-center rounded-xl px-4 py-2 font-semibold text-white bg-[#240B36] opacity-50 {{ $payment_check ? 'cursor-not-allowed' : 'cursor-pointer' }} transition shadow-sm">إتمام
                        الطلب</button>
                </div>

            </div>

            <aside
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow p-6 h-max">
                <h3 class="font-bold mb-3">الإجمالي</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span>المجموع</span><span id="sumSub">{{ $sumSub }}</span></div>
                    <div class="flex justify-between"><span>الخصم</span><span id="sumDiscount">{{ $sumDiscount }}</span></div>
                    <div class="flex justify-between"><span>الضريبة</span><span id="sumTax">{{ $sumTax }}</span></div>
                </div>
                <hr class="my-4 border-gray-200 dark:border-gray-800" />
                <div class="space-y-3">
                    <div class="flex gap-2">
                        <input id="couponInput" wire:model="coupon"
                            class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 px-4 py-2 outline-none focus:ring-4 focus:ring-[#240B36]/20"
                            placeholder="كود الخصم (إن وجد)">
                        <button id="applyCoupon" wire:click="applyCoupon"
                            class="rounded-xl px-4 py-2 font-semibold border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 hover:bg-gray-50 active:scale-95 transition">تطبيق</button>
                    </div>
                    <p id="couponMsg" class="text-xs text-gray-500"></p>
                </div>
                <hr class="my-4 border-gray-200 dark:border-gray-800" />
                <div class="flex justify-between font-bold text-lg"><span>الإجمالي المستحق</span><span
                        id="sumTotal2">{{ $sumTotal }}</span></div>
            </aside>
        </div>
    </section>
    @endif

    @if($mode === 'success')
    <!-- ===== الصفحة 3: نجاح الطلب ===== -->
    <section id="view-success" class="hidden max-w-6xl mx-auto px-4 sm:px-6 lg:px-10 py-16">
        <div
            class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow p-8 text-center">
            <div class="mx-auto w-16 h-16 rounded-full grid place-items-center bg-green-100 text-green-700 mb-4">✓
            </div>
            <h2 class="text-2xl font-extrabold mb-2">تم إنشاء الطلب بنجاح</h2>
            <p class="text-gray-600 dark:text-gray-300 mb-6">
                سنرسل إليك فاتورة عبر البريد الإلكتروني. يمكنك إدارة موقعك من لوحة التحكم.
            </p>

            <div class="grid sm:grid-cols-3 gap-4 text-sm text-right max-w-3xl mx-auto mb-6">
                {{-- <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="text-gray-500">رقم الطلب</div>
                    <div id="sx-order" class="font-bold">{{ $order->id }}</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="text-gray-500">الدومين</div>
                    <div id="sx-domain" class="font-bold">{{ $order->domain_name }}</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="text-gray-500">الإجمالي</div>
                    <div id="sx-total" class="font-bold">{{ $order->total }}</div>
                </div> --}}
            </div>

            <div class="flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('client.home') }}" id="sx-dashboard"
                    class="inline-flex items-center justify-center rounded-xl px-5 py-2.5 font-semibold text-white bg-[#240B36] hover:opacity-95 active:scale-95 transition shadow-sm">
                    الذهاب للوحة التحكم
                </a>
                <button id="sx-print"
                    class="rounded-xl px-5 py-2.5 font-semibold border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 hover:bg-gray-50 active:scale-95 transition">
                    طباعة الفاتورة
                </button>
            </div>

            <div id="sx-hint" class="text-xs text-gray-500 mt-6">
                إن كنت قد اخترت ربط نطاق تملكه، سنعرض لك سجلات DNS في صفحة الإعداد لاحقًا.
            </div>
        </div>
    </section>
    @endif

    <script>
        document.getElementById('sx-print')?.addEventListener('click', () => window.print());
    </script>
</div>
