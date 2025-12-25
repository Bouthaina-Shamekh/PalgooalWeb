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
    </style>
</head>

<body class="h-full bg-slate-50 text-slate-900">
    <div id="page-builder-root" class="min-h-screen flex flex-col"
        data-load-url="{{ route('dashboard.pages.builder.data', $page) }}"
        data-save-url="{{ route('dashboard.pages.builder.data.save', $page) }}" data-preview-url="{{ $frontUrl }}"
        data-builder-url="{{ route('dashboard.pages.builder', $page) }}" data-page-id="{{ $page->id }}">

        {{-- Header (كما هو عندك) --}}
        <header class="sticky top-0 z-30 w-full bg-white/90 backdrop-blur border-b border-slate-200 shadow-sm">
            <div class="mx-auto max-w-6xl px-4">
                <div class="builder-header">
                    <div class="cluster rtl:flex-row-reverse">
                        <a href="{{ route('dashboard.pages.index') }}"
                            class="px-3 py-2 rounded-full text-sm font-medium text-slate-700 border border-slate-200 bg-white hover:bg-slate-50 transition flex items-center gap-2 rtl:flex-row-reverse"
                            aria-label="{{ __('Back to pages') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rtl:rotate-180" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10.5 19.5L3 12l7.5-7.5M3 12h18" />
                            </svg>
                            <span class="hidden sm:inline">{{ __('Back to pages') }}</span>
                        </a>

                        <div class="builder-chip">
                            <span class="label">{{ __('Page') }}</span>
                            <span class="value">{{ $pageTitle }}</span>
                            <span class="text-slate-300">|</span>
                            <span class="text-xs font-semibold text-sky-700">{{ strtoupper($currentLocale) }}</span>
                        </div>

                        @if ($hasMultipleLocales)
                            <div class="builder-chip rtl:flex-row-reverse">
                                <span class="label">{{ __('Language') }}</span>
                                <div class="min-w-[140px] builder-lang">
                                    <x-lang.language-switcher-dashboard />
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="builder-preview-stack">
                        <button type="button" class="preview-toggle" id="preview-toggle-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 4.5h16.5m-16.5 0A1.5 1.5 0 002.25 6v12A1.5 1.5 0 003.75 19.5h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5m-16.5 0V6M20.25 4.5V6m-16.5 0h16.5M6 18.75h12" />
                            </svg>
                            <span class="builder-preview-label" data-preview-label>Desktop</span>
                        </button>

                        <div class="preview-menu" id="preview-menu">
                            <button type="button" class="preview-item builder-preview-btn active"
                                data-preview="desktop">
                                <span>Desktop</span>
                            </button>
                            <button type="button" class="preview-item builder-preview-btn" data-preview="tablet">
                                <span>Tablet</span>
                            </button>
                            <button type="button" class="preview-item builder-preview-btn" data-preview="mobile">
                                <span>Mobile</span>
                            </button>
                        </div>
                    </div>

                    <div class="cluster rtl:flex-row-reverse">
                        <a href="{{ $frontUrl }}" target="_blank" rel="noopener"
                            class="px-4 py-2 rounded-full text-sm font-medium text-slate-700 border border-slate-200 bg-white hover:bg-slate-50 transition flex items-center gap-2 rtl:flex-row-reverse">
                            <span class="hidden sm:inline">{{ __('Open live page') }}</span>
                        </a>

                        <div class="builder-save-wrap">
                            <button id="builder-save" type="button"
                                class="px-5 py-2 rounded-full text-sm font-semibold text-white bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600 shadow-md hover:shadow-lg transition flex items-center gap-2">
                                <span>{{ __('Save') }}</span>
                            </button>

                            <div id="builder-save-status"
                                class="flex items-center gap-1 text-[11px] font-medium text-slate-600 px-2.5 py-1 rounded-full bg-white/75 border border-slate-200/80 shadow-sm"
                                aria-live="polite">
                                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse" data-status-dot></span>
                                <span data-status-text>{{ __('Unsaved') }}</span>
                                <span class="text-slate-400">|</span>
                                <span class="text-slate-500" data-status-time>--:--</span>
                            </div>
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
                class="w-[320px] order-1 border-r border-slate-200 rtl:border-l rtl:border-r-0 bg-white/90 backdrop-blur p-4 space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="builder-tabs" role="tablist" aria-label="Builder panels">
                        <button type="button" class="builder-tab active" data-tab-target="palette" role="tab"
                            aria-selected="true">
                            {{ __('Blocks') }}
                        </button>
                        <button type="button" class="builder-tab" data-tab-target="outline" role="tab"
                            aria-selected="false">
                            {{ __('Outline') }}
                        </button>
                    </div>

                    <span class="text-[11px] text-slate-500"
                        data-tab-helper="palette">{{ __('Drag to canvas') }}</span>
                    <span class="text-[11px] text-slate-500 hidden"
                        data-tab-helper="outline">{{ __('Reorder & jump') }}</span>
                </div>

                {{-- TAB: Blocks --}}
                <div class="builder-tab-content active" data-tab-content="palette">
                    <div id="gjs-blocks" class="space-y-3"></div>
                </div>

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
