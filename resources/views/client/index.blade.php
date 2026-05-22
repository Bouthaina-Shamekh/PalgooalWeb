<x-client-layout>
@php
    $clientName = trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? ''));
    $hour = (int) now()->format('H');
    $greeting = $hour < 12 ? 'صباح الخير' : ($hour < 18 ? 'مساء الخير' : 'مساء النور');

    $statusMap = [
        'active'    => ['label' => 'نشط',    'dot' => 'bg-emerald-500', 'pill' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'pending'   => ['label' => 'معلق',   'dot' => 'bg-amber-400',   'pill' => 'bg-amber-50 text-amber-700 ring-amber-200'],
        'suspended' => ['label' => 'موقوف',  'dot' => 'bg-red-500',     'pill' => 'bg-red-50 text-red-700 ring-red-200'],
        'cancelled' => ['label' => 'ملغى',   'dot' => 'bg-slate-400',   'pill' => 'bg-slate-100 text-slate-600 ring-slate-200'],
    ];

    $invoiceStatusMap = [
        'paid'    => ['label' => 'مدفوعة',  'pill' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'unpaid'  => ['label' => 'غير مدفوعة', 'pill' => 'bg-red-50 text-red-700 ring-red-200'],
        'overdue' => ['label' => 'متأخرة',  'pill' => 'bg-orange-50 text-orange-700 ring-orange-200'],
    ];
@endphp

{{-- Welcome Hero --}}
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 p-6 lg:p-8 mb-6 shadow-xl" dir="rtl">
    <div class="pointer-events-none absolute -top-10 -left-10 h-48 w-48 rounded-full bg-white/5"></div>
    <div class="pointer-events-none absolute -bottom-10 -right-10 h-64 w-64 rounded-full bg-white/5"></div>

    <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-4">
            {{-- Avatar --}}
            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-white/10 text-white shadow-inner overflow-hidden">
                @if ($client->avatar)
                    <img src="{{ Storage::url($client->avatar) }}" alt="{{ $clientName }}" class="h-full w-full object-cover">
                @else
                    <i class="ti ti-user text-2xl leading-none"></i>
                @endif
            </div>
            <div>
                <p class="text-sm text-slate-400">{{ $greeting }}</p>
                <h1 class="text-xl font-bold text-white lg:text-2xl">{{ $clientName ?: 'مرحباً بك' }}</h1>
                @if ($client->company_name)
                    <p class="mt-0.5 text-sm text-slate-400">{{ $client->company_name }}</p>
                @endif
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('client.subscriptions') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-slate-100">
                <i class="ti ti-layout-dashboard text-base leading-none"></i>
                مواقعي
            </a>
            <a href="{{ route('client.domains.search') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 transition hover:bg-white/20">
                <i class="ti ti-search text-base leading-none"></i>
                بحث نطاق
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="relative mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
        @php
            $stats = [
                ['icon' => 'ti-package',       'value' => $client->subscriptions_count, 'label' => 'اشتراكات', 'href' => route('client.subscriptions')],
                ['icon' => 'ti-world',         'value' => $client->domains_count,       'label' => 'نطاقات',   'href' => route('client.domains.index')],
                ['icon' => 'ti-file-invoice',  'value' => $invoiceCount,               'label' => 'فواتير',   'href' => route('client.invoices')],
                ['icon' => 'ti-alert-circle',  'value' => $unpaidInvoiceCount,         'label' => 'غير مدفوعة', 'href' => route('client.invoices')],
            ];
        @endphp
        @foreach ($stats as $stat)
        <a href="{{ $stat['href'] }}"
           class="rounded-xl bg-white/10 px-4 py-3 backdrop-blur-sm transition hover:bg-white/15">
            <div class="flex items-center gap-2 text-slate-400 text-xs mb-1">
                <i class="ti {{ $stat['icon'] }} text-sm"></i>
                {{ $stat['label'] }}
            </div>
            <p class="text-lg font-bold text-white leading-tight">{{ $stat['value'] }}</p>
        </a>
        @endforeach
    </div>
</div>

{{-- Unpaid Invoice Alert --}}
@if ($unpaidInvoiceCount > 0)
<div class="mb-6 flex items-center justify-between gap-4 rounded-2xl bg-amber-50 border border-amber-200 px-5 py-4" dir="rtl">
    <div class="flex items-center gap-3">
        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
            <i class="ti ti-alert-triangle text-base leading-none"></i>
        </div>
        <div>
            <p class="text-sm font-semibold text-amber-800">لديك {{ $unpaidInvoiceCount }} {{ $unpaidInvoiceCount === 1 ? 'فاتورة غير مدفوعة' : 'فواتير غير مدفوعة' }}</p>
            <p class="text-xs text-amber-600 mt-0.5">قم بتسديدها لتجنب تعليق الخدمة</p>
        </div>
    </div>
    <a href="{{ route('client.invoices') }}"
       class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-amber-700">
        عرض الفواتير
        <i class="ti ti-arrow-left text-xs"></i>
    </a>
</div>
@endif

{{-- Main Grid --}}
<div class="grid grid-cols-1 lg:grid-cols-5 gap-5" dir="rtl">

    {{-- Recent Subscriptions --}}
    <div class="lg:col-span-3 rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <i class="ti ti-layout-dashboard text-slate-500"></i>
                <h2 class="font-semibold text-slate-800 text-sm">آخر الاشتراكات</h2>
            </div>
            <a href="{{ route('client.subscriptions') }}"
               class="text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
                عرض الكل <i class="ti ti-arrow-left text-xs"></i>
            </a>
        </div>

        @forelse ($recentSubscriptions as $sub)
            @php
                $status = $statusMap[strtolower((string)$sub->status)] ?? ['label' => ucfirst((string)$sub->status), 'dot' => 'bg-slate-400', 'pill' => 'bg-slate-100 text-slate-600 ring-slate-200'];
            @endphp
            <a href="{{ route('client.subscriptions.show', $sub) }}"
               class="group flex items-center gap-4 px-5 py-4 border-b border-slate-100 last:border-0 transition hover:bg-slate-50/70">
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-400 group-hover:bg-slate-200 transition">
                    <i class="ti ti-world text-sm leading-none"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-slate-800 text-sm truncate">
                        {{ $sub->plan->name ?? 'اشتراك' }}
                    </p>
                    <p class="text-xs text-slate-400 mt-0.5 truncate">
                        {{ $sub->domain_name ?: ($sub->subdomain ? $sub->subdomain . '.*' : 'بدون نطاق') }}
                    </p>
                </div>
                <div class="flex flex-col items-end gap-1.5 flex-shrink-0">
                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $status['pill'] }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $status['dot'] }}"></span>
                        {{ $status['label'] }}
                    </span>
                    @if ($sub->next_due_date)
                        <span class="text-xs text-slate-400">{{ $sub->next_due_date->format('d/m/Y') }}</span>
                    @endif
                </div>
            </a>
        @empty
            <div class="flex flex-col items-center justify-center py-12 text-center px-5">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 mb-3">
                    <i class="ti ti-layout-dashboard text-xl leading-none text-slate-300"></i>
                </div>
                <p class="text-sm font-semibold text-slate-600">لا توجد اشتراكات بعد</p>
                <p class="text-xs text-slate-400 mt-1">تواصل معنا لبدء موقعك</p>
            </div>
        @endforelse
    </div>

    {{-- Recent Invoices --}}
    <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <i class="ti ti-file-invoice text-slate-500"></i>
                <h2 class="font-semibold text-slate-800 text-sm">آخر الفواتير</h2>
            </div>
            <a href="{{ route('client.invoices') }}"
               class="text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
                عرض الكل <i class="ti ti-arrow-left text-xs"></i>
            </a>
        </div>

        @forelse ($recentInvoices as $invoice)
            @php
                $iStatus = $invoiceStatusMap[strtolower((string)$invoice->status)] ?? ['label' => ucfirst((string)$invoice->status), 'pill' => 'bg-slate-100 text-slate-600 ring-slate-200'];
                $amount  = number_format(($invoice->total_cents ?? 0) / 100, 2);
            @endphp
            <div class="flex items-center gap-3 px-5 py-3.5 border-b border-slate-100 last:border-0 transition hover:bg-slate-50/70">
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl
                    {{ strtolower((string)$invoice->status) === 'paid' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500' }}">
                    <i class="ti ti-receipt text-sm leading-none"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-slate-800 text-xs truncate">{{ $invoice->number }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        {{ $invoice->due_date?->format('d/m/Y') ?: '—' }}
                    </p>
                </div>
                <div class="flex flex-col items-end gap-1 flex-shrink-0">
                    <span class="text-sm font-bold text-slate-800">{{ $amount }} <span class="text-xs font-normal text-slate-400">{{ $invoice->currency }}</span></span>
                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ring-1 {{ $iStatus['pill'] }}">
                        {{ $iStatus['label'] }}
                    </span>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center py-12 text-center px-5">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 mb-3">
                    <i class="ti ti-file-off text-xl leading-none text-slate-300"></i>
                </div>
                <p class="text-sm font-semibold text-slate-600">لا توجد فواتير بعد</p>
            </div>
        @endforelse
    </div>

</div>

{{-- Quick Nav Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5" dir="rtl">
    @php
        $quickLinks = [
            ['href' => route('client.subscriptions'),    'icon' => 'ti-layout-dashboard', 'label' => 'مواقعي',        'color' => 'text-violet-600', 'bg' => 'bg-violet-50', 'ring' => 'hover:ring-violet-200'],
            ['href' => route('client.domains.index'),    'icon' => 'ti-world',            'label' => 'النطاقات',      'color' => 'text-sky-600',    'bg' => 'bg-sky-50',    'ring' => 'hover:ring-sky-200'],
            ['href' => route('client.invoices'),         'icon' => 'ti-file-invoice',     'label' => 'الفواتير',      'color' => 'text-emerald-600','bg' => 'bg-emerald-50','ring' => 'hover:ring-emerald-200'],
            ['href' => route('client.update_account'),   'icon' => 'ti-user-edit',        'label' => 'حسابي',         'color' => 'text-orange-600', 'bg' => 'bg-orange-50', 'ring' => 'hover:ring-orange-200'],
        ];
    @endphp
    @foreach ($quickLinks as $link)
    <a href="{{ $link['href'] }}"
       class="group flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm ring-1 ring-transparent transition hover:shadow-md hover:ring-1 {{ $link['ring'] }}">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $link['bg'] }} transition group-hover:scale-110">
            <i class="ti {{ $link['icon'] }} text-xl leading-none {{ $link['color'] }}"></i>
        </div>
        <p class="font-semibold text-slate-800 text-sm">{{ $link['label'] }}</p>
    </a>
    @endforeach
</div>

</x-client-layout>
