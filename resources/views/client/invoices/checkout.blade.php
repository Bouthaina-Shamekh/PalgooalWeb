<x-client-layout>
@php
    $status      = (string) ($invoice->status ?? 'unpaid');
    $payState    = $payment_state ?: ($status === 'paid' ? 'paid' : '');
    $totalMajor  = number_format(($invoice->total_cents ?? 0) / 100, 2);
    $subMajor    = number_format(($invoice->subtotal_cents ?? $invoice->total_cents ?? 0) / 100, 2);
    $currency    = $invoice->currency ?? 'USD';
    $canPay      = in_array($status, ['draft', 'unpaid'], true);
    $hasDomain   = $invoice->items->contains(fn($i) => $i->item_type === 'domain');
    $clientName  = trim(($invoice->client->first_name ?? '') . ' ' . ($invoice->client->last_name ?? ''));

    $statusMap = [
        'paid'      => ['label' => 'مدفوعة',     'dot' => 'bg-emerald-500', 'pill' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'unpaid'    => ['label' => 'غير مدفوعة', 'dot' => 'bg-red-500',     'pill' => 'bg-red-50 text-red-700 ring-red-200'],
        'draft'     => ['label' => 'مسودة',      'dot' => 'bg-amber-400',   'pill' => 'bg-amber-50 text-amber-700 ring-amber-200'],
        'cancelled' => ['label' => 'ملغاة',      'dot' => 'bg-slate-400',   'pill' => 'bg-slate-100 text-slate-600 ring-slate-200'],
    ];
    $st = $statusMap[$status] ?? ['label' => ucfirst($status), 'dot' => 'bg-slate-400', 'pill' => 'bg-slate-100 text-slate-600 ring-slate-200'];
@endphp

{{-- Print Styles --}}
@push('styles')
<style>
@media print {
    body * { visibility: hidden !important; }
    #invoice-printable, #invoice-printable * { visibility: visible !important; }
    #invoice-printable { position: absolute; inset: 0; padding: 32px; }
    .no-print { display: none !important; }
}
</style>
@endpush

{{-- Breadcrumb (no print) --}}
<nav class="no-print flex items-center gap-2 text-sm text-slate-400 mb-6 font-cairo" dir="rtl">
    <a href="{{ route('client.home') }}" class="hover:text-slate-700 transition">الرئيسية</a>
    <i class="ti ti-chevron-left text-xs"></i>
    <a href="{{ route('client.invoices') }}" class="hover:text-slate-700 transition">الفواتير</a>
    <i class="ti ti-chevron-left text-xs"></i>
    <span class="text-slate-700 font-medium">{{ $invoice->number }}</span>
</nav>

{{-- Flash (no print) --}}
@if (session('success'))
    <div class="no-print mb-5 flex items-center gap-3 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700" dir="rtl">
        <i class="ti ti-circle-check flex-shrink-0"></i> {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="no-print mb-5 flex items-center gap-3 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700" dir="rtl">
        <i class="ti ti-alert-circle flex-shrink-0"></i> {{ session('error') }}
    </div>
@endif
@if (session('info'))
    <div class="no-print mb-5 flex items-center gap-3 rounded-xl bg-sky-50 border border-sky-200 px-4 py-3 text-sm text-sky-700" dir="rtl">
        <i class="ti ti-info-circle flex-shrink-0"></i> {{ session('info') }}
    </div>
@endif

<div class="flex flex-col lg:flex-row gap-6" dir="rtl">

    {{-- ══════════════════════════
         INVOICE DETAILS (printable)
    ══════════════════════════ --}}
    <div class="flex-1 min-w-0" id="invoice-printable">

        {{-- Invoice Card --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">

            {{-- Invoice Header --}}
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 px-6 py-6 border-b border-slate-100">
                <div>
                    <div class="flex flex-wrap items-center gap-3 mb-2">
                        <h1 class="text-xl font-bold text-slate-900">{{ $invoice->number }}</h1>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $st['pill'] }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $st['dot'] }}"></span>
                            {{ $st['label'] }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-400">
                        صدرت: {{ $invoice->created_at?->format('d/m/Y H:i') ?? '—' }}
                    </p>
                </div>

                <div class="text-start sm:text-end">
                    <p class="text-xs text-slate-400 mb-1">الإجمالي المستحق</p>
                    <p class="text-3xl font-bold text-slate-900">{{ $totalMajor }} <span class="text-lg font-normal text-slate-500">{{ $currency }}</span></p>
                </div>
            </div>

            {{-- Invoice Meta --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-0 divide-y sm:divide-y-0 sm:divide-x-reverse sm:divide-x border-b border-slate-100">
                <div class="px-5 py-4">
                    <p class="text-xs text-slate-400 mb-1">العميل</p>
                    <p class="text-sm font-semibold text-slate-800">{{ $clientName ?: 'حسابي' }}</p>
                    @if ($invoice->client?->email)
                        <p class="text-xs text-slate-400 mt-0.5">{{ $invoice->client->email }}</p>
                    @endif
                </div>
                <div class="px-5 py-4">
                    <p class="text-xs text-slate-400 mb-1">تاريخ الاستحقاق</p>
                    <p class="text-sm font-semibold text-slate-800">{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</p>
                </div>
                <div class="px-5 py-4">
                    <p class="text-xs text-slate-400 mb-1">رقم الطلب</p>
                    <p class="text-sm font-semibold text-slate-800">
                        {{ $invoice->order->order_number ?? 'فاتورة مباشرة' }}
                    </p>
                </div>
            </div>

            {{-- Items --}}
            <div class="px-6 py-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-4">بنود الفاتورة</p>

                <div class="space-y-2">
                    @forelse ($invoice->items as $item)
                        <div class="flex items-center justify-between gap-4 rounded-xl bg-slate-50 px-4 py-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-800 truncate">{{ $item->description ?: 'بند غير مسمى' }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">الكمية: {{ $item->qty ?? 1 }}</p>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <p class="text-sm font-bold text-slate-800">
                                    {{ number_format(($item->total_cents ?? 0) / 100, 2) }} {{ $currency }}
                                </p>
                                @if (($item->qty ?? 1) > 1)
                                    <p class="text-xs text-slate-400">
                                        {{ number_format(($item->unit_price_cents ?? 0) / 100, 2) }} × {{ $item->qty }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-8 text-center rounded-xl border border-dashed border-slate-200">
                            <p class="text-sm text-slate-400">لا توجد بنود في هذه الفاتورة</p>
                        </div>
                    @endforelse
                </div>

                {{-- Totals --}}
                <div class="mt-4 border-t border-slate-100 pt-4 space-y-2">
                    <div class="flex items-center justify-between text-sm text-slate-500">
                        <span>المجموع الفرعي</span>
                        <span>{{ $subMajor }} {{ $currency }}</span>
                    </div>
                    <div class="flex items-center justify-between font-bold text-slate-900">
                        <span>الإجمالي</span>
                        <span class="text-lg">{{ $totalMajor }} {{ $currency }}</span>
                    </div>
                </div>
            </div>

            {{-- Print Button --}}
            <div class="no-print px-6 py-4 border-t border-slate-100 flex justify-end">
                <button onclick="window.print()"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    <i class="ti ti-printer text-base leading-none"></i>
                    طباعة الفاتورة
                </button>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════
         PAYMENT PANEL (no print)
    ══════════════════════════ --}}
    <div class="no-print w-full lg:w-80 flex-shrink-0">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden sticky top-24">

            {{-- Demo badge --}}
            <div class="flex items-center gap-3 bg-slate-50 border-b border-slate-100 px-5 py-3">
                <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-600">
                    <i class="ti ti-flask text-sm leading-none"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-700">بوابة تجريبية</p>
                    <p class="text-xs text-slate-400">لا تُخصم رسوم حقيقية</p>
                </div>
            </div>

            <div class="p-5">
                {{-- PAID state --}}
                @if ($payState === 'paid')
                    <div class="text-center py-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 mx-auto mb-3">
                            <i class="ti ti-circle-check text-3xl leading-none text-emerald-600"></i>
                        </div>
                        <p class="font-bold text-slate-800 mb-1">تم الدفع بنجاح</p>
                        <p class="text-xs text-slate-400 mb-5">تم تسجيل الفاتورة كمدفوعة</p>
                        <div class="space-y-2">
                            @if ($hasDomain)
                                <a href="{{ route('client.domains.index') }}"
                                   class="flex items-center justify-center gap-2 w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">
                                    <i class="ti ti-world text-sm leading-none"></i>
                                    عرض نطاقاتي
                                </a>
                            @endif
                            <a href="{{ route('client.invoices') }}"
                               class="flex items-center justify-center gap-2 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                <i class="ti ti-arrow-right text-sm leading-none"></i>
                                العودة للفواتير
                            </a>
                        </div>
                    </div>

                {{-- CAN PAY state --}}
                @elseif ($canPay)
                    @if ($payState === 'failed')
                        <div class="mb-4 flex items-center gap-2 rounded-xl bg-red-50 border border-red-200 px-3 py-2.5 text-xs text-red-700">
                            <i class="ti ti-alert-circle flex-shrink-0"></i>
                            فشل الدفع التجريبي. أعد المحاولة.
                        </div>
                    @elseif ($payState === 'cancelled')
                        <div class="mb-4 flex items-center gap-2 rounded-xl bg-amber-50 border border-amber-200 px-3 py-2.5 text-xs text-amber-700">
                            <i class="ti ti-alert-triangle flex-shrink-0"></i>
                            تم إلغاء الدفع. الفاتورة لا تزال غير مدفوعة.
                        </div>
                    @endif

                    <p class="text-xs font-semibold text-slate-500 mb-3">معلومات البطاقة التجريبية</p>

                    <form id="demoCheckoutForm" method="POST" action="{{ route('client.invoices.checkout.process', $invoice) }}" class="space-y-3">
                        @csrf
                        <input type="hidden" name="scenario" id="paymentScenario" value="{{ old('scenario', 'success') }}">

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">اسم حامل البطاقة</label>
                            <input type="text" name="card_holder"
                                   value="{{ old('card_holder', 'Demo Client') }}"
                                   placeholder="Demo Client"
                                   class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none transition focus:border-slate-500 focus:bg-white"
                                   dir="ltr">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">رقم البطاقة</label>
                            <input type="text" name="card_number"
                                   value="{{ old('card_number', '4242 4242 4242 4242') }}"
                                   placeholder="4242 4242 4242 4242"
                                   class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none transition focus:border-slate-500 focus:bg-white"
                                   dir="ltr">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">انتهاء الصلاحية</label>
                                <input type="text" name="expiry_date"
                                       value="{{ old('expiry_date', '12/30') }}"
                                       placeholder="12/30"
                                       class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none transition focus:border-slate-500 focus:bg-white"
                                       dir="ltr">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">CVC</label>
                                <input type="text" name="cvc"
                                       value="{{ old('cvc', '123') }}"
                                       placeholder="123"
                                       class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none transition focus:border-slate-500 focus:bg-white"
                                       dir="ltr">
                            </div>
                        </div>

                        @if ($errors->any())
                            <div class="flex items-center gap-2 rounded-xl bg-red-50 border border-red-200 px-3 py-2.5 text-xs text-red-700">
                                <i class="ti ti-alert-circle flex-shrink-0"></i>
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <button type="submit" id="payNowBtn" data-scenario="success"
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-700">
                            <i class="ti ti-lock-check text-sm leading-none"></i>
                            دفع {{ $totalMajor }} {{ $currency }}
                        </button>

                        <div class="grid grid-cols-2 gap-2">
                            <button type="submit" data-scenario="failed"
                                    class="flex items-center justify-center rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100">
                                محاكاة الرفض
                            </button>
                            <button type="submit" data-scenario="cancel"
                                    class="flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
                                إلغاء
                            </button>
                        </div>

                        <p class="text-center text-xs text-slate-400">
                            بطاقة تجريبية: 4242 4242 4242 4242
                        </p>
                    </form>

                {{-- CLOSED state --}}
                @else
                    <div class="text-center py-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 mx-auto mb-3">
                            <i class="ti ti-ban text-2xl leading-none text-slate-400"></i>
                        </div>
                        <p class="font-semibold text-slate-700 text-sm mb-1">الدفع غير متاح</p>
                        <p class="text-xs text-slate-400 mb-4">هذه الفاتورة لم تعد مفتوحة للدفع</p>
                        <a href="{{ route('client.invoices') }}"
                           class="flex items-center justify-center gap-2 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            <i class="ti ti-arrow-right text-sm leading-none"></i>
                            العودة للفواتير
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('demoCheckoutForm');
    const payNowBtn = document.getElementById('payNowBtn');
    const scenarioInput = document.getElementById('paymentScenario');
    if (!form || !payNowBtn || !scenarioInput) return;

    form.querySelectorAll('button[type="submit"][data-scenario]').forEach(btn => {
        btn.addEventListener('click', function () {
            scenarioInput.value = btn.dataset.scenario || 'success';
        });
    });

    form.addEventListener('submit', function (e) {
        const scenario = e.submitter?.dataset?.scenario || scenarioInput.value || 'success';
        scenarioInput.value = scenario;
        if (scenario !== 'success') return;
        payNowBtn.disabled = true;
        payNowBtn.innerHTML = '<i class="ti ti-loader-2 animate-spin text-sm leading-none"></i> جاري معالجة الدفع...';
    });
});
</script>
@endpush
</x-client-layout>
