<x-client-layout>
@php
    $templateName    = $subscription->template?->translation()?->name ?? $subscription->template?->name ?? 'موقعك';
    $homePage        = $pages->firstWhere('is_home', true) ?? $pages->first();
    $homeTrans       = $homePage?->translations->firstWhere('locale', $locale) ?? $homePage?->translations->first();
    $siteName        = $homeTrans?->title ?: ($templateName ?: 'موقعك');
    $siteUrl         = $siteUrl ?? $subscription->activeSiteUrl();
    $domainName      = trim((string) ($subscription->domain_name ?? ''));
    $displayDomain   = $domainName ?: ($siteUrl ? parse_url($siteUrl, PHP_URL_HOST) : null);

    $totalSections   = $pages->sum(fn($p) => $p->sections->count());
    $visiblePages    = $pages->where('is_active', true)->count();

    $homepageEditorUrl = route('client.subscriptions.homepage-editor.index', $subscription);
    $pagesUrl          = route('client.subscriptions.pages', $subscription);
    $siteDashboardUrl  = route('client.subscriptions.site', $subscription);
    $headerEditorUrl   = route('client.subscriptions.site-header-editor.index', $subscription);
    $footerEditorUrl   = route('client.subscriptions.site-footer-editor.index', $subscription);

    $statusMap = [
        'active'    => ['label' => 'نشط',    'dot' => 'bg-emerald-500', 'pill' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'pending'   => ['label' => 'معلق',   'dot' => 'bg-amber-400',   'pill' => 'bg-amber-50 text-amber-700 ring-amber-200'],
        'suspended' => ['label' => 'موقوف',  'dot' => 'bg-red-500',     'pill' => 'bg-red-50 text-red-700 ring-red-200'],
        'cancelled' => ['label' => 'ملغى',   'dot' => 'bg-slate-400',   'pill' => 'bg-slate-100 text-slate-600 ring-slate-200'],
    ];
    $status = $statusMap[$subscription->status] ?? ['label' => ucfirst((string)($subscription->status ?? '')), 'dot' => 'bg-slate-400', 'pill' => 'bg-slate-100 text-slate-600 ring-slate-200'];
@endphp

{{-- ══════════════════════════════════════════
     BREADCRUMB
══════════════════════════════════════════ --}}
<nav class="flex items-center gap-2 text-sm text-slate-400 mb-6 font-cairo" dir="rtl">
    <a href="{{ route('client.home') }}" class="hover:text-slate-700 transition">الرئيسية</a>
    <i class="ti ti-chevron-left text-xs"></i>
    <a href="{{ route('client.subscriptions') }}" class="hover:text-slate-700 transition">الاشتراكات</a>
    <i class="ti ti-chevron-left text-xs"></i>
    <span class="text-slate-700 font-medium">{{ $siteName }}</span>
</nav>

{{-- ══════════════════════════════════════════
     HERO CARD
══════════════════════════════════════════ --}}
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 p-6 lg:p-8 mb-6 shadow-xl" dir="rtl">
    {{-- Decorative circles --}}
    <div class="pointer-events-none absolute -top-10 -left-10 h-48 w-48 rounded-full bg-white/5"></div>
    <div class="pointer-events-none absolute -bottom-10 -right-10 h-64 w-64 rounded-full bg-white/5"></div>

    <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-4">
            {{-- Site Icon --}}
            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-white/10 text-white shadow-inner">
                <i class="ti ti-world text-2xl leading-none"></i>
            </div>
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-xl font-bold text-white lg:text-2xl">{{ $siteName }}</h1>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $status['pill'] }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $status['dot'] }}"></span>
                        {{ $status['label'] }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-slate-400">{{ $templateName }}</p>
                @if ($displayDomain)
                    <div class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-3 py-1 text-xs text-slate-300">
                        <i class="ti ti-link text-xs"></i>
                        {{ $displayDomain }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Primary Actions --}}
        <div class="flex flex-wrap gap-3">
            <a href="{{ $homepageEditorUrl }}"
               class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-slate-100">
                <i class="ti ti-pencil text-base leading-none"></i>
                تعديل الموقع
            </a>
            @if ($siteUrl)
            <a href="{{ $siteUrl }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 transition hover:bg-white/20">
                <i class="ti ti-external-link text-base leading-none"></i>
                عرض الموقع
            </a>
            @endif
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="relative mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
        @php
            $stats = [
                ['icon' => 'ti-layout-2',      'value' => $pages->count(),  'label' => 'صفحة',   'sub' => $visiblePages . ' مرئية'],
                ['icon' => 'ti-stack-2',        'value' => $totalSections,   'label' => 'قسم',    'sub' => 'في كل الصفحات'],
                ['icon' => 'ti-home',           'value' => $homePage ? 1 : 0,'label' => 'رئيسية', 'sub' => $homeTrans?->title ?? '—'],
                ['icon' => 'ti-calendar-check', 'value' => $subscription->next_due_date?->format('d/m') ?? '—', 'label' => 'تجديد', 'sub' => $subscription->next_due_date?->format('Y') ?? ''],
            ];
        @endphp
        @foreach ($stats as $stat)
        <div class="rounded-xl bg-white/10 px-4 py-3 backdrop-blur-sm">
            <div class="flex items-center gap-2 text-slate-400 text-xs mb-1">
                <i class="ti {{ $stat['icon'] }} text-sm"></i>
                {{ $stat['label'] }}
            </div>
            <p class="text-lg font-bold text-white leading-tight">{{ $stat['value'] }}</p>
            <p class="text-xs text-slate-400 truncate">{{ $stat['sub'] }}</p>
        </div>
        @endforeach
    </div>
</div>

{{-- ══════════════════════════════════════════
     QUICK ACTION CARDS
══════════════════════════════════════════ --}}
<div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-6" dir="rtl">
    @php
        $actions = [
            [
                'href'    => $homepageEditorUrl,
                'icon'    => 'ti-home-edit',
                'label'   => 'الصفحة الرئيسية',
                'sub'     => 'تعديل المحتوى',
                'color'   => 'from-violet-500 to-purple-600',
                'ring'    => 'ring-violet-200',
                'text'    => 'text-violet-600',
                'bg'      => 'bg-violet-50',
                'primary' => true,
            ],
            [
                'href'    => $pagesUrl,
                'icon'    => 'ti-layout-list',
                'label'   => 'الصفحات',
                'sub'     => $pages->count() . ' صفحات',
                'color'   => 'from-sky-500 to-blue-600',
                'ring'    => 'ring-sky-200',
                'text'    => 'text-sky-600',
                'bg'      => 'bg-sky-50',
                'primary' => false,
            ],
            [
                'href'    => $headerEditorUrl,
                'icon'    => 'ti-layout-navbar',
                'label'   => 'الهيدر',
                'sub'     => 'رأس الموقع',
                'color'   => 'from-emerald-500 to-teal-600',
                'ring'    => 'ring-emerald-200',
                'text'    => 'text-emerald-600',
                'bg'      => 'bg-emerald-50',
                'primary' => false,
            ],
            [
                'href'    => $footerEditorUrl,
                'icon'    => 'ti-layout-bottombar',
                'label'   => 'الفوتر',
                'sub'     => 'ذيل الموقع',
                'color'   => 'from-orange-400 to-amber-500',
                'ring'    => 'ring-orange-200',
                'text'    => 'text-orange-600',
                'bg'      => 'bg-orange-50',
                'primary' => false,
            ],
        ];
    @endphp

    @foreach ($actions as $action)
    <a href="{{ $action['href'] }}"
       class="group flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm ring-1 ring-transparent transition hover:shadow-md hover:ring-1 hover:{{ $action['ring'] }}">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $action['bg'] }} transition group-hover:scale-110">
            <i class="ti {{ $action['icon'] }} text-xl leading-none {{ $action['text'] }}"></i>
        </div>
        <div>
            <p class="font-semibold text-slate-800 text-sm">{{ $action['label'] }}</p>
            <p class="text-xs text-slate-400 mt-0.5">{{ $action['sub'] }}</p>
        </div>
    </a>
    @endforeach
</div>

{{-- ══════════════════════════════════════════
     PAGES LIST
══════════════════════════════════════════ --}}
<div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden" dir="rtl">
    {{-- Header --}}
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
        <div class="flex items-center gap-2">
            <i class="ti ti-layout-2 text-slate-500"></i>
            <h2 class="font-semibold text-slate-800">صفحات الموقع</h2>
            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500">
                {{ $pages->count() }}
            </span>
        </div>
        <a href="{{ $pagesUrl }}"
           class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50">
            <i class="ti ti-plus text-sm leading-none"></i>
            إضافة صفحة
        </a>
    </div>

    {{-- Rows --}}
    @forelse ($pages->sortByDesc('is_home') as $page)
        @php
            $pt         = $page->translations->firstWhere('locale', $locale) ?? $page->translations->first();
            $pageTitle  = $pt?->title ?? $page->slug ?? 'صفحة بدون عنوان';
            $pageSlug   = trim((string) ($pt?->slug ?? $page->slug ?? ''));
            $pageUrl    = $siteUrl
                            ? ($page->is_home || $pageSlug === '' ? $siteUrl : rtrim($siteUrl, '/') . '/' . ltrim($pageSlug, '/'))
                            : null;
            $editorUrl  = $page->is_home
                            ? $homepageEditorUrl
                            : route('client.subscriptions.pages.editor.index', ['subscription' => $subscription, 'page' => $page]);
            $secCount   = $page->sections->count();
        @endphp
        <div class="group flex items-center gap-4 px-6 py-4 border-b border-slate-100 last:border-0 transition hover:bg-slate-50/60">
            {{-- Icon --}}
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl
                        {{ $page->is_home ? 'bg-violet-50 text-violet-600' : 'bg-slate-100 text-slate-400' }}">
                <i class="ti {{ $page->is_home ? 'ti-home' : 'ti-file-text' }} text-base leading-none"></i>
            </div>

            {{-- Info --}}
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-semibold text-slate-800 text-sm">{{ $pageTitle }}</span>
                    @if ($page->is_home)
                        <span class="rounded-full bg-violet-100 px-2 py-0.5 text-xs font-semibold text-violet-700">رئيسية</span>
                    @endif
                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold
                                 {{ $page->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                        {{ $page->is_active ? 'مرئية' : 'مخفية' }}
                    </span>
                </div>
                <p class="mt-0.5 text-xs text-slate-400">
                    {{ $secCount }} {{ $secCount === 1 ? 'قسم' : 'أقسام' }}
                    @if (! $page->is_home && $pageSlug !== '')
                        · <span class="font-mono">/{{ $pageSlug }}</span>
                    @endif
                </p>
            </div>

            {{-- Actions --}}
            <div class="flex flex-shrink-0 items-center gap-2 opacity-0 transition group-hover:opacity-100">
                @if ($pageUrl)
                <a href="{{ $pageUrl }}" target="_blank" rel="noopener"
                   title="عرض الصفحة"
                   class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700">
                    <i class="ti ti-eye text-sm leading-none"></i>
                </a>
                @endif
                <a href="{{ $editorUrl }}"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-slate-700">
                    <i class="ti ti-pencil text-xs leading-none"></i>
                    تعديل
                </a>
            </div>
        </div>
    @empty
        <div class="flex flex-col items-center justify-center py-16 text-center text-slate-400" dir="rtl">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 mb-4">
                <i class="ti ti-file-off text-3xl leading-none text-slate-300"></i>
            </div>
            <p class="font-semibold text-slate-600">لا توجد صفحات بعد</p>
            <p class="text-sm mt-1">ابدأ بإضافة صفحتك الأولى</p>
            <a href="{{ $pagesUrl }}"
               class="mt-5 inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">
                <i class="ti ti-plus text-sm leading-none"></i>
                إضافة صفحة
            </a>
        </div>
    @endforelse
</div>
</x-client-layout>
