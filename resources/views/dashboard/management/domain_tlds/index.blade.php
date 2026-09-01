<x-dashboard-layout>
    {{-- A. Header --}}
    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">أسعار الدومينات (TLD)</h2>
            <p class="mt-1 text-xs text-gray-500">إدارة المزامنة والتسعير والكتالوج للأجزاء الظاهرة فقط لتحسين الأداء.
            </p>
        </div>
        <nav class="text-xs text-gray-500" aria-label="Breadcrumb">
            <ol class="flex items-center gap-1">
                <li><a class="text-indigo-600 hover:underline" href="#">الرئيسية</a></li>
                <li>/</li>
                <li class="text-gray-400">إدارة TLD</li>
            </ol>
        </nav>
    </div>

    {{-- رسائل --}}
    @if (session('ok'))
        <div role="status" aria-live="polite"
            class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-700">
            {{ session('ok') }}
        </div>
    @endif
    @if ($errors->any())
        <div role="alert" aria-live="assertive"
            class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">
            {{ implode(' | ', $errors->all()) }}
        </div>
    @endif

    <div class="space-y-6">

        {{-- B. Sync section: تصفية + مزامنة، formان مستقلان بصريًا، شبكة متوازنة compact --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="grid gap-4 lg:grid-cols-2">
                {{-- فلترة بالمزوّد (GET) — form مستقل تمامًا --}}
                <div>
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">تصفية الجدول</h3>
                    <form method="get" class="flex flex-wrap items-end gap-3">
                        <div class="min-w-[10rem] flex-1">
                            <label for="filter-provider-id"
                                class="mb-1 block text-xs font-medium text-gray-600">المزوّد</label>
                            <select id="filter-provider-id" name="provider_id"
                                class="block w-full rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="">-- الكل --</option>
                                @foreach ($providers as $p)
                                    <option value="{{ $p->id }}" @selected($providerId == $p->id)>{{ $p->name }}
                                        ({{ $p->type }})</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit"
                            class="inline-flex items-center justify-center rounded-md bg-blue-500 px-4 py-2 text-sm font-medium text-white hover:bg-blue-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">تصفية</button>
                    </form>
                </div>

                {{-- مزامنة من المزوّد (POST) — form مستقل تمامًا، لا يشارك select مع فورم التصفية --}}
                <div class="rounded-md border border-dashed border-gray-300 bg-gray-50/60 p-3">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">مزامنة الأسعار من
                        المزوّد</h3>
                    <form action="{{ route('dashboard.domain_tlds.sync') }}" method="post"
                        class="flex flex-wrap items-end gap-3">
                        @csrf
                        <div class="min-w-[9rem] flex-1">
                            <label for="sync-provider-id"
                                class="mb-1 block text-xs font-medium text-gray-600">المزوّد</label>
                            <select id="sync-provider-id" name="provider_id" required
                                class="block w-full rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                @foreach ($providers as $p)
                                    <option value="{{ $p->id }}" @selected($providerId == $p->id)>{{ $p->name }}
                                        ({{ $p->type }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="min-w-[9rem] flex-1">
                            <label for="sync-tlds" class="mb-1 block text-xs font-medium text-gray-600">TLDs
                                (اختياري)</label>
                            <input id="sync-tlds" name="tlds" placeholder="com,net,org" value="{{ old('tlds') }}"
                                class="block w-full rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <button type="submit"
                            class="inline-flex items-center justify-center rounded-md bg-indigo-500 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">سحب
                            الأسعار</button>
                    </form>
                    <p class="mt-2 text-[11px] leading-relaxed text-gray-400">اتركه فارغًا لمزامنة العناصر "ضمن
                        المزامنة" فقط.</p>
                </div>
            </div>
        </div>

        {{-- C. Catalog / pricing table — المحتوى الرئيسي، الأولوية البصرية الأعلى --}}
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-gray-800">كتالوج الأسعار</h3>

                <div class="flex flex-wrap items-center gap-3">
                    {{-- مجموعة "ضمن المزامنة" — منفصلة بصريًا عن الحذف --}}
                    <div class="flex items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-2 py-1.5">
                        <span class="text-[11px] font-medium text-gray-500">ضمن المزامنة:</span>
                        <button type="button" id="checkAll"
                            class="rounded border border-gray-300 bg-white px-2 py-1 text-[11px] font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">تحديد
                            الكل</button>
                        <button type="button" id="uncheckAll"
                            class="rounded border border-gray-300 bg-white px-2 py-1 text-[11px] font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">إلغاء</button>
                    </div>

                    {{-- حذف جماعي (اختياري) — form مستقل، danger zone منفصلة تمامًا --}}
                    <form id="bulkDeleteForm" action="{{ route('dashboard.domain_tlds.bulk-destroy') }}"
                        method="POST" onsubmit="return confirm('هل تريد حذف العناصر المحددة؟');"
                        class="flex items-center gap-2 rounded-md border border-red-200 bg-red-50/50 px-2 py-1.5">
                        @csrf
                        <span class="text-[11px] font-medium text-red-500">حذف جماعي:</span>
                        <button type="submit"
                            class="rounded border border-red-300 bg-white px-2 py-1 text-[11px] font-medium text-red-600 shadow-sm hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1">حذف
                            المحدد</button>
                    </form>
                </div>
            </div>

            <p class="mb-3 text-[11px] text-gray-400">"ضمن المزامنة" يخص الكتالوج، وهو مستقل تمامًا عن تحديد صفوف
                الحذف الجماعي (آخر عمودين في الجدول).</p>

            {{-- حفظ الكتالوج + أسعار البيع (POST) للصفحة الحالية --}}
            <form id="saveAllForm" action="{{ route('dashboard.domain_tlds.save-all') }}" method="post">
                @csrf
                <input type="hidden" name="provider_id" value="{{ $providerId }}">

                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table id="tldsTable" class="min-w-full divide-y divide-gray-200 text-end text-sm">
                        <thead class="bg-gray-50 text-[11px] font-semibold text-gray-600">
                            <tr>
                                <th scope="col" class="px-3 py-2">TLD</th>
                                <th scope="col" class="px-2 py-2">
                                    <span title="عند التفعيل يظهر هذا الامتداد للعميل ويمكن شراؤه. عند التعطيل يُستبعد كليًا من أسعار الموقع حتى لو كان له سعر مضبوط.">متاح
                                        للبيع</span>
                                </th>
                                <th scope="col" class="px-2 py-2">العملة</th>
                                <th scope="col" class="px-3 py-2">Register</th>
                                <th scope="col" class="px-3 py-2">Renew</th>
                                <th scope="col" class="px-3 py-2">Transfer</th>
                                <th scope="col" class="px-2 py-2">
                                    <span title="يحدّد فقط أي الامتدادات تُعاد مزامنتها تلقائيًا من المزوّد عند ترك حقل TLDs فارغًا، ولا يؤثر على ظهور الامتداد للعميل.">ضمن
                                        المزامنة</span>
                                </th>
                                <th scope="col" class="px-2 py-2">آخر مزامنة</th>
                                <th scope="col" class="px-2 py-2">
                                    <label class="inline-flex items-center gap-1">
                                        <input type="checkbox" id="selectAllRows"
                                            aria-label="تحديد كل الصفوف للحذف الجماعي"
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span>تحديد</span>
                                    </label>
                                </th>
                                <th scope="col" class="px-2 py-2">حذف</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($rows as $row)
                                @php
                                    $reg = $row->prices->firstWhere('action', 'register');
                                    $ren = $row->prices->firstWhere('action', 'renew');
                                    $tra = $row->prices->firstWhere('action', 'transfer');
                                    $pricingGroups = [
                                        'Register' => $reg,
                                        'Renew' => $ren,
                                        'Transfer' => $tra,
                                    ];
                                @endphp
                                <tr class="hover:bg-indigo-50/30" data-row-id="{{ $row->id }}">
                                    <td class="whitespace-nowrap px-3 py-2 text-sm font-semibold text-gray-900">
                                        .{{ $row->tld }}</td>
                                    <td class="px-2 py-2">
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $row->enabled ? 'bg-green-100 text-green-700 ring-1 ring-green-200' : 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' }}">{{ $row->enabled ? 'متاح للبيع' : 'غير متاح' }}</span>
                                    </td>
                                    <td class="px-2 py-2 text-xs font-medium text-gray-600">{{ $row->currency }}</td>
                                    @foreach ($pricingGroups as $price)
                                        <td class="min-w-[9rem] px-3 py-2.5 align-top">
                                            <div class="text-[11px] text-gray-500">التكلفة
                                                <span class="font-medium text-gray-700">{{ optional($price)->cost ?? '—' }}</span>
                                            </div>
                                            @if ($price)
                                                <input name="items[{{ $price->id }}][id]" type="hidden"
                                                    value="{{ $price->id }}">
                                                <div class="mt-1.5 flex items-center gap-1.5">
                                                    <label for="sale-{{ $price->id }}"
                                                        class="text-xs font-medium text-gray-700">البيع</label>
                                                    <input id="sale-{{ $price->id }}"
                                                        name="items[{{ $price->id }}][sale]" type="number"
                                                        step="0.01" min="0" value="{{ $price->sale }}"
                                                        class="w-24 rounded-md border border-gray-300 bg-white px-1.5 py-1 text-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                                </div>
                                                @if (is_null($price->sale))
                                                    <p class="mt-1 text-[10px] leading-tight text-amber-600">يُستخدم
                                                        سعر التكلفة</p>
                                                @endif
                                            @else
                                                <span class="text-xs text-gray-400">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="px-2 py-2">
                                        <input type="checkbox"
                                            class="catalog-checkbox h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            aria-label="ضمن المزامنة: .{{ $row->tld }}"
                                            name="catalog[{{ $row->id }}]" value="1"
                                            @checked($row->in_catalog)>
                                        <input type="hidden" name="visible_ids[]" value="{{ $row->id }}">
                                    </td>
                                    <td class="whitespace-nowrap px-2 py-2 text-[11px] text-gray-400">
                                        {{ optional($row->synced_at)->format('Y-m-d H:i') ?? '—' }}
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="checkbox"
                                            class="row-check h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            aria-label="تحديد .{{ $row->tld }} للحذف الجماعي"
                                            value="{{ $row->id }}">
                                    </td>
                                    <td class="px-2 py-2">
                                        <form action="{{ route('dashboard.domain_tlds.destroy', $row) }}"
                                            method="POST"
                                            onsubmit="return confirm('حذف .{{ $row->tld }} وجميع أسعاره؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="rounded-md border border-red-200 bg-white px-2.5 py-1 text-[11px] font-medium text-red-600 shadow-sm hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1">حذف</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-4">
                    <button type="submit"
                        class="inline-flex items-center rounded-md bg-emerald-600 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1">حفظ
                        الكتالوج وأسعار البيع</button>
                    <p class="text-[11px] text-gray-500">يحفظ "ضمن المزامنة" وأسعار البيع للصفحة الحالية فقط.</p>
                </div>

                <div class="mt-4">{{ $rows->withQueryString()->links() }}</div>
            </form>
        </div>

        {{-- D. Secondary / advanced: تسعير تلقائي بالجملة — مطوٍ افتراضيًا، بدون Modal وبدون JS جديد --}}
        <details class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <summary
                class="cursor-pointer select-none rounded-lg px-5 py-4 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                التسعير بالجملة (متقدم)
            </summary>
            <div class="border-t border-gray-100 p-5">
                <form action="{{ route('dashboard.domain_tlds.apply-pricing') }}" method="post"
                    class="rounded-lg bg-indigo-50/40 p-4 ring-1 ring-inset ring-indigo-100/60">
                    @csrf
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12 sm:col-span-3">
                            <label for="pricing-scope"
                                class="mb-1 block text-xs font-medium text-gray-600">النطاق</label>
                            <select id="pricing-scope" name="scope"
                                class="block w-full rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="page">الصفحة الحالية فقط</option>
                                <option value="provider" @selected($providerId)>كل صفوف المزود المصفّى</option>
                            </select>
                        </div>
                        <div class="col-span-12 sm:col-span-3">
                            <label for="pricing-provider-id" class="mb-1 block text-xs font-medium text-gray-600">المزوّد
                                (عند اختيار نطاق المزود)</label>
                            <select id="pricing-provider-id" name="provider_id"
                                class="block w-full rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="">— اختر —</option>
                                @foreach ($providers as $p)
                                    <option value="{{ $p->id }}" @selected($providerId == $p->id)>{{ $p->name }}
                                        ({{ $p->type }})</option>
                                @endforeach
                            </select>
                            <label class="mt-2 inline-flex items-center gap-2 text-[11px] font-medium text-gray-600">
                                <input type="checkbox" name="only_in_catalog" value="1"
                                    class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span>فقط "ضمن المزامنة"</span>
                            </label>
                        </div>
                        <div class="col-span-12 sm:col-span-3">
                            <span class="mb-1 block text-xs font-medium text-gray-600">الأكشن</span>
                            <div class="flex flex-wrap gap-x-3 gap-y-2 rounded-md bg-white p-2 ring-1 ring-gray-200">
                                <label class="inline-flex items-center gap-1 text-[11px] font-medium text-gray-700">
                                    <input type="checkbox" name="actions[]" value="register" checked
                                        class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    Register
                                </label>
                                <label class="inline-flex items-center gap-1 text-[11px] font-medium text-gray-700">
                                    <input type="checkbox" name="actions[]" value="renew" checked
                                        class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    Renew
                                </label>
                                <label class="inline-flex items-center gap-1 text-[11px] font-medium text-gray-700">
                                    <input type="checkbox" name="actions[]" value="transfer" checked
                                        class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    Transfer
                                </label>
                            </div>
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <label for="pricing-years" class="mb-1 block text-xs font-medium text-gray-600">المدة
                                (سنوات)</label>
                            <input id="pricing-years" type="number" name="years" value="1" min="1" max="10"
                                class="block w-24 rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div class="col-span-12 sm:col-span-4">
                            <span class="mb-1 block text-xs font-medium text-gray-600">نمط التسعير</span>
                            <div class="flex flex-wrap items-center gap-4 rounded-md bg-white p-2 ring-1 ring-gray-200">
                                <label
                                    class="inline-flex items-center gap-1 text-[11px] font-medium text-gray-700"><input
                                        type="radio" name="mode" value="percent" checked
                                        class="h-3.5 w-3.5 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    نسبة
                                    %</label>
                                <label
                                    class="inline-flex items-center gap-1 text-[11px] font-medium text-gray-700"><input
                                        type="radio" name="mode" value="fixed_margin"
                                        class="h-3.5 w-3.5 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    هامش ثابت
                                    +</label>
                                <label
                                    class="inline-flex items-center gap-1 text-[11px] font-medium text-gray-700"><input
                                        type="radio" name="mode" value="fixed_final"
                                        class="h-3.5 w-3.5 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    سعر
                                    نهائي =</label>
                                <label for="pricing-value" class="sr-only">قيمة التسعير</label>
                                <input id="pricing-value" type="number" step="0.01" name="value"
                                    placeholder="القيمة" required
                                    class="w-28 rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                            </div>
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <label for="pricing-rounding" class="mb-1 block text-xs font-medium text-gray-600">طريقة
                                التقريب</label>
                            <select id="pricing-rounding" name="rounding"
                                class="block w-full rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="2dp">رقمين عشريين (2dp)</option>
                                <option value="99">إنهاء بـ .99</option>
                            </select>
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <span class="mb-1 block text-xs font-medium text-gray-600">خيارات</span>
                            <label
                                class="inline-flex items-center gap-2 rounded-md border border-red-200 bg-red-50/60 px-2 py-1 text-[11px] font-medium text-red-700 ring-1 ring-red-100">
                                <input type="checkbox" name="overwrite" value="1"
                                    class="h-3.5 w-3.5 rounded border-red-300 text-red-600 focus:ring-red-500">
                                ⚠ الكتابة فوق أسعار البيع الموجودة
                            </label>
                        </div>
                        <div class="col-span-12 sm:col-span-2 flex items-end">
                            <button type="submit"
                                class="w-full rounded-md bg-emerald-500 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1">تطبيق
                                التسعير</button>
                        </div>
                    </div>
                    @foreach ($rows as $row)
                        <input type="hidden" name="visible_ids[]" value="{{ $row->id }}">
                    @endforeach
                </form>
            </div>
        </details>

        <div class="rounded-md bg-gray-50 px-4 py-3 text-[11px] leading-relaxed text-gray-600">
            ملاحظة: يتم سحب الأسعار فقط لـ TLDs "ضمن المزامنة" في الكتالوج أو المحددة يدويًا في حقل <strong
                class="font-semibold text-gray-700">TLDs</strong> عند المزامنة.
        </div>
    </div>

    {{-- أدوات بسيطة للواجهة --}}
    <script>
        document.getElementById('checkAll')?.addEventListener('click', () => {
            document.querySelectorAll('.catalog-checkbox').forEach(cb => cb.checked = true);
        });
        document.getElementById('uncheckAll')?.addEventListener('click', () => {
            document.querySelectorAll('.catalog-checkbox').forEach(cb => cb.checked = false);
        });

        const selectAllRows = document.getElementById('selectAllRows');
        selectAllRows?.addEventListener('change', () => {
            document.querySelectorAll('.row-check').forEach(cb => cb.checked = selectAllRows.checked);
        });

        const bulkForm = document.getElementById('bulkDeleteForm');
        bulkForm?.addEventListener('submit', e => {
            bulkForm.querySelectorAll('input[name="delete_ids[]"]').forEach(n => n.remove());
            document.querySelectorAll('.row-check:checked').forEach(cb => {
                const h = document.createElement('input');
                h.type = 'hidden';
                h.name = 'delete_ids[]';
                h.value = cb.value;
                bulkForm.appendChild(h);
            });
            if (!bulkForm.querySelectorAll('input[name="delete_ids[]"]').length) {
                e.preventDefault();
                alert('اختر صفوفًا للحذف أولاً.');
            }
        });
    </script>
</x-dashboard-layout>
