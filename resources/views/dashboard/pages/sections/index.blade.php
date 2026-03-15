@php
    $pageTranslation = method_exists($page, 'translation') ? $page->translation() : null;
    $pageTitle = $pageTranslation?->title ?? $page->slug ?? ('#' . $page->id);
    $frontUrl = $page->is_home ? url('/') : (($pageTranslation?->slug ?? null) ? url($pageTranslation->slug) : url('/'));
    $currentLocale = app()->getLocale();
    $activeLocaleCodes = collect($languages ?? [])->pluck('code')->filter()->values();
    $activeCount = $sections->where('is_active', true)->count();
    $inactiveCount = $sections->count() - $activeCount;
    $needsTranslationCount = $sections->filter(fn ($section) => $activeLocaleCodes->diff($section->translations->pluck('locale'))->isNotEmpty())->count();
    $groupedTypes = collect($sectionTypes ?? [])->groupBy(fn ($meta) => $meta['category'] ?? 'other');
    $highlightSectionId = (int) request('highlight');
    $pageBuilderMode = in_array($page->builder_mode, ['visual', 'sections'], true) ? $page->builder_mode : 'sections';
    $isRtl = current_dir() === 'rtl';
    $drawerClosedTranslateClass = $isRtl ? '-translate-x-full' : 'translate-x-full';
@endphp

@extends('dashboard.pages.sections.layouts.workspace')

@section('workspace-header-actions')
    <button type="button" data-open-section-library class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
        {{ __('Add Section') }}
    </button>
@endsection

@section('workspace-main')
    @if (session('success'))
        <div class="mb-4 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="mb-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">{{ __('Sections') }}</p>
            <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $sections->count() }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ __('Blocks currently attached to this page.') }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">{{ __('Visibility') }}</p>
            <div class="mt-3 flex items-end justify-between gap-4">
                <div>
                    <p class="text-3xl font-semibold text-slate-900">{{ $activeCount }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Visible sections on the frontend.') }}</p>
                </div>
                <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-medium text-rose-700">{{ $inactiveCount }} {{ __('hidden') }}</span>
            </div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">{{ __('Translations') }}</p>
            <div class="mt-3 flex items-end justify-between gap-4">
                <div>
                    <p class="text-3xl font-semibold text-slate-900">{{ $needsTranslationCount }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Sections that still miss active locales.') }}</p>
                </div>
                <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700">{{ $activeLocaleCodes->count() }} {{ __('locales') }}</span>
            </div>
        </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4 lg:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">{{ __('Sections Workspace') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Manage order, visibility, duplication, and translations from one screen.') }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2 rtl:flex-row-reverse">
                    <a href="{{ route('dashboard.pages.index') }}" class="inline-flex items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">{{ __('Back to Pages') }}</a>
                    <a href="{{ route('dashboard.pages.builder', $page) }}" class="inline-flex items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">{{ __('Open Visual Builder') }}</a>
                    <a href="{{ $frontUrl }}" target="_blank" class="inline-flex items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">{{ __('Preview Frontend') }}</a>
                </div>
            </div>
        </div>

        <div class="p-5 lg:p-6">
            @if ($sections->isEmpty())
                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-slate-500 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ __('Start by adding your first section') }}</h3>
                    <p class="mx-auto mt-2 max-w-2xl text-sm text-slate-500">{{ __('Use the section library to create a ready-to-edit block instantly, then fine-tune it in the editor.') }}</p>
                    <div class="mt-6 flex flex-wrap items-center justify-center gap-3 rtl:flex-row-reverse">
                        <button type="button" data-open-section-library class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">{{ __('Open Section Library') }}</button>
                    </div>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($sections as $section)
                        @php
                            $translation = method_exists($section, 'translation') ? $section->translation($currentLocale) : null;
                            $fallbackTranslation = $translation ?? $section->translations->first();
                            $typeMeta = $sectionTypes[$section->type] ?? null;
                            $typeLabel = $typeMeta['label'] ?? \Illuminate\Support\Str::headline(str_replace(['_', '-'], ' ', $section->type));
                            $content = is_array($fallbackTranslation?->content ?? null) ? $fallbackTranslation->content : [];
                            $sectionTitle = $fallbackTranslation?->title ?? ($content['title'] ?? $typeLabel);
                            $sectionSummary = trim((string) ($content['subtitle'] ?? ''));
                            $sectionSummary = $sectionSummary !== '' ? $sectionSummary : ($typeMeta['description'] ?? __('No content summary yet.'));
                            $existingLocales = $section->translations->pluck('locale')->filter()->values();
                            $missingLocales = $activeLocaleCodes->diff($existingLocales)->values();
                            $isComplete = $missingLocales->isEmpty();
                        @endphp

                        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition {{ $highlightSectionId === $section->id ? 'ring-2 ring-slate-900/10 border-slate-900/20' : 'hover:border-slate-300' }}">
                            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                                <div class="flex items-start gap-4 rtl:flex-row-reverse">
                                    <div class="min-w-[92px] rounded-3xl bg-slate-100 px-3 py-4 text-center">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">{{ __('Order') }}</p>
                                        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $section->order ?? $loop->iteration }}</p>
                                        <div class="mt-3 flex items-center justify-center gap-1 rtl:flex-row-reverse">
                                            <form action="{{ route('dashboard.pages.sections.move', [$page, $section]) }}" method="POST">@csrf<input type="hidden" name="direction" value="up"><button type="submit" class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50">{{ __('Up') }}</button></form>
                                            <form action="{{ route('dashboard.pages.sections.move', [$page, $section]) }}" method="POST">@csrf<input type="hidden" name="direction" value="down"><button type="submit" class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50">{{ __('Down') }}</button></form>
                                        </div>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-slate-900 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-white">{{ $typeLabel }}</span>
                                            @if ($section->variant)
                                                <span class="rounded-full bg-violet-50 px-3 py-1 text-xs font-medium text-violet-700">{{ $section->variant }}</span>
                                            @endif
                                            <span class="rounded-full px-3 py-1 text-xs font-medium {{ $section->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">{{ $section->is_active ? __('Active') : __('Hidden') }}</span>
                                            <span class="rounded-full px-3 py-1 text-xs font-medium {{ $isComplete ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700' }}">{{ $isComplete ? __('Translations complete') : __('Missing translations') }}</span>
                                        </div>
                                        <h3 class="mt-3 truncate text-xl font-semibold text-slate-900">{{ $sectionTitle }}</h3>
                                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">{{ \Illuminate\Support\Str::limit($sectionSummary, 180) }}</p>
                                        <div class="mt-4 flex flex-wrap items-center gap-2">
                                            @foreach ($existingLocales as $locale)
                                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-700">{{ strtoupper($locale) }}</span>
                                            @endforeach
                                            @foreach ($missingLocales as $locale)
                                                <span class="rounded-full border border-dashed border-amber-300 bg-amber-50 px-2.5 py-1 text-[11px] font-medium text-amber-700">{{ strtoupper($locale) }} {{ __('missing') }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 rtl:flex-row-reverse xl:max-w-[18rem] xl:justify-end">
                                    <form action="{{ route('dashboard.pages.sections.toggle-active', [$page, $section]) }}" method="POST">@csrf<button type="submit" class="inline-flex items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">{{ $section->is_active ? __('Hide') : __('Show') }}</button></form>
                                    <form action="{{ route('dashboard.pages.sections.duplicate', [$page, $section]) }}" method="POST">@csrf<button type="submit" class="inline-flex items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">{{ __('Duplicate') }}</button></form>
                                    <a href="{{ route('dashboard.pages.sections.edit', [$page, $section]) }}" class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">{{ __('Edit Content') }}</a>
                                    <form action="{{ route('dashboard.pages.sections.destroy', [$page, $section]) }}" method="POST" onsubmit="return confirm('{{ __('Are you sure you want to delete this section? This action cannot be undone.') }}')">@csrf @method('DELETE')<button type="submit" class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-medium text-rose-700 transition hover:bg-rose-100">{{ __('Delete') }}</button></form>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div id="section-library-overlay" class="fixed inset-0 z-[60] hidden bg-slate-950/55"></div>
    <aside id="section-library-drawer" data-closed-translate="{{ $drawerClosedTranslateClass }}" class="fixed inset-y-0 z-[61] flex w-full max-w-2xl flex-col border-slate-200 bg-white shadow-2xl transition-transform duration-200 {{ $isRtl ? 'left-0 border-r -translate-x-full' : 'right-0 border-l translate-x-full' }}" aria-hidden="true">
        <div class="border-b border-slate-200 px-5 py-4 lg:px-6">
            <div class="flex items-start justify-between gap-4 rtl:flex-row-reverse">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">{{ __('Add Section to Page') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Choose a block and we will add it instantly, then open the editor for final customization.') }}</p>
                </div>
                <button type="button" data-close-section-library class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50" aria-label="{{ __('Close') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </div>
        <div class="border-b border-slate-200 px-5 py-4 lg:px-6">
            <div class="flex items-center gap-3 rtl:flex-row-reverse">
                <div class="relative flex-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 ltr:left-3 rtl:right-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m0 0A7.5 7.5 0 1 0 6 6a7.5 7.5 0 0 0 10.65 10.65Z" /></svg>
                    <input id="section-library-search" type="text" placeholder="{{ __('Search section types') }}" class="w-full rounded-full border border-slate-200 bg-white py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400 ltr:pl-10 ltr:pr-4 ltr:text-left rtl:pl-4 rtl:pr-10 rtl:text-right">
                </div>
            </div>
        </div>
        <div class="workspace-scrollbar flex-1 overflow-y-auto px-5 py-5 lg:px-6">
            <div class="space-y-6">
                @foreach ($groupedTypes as $category => $items)
                    <section data-library-group>
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">{{ \Illuminate\Support\Str::headline($category) }}</h4>
                            <span class="text-xs text-slate-400">{{ count($items) }} {{ __('types') }}</span>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            @foreach ($items as $type => $meta)
                                <form action="{{ route('dashboard.pages.sections.quick-store', $page) }}" method="POST" data-library-item data-library-text="{{ \Illuminate\Support\Str::lower($meta['label'] . ' ' . ($meta['description'] ?? '') . ' ' . $category) }}">
                                    @csrf
                                    <input type="hidden" name="type" value="{{ $type }}">
                                    <button type="submit" class="group flex h-full w-full flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md ltr:text-left rtl:text-right">
                                        @if (! empty($meta['preview']))
                                            <div class="aspect-[16/10] overflow-hidden bg-slate-100"><img src="{{ asset($meta['preview']) }}" alt="{{ $meta['label'] }}" class="h-full w-full object-cover transition duration-200 group-hover:scale-[1.02]" loading="lazy"></div>
                                        @endif
                                        <div class="flex flex-1 flex-col p-4">
                                            <div class="flex items-start justify-between gap-3 rtl:flex-row-reverse">
                                                <div>
                                                    <h5 class="text-sm font-semibold text-slate-900">{{ $meta['label'] }}</h5>
                                                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ $meta['description'] ?? __('No description provided.') }}</p>
                                                </div>
                                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-600">{{ \Illuminate\Support\Str::headline($category) }}</span>
                                            </div>
                                            <div class="mt-4 flex items-center justify-between text-xs text-slate-500"><span>{{ __('Creates a draft instantly') }}</span><span class="font-semibold text-slate-900">{{ __('Add') }}</span></div>
                                        </div>
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </aside>
@endsection

@section('workspace-sidebar')
    @if ($pageBuilderMode !== 'sections')
        <div class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">{{ __('Sections are not the active builder yet') }}</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('This page still renders from the Visual Builder. Switch the page mode if you want these sections to appear on the frontend.') }}</p>
            <form action="{{ route('dashboard.pages.builder-mode', $page) }}" method="POST" class="mt-4">
                @csrf
                <input type="hidden" name="builder_mode" value="sections">
                <button type="submit" class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">{{ __('Switch to Sections Builder') }}</button>
            </form>
        </div>
    @endif

    <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-5">
            <h3 class="text-xl font-semibold text-slate-900">{{ $pageTitle }}</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500">{{ __('Customize this page sections and keep the structure organized.') }}</p>
            <button type="button" data-open-section-library class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-slate-700 transition hover:text-slate-900 rtl:flex-row-reverse">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6 4.5 12l6 6M19.5 12h-15" />
                </svg>
                <span>{{ __('Need help? Open the section library') }}</span>
            </button>
        </div>

        <div class="space-y-4 px-5 py-5">
            <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                <h4 class="text-lg font-semibold text-slate-900">{{ __('Page Elements') }}</h4>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $sections->count() }}</span>
            </div>

            <button type="button" data-open-section-library class="flex w-full items-center justify-center gap-2 rounded-2xl border border-dashed border-emerald-300 bg-emerald-50/50 px-4 py-4 text-sm font-semibold text-emerald-700 transition hover:border-emerald-400 hover:bg-emerald-50 rtl:flex-row-reverse">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>{{ __('Add New Element') }}</span>
            </button>

            <div class="space-y-3">
                @forelse ($sections as $section)
                    @php
                        $sidebarTranslation = method_exists($section, 'translation') ? $section->translation($currentLocale) : null;
                        $sidebarFallbackTranslation = $sidebarTranslation ?? $section->translations->first();
                        $sidebarTypeMeta = $sectionTypes[$section->type] ?? null;
                        $sidebarTypeLabel = $sidebarTypeMeta['label'] ?? \Illuminate\Support\Str::headline(str_replace(['_', '-'], ' ', $section->type));
                        $sidebarTitle = $sidebarFallbackTranslation?->title ?: $sidebarTypeLabel;
                    @endphp

                    <article class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 transition {{ $highlightSectionId === $section->id ? 'border-slate-900/20 ring-2 ring-slate-900/10' : 'hover:border-slate-300' }}">
                        <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                            <div class="flex min-w-0 items-center gap-3 rtl:flex-row-reverse">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 6h8M8 12h8M8 18h8" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 6h.01M5 12h.01M5 18h.01" />
                                    </svg>
                                </span>

                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $sidebarTitle }}</p>
                                    <div class="mt-1 flex flex-wrap items-center gap-2 rtl:flex-row-reverse">
                                        <span class="text-xs text-slate-500">{{ $sidebarTypeLabel }}</span>
                                        <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $section->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                            {{ $section->is_active ? __('Active') : __('Hidden') }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 rtl:flex-row-reverse">
                                <a href="{{ route('dashboard.pages.sections.edit', [$page, $section]) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" aria-label="{{ __('Edit section') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                    </svg>
                                </a>
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6h10M4 6h.01M10 12h10M4 12h.01M10 18h10M4 18h.01" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                        {{ __('No elements have been added yet.') }}
                    </div>
                @endforelse
            </div>
        </div>

        <div class="border-t border-slate-200 bg-slate-50/70 px-5 py-4">
            <div class="flex items-center justify-center rounded-2xl bg-emerald-100 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ __('Changes save automatically') }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const overlay = document.getElementById('section-library-overlay');
            const drawer = document.getElementById('section-library-drawer');
            const hiddenTranslateClass = drawer?.dataset.closedTranslate || 'translate-x-full';
            const openButtons = document.querySelectorAll('[data-open-section-library]');
            const closeButtons = document.querySelectorAll('[data-close-section-library]');
            const searchInput = document.getElementById('section-library-search');
            const libraryItems = Array.from(document.querySelectorAll('[data-library-item]'));
            const libraryGroups = Array.from(document.querySelectorAll('[data-library-group]'));

            const openDrawer = () => {
                if (!overlay || !drawer) return;
                overlay.classList.remove('hidden');
                drawer.classList.remove(hiddenTranslateClass);
                drawer.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');
                if (searchInput) window.setTimeout(() => searchInput.focus(), 80);
            };

            const closeDrawer = () => {
                if (!overlay || !drawer) return;
                overlay.classList.add('hidden');
                drawer.classList.add(hiddenTranslateClass);
                drawer.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
            };

            openButtons.forEach((button) => button.addEventListener('click', openDrawer));
            closeButtons.forEach((button) => button.addEventListener('click', closeDrawer));
            if (overlay) overlay.addEventListener('click', closeDrawer);

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') closeDrawer();
            });

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const query = searchInput.value.trim().toLowerCase();
                    libraryItems.forEach((item) => {
                        const haystack = item.getAttribute('data-library-text') || '';
                        item.classList.toggle('hidden', !(query === '' || haystack.includes(query)));
                    });
                    libraryGroups.forEach((group) => {
                        const visibleItems = group.querySelectorAll('[data-library-item]:not(.hidden)');
                        group.classList.toggle('hidden', visibleItems.length === 0);
                    });
                });
            }
        });
    </script>
@endpush
