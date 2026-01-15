{{-- resources/views/dashboard/pages/builder.blade.php --}}
@php
    // Current page translation & title
    $translation = $page->translation();
    $pageTitle = $translation?->title ?? __('Page Builder');

    // Public URL for this page (home or normal slug)
    $frontUrl = $page->is_home ? url('/') : ($translation?->slug ? url($translation->slug) : url('/'));

    // Locales available for this page (used for language switch)
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

    {{-- Main Tailwind / Palgoals stylesheet used in the builder shell --}}
    <link rel="stylesheet" href="{{ mix('assets/tamplate/css/app.css') }}" id="palgoals-app-css">

    {{-- GrapesJS + custom builder JS entry --}}
    @vite('resources/js/dashboard/builder/index.js')

    <style>
        /* =========================================================
            Palgoals Builder Sidebar - Clean Style (Single Source of Truth)
            ========================================================= */
        /* 1) Tokens */
        :root {
            --pg-primary: #240B36;
            --pg-secondary: #AE1028;

            --pg-bg: #ffffff;
            --pg-panel: #f8f7fb;
            --pg-muted: rgba(36, 11, 54, .04);

            --pg-border: rgba(36, 11, 54, .12);
            --pg-border-soft: rgba(36, 11, 54, .08);

            --pg-text: #0f172a;
            --pg-sub: #64748b;

            --pg-radius: 14px;
            --pg-tile-radius: 12px;

            --pg-focus: rgba(174, 16, 40, .14);
            --pg-shadow: 0 14px 26px rgba(36, 11, 54, .10);

            --pg-ring: rgba(174, 16, 40, .14);
            --pg-soft: rgba(36, 11, 54, .03);
        }

        /* 2) GrapesJS safety */
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

        /* =========================================================
            Sidebar Shell
        ========================================================= */
        main>aside {
            background: linear-gradient(180deg, rgba(36, 11, 54, .03) 0%, rgba(174, 16, 40, .012) 100%);
            border-color: rgba(36, 11, 54, .10) !important;
        }

        /* Title "العناصر" */
        main>aside>div:first-child h2 {
            color: var(--pg-primary);
            font-weight: 900;
            letter-spacing: .2px;
        }

        /* =========================================================
            Tabs (Pill Style) - works with data-active
        ========================================================= */
        .pg-sidebar-tab-btn {
            position: relative;
            color: var(--pg-sub);
            background: transparent;
            border: 0;
            outline: none;
            transition: background .15s ease, color .15s ease, box-shadow .15s ease;
        }

        .pg-sidebar-tab-btn:hover {
            color: var(--pg-primary);
        }

        .pg-sidebar-tab-btn[data-active="true"] {
            background: var(--pg-bg);
            color: var(--pg-primary);
            box-shadow: 0 10px 22px rgba(36, 11, 54, .08);
        }

        .pg-sidebar-tab-btn:focus,
        .pg-sidebar-tab-btn:focus-visible {
            outline: none !important;
            box-shadow: 0 0 0 3px var(--pg-focus), 0 10px 22px rgba(36, 11, 54, .08);
        }

        /* Hide inactive content */
        .pg-sidebar-tab-content[data-active="false"] {
            display: none;
        }

        /* Sidebar Panels (Widgets <-> Element Settings) */
        .pg-sidebar-panel[data-active="false"] {
            display: none;
        }

        .pg-sidebar-panel[data-active="true"] {
            display: block;
        }

        /* =========================================================
            Search
        ========================================================= */
        main>aside input[type="text"],
        main>aside input[type="search"] {
            background: rgba(36, 11, 54, .04) !important;
            border: 1px solid rgba(36, 11, 54, .12) !important;
            color: var(--pg-text) !important;
        }

        main>aside input[type="text"]::placeholder,
        main>aside input[type="search"]::placeholder {
            color: rgba(100, 116, 139, .9);
        }

        main>aside input[type="text"]:focus,
        main>aside input[type="search"]:focus {
            outline: none !important;
            border-color: rgba(174, 16, 40, .45) !important;
            box-shadow: 0 0 0 3px var(--pg-focus) !important;
        }

        /* =========================================================
            Section headers inside widgets (التنسيق / أساسي ...)
        ========================================================= */
        .pg-widgets-panel .pg-section-title {
            color: var(--pg-primary);
            font-weight: 900;
        }

        .pg-widgets-panel .pg-section-sub {
            color: rgba(100, 116, 139, .85);
            font-weight: 700;
        }

        /* Button: Hide/Show */
        #pg-widgets-toggle {
            color: rgba(100, 116, 139, .85);
            font-weight: 800;
            font-size: 11px;
            border: 0;
            background: transparent;
            padding: 0;
            cursor: pointer;
        }

        #pg-widgets-toggle:hover {
            color: var(--pg-primary);
        }

        /* Collapse wrapper (if used) */
        #pg-widgets-wrap.is-collapsed {
            display: none;
        }

        /* =========================================================
            Widgets Container
        ========================================================= */

        /* IMPORTANT: do NOT make #gjs-blocks grid here (avoid conflicts) */
        .pg-widgets-panel #gjs-blocks {
            position: relative;
        }

        /* Grid (created by simplifyBlocksPalette) */
        .pg-widgets-panel #gjs-blocks .pg-blocks-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            padding: 0 !important;
            /* keep spacing controlled by outer padding (p-3) */
            margin-top: 8px;
        }

        /* Remove Grapes category chrome */
        .pg-widgets-panel #gjs-blocks .gjs-block-category {
            background: transparent !important;
            border: 0 !important;
            box-shadow: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .pg-widgets-panel #gjs-blocks .gjs-title {
            display: none !important;
        }

        /* =========================================================
            Widget Tile (square-ish, clean, Palgoals brand)
        ========================================================= */
        .pg-widgets-panel #gjs-blocks .pg-widget-tile {
            background: var(--pg-bg);
            border: 1px solid rgba(36, 11, 54, .12) !important;
            border-radius: var(--pg-tile-radius);
            min-height: 118px;
            /* gives square feel */
            padding: 14px 10px;
            margin: 0 !important;

            width: 100%;
            text-align: center;
            cursor: pointer;

            display: flex !important;
            align-items: center;
            justify-content: center;

            transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
            box-shadow: 0 10px 18px rgba(15, 23, 42, 0.06);
        }

        /* hide media previews */
        .pg-widgets-panel #gjs-blocks .pg-widget-tile .gjs-block-media,
        .pg-widgets-panel #gjs-blocks .pg-widget-tile .gjs-block__media,
        .pg-widgets-panel #gjs-blocks .pg-widget-tile img {
            display: none !important;
        }

        /* label wrapper */
        .pg-widgets-panel #gjs-blocks .pg-widget-tile .gjs-block-label {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* inner card */
        .pg-widgets-panel #gjs-blocks .pg-widget-tile .pg-block-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        /* icon box */
        .pg-widgets-panel #gjs-blocks .pg-widget-tile .pg-block-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;

            background: rgba(36, 11, 54, .07);
            color: var(--pg-primary);
            position: relative;
        }

        /* brand dot */
        .pg-widgets-panel #gjs-blocks .pg-widget-tile .pg-block-icon::after {
            content: "";
            position: absolute;
            right: 8px;
            top: 8px;
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: rgba(174, 16, 40, .85);
            box-shadow: 0 0 0 3px rgba(174, 16, 40, .10);
        }

        /* svg size */
        .pg-widgets-panel #gjs-blocks .pg-widget-tile .pg-block-icon svg {
            width: 30px;
            height: 30px;
            display: block;
        }

        /* title */
        .pg-widgets-panel #gjs-blocks .pg-widget-tile .pg-block-title {
            font-size: 12px;
            font-weight: 900;
            color: var(--pg-primary);
            line-height: 1.2;
        }

        /* hover/focus */
        .pg-widgets-panel #gjs-blocks .pg-widget-tile:hover {
            border-color: rgba(174, 16, 40, .32) !important;
            box-shadow: var(--pg-shadow);
            transform: translateY(-1px);
        }

        .pg-widgets-panel #gjs-blocks .pg-widget-tile:focus,
        .pg-widgets-panel #gjs-blocks .pg-widget-tile:focus-visible {
            outline: none !important;
            border-color: rgba(174, 16, 40, .45) !important;
            box-shadow: 0 0 0 3px var(--pg-focus), var(--pg-shadow);
        }

        /* Filter hide */
        .pg-widgets-panel #gjs-blocks .pg-widget-tile.is-hidden {
            display: none !important;
        }

        /* Empty message */
        .pg-widgets-empty {
            margin-top: 10px;
            padding: 12px;
            border: 1px dashed rgba(36, 11, 54, .18);
            border-radius: 12px;
            background: rgba(36, 11, 54, .03);
            color: #64748b;
            font-size: 12px;
            text-align: center;
        }

        /* =========================================================
            Scrollbar (Sidebar only)
        ========================================================= */
        main>aside ::-webkit-scrollbar {
            width: 10px;
        }

        main>aside ::-webkit-scrollbar-track {
            background: rgba(36, 11, 54, .03);
            border-radius: 999px;
        }

        main>aside ::-webkit-scrollbar-thumb {
            background: rgba(36, 11, 54, .22);
            border-radius: 999px;
            border: 2px solid rgba(255, 255, 255, .75);
        }

        main>aside ::-webkit-scrollbar-thumb:hover {
            background: rgba(174, 16, 40, .30);
        }

        main>aside {
            scrollbar-color: rgba(36, 11, 54, .22) rgba(36, 11, 54, .04);
            scrollbar-width: thin;
        }

        /* =========================================================
   Properties Panel (Globals) – Inner Tabs
   ========================================================= */
        .pg-props-tab-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 36px;
            border-radius: 12px;
            border: 1px solid rgba(36, 11, 54, .12);
            background: rgba(36, 11, 54, .03);
            color: #475569;
            font-size: 12px;
            font-weight: 800;
            transition: all .15s ease;
        }

        .pg-props-tab-btn[data-active="true"] {
            background: #fff;
            border-color: rgba(174, 16, 40, .35);
            box-shadow: 0 0 0 3px var(--pg-ring);
            color: var(--pg-primary);
        }

        .pg-props-tab-content[data-active="false"] {
            display: none;
        }

        .pg-gjs-box {
            border: 1px solid rgba(36, 11, 54, .10);
            border-radius: 14px;
            background: #fff;
            padding: 10px;
            font-size: 12px;
        }

        /* =========================================================
   GrapesJS UI inside Traits/Styles/Layers
   (نظافة + تقليل ضغط + شكل أقرب للوحة تحكم حديثة)
   ========================================================= */

        /* Titles/Rows spacing */
        #gjs-traits .gjs-trt-trait,
        #gjs-styles .gjs-sm-property,
        #gjs-layers .gjs-layer {
            margin: 0 !important;
            padding: 8px 6px !important;
            border-radius: 12px;
        }

        #gjs-traits .gjs-trt-trait:hover,
        #gjs-styles .gjs-sm-property:hover {
            background: var(--pg-soft);
        }

        /* Labels */
        #gjs-traits .gjs-trt-trait__label,
        #gjs-styles .gjs-sm-label {
            color: #334155;
            font-weight: 800;
            font-size: 12px;
        }

        /* Inputs */
        #gjs-traits input,
        #gjs-traits select,
        #gjs-traits textarea,
        #gjs-styles input,
        #gjs-styles select,
        #gjs-styles textarea {
            width: 100%;
            border-radius: 12px !important;
            border: 1px solid rgba(36, 11, 54, .14) !important;
            background: #fff !important;
            padding: 8px 10px !important;
            outline: none !important;
        }

        #gjs-traits input:focus,
        #gjs-traits select:focus,
        #gjs-traits textarea:focus,
        #gjs-styles input:focus,
        #gjs-styles select:focus,
        #gjs-styles textarea:focus {
            border-color: rgba(174, 16, 40, .42) !important;
            box-shadow: 0 0 0 3px var(--pg-ring) !important;
        }

        /* Style Manager: Sector header */
        #gjs-styles .gjs-sm-sector {
            border: 1px solid rgba(36, 11, 54, .10);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        #gjs-styles .gjs-sm-sector-title {
            background: rgba(36, 11, 54, .03) !important;
            padding: 10px 12px !important;
            font-weight: 900 !important;
            color: var(--pg-primary) !important;
            border-bottom: 1px solid rgba(36, 11, 54, .08);
        }

        /* Layers */
        #gjs-layers .gjs-layer {
            border: 1px solid rgba(36, 11, 54, .08);
        }

        #gjs-layers .gjs-layer-title {
            font-weight: 800;
            color: #334155;
        }

        .gjs-one-bg {
            background-color: transparent;
        }

        /* ===== Elementor-like radio buttons inside StyleManager ===== */
        #gjs-styles .gjs-sm-property__radio {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 6px;
        }

        #gjs-styles .gjs-radio-item {
            border: 1px solid rgba(36, 11, 54, .12);
            border-radius: 12px;
            background: rgba(36, 11, 54, .03);
            padding: 8px 0;
            font-weight: 900;
            text-align: center;
        }

        #gjs-styles .gjs-radio-item input:checked+.gjs-radio-item-label {
            color: var(--pg-primary);
        }

        #gjs-styles .gjs-radio-item:has(input:checked) {
            background: #fff;
            border-color: rgba(174, 16, 40, .35);
            box-shadow: 0 0 0 3px var(--pg-ring);
        }

        /* titles inside sectors */
        #gjs-styles .gjs-sm-sector-title {
            cursor: pointer;
        }

        @media (min-width: 1024px) {
            [data-pg-hide-desktop="1"] {
                display: none !important;
            }
        }

        @media (min-width: 768px) and (max-width: 1023px) {
            [data-pg-hide-tablet="1"] {
                display: none !important;
            }
        }

        @media (max-width: 767px) {
            [data-pg-hide-mobile="1"] {
                display: none !important;
            }
        }
    </style>

    <head>

    <body class="h-full bg-slate-50 text-slate-900">
        {{-- Root builder wrapper (used by page-builder.js via data-* attributes) --}}
        <div id="page-builder-root" data-locale="{{ app()->getLocale() }}" class="min-h-screen flex flex-col"
            data-load-url="{{ route('dashboard.pages.builder.data', $page) }}"
            data-save-url="{{ route('dashboard.pages.builder.data.save', $page) }}"
            data-preview-url="{{ $frontUrl }}" data-builder-url="{{ route('dashboard.pages.builder', $page) }}"
            data-publish-url="{{ route('dashboard.pages.builder.publish', $page) }}"
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
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 rtl:rotate-180" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
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

                            {{-- Publish button --}}
                            <button id="builder-publish" type="button"
                                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                                🚀 <span>نشر الصفحة</span>
                            </button>

                            {{-- Preview link --}}
                            <a id="builder-preview" href="{{ $frontUrl }}" target="_blank"
                                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                👁 <span>معاينة</span>
                            </a>

                            {{-- Reset builder content --}}
                            <button id="builder-reset"
                                class="px-3 py-1.5 text-xs font-semibold rounded-full border border-red-200 text-red-600 bg-red-50 hover:bg-red-100 transition">
                                {{ __('Reset Page') }}
                            </button>

                            {{-- Save builder content --}}
                            <button id="pg-save-btn" type="button"
                                class="px-5 py-1.5 text-xs font-semibold rounded-full bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow hover:shadow-md transition">
                                {{ __('Save') }}
                            </button>

                            <span id="pg-save-status" class="text-xs text-slate-500">
                                {{ __('Saved') }}
                            </span>

                            {{-- Realtime save status --}}
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
                                    <div
                                        class="inline-flex items-center gap-2 text-xs text-slate-500 bg-white/80 border border-slate-200 rounded-full px-3 py-1 shadow-sm">
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

                {{-- ===== SIDEBAR: Elementor-like Widgets Panel ===== --}}
                <aside
                    class="relative order-1 w-[360px] min-w-[360px] max-w-[360px]  md:w-80 xl:w-72 border border-slate-200/80 bg-white shadow-xl shadow-slate-200/60 flex flex-col h-[calc(100vh-3.5rem)]">

                    {{-- Header title --}}
                    <div class="px-4 pt-4 pb-3 border-b border-slate-200">
                        <h2 class="text-base font-semibold text-slate-900 text-center">
                            العناصر
                        </h2>
                    </div>
                    {{-- Search bar (for widgets tab) --}}
                    <div class="px-4 py-3 border-b border-slate-200 pg-widgets-search-wrap">

                        <div class="relative">
                            <input type="text" id="pg-widgets-search" data-role="widgets-search"
                                class="w-full rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-sky-400 focus:border-sky-400"
                                placeholder="... ابحث في الودجات" dir="auto" />
                            <span
                                class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400 text-xs">
                                🔍
                            </span>
                        </div>
                    </div>

                    {{-- TAB CONTENTS --}}
                    <div class="flex-1 overflow-y-auto">
                        {{-- Widgets tab (main) --}}
                        <section class="px-4 py-4 space-y-6 pg-sidebar-panel pg-widgets-panel" data-panel="widgets"
                            data-active="true">
                            {{-- Layout group (التنسيق) --}}
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-slate-700">
                                        التنسيق
                                    </span>
                                    <button type="button" id="pg-widgets-toggle"
                                        class="text-[10px] text-slate-400 hover:text-slate-600 flex items-center gap-1">
                                        <span>إخفاء / إظهار</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>

                                {{-- هنا سيقوم GrapesJS بملء البلوكات، نعرضها كشبكة تشبه Elementor --}}
                                <div id="pg-widgets-wrap">
                                    <div id="gjs-blocks" class="p-0"></div>
                                </div>
                            </div>

                            {{-- Basic group (أساسي) – يمكن لاحقاً تقسيم البلوكات حسب الكاتيجوري من خلال JS --}}
                            <div class="pt-4 border-t border-slate-200 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-slate-700">
                                        أساسي
                                    </span>
                                    <button type="button"
                                        class="text-[10px] text-slate-400 hover:text-slate-600 flex items-center gap-1">
                                        <span>إخفاء / إظهار</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>

                                {{-- يمكنك لاحقاً عمل كونتينر ثاني مثلاً #gjs-blocks-basic من خلال JS
                                 حالياً نضع Placeholder فقط حتى لا نغيّر منطق JS الموجود --}}
                                <div
                                    class="grid grid-cols-2 gap-3 text-[11px] text-slate-400 border border-dashed border-slate-200 rounded-xl py-6 px-3 text-center">
                                    <span>
                                        سيتم تقسيم العناصر إلى مجموعات (Basic / Layout / Media ...)
                                        من خلال إعدادات BlockManager في JavaScript لاحقاً.
                                    </span>
                                </div>
                            </div>
                        </section>

                        {{-- Globals tab (Properties Panel) --}}
                        <section class="px-4 py-4 space-y-4 pg-sidebar-panel" data-panel="element"
                            data-active="false">

                            <div class="pg-props-tabs-wrap">
                                <div class="px-3 pt-3 pb-2 border-b border-slate-200/70">
                                    <div class="flex items-center justify-between">
                                        <div class="text-[11px] font-semibold tracking-[0.10em] text-slate-500">
                                            إعدادات العنصر
                                        </div>

                                        <div id="pg-props-selected"
                                            class="text-xs font-semibold text-slate-700 truncate max-w-[60%]">
                                            لا يوجد تحديد
                                        </div>
                                    </div>

                                    <div class="mt-3 grid grid-cols-3 gap-2">
                                        <button type="button" class="pg-props-tab-btn" data-prop-tab="traits"
                                            data-active="true">محتوى</button>
                                        <button type="button" class="pg-props-tab-btn" data-prop-tab="styles"
                                            data-active="false">تنسيق</button>
                                        <button type="button" class="pg-props-tab-btn" data-prop-tab="advanced"
                                            data-active="false">متقدم</button>
                                    </div>
                                </div>

                                <div class="p-3">
                                    <div class="pg-props-tab-content" data-prop-content="traits" data-active="true">
                                        <div id="gjs-traits"></div>
                                    </div>

                                    <div class="pg-props-tab-content" data-prop-content="styles" data-active="false">
                                        <div id="gjs-styles"></div>
                                    </div>

                                    <div class="pg-props-tab-content" data-prop-content="advanced"
                                        data-active="false">
                                        <div id="pg-advanced" class="space-y-4"></div>
                                    </div>
                                </div>
                            </div>
                        </section>




                        {{-- Yoast SEO tab (Placeholder حالياً) --}}
                        <section class="px-4 py-4 space-y-3 pg-sidebar-tab-content" data-tab-content="yoast"
                            data-active="false">
                            <p class="text-xs text-slate-500">
                                هنا يمكن لاحقاً إضافة حقول السيو الخاصة بالصفحة (العنوان، الوصف، الـ OG image...)
                                أو ربط إضافات خارجية.
                            </p>
                        </section>
                    </div>
                </aside>
            </main>
        </div>
    </body>

</html>
