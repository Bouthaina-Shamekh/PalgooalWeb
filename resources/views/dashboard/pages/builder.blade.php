{{-- resources/views/dashboard/pages/builder.blade.php --}}
@php
    // Current page translation & title
    $translation   = $page->translation();
    $pageTitle     = $translation?->title ?? __('Page Builder');

    // Public URL for this page (home or normal slug)
    $frontUrl      = $page->is_home
        ? url('/')
        : ($translation?->slug ? url($translation->slug) : url('/'));

    // Locales available for this page (used for language switch)
    $availableLocales  = $page->translations->pluck('locale')->filter()->unique();
    $currentLocale     = app()->getLocale();
    $hasMultipleLocales = $availableLocales->count() > 1;
@endphp

<!doctype html>
<html lang="{{ app()->getLocale() }}" class="h-full" dir="{{ current_dir() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $pageTitle }} - {{ __('Visual Builder') }}</title>

    {{-- Main Tailwind / Palgoals stylesheet used in the builder shell --}}
    <link rel="stylesheet" href="{{ mix('assets/tamplate/css/app.css') }}" id="palgoals-app-css">

    {{-- GrapesJS + custom builder JS entry --}}
    @vite('resources/js/dashboard/page-builder.js')

    <style>
        /* Make GrapesJS canvas fill the available height */
        .gjs-cv-canvas {
            top: 0;
            width: 100%;
            height: 100%;
        }

        /* Always show the selected component toolbar */
        .gjs-selected .gjs-toolbar {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 99999;
        }

        /* Small tweak for default blocks background */
        .gjs-blocks-cs.gjs-one-bg.gjs-two-color {
            background-color: #ffffff !important;
        }
    </style>
</head>

<body class="h-full bg-slate-50 text-slate-900">
    {{-- Root builder wrapper (used by page-builder.js via data-* attributes) --}}
    <div id="page-builder-root"
         class="min-h-screen flex flex-col"
         data-load-url="{{ route('dashboard.pages.builder.data', $page) }}"
         data-save-url="{{ route('dashboard.pages.builder.data.save', $page) }}"
         data-preview-url="{{ $frontUrl }}"
         data-builder-url="{{ route('dashboard.pages.builder', $page) }}"
         data-page-id="{{ $page->id }}">

        {{-- ===========================
             TOP APP BAR / BUILDER HEADER
             ============================ --}}
        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200 shadow-sm">
            <div class="w-full px-4 py-2 lg:px-6">
                <div class="flex items-center justify-between gap-4 rtl:flex-row-reverse">

                    {{-- LEFT GROUP: Back, Page title, Language switch --}}
                    <div class="flex items-center gap-3 rtl:flex-row-reverse">

                        {{-- Back to pages index --}}
                        <a href="{{ route('dashboard.pages.index') }}"
                           class="flex items-center gap-1.5 px-3 py-1.5 bg-white rounded-full border border-slate-200 hover:bg-slate-50 text-xs font-medium transition">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                 class="w-4 h-4 rtl:rotate-180"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke="currentColor"
                                 stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M10.5 19.5L3 12l7.5-7.5M3 12h18" />
                            </svg>
                            <span>{{ __('Back') }}</span>
                        </a>

                        {{-- Current page title chip --}}
                        <div class="px-4 py-1 rounded-full text-[13px] font-semibold bg-slate-100 text-slate-800">
                            {{ $pageTitle }}
                        </div>

                        {{-- Language switcher (dynamic, from DB) --}}
                        @if ($hasMultipleLocales)
                            {{-- Variant "builder" can be styled differently inside the component --}}
                            <x-lang.language-switcher variant="builder" />
                        @endif
                    </div>

                    {{-- CENTER GROUP: Device preview toggles (Desktop / Tablet / Mobile) --}}
                    <div class="hidden sm:flex items-center gap-1 bg-slate-100 rounded-full p-[3px]">
                        {{-- Buttons are wired in page-builder.js via .builder-preview-btn + data-preview attr --}}
                        <button class="px-4 py-1.5 text-xs rounded-full font-medium builder-preview-btn active"
                                data-preview="desktop">
                            Desktop
                        </button>
                        <button class="px-4 py-1.5 text-xs rounded-full font-medium builder-preview-btn"
                                data-preview="tablet">
                            Tablet
                        </button>
                        <button class="px-4 py-1.5 text-xs rounded-full font-medium builder-preview-btn"
                                data-preview="mobile">
                            Mobile
                        </button>
                    </div>

                    {{-- RIGHT GROUP: Live page, Reset, Save, and status --}}
                    <div class="flex items-center gap-2 rtl:flex-row-reverse">

                        {{-- Open current page on frontend --}}
                        <a href="{{ $frontUrl }}" target="_blank"
                           class="text-xs px-3 py-1.5 rounded-full border border-slate-200 bg-white hover:bg-slate-50 font-medium">
                            {{ __('Live Page') }}
                        </a>

                        {{-- Reset builder content (handled in JS) --}}
                        <button id="builder-reset"
                                class="px-3 py-1.5 text-xs font-semibold rounded-full border border-red-200 text-red-600 bg-red-50 hover:bg-red-100 transition">
                            {{ __('Reset Page') }}
                        </button>

                        {{-- Save builder content --}}
                        <button id="builder-save"
                                class="px-5 py-1.5 text-xs font-semibold rounded-full bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow hover:shadow-md transition">
                            {{ __('Save') }}
                        </button>

                        {{-- Realtime save status (Updated / Unsaved + time) --}}
                        <div id="builder-save-status"
                             class="hidden sm:flex items-center gap-1.5 px-3 py-1 rounded-full bg-white border border-slate-200 text-[11px] text-slate-600">
                            <span data-status-dot class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                            <span data-status-text>{{ __('Unsaved') }}</span>
                            <span class="text-slate-400">•</span>
                            <span data-status-time>--:--</span>
                        </div>
                    </div>

                </div>
            </div>
        </header>


        {{-- ===========================
             MAIN LAYOUT (Canvas + Sidebar)
             ============================ --}}
        <main class="flex-1 flex bg-slate-50">

            {{-- ===== CANVAS AREA (GrapesJS iframe / content) ===== --}}
            <section class="flex-1 order-2">
                <div class="h-full rounded-2xl border border-slate-200 bg-white shadow-sm builder-canvas">
                    <div class="h-full overflow-auto p-0">

                        {{-- Empty state – shown before GrapesJS is fully initialised --}}
                        <div id="builder-empty-state"
                             class="min-h-[420px] flex items-center justify-center text-center">
                            <div class="max-w-md space-y-6">
                                <p class="text-sm font-semibold text-slate-700">
                                    {{ __('Drag blocks to start building') }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ __('Use the Blocks panel to drag elements and reorder them on the canvas.') }}
                                </p>
                                <div class="inline-flex items-center gap-2 text-xs text-slate-500 bg-white/80 border border-slate-200 rounded-full px-3 py-1 shadow-sm">
                                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                                    <span>{{ __('Ready for content') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- GrapesJS mount point (editor renders here) --}}
                        <div id="gjs" class="min-h-[620px]"></div>
                    </div>
                </div>
            </section>


            {{-- ===== SIDEBAR: Blocks / Outline / Traits / Styles ===== --}}
            <aside
                class="relative order-1 w-full md:w-80 xl:w-96 overflow-hidden border border-slate-200/80 pg-blocks-panel p-5 shadow-xl shadow-slate-200/60 backdrop-blur supports-[backdrop-filter]:backdrop-blur-md flex flex-col">

                {{-- Decorative gradient overlays (top & bottom) --}}
                <div class="pointer-events-none absolute inset-x-0 top-0 h-14"></div>
                <div class="pointer-events-none absolute inset-x-0 bottom-0 h-16"></div>

                {{-- Sidebar inner layout --}}
                <div class="relative flex flex-col gap-4 flex-1">

                    {{-- Tabs header: Blocks vs Outline --}}
                    <div class="flex items-center justify-between gap-2">
                        {{-- Tab buttons --}}
                        <div class="builder-tabs inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-100 p-1 text-[11px] font-semibold shadow-inner shadow-slate-200/50"
                             role="tablist" aria-label="Builder panels">
                            <button type="button"
                                    class="builder-tab data-[selected=true]:bg-gradient-to-r data-[selected=true]:from-sky-500 data-[selected=true]:to-indigo-600 data-[selected=true]:text-white flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-semibold text-slate-600 transition hover:bg-white hover:shadow focus:outline-none focus:ring-2 focus:ring-sky-500/30"
                                    data-tab-target="palette" role="tab" aria-selected="true" data-selected="true">
                                {{ __('Blocks') }}
                            </button>
                            <button type="button"
                                    class="builder-tab data-[selected=true]:bg-gradient-to-r data-[selected=true]:from-sky-500 data-[selected=true]:to-indigo-600 data-[selected=true]:text-white flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-semibold text-slate-600 transition hover:bg-white hover:shadow focus:outline-none focus:ring-2 focus:ring-sky-500/30"
                                    data-tab-target="outline" role="tab" aria-selected="false" data-selected="false">
                                {{ __('Outline') }}
                            </button>
                        </div>

                        {{-- Helper text (changes per active tab) --}}
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500"
                                  data-tab-helper="palette">
                                {{ __('Drag to canvas') }}
                            </span>
                            <span class="hidden text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500"
                                  data-tab-helper="outline">
                                {{ __('Reorder & jump') }}
                            </span>
                        </div>
                    </div>

                    {{-- Tabs content container --}}
                    <div class="relative flex-1">
                        <div class="relative overflow-y-auto">

                            {{-- TAB: BLOCKS / PALETTE --}}
                            <section class="builder-tab-content space-y-3" data-tab-content="palette">
                                {{-- Palgoals blocks box (title + GrapesJS blocks) --}}
                                <div class="rounded-2xl border border-slate-200 bg-white/90 shadow-md shadow-slate-200/70">
                                    <div class="px-3 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                        {{ __('Blocks') }}
                                    </div>

                                    {{-- GrapesJS blocks container – populated in JS via editor.BlockManager --}}
                                    <div id="gjs-blocks" class="space-y-3 p-3"></div>
                                </div>

                                {{-- Simple search input (UI only – can be wired later to filter blocks) --}}
                                <div class="pg-blocks-search mt-2 mb-3">
                                    <div class="relative">
                                        <input type="text"
                                               class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2 text-xs text-slate-600 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                                               placeholder="ابحث في البلوكات..." dir="auto" />
                                        <span
                                            class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400 text-xs">
                                            🔍
                                        </span>
                                    </div>
                                </div>
                            </section>

                            {{-- TAB: OUTLINE / LAYERS + TRAITS + STYLES --}}
                            <section class="builder-tab-content hidden space-y-4"
                                     data-tab-content="outline"
                                     aria-hidden="true">

                                {{-- Layers / Outline tree --}}
                                <div class="rounded-2xl border border-slate-200 bg-white/90 shadow-md shadow-slate-200/70">
                                    <div class="flex items-center justify-between px-3 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                        <span>{{ __('Layers') }}</span>
                                        <span class="text-xs font-semibold text-slate-700">{{ __('Outline') }}</span>
                                    </div>
                                    <div id="gjs-layers"
                                         class="mx-3 mb-3 rounded-xl border border-slate-200/80 bg-white p-2">
                                    </div>
                                </div>

                                {{-- Traits / Component settings --}}
                                <div class="rounded-2xl border border-slate-200 bg-white/90 shadow-md shadow-slate-200/70">
                                    <div class="flex items-center justify-between px-3 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                        <span>{{ __('Traits') }}</span>
                                        <span class="text-xs font-semibold text-slate-700">{{ __('Settings') }}</span>
                                    </div>
                                    <div id="gjs-traits"
                                         class="mx-3 mb-3 rounded-xl border border-slate-200/80 bg-white p-2">
                                    </div>
                                </div>

                                {{-- Styles / CSS panel --}}
                                <div class="rounded-2xl border border-slate-200 bg-white/90 shadow-md shadow-slate-200/70">
                                    <div class="flex items-center justify-between px-3 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                        <span>{{ __('Styles') }}</span>
                                        <span class="text-xs font-semibold text-slate-700">{{ __('Design') }}</span>
                                    </div>
                                    <div id="gjs-styles"
                                         class="mx-3 mb-3 rounded-xl border border-slate-200/80 bg-white p-2">
                                    </div>
                                </div>

                                {{-- Optional: legacy / lite panels placeholder (kept hidden for fallback) --}}
                                <div class="hidden" id="lite-fallback-panels"></div>
                            </section>
                        </div>
                    </div>
                </div>
            </aside>
        </main>
    </div>
</body>

</html>
