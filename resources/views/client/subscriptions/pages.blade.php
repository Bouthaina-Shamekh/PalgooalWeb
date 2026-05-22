<x-client-layout>
@php
    $templateName    = $subscription->template?->translation()?->name ?? $subscription->template?->name ?? 'موقعك';
    $showUrl         = route('client.subscriptions.show', $subscription);
    $createPageUrl   = route('client.subscriptions.pages.store', $subscription);
    $homepageEditorUrl = route('client.subscriptions.homepage-editor.index', $subscription);
    $pageCount       = $pages->count();
    $activePagesCount = $pages->where('is_active', true)->count();
    $createPageErrors = $errors->getBag('createPage');
    $pageSettingsErrors = $errors->getBag('pageSettings');
    $siteUrl         = $subscription->activeSiteUrl();
    $siteUrl         = $siteUrl ? rtrim($siteUrl, '/') : null;
@endphp

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-sm text-slate-400 mb-6 font-cairo" dir="rtl">
    <a href="{{ route('client.home') }}" class="hover:text-slate-700 transition">الرئيسية</a>
    <i class="ti ti-chevron-left text-xs"></i>
    <a href="{{ route('client.subscriptions') }}" class="hover:text-slate-700 transition">الاشتراكات</a>
    <i class="ti ti-chevron-left text-xs"></i>
    <a href="{{ $showUrl }}" class="hover:text-slate-700 transition">{{ $templateName }}</a>
    <i class="ti ti-chevron-left text-xs"></i>
    <span class="text-slate-700 font-medium">الصفحات</span>
</nav>

{{-- Header --}}
<div class="flex items-center justify-between mb-6" dir="rtl">
    <div>
        <h1 class="text-xl font-bold text-slate-900">صفحات الموقع</h1>
        <p class="text-sm text-slate-400 mt-0.5">{{ $pageCount }} صفحة · {{ $activePagesCount }} مرئية</p>
    </div>
    <button onclick="document.getElementById('add-page-form').classList.toggle('hidden')"
            class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">
        <i class="ti ti-plus text-sm leading-none"></i>
        إضافة صفحة
    </button>
</div>

{{-- Add Page Form --}}
<div id="add-page-form" class="{{ $createPageErrors->any() ? '' : 'hidden' }} rounded-2xl border border-slate-200 bg-white p-5 mb-5 shadow-sm" dir="rtl">
    <p class="text-sm font-semibold text-slate-700 mb-4">صفحة جديدة</p>
    <form method="POST" action="{{ $createPageUrl }}" class="flex flex-col sm:flex-row gap-3">
        @csrf
        <div class="flex-1">
            <input
                name="title"
                type="text"
                value="{{ old('title') }}"
                placeholder="عنوان الصفحة (مثال: من نحن)"
                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:bg-white"
                required
                autofocus
            >
            @if ($createPageErrors->has('title'))
                <p class="mt-1.5 text-xs text-rose-600">{{ $createPageErrors->first('title') }}</p>
            @endif
        </div>
        <div class="sm:w-48">
            <input
                name="slug"
                type="text"
                value="{{ old('slug') }}"
                placeholder="about-us (اختياري)"
                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:bg-white"
            >
            @if ($createPageErrors->has('slug'))
                <p class="mt-1.5 text-xs text-rose-600">{{ $createPageErrors->first('slug') }}</p>
            @endif
        </div>
        <button type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700 whitespace-nowrap">
            <i class="ti ti-device-floppy text-sm leading-none"></i>
            إنشاء وتعديل
        </button>
    </form>
</div>

{{-- Pages List --}}
<div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm" dir="rtl">
    @forelse ($pages->sortByDesc('is_home') as $page)
        @php
            $pt        = $page->translations->firstWhere('locale', $locale) ?? $page->translations->first();
            $pageTitle = $pt?->title ?? $page->slug ?? 'صفحة بدون عنوان';
            $pageSlug  = trim((string) ($pt?->slug ?? $page->slug ?? ''));
            $pageUrl   = $siteUrl
                ? ($page->is_home || $pageSlug === '' ? $siteUrl : $siteUrl . '/' . ltrim($pageSlug, '/'))
                : null;
            $editorUrl = $page->is_home
                ? $homepageEditorUrl
                : route('client.subscriptions.pages.editor.index', ['subscription' => $subscription, 'page' => $page]);
            $updateUrl   = route('client.subscriptions.pages.update', ['subscription' => $subscription, 'page' => $page]);
            $setHomeUrl  = route('client.subscriptions.pages.set-home', ['subscription' => $subscription, 'page' => $page]);
            $deleteUrl   = route('client.subscriptions.pages.destroy', ['subscription' => $subscription, 'page' => $page]);
            $hasSettingsError = $pageSettingsErrors->any() && (string) old('page_id') === (string) $page->id;
        @endphp

        <div class="border-b border-slate-100 last:border-0" x-data="{ open: {{ $hasSettingsError ? 'true' : 'false' }} }">
            {{-- Main Row --}}
            <div class="group flex items-center gap-4 px-5 py-4 transition hover:bg-slate-50/70">
                {{-- Icon --}}
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl
                    {{ $page->is_home ? 'bg-violet-50 text-violet-600' : 'bg-slate-100 text-slate-400' }}">
                    <i class="ti {{ $page->is_home ? 'ti-home' : 'ti-file-text' }} text-sm leading-none"></i>
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
                        {{ $page->sections->count() }} أقسام
                        @if (! $page->is_home && $pageSlug !== '')
                            · <span class="font-mono">/{{ $pageSlug }}</span>
                        @endif
                    </p>
                </div>

                {{-- Actions --}}
                <div class="flex flex-shrink-0 items-center gap-2">
                    {{-- Settings toggle --}}
                    <button @click="open = !open"
                            title="الإعدادات"
                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 transition hover:bg-slate-50 hover:text-slate-700">
                        <i class="ti ti-settings text-sm leading-none"></i>
                    </button>
                    @if ($pageUrl)
                    <a href="{{ $pageUrl }}" target="_blank" rel="noopener"
                       title="عرض الصفحة"
                       class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 transition hover:bg-slate-50 hover:text-slate-700">
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

            {{-- Settings Panel --}}
            <div x-show="open" x-cloak class="border-t border-slate-100 bg-slate-50/50 px-5 py-4">
                <form method="POST" action="{{ $updateUrl }}" class="flex flex-col sm:flex-row gap-3 mb-3">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="page_id" value="{{ $page->id }}">
                    <div class="flex-1">
                        <input type="text" name="title"
                               value="{{ $hasSettingsError ? old('title') : $pageTitle }}"
                               placeholder="عنوان الصفحة"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm outline-none transition focus:border-slate-500"
                               required>
                        @if ($hasSettingsError && $pageSettingsErrors->has('title'))
                            <p class="mt-1 text-xs text-rose-600">{{ $pageSettingsErrors->first('title') }}</p>
                        @endif
                    </div>
                    <div class="sm:w-44">
                        <input type="text" name="slug"
                               value="{{ $hasSettingsError ? old('slug') : $pageSlug }}"
                               placeholder="slug (اختياري)"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm outline-none transition focus:border-slate-500">
                        @if ($hasSettingsError && $pageSettingsErrors->has('slug'))
                            <p class="mt-1 text-xs text-rose-600">{{ $pageSettingsErrors->first('slug') }}</p>
                        @endif
                    </div>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-slate-800 px-4 py-2 text-xs font-semibold text-white transition hover:bg-slate-700 whitespace-nowrap">
                        <i class="ti ti-check text-xs leading-none"></i>
                        حفظ
                    </button>
                </form>

                <div class="flex flex-wrap gap-2">
                    @if (! $page->is_home)
                        <form method="POST" action="{{ $setHomeUrl }}">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700 transition hover:bg-sky-100">
                                <i class="ti ti-home-check text-xs leading-none"></i>
                                تعيين كرئيسية
                            </button>
                        </form>
                    @endif

                    @if ($pageCount > 1)
                        <form method="POST" action="{{ $deleteUrl }}"
                              onsubmit="return confirm('{{ $page->is_home ? 'حذف الرئيسية وترقية صفحة أخرى تلقائياً؟' : 'حذف هذه الصفحة؟' }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">
                                <i class="ti ti-trash text-xs leading-none"></i>
                                حذف
                            </button>
                        </form>
                    @else
                        <span class="text-xs text-slate-400 self-center">يجب الإبقاء على صفحة واحدة على الأقل</span>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="flex flex-col items-center justify-center py-16 text-center" dir="rtl">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 mb-3">
                <i class="ti ti-file-off text-2xl leading-none text-slate-300"></i>
            </div>
            <p class="font-semibold text-slate-600 text-sm">لا توجد صفحات بعد</p>
            <p class="text-xs text-slate-400 mt-1 mb-4">ابدأ بإضافة أول صفحة لموقعك</p>
            <button onclick="document.getElementById('add-page-form').classList.remove('hidden'); document.getElementById('add-page-form').scrollIntoView({behavior:'smooth'})"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">
                <i class="ti ti-plus text-sm leading-none"></i>
                إضافة صفحة
            </button>
        </div>
    @endforelse
</div>
</x-client-layout>
