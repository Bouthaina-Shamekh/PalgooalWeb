<x-client-layout>
@php
    $statusMap = [
        'paid'      => ['label' => 'مدفوعة',       'dot' => 'bg-emerald-500', 'pill' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'unpaid'    => ['label' => 'غير مدفوعة',   'dot' => 'bg-red-500',     'pill' => 'bg-red-50 text-red-700 ring-red-200'],
        'draft'     => ['label' => 'مسودة',        'dot' => 'bg-amber-400',   'pill' => 'bg-amber-50 text-amber-700 ring-amber-200'],
        'cancelled' => ['label' => 'ملغاة',        'dot' => 'bg-slate-400',   'pill' => 'bg-slate-100 text-slate-600 ring-slate-200'],
        'overdue'   => ['label' => 'متأخرة',       'dot' => 'bg-orange-500',  'pill' => 'bg-orange-50 text-orange-700 ring-orange-200'],
    ];

    $totalAmount   = $invoices->sum(fn($i) => ($i->total_cents ?? 0) / 100);
    $unpaidAmount  = $invoices->where('status', 'unpaid')->sum(fn($i) => ($i->total_cents ?? 0) / 100);
    $paidCount     = $invoices->where('status', 'paid')->count();
    $unpaidCount   = $invoices->whereIn('status', ['unpaid', 'draft'])->count();
    $currency      = $invoices->first()?->currency ?? 'USD';
@endphp

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-sm text-slate-400 mb-6 font-cairo" dir="rtl">
    <a href="{{ route('client.home') }}" class="hover:text-slate-700 transition">الرئيسية</a>
    <i class="ti ti-chevron-left text-xs"></i>
    <span class="text-slate-700 font-medium">الفواتير</span>
</nav>

{{-- Flash --}}
@foreach (['success' => 'emerald', 'error' => 'red', 'info' => 'sky'] as $type => $color)
    @if (session($type))
        <div class="mb-5 flex items-center gap-3 rounded-xl bg-{{ $color }}-50 border border-{{ $color }}-200 px-4 py-3 text-sm text-{{ $color }}-700" dir="rtl">
            <i class="ti ti-{{ $type === 'success' ? 'circle-check' : ($type === 'error' ? 'alert-circle' : 'info-circle') }} flex-shrink-0"></i>
            {{ session($type) }}
        </div>
    @endif
@endforeach

{{-- Hero --}}
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 p-6 lg:p-8 mb-6 shadow-xl" dir="rtl">
    <div class="pointer-events-none absolute -top-10 -left-10 h-48 w-48 rounded-full bg-white/5"></div>
    <div class="pointer-events-none absolute -bottom-10 -right-10 h-64 w-64 rounded-full bg-white/5"></div>

    <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-white/10 text-white shadow-inner">
                <i class="ti ti-file-invoice text-2xl leading-none"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-white lg:text-2xl">الفواتير</h1>
                <p class="mt-1 text-sm text-slate-400">سجل مدفوعاتك ومتابعة الفواتير</p>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="relative mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-xl bg-white/10 px-4 py-3 backdrop-blur-sm">
            <div class="flex items-center gap-2 text-slate-400 text-xs mb-1">
                <i class="ti ti-files text-sm"></i>
                إجمالي الفواتير
            </div>
            <p class="text-lg font-bold text-white leading-tight">{{ $invoices->total() }}</p>
        </div>
        <div class="rounded-xl bg-white/10 px-4 py-3 backdrop-blur-sm">
            <div class="flex items-center gap-2 text-slate-400 text-xs mb-1">
                <i class="ti ti-circle-check text-sm"></i>
                مدفوعة
            </div>
            <p class="text-lg font-bold text-white leading-tight">{{ $paidCount }}</p>
        </div>
        <div class="rounded-xl bg-white/10 px-4 py-3 backdrop-blur-sm">
            <div class="flex items-center gap-2 text-slate-400 text-xs mb-1">
                <i class="ti ti-alert-circle text-sm"></i>
                غير مدفوعة
            </div>
            <p class="text-lg font-bold text-white leading-tight">{{ $unpaidCount }}</p>
        </div>
        <div class="rounded-xl bg-white/10 px-4 py-3 backdrop-blur-sm">
            <div class="flex items-center gap-2 text-slate-400 text-xs mb-1">
                <i class="ti ti-cash text-sm"></i>
                مستحق الدفع
            </div>
            <p class="text-lg font-bold text-white leading-tight">{{ number_format($unpaidAmount, 2) }} <span class="text-xs font-normal text-slate-400">{{ $currency }}</span></p>
        </div>
    </div>
</div>

{{-- Unpaid Alert --}}
@if ($unpaidCount > 0)
<div class="mb-5 flex items-center justify-between gap-4 rounded-2xl bg-amber-50 border border-amber-200 px-5 py-4" dir="rtl">
    <div class="flex items-center gap-3">
        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
            <i class="ti ti-alert-triangle text-base leading-none"></i>
        </div>
        <div>
            <p class="text-sm font-semibold text-amber-800">{{ $unpaidCount }} {{ $unpaidCount === 1 ? 'فاتورة تنتظر الدفع' : 'فواتير تنتظر الدفع' }}</p>
            <p class="text-xs text-amber-600 mt-0.5">المبلغ المستحق: {{ number_format($unpaidAmount, 2) }} {{ $currency }}</p>
        </div>
    </div>
</div>
@endif

{{-- Invoices List --}}
<div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm" dir="rtl">
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
        <div class="flex items-center gap-2">
            <i class="ti ti-list text-slate-500"></i>
            <h2 class="font-semibold text-slate-800">قائمة الفواتير</h2>
            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500">
                {{ $invoices->total() }}
            </span>
        </div>
    </div>

    @forelse ($invoices as $invoice)
        @php
            $statusKey = strtolower((string)($invoice->status ?? 'draft'));
            $status    = $statusMap[$statusKey] ?? ['label' => ucfirst($statusKey), 'dot' => 'bg-slate-400', 'pill' => 'bg-slate-100 text-slate-600 ring-slate-200'];
            $amount    = number_format(($invoice->total_cents ?? 0) / 100, 2);
            $isUnpaid  = in_array($statusKey, ['unpaid', 'draft']);
            $isPaid    = $statusKey === 'paid';
            $itemsSummary = $invoice->items->take(2)->pluck('description')->filter()->implode(' · ');
        @endphp

        <div class="group flex items-center gap-4 px-6 py-4 border-b border-slate-100 last:border-0 transition hover:bg-slate-50/60">
            {{-- Icon --}}
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl
                {{ $isPaid ? 'bg-emerald-50 text-emerald-600' : ($isUnpaid ? 'bg-red-50 text-red-500' : 'bg-slate-100 text-slate-400') }}">
                <i class="ti ti-receipt text-base leading-none"></i>
            </div>

            {{-- Info --}}
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-semibold text-slate-800 text-sm">{{ $invoice->number }}</span>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $status['pill'] }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $status['dot'] }}"></span>
                        {{ $status['label'] }}
                    </span>
                </div>
                <p class="mt-0.5 text-xs text-slate-400 truncate">
                    {{ $itemsSummary ?: 'لا توجد بنود' }}
                    @if ($invoice->due_date)
                        · الاستحقاق: {{ $invoice->due_date->format('d/m/Y') }}
                    @endif
                </p>
            </div>

            {{-- Amount + Action --}}
            <div class="flex flex-shrink-0 items-center gap-3">
                <div class="text-end">
                    <p class="font-bold text-slate-800 text-sm">{{ $amount }}</p>
                    <p class="text-xs text-slate-400">{{ $invoice->currency ?? 'USD' }}</p>
                </div>

                @if ($isUnpaid)
                    <a href="{{ route('client.invoices.checkout', $invoice) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-slate-700 whitespace-nowrap">
                        <i class="ti ti-credit-card text-xs leading-none"></i>
                        ادفع الآن
                    </a>
                @elseif ($isPaid)
                    <a href="{{ route('client.invoices.checkout', $invoice) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 whitespace-nowrap">
                        <i class="ti ti-eye text-xs leading-none"></i>
                        عرض
                    </a>
                @else
                    <span class="text-xs text-slate-400">—</span>
                @endif
            </div>
        </div>
    @empty
        <div class="flex flex-col items-center justify-center py-16 text-center" dir="rtl">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 mb-4">
                <i class="ti ti-file-off text-3xl leading-none text-slate-300"></i>
            </div>
            <p class="font-semibold text-slate-600">لا توجد فواتير بعد</p>
            <p class="text-sm text-slate-400 mt-1">ستظهر هنا فواتير اشتراكاتك ونطاقاتك</p>
        </div>
    @endforelse
</div>

{{-- Pagination --}}
@if ($invoices->hasPages())
    <div class="mt-5 flex justify-center" dir="rtl">
        {{ $invoices->links() }}
    </div>
@endif

</x-client-layout>
