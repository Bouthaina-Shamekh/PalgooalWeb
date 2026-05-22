<x-client-layout>
@php
    $statusMap = [
        'active'    => ['label' => 'نشط',    'dot' => 'bg-emerald-500', 'pill' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'pending'   => ['label' => 'معلق',   'dot' => 'bg-amber-400',   'pill' => 'bg-amber-50 text-amber-700 ring-amber-200'],
        'expired'   => ['label' => 'منتهي',  'dot' => 'bg-red-500',     'pill' => 'bg-red-50 text-red-700 ring-red-200'],
        'cancelled' => ['label' => 'ملغى',   'dot' => 'bg-slate-400',   'pill' => 'bg-slate-100 text-slate-600 ring-slate-200'],
    ];
@endphp

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-sm text-slate-400 mb-6 font-cairo" dir="rtl">
    <a href="{{ route('client.home') }}" class="hover:text-slate-700 transition">الرئيسية</a>
    <i class="ti ti-chevron-left text-xs"></i>
    <span class="text-slate-700 font-medium">النطاقات</span>
</nav>

{{-- Flash Messages --}}
@if (session('success'))
    <div class="mb-5 flex items-center gap-3 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700" dir="rtl">
        <i class="ti ti-circle-check flex-shrink-0"></i>
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="mb-5 flex items-center gap-3 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700" dir="rtl">
        <i class="ti ti-alert-circle flex-shrink-0"></i>
        {{ session('error') }}
    </div>
@endif

{{-- Hero Card --}}
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 p-6 lg:p-8 mb-6 shadow-xl" dir="rtl">
    <div class="pointer-events-none absolute -top-10 -left-10 h-48 w-48 rounded-full bg-white/5"></div>
    <div class="pointer-events-none absolute -bottom-10 -right-10 h-64 w-64 rounded-full bg-white/5"></div>

    <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-white/10 text-white shadow-inner">
                <i class="ti ti-world text-2xl leading-none"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-white lg:text-2xl">نطاقاتي</h1>
                <p class="mt-1 text-sm text-slate-400">إدارة النطاقات المسجلة وتجديدها</p>
            </div>
        </div>
        <a href="{{ route('client.domains.search') }}"
           class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-slate-100">
            <i class="ti ti-search text-base leading-none"></i>
            البحث عن نطاق جديد
        </a>
    </div>

    {{-- Stats --}}
    <div class="relative mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
        @php
            $stats = [
                ['icon' => 'ti-world',         'value' => $domainStats['total'] ?? 0,   'label' => 'إجمالي'],
                ['icon' => 'ti-circle-check',  'value' => $domainStats['active'] ?? 0,  'label' => 'نشط'],
                ['icon' => 'ti-loader-2',      'value' => $domainStats['pending'] ?? 0, 'label' => 'معلق'],
                ['icon' => 'ti-alert-circle',  'value' => $domainStats['expired'] ?? 0, 'label' => 'منتهي'],
            ];
        @endphp
        @foreach ($stats as $stat)
        <div class="rounded-xl bg-white/10 px-4 py-3 backdrop-blur-sm">
            <div class="flex items-center gap-2 text-slate-400 text-xs mb-1">
                <i class="ti {{ $stat['icon'] }} text-sm"></i>
                {{ $stat['label'] }}
            </div>
            <p class="text-lg font-bold text-white leading-tight">{{ $stat['value'] }}</p>
        </div>
        @endforeach
    </div>
</div>

{{-- Domains Table --}}
<div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm" dir="rtl">
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
        <div class="flex items-center gap-2">
            <i class="ti ti-list text-slate-500"></i>
            <h2 class="font-semibold text-slate-800">قائمة النطاقات</h2>
            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500">
                {{ $domains->total() }}
            </span>
        </div>
    </div>

    @forelse ($domains as $domain)
        @php
            $statusKey   = strtolower((string) $domain->status);
            $status      = $statusMap[$statusKey] ?? ['label' => ucfirst($statusKey ?: '—'), 'dot' => 'bg-slate-400', 'pill' => 'bg-slate-100 text-slate-600 ring-slate-200'];
            $renewalDate = $domain->renewal_date ? \Carbon\Carbon::parse($domain->renewal_date) : null;
            $isExpiringSoon = $renewalDate && $renewalDate->diffInDays(now()) <= 30 && $renewalDate->isFuture();
        @endphp
        <div class="group border-b border-slate-100 last:border-0 transition hover:bg-slate-50/60">
            <div class="flex items-center gap-4 px-6 py-4">
                {{-- Icon --}}
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                    <i class="ti ti-world text-base leading-none"></i>
                </div>

                {{-- Info --}}
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-semibold text-slate-800 text-sm break-all">{{ $domain->domain_name }}</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $status['pill'] }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $status['dot'] }}"></span>
                            {{ $status['label'] }}
                        </span>
                        @if ($isExpiringSoon)
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">
                                <i class="ti ti-clock text-xs"></i>
                                ينتهي قريباً
                            </span>
                        @endif
                        @if ($domain->auto_renew)
                            <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700 ring-1 ring-sky-200">
                                <i class="ti ti-refresh text-xs"></i>
                                تجديد تلقائي
                            </span>
                        @endif
                    </div>
                    <p class="mt-0.5 text-xs text-slate-400">
                        @if ($renewalDate)
                            ينتهي في {{ $renewalDate->format('d/m/Y') }}
                        @else
                            لا يوجد تاريخ تجديد
                        @endif
                        @if ($domain->template)
                            · {{ $domain->template->name }}
                        @endif
                    </p>
                </div>

                {{-- Actions --}}
                <div class="flex flex-shrink-0 items-center gap-2 opacity-0 group-hover:opacity-100 transition">
                    {{-- Copy --}}
                    <button type="button"
                            data-copy-value="{{ $domain->domain_name }}"
                            title="نسخ النطاق"
                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 transition hover:bg-slate-50 hover:text-slate-700">
                        <i class="ti ti-copy text-sm leading-none"></i>
                    </button>

                    {{-- DNS --}}
                    <a href="{{ route('client.domains.dns.edit', $domain->id) }}"
                       title="إعدادات DNS"
                       class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 transition hover:bg-slate-50 hover:text-slate-700">
                        <i class="ti ti-server text-sm leading-none"></i>
                    </a>

                    {{-- Renew --}}
                    <form method="POST" action="{{ route('client.domains.renew', $domain->id) }}">
                        @csrf
                        <button type="submit"
                                title="تجديد"
                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 transition hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200">
                            <i class="ti ti-refresh text-sm leading-none"></i>
                        </button>
                    </form>

                    {{-- Auto-renew toggle --}}
                    <form method="POST" action="{{ route('client.domains.auto-renew', $domain->id) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                title="{{ $domain->auto_renew ? 'إيقاف التجديد التلقائي' : 'تفعيل التجديد التلقائي' }}"
                                class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition
                                    {{ $domain->auto_renew
                                        ? 'border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100'
                                        : 'border-slate-200 bg-white text-slate-500 hover:bg-slate-50' }}">
                            <i class="ti ti-refresh text-xs leading-none"></i>
                            {{ $domain->auto_renew ? 'إيقاف التلقائي' : 'تفعيل التلقائي' }}
                        </button>
                    </form>

                    {{-- Delete --}}
                    <form method="POST" action="{{ route('client.domains.destroy', $domain->id) }}"
                          onsubmit="return confirm('حذف هذا النطاق نهائياً؟')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                title="حذف"
                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 transition hover:bg-red-50 hover:text-red-600 hover:border-red-200">
                            <i class="ti ti-trash text-sm leading-none"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="flex flex-col items-center justify-center py-16 text-center" dir="rtl">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 mb-4">
                <i class="ti ti-world-off text-3xl leading-none text-slate-300"></i>
            </div>
            <p class="font-semibold text-slate-600">لا توجد نطاقات بعد</p>
            <p class="text-sm text-slate-400 mt-1">ابدأ بالبحث عن نطاق وإضافته لحسابك</p>
            <a href="{{ route('client.domains.search') }}"
               class="mt-5 inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">
                <i class="ti ti-search text-sm leading-none"></i>
                البحث عن نطاق
            </a>
        </div>
    @endforelse
</div>

{{-- Pagination --}}
@if ($domains->hasPages())
    <div class="mt-5 flex justify-center" dir="rtl">
        {{ $domains->links() }}
    </div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-copy-value]').forEach((btn) => {
        btn.addEventListener('click', async function () {
            const value = btn.getAttribute('data-copy-value');
            const icon = btn.querySelector('i');
            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(value);
                } else {
                    const ta = document.createElement('textarea');
                    ta.value = value;
                    ta.style.position = 'absolute';
                    ta.style.left = '-9999px';
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    ta.remove();
                }
                if (icon) { icon.className = 'ti ti-check text-sm leading-none text-emerald-500'; }
                setTimeout(() => { if (icon) icon.className = 'ti ti-copy text-sm leading-none'; }, 1600);
            } catch (e) {}
        });
    });
});
</script>
@endpush
</x-client-layout>
