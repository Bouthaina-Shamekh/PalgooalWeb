{{-- resources/views/dashboard/pages/builder.blade.php --}}
@php
    $translation = $page->translation();
    $pageTitle = $translation?->title ?? __('Page Builder');
    $frontUrl = $page->is_home ? url('/') : ($translation?->slug ? url($translation->slug) : url('/'));
    $availableLocales = $page->translations->pluck('locale')->filter()->unique();
    $currentLocale = app()->getLocale();
    $hasMultipleLocales = $availableLocales->count() > 1;
@endphp

<!doctype html>
<html lang="{{ app()->getLocale() }}" class="h-full" dir="{{ current_dir() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }} - {{ __('Visual Builder') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/dashboard/css/builder.css') }}">
    <link rel="stylesheet" href="{{ mix('assets/tamplate/css/app.css') }}" id="palgoals-app-css">
    @vite('resources/js/dashboard/page-builder.js')
    <style>
        /* Reset some default styling */
        .gjs-cv-canvas {
            top: 0;
            width: 100%;
            height: 100%;
        }

        .gjs-selected .gjs-toolbar {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 99999;
        }
    </style>
</head>

<body class="h-full bg-slate-50 text-slate-900">
    <div id="page-builder-root" class="min-h-screen flex flex-col"
        data-load-url="{{ route('dashboard.pages.builder.data', $page) }}"
        data-save-url="{{ route('dashboard.pages.builder.data.save', $page) }}" data-preview-url="{{ $frontUrl }}"
        data-builder-url="{{ route('dashboard.pages.builder', $page) }}" data-page-id="{{ $page->id }}">

        {{-- Header (كما هو عندك) --}}
        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200 shadow-sm">
            <div class="w-full px-4 py-2 lg:px-6">
                <div class="flex items-center justify-between gap-4 rtl:flex-row-reverse">

                    {{-- Left Group (Page Info + Language) --}}
                    <div class="flex items-center gap-3 rtl:flex-row-reverse">

                        {{-- Back Button --}}
                        <a href="{{ route('dashboard.pages.index') }}"
                            class="flex items-center gap-1.5 px-3 py-1.5 bg-white rounded-full border border-slate-200 hover:bg-slate-50 text-xs font-medium transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 rtl:rotate-180" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10.5 19.5L3 12l7.5-7.5M3 12h18" />
                            </svg>
                            <span>{{ __('Back') }}</span>
                        </a>

                        {{-- Page title --}}
                        <div class="px-4 py-1 rounded-full text-[13px] font-semibold bg-slate-100 text-slate-800">
                            {{ $pageTitle }}
                        </div>

                        {{-- Language Switch --}}
                        @if ($hasMultipleLocales)
                            <x-lang.language-switcher variant="builder" />
                        @endif
                    </div>

                    {{-- Center Group: Preview Mode --}}
                    <div class="hidden sm:flex items-center gap-1 bg-slate-100 rounded-full p-[3px]">
                        <button class="px-4 py-1.5 text-xs rounded-full font-medium builder-preview-btn active"
                            data-preview="desktop">Desktop</button>
                        <button class="px-4 py-1.5 text-xs rounded-full font-medium builder-preview-btn"
                            data-preview="tablet">Tablet</button>
                        <button class="px-4 py-1.5 text-xs rounded-full font-medium builder-preview-btn"
                            data-preview="mobile">Mobile</button>
                    </div>

                    {{-- Right Group: Main Actions --}}
                    <div class="flex items-center gap-2 rtl:flex-row-reverse">

                        <a href="{{ $frontUrl }}" target="_blank"
                            class="text-xs px-3 py-1.5 rounded-full border border-slate-200 bg-white hover:bg-slate-50 font-medium">
                            {{ __('Live Page') }}
                        </a>

                        {{-- Reset --}}
                        <button id="builder-reset"
                            class="px-3 py-1.5 text-xs font-semibold rounded-full border border-red-200 text-red-600 bg-red-50 hover:bg-red-100 transition">
                            {{ __('Reset Page') }}
                        </button>

                        {{-- Save --}}
                        <button id="builder-save"
                            class="px-5 py-1.5 text-xs font-semibold rounded-full bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow hover:shadow-md transition">
                            {{ __('Save') }}
                        </button>

                        {{-- Status bubble --}}
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


        <main class="flex-1 flex bg-slate-50">
            {{-- Canvas --}}
            <section class="flex-1  order-2">
                <div class="h-full rounded-2xl border border-slate-200 bg-white shadow-sm builder-canvas">

                    <div class="h-full overflow-auto p-0">
                        {{-- Empty state (سنخفيه بالـ JS عندما يتهيأ GrapesJS) --}}
                        <div id="builder-empty-state"
                            class="min-h-[420px] flex items-center justify-center text-center">
                            <div class="max-w-md space-y-6">
                                <p class="text-sm font-semibold text-slate-700">
                                    {{ __('Drag blocks to start building') }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ __('Use the Blocks panel to drag elements and reorder them on the canvas.') }}
                                </p>
                                <div
                                    class="inline-flex items-center gap-2 text-xs text-slate-500 bg-white/80 border border-slate-200 rounded-full px-3 py-1 shadow-sm">
                                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                                    <span>{{ __('Ready for content') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- GrapesJS mount --}}
                        <div id="gjs" class="min-h-[620px]"></div>
                    </div>
                </div>
            </section>

            {{-- Sidebar --}}
            <aside
                class="w-full md:w-80 xl:w-96 border-l border-slate-200/70 bg-slate-50/80 backdrop-blur-sm flex flex-col">
                {{-- Header: Drag to canvas + tabs --}}
                <div class="px-4 pt-4 pb-2 border-b border-slate-200/80">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-[11px] font-medium uppercase tracking-[0.18em] text-slate-400">
                            Drag to canvas
                        </div>

                        {{-- Tabs Outline / Blocks --}}
                        <div
                            class="inline-flex items-center rounded-full bg-slate-100 p-[3px] text-[11px] font-semibold">
                            <button type="button"
                                class="builder-tab px-3 py-1 rounded-full text-slate-500 hover:text-slate-900"
                                data-tab-target="outline">
                                Outline
                            </button>
                            <button type="button"
                                class="builder-tab px-3 py-1 rounded-full bg-white text-slate-900 shadow-sm"
                                data-tab-target="palette">
                                Blocks
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Content --}}
                <div class="flex-1 overflow-y-auto">
                    {{-- Outline --}}
                    <section class="builder-tab-content hidden" data-tab-content="outline">
                        <div id="gjs-layers" class="h-full"></div>
                    </section>

                    {{-- Blocks --}}
                    <section class="builder-tab-content block" data-tab-content="palette">
                        <div class="h-full bg-slate-900 text-slate-50 rounded-tl-3xl pt-4 pb-6 px-3 shadow-inner">
                            <div class=" builder-blocks-title text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400 px-1 mb-2">
                                Palgoals blocks
                            </div>
                            <div id="gjs-blocks" class="space-y-4"></div>
                        </div>
                    </section>

                    {{-- TAB: Outline (Layers + Traits + Styles) --}}
                    <div class="builder-tab-content" data-tab-content="outline">
                        <div class="space-y-3">
                            <div class="builder-panel-chip">
                                <span class="label">Layers</span>
                                <span class="value">Outline</span>
                            </div>
                            <div id="gjs-layers" class="rounded-xl border border-slate-200 bg-white p-2"></div>

                            <div class="builder-panel-chip mt-3">
                                <span class="label">Traits</span>
                                <span class="value">Settings</span>
                            </div>
                            <div id="gjs-traits" class="rounded-xl border border-slate-200 bg-white p-2"></div>

                            <div class="builder-panel-chip mt-3">
                                <span class="label">Styles</span>
                                <span class="value">Design</span>
                            </div>
                            <div id="gjs-styles" class="rounded-xl border border-slate-200 bg-white p-2"></div>

                            {{-- Optional: keep old Lite builder panels as fallback (hidden) --}}
                            <div class="hidden" id="lite-fallback-panels"></div>
                        </div>
                    </div>
            </aside>
        </main>
    </div>
</body>

</html>
