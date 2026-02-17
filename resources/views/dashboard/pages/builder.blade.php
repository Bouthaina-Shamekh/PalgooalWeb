{{-- resources/views/dashboard/pages/builder.blade.php --}}
@php
    // Current page translation & title
    $translation = $page->translation();
    $pageTitle = $translation?->title ?? __('Page Builder');

    // Public URL for this page (home or normal slug)
    $frontUrl = $page->is_home ? url('/') : ($translation?->slug ? url($translation->slug) : url('/'));

    // Locales available for this page (used for language switch)
    $availableLocales = $page->translations->pluck('locale')->filter()->unique();
    $currentLocale = request('locale', app()->getLocale());
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
    <link rel="stylesheet" href="{{ asset('assets/dashboard/css/builder-new.css') }}">
</head>

<body class="h-full bg-slate-50 text-slate-900 ">
    {{-- Root builder wrapper (used by page-builder.js via data-* attributes) --}}
    <div id="page-builder-root" data-locale="{{ app()->getLocale() }}" class="min-h-screen flex flex-col"
        data-load-url="{{ route('dashboard.pages.builder.data', $page) }}"
        data-save-url="{{ route('dashboard.pages.builder.data.save', $page) }}" data-preview-url="{{ $frontUrl }}"
        data-builder-url="{{ route('dashboard.pages.builder', $page) }}"
        data-publish-url="{{ route('dashboard.pages.builder.publish', $page) }}" data-page-id="{{ $page->id }}">

        {{-- ===========================
        TOP APP BAR / BUILDER HEADER
        ============================ --}}
        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200 shadow-sm">
            <div class="w-full px-4 py-2 lg:px-6">
                <div class="flex items-center justify-between gap-4 rtl:flex-row-reverse">
                    {{-- LEFT GROUP: Back, Page title, Language --}}
                    <div class="flex items-center gap-3 rtl:flex-row-reverse">

                        {{-- Back (icon only) --}}
                        <a href="{{ route('dashboard.pages.index') }}"
                            class="group flex items-center justify-center w-9 h-9 rounded-full
               border border-slate-200 bg-white
               hover:bg-slate-100 hover:shadow-md hover:scale-105
               transition-all duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 rtl:rotate-180" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10.5 19.5L3 12l7.5-7.5M3 12h18" />
                            </svg>
                        </a>

                        {{-- Page title --}}
                        <div
                            class="px-4 py-1.5 rounded-full text-[13px] font-semibold
               bg-slate-100 text-slate-800
               border border-slate-200">
                            {{ $pageTitle }}
                        </div>

                        {{-- Language switcher --}}
                        @if ($hasMultipleLocales)
                            <div class="ml-1">
                                <x-lang.language-switcher variant="builder" />
                            </div>
                        @endif
                    </div>


                    {{-- CENTER GROUP: Device Preview Switcher --}}
                    <div
                        class="hidden sm:flex items-center gap-1 rounded-full bg-slate-100 p-1
           shadow-inner">

                        {{-- Desktop --}}
                        <button data-preview="desktop" title="Desktop"
                            class="builder-preview-btn group relative flex items-center justify-center
               w-10 h-9 rounded-full transition-all duration-200
               hover:bg-white hover:shadow-md hover:scale-105
               active:scale-100 cursor-pointer
               [&.active]:bg-white [&.active]:shadow-lg [&.active]:ring-1 [&.active]:ring-slate-300">

                            <svg class="w-5 h-5 text-slate-600 group-hover:text-slate-900 transition-colors"
                                fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 5h16v10H4z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 19h8M12 15v4" />
                            </svg>
                        </button>

                        {{-- Tablet --}}
                        <button data-preview="tablet" title="Tablet"
                            class="builder-preview-btn group relative flex items-center justify-center
               w-10 h-9 rounded-full transition-all duration-200
               hover:bg-white hover:shadow-md hover:scale-105 cursor-pointer
               [&.active]:bg-white [&.active]:shadow-lg [&.active]:ring-1 [&.active]:ring-slate-300">

                            <svg class="w-5 h-5 text-slate-600 group-hover:text-slate-900 transition-colors"
                                fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <rect x="6" y="2" width="12" height="20" rx="2" />
                                <circle cx="12" cy="18" r="0.8" />
                            </svg>
                        </button>

                        {{-- Mobile --}}
                        <button data-preview="mobile" title="Mobile"
                            class="builder-preview-btn group relative flex items-center justify-center
               w-10 h-9 rounded-full transition-all duration-200
               hover:bg-white hover:shadow-md hover:scale-105 cursor-pointer
               [&.active]:bg-white [&.active]:shadow-lg [&.active]:ring-1 [&.active]:ring-slate-300">

                            <svg class="w-4 h-5 text-slate-600 group-hover:text-slate-900 transition-colors"
                                fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <rect x="8" y="2" width="8" height="20" rx="2" />
                                <circle cx="12" cy="18" r="0.8" />
                            </svg>
                        </button>
                    </div>


                    {{-- RIGHT GROUP: Actions --}}
                    <div class="relative flex items-center gap-2 rtl:flex-row-reverse">

                        {{-- Save --}}
                        <button id="pg-save-btn" type="button"
                            class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg
               bg-blue-600 text-white hover:bg-blue-700 transition shadow">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            Save
                        </button>

                        {{-- Preview --}}
                        <a id="builder-preview" href="{{ $frontUrl }}" target="_blank"
                            class="inline-flex items-center gap-2 px-3 py-2 text-xs font-medium rounded-lg
               border border-slate-300 text-slate-700 hover:bg-slate-100 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5
                   c4.478 0 8.268 2.943 9.542 7
                   -1.274 4.057-5.064 7-9.542 7
                   -4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Preview
                        </a>

                        {{-- Publish --}}
                        <button id="builder-publish" type="button"
                            class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg
               bg-emerald-600 text-white hover:bg-emerald-700 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7" />
                            </svg>
                            Publish
                        </button>

                        {{-- Settings Dropdown --}}
                        <div class="relative">
                            <button id="builder-settings-btn"
                                class="flex items-center justify-center w-9 h-9 rounded-full
                   border border-slate-300 hover:bg-slate-100 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6 12h.01M12 12h.01M18 12h.01" />
                                </svg>
                            </button>

                            {{-- Dropdown --}}
                            <div id="builder-settings-menu"
                                class="hidden absolute right-0 mt-2 w-48 rounded-xl bg-white
                   border border-slate-200 shadow-lg z-50 overflow-hidden">

                                <a href="{{ $frontUrl }}" target="_blank"
                                    class="flex items-center gap-2 px-4 py-2 text-xs text-slate-700
                       hover:bg-slate-50">
                                    🌍 Live Page
                                </a>

                                <button id="builder-reset"
                                    class="w-full text-left flex items-center gap-2 px-4 py-2
                       text-xs text-red-600 hover:bg-red-50">
                                    🗑 Reset Page
                                </button>

                                <div class="border-t border-slate-100"></div>

                                <div class="px-4 py-2 text-[11px] text-slate-500">
                                    Builder Settings
                                </div>
                            </div>
                        </div>

                        {{-- Save Status
                        <span id="pg-save-status" class="text-xs text-slate-500">
                            Saved
                        </span> --}}
                        {{-- Realtime save status --}}
                        <div id="builder-save-status"
                            class="hidden sm:flex items-center gap-1.5 px-3 py-1 rounded-full bg-white border border-slate-200 text-[11px] text-slate-600">
                            <span data-status-dot class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span> <span
                                data-status-text>{{ __('Unsaved') }}</span> <span class="text-slate-400">•</span>
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
                <div class="h-full builder-canvas">
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
            <aside id="sidebar"
                class="transition-[width,min-width] duration-300 ease-in-out relative order-1 w-[360px] min-w-[360px] max-w-[360px] md:w-80 xl:w-72 border border-slate-200/80 bg-white shadow-xl shadow-slate-200/60 flex flex-col h-[calc(100vh-61px)]">
                <button id="btn-open-layout"
                    class="group flex items-center justify-center w-9 h-9 rounded-full border border-slate-200 bg-white hover:bg-slate-100 hover:shadow-md hover:scale-105 transition-all duration-200 absolute  top-2 left-4 rtl:right-4 rtl:left-auto">
                    <svg fill="#000000" class="w-4 h-4" viewBox="0 0 256 256" id="Flat"
                        xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M228,128a12,12,0,0,1-12,12H140v76a12,12,0,0,1-24,0V140H40a12,12,0,0,1,0-24h76V40a12,12,0,0,1,24,0v76h76A12,12,0,0,1,228,128Z" />
                    </svg>
                </button>
                <button id="btnSidebar"
                    class="group flex items-center justify-center w-4.5 h-12 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 hover:shadow-md hover:scale-105 transition-all duration-200 absolute  top-1/2 -right-2.25 rtl:-left-2.25 rtl:right-auto z-1">
                    <svg class="w-4 h-4 rtl:rotate-180" viewBox="0 0 1024 1024" class="icon"
                        xmlns="http://www.w3.org/2000/svg">
                        <path fill="#000000"
                            d="M685.248 104.704a64 64 0 010 90.496L368.448 512l316.8 316.8a64 64 0 01-90.496 90.496L232.704 557.248a64 64 0 010-90.496l362.048-362.048a64 64 0 0190.496 0z" />
                    </svg>
                </button>
                <div
                    class="flex flex-col overflow-y-auto [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">

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
                                class="w-full rounded-md border text-left rtl:text-right border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-sky-400 focus:border-sky-400"
                                placeholder="... ابحث في الودجات" dir="auto" />
                            <span
                                class="pointer-events-none absolute inset-y-0 right-3 rtl:left-3 rtl:right-auto flex items-center text-slate-400 text-xs">
                                🔍
                            </span>
                        </div>
                    </div>

                    {{-- TAB CONTENTS --}}
                    <div class="flex-1">
                        <div id="gjs-blocks" style="display:none;"></div>
                        {{-- Widgets tab (main) --}}
                        <section class="px-4 py-4 space-y-6 pg-sidebar-panel pg-widgets-panel" data-panel="widgets"
                            data-active="true">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-slate-700">
                                        الاساسيات
                                    </span>
                                    <button type="button" data-target="1"
                                        class="pg-widgets-toggle group flex items-center gap-1.5 text-slate-500 hover:text-slate-800 transition-all duration-200">
                                        <!-- Eye -->
                                        <svg id="widgets-eye" class="w-4 h-4 transition-opacity duration-200"
                                            fill="none" stroke="currentColor" stroke-width="1.8"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5 c4.478 0 8.268 2.943 9.542 7 -1.274 4.057-5.064 7-9.542 7 -4.477 0-8.268-2.943-9.542-7z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>

                                        <!-- Arrow -->
                                        <svg id="widgets-arrow"
                                            class="w-3 h-3 transition-transform duration-200 rotate-0" fill="none"
                                            stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                </div>

                                {{-- هنا سيقوم GrapesJS بملء البلوكات، نعرضها كشبكة تشبه Elementor --}}
                                <div class="pg-widgets-wrap" data-index="1">
                                    {{-- <div id="gjs-blocks" class="p-0">

                                    </div> --}}
                                    <div class="p-0 gjs-blocks-host">
                                        <div id="blocks-basic" class="pg-blocks-grid"></div>
                                    </div>
                                </div>

                            </div>

                        </section>
                        <section class="px-4 py-4 space-y-6 pg-sidebar-panel pg-widgets-panel" data-panel="widgets"
                            data-active="false">
                            {{-- Layout group (التنسيق) --}}
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-slate-700">
                                        التنسيق
                                    </span>
                                    <button type="button" data-target="2"
                                        class="pg-widgets-toggle group flex items-center gap-1.5 text-slate-500 hover:text-slate-800 transition-all duration-200">
                                        <!-- Eye -->
                                        <svg id="widgets-eye" class="w-4 h-4 transition-opacity duration-200"
                                            fill="none" stroke="currentColor" stroke-width="1.8"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5 c4.478 0 8.268 2.943 9.542 7 -1.274 4.057-5.064 7-9.542 7 -4.477 0-8.268-2.943-9.542-7z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>

                                        <!-- Arrow -->
                                        <svg id="widgets-arrow"
                                            class="w-3 h-3 transition-transform duration-200 rotate-0" fill="none"
                                            stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                </div>

                                {{-- هنا سيقوم GrapesJS بملء البلوكات، نعرضها كشبكة تشبه Elementor --}}
                                <div class="pg-widgets-wrap is-collapsed" data-index="2">
                                    {{-- <div id="gjs-blocks" class="p-0">

                                    </div> --}}
                                    <div class="p-0 gjs-blocks-host">
                                        <div id="blocks-layout" class="pg-blocks-grid"></div>
                                    </div>
                                </div>

                            </div>

                        </section>
                        <section class="px-4 py-4 space-y-6 pg-sidebar-panel pg-widgets-panel" data-panel="widgets"
                            data-active="false">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-slate-700">
                                        الأقسام
                                    </span>
                                    <button type="button" data-target="3"
                                        class="pg-widgets-toggle group flex items-center gap-1.5 text-slate-500 hover:text-slate-800 transition-all duration-200">
                                        <!-- Eye -->
                                        <svg id="widgets-eye" class="w-4 h-4 transition-opacity duration-200"
                                            fill="none" stroke="currentColor" stroke-width="1.8"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5 c4.478 0 8.268 2.943 9.542 7 -1.274 4.057-5.064 7-9.542 7 -4.477 0-8.268-2.943-9.542-7z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>

                                        <!-- Arrow -->
                                        <svg id="widgets-arrow"
                                            class="w-3 h-3 transition-transform duration-200 rotate-0" fill="none"
                                            stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                </div>

                                {{-- هنا سيقوم GrapesJS بملء البلوكات، نعرضها كشبكة تشبه Elementor --}}
                                <div class="pg-widgets-wrap" data-index="3">
                                    {{-- <div id="gjs-blocks" class="p-0">

                                    </div> --}}
                                    <div class="p-0 gjs-blocks-host">
                                        <div id="blocks-sections" class="pg-blocks-grid"></div>
                                    </div>
                                </div>

                            </div>

                        </section>
                    </div>

                    {{-- Globals tab (Properties Panel) --}}
                    <section class="px-4 py-4 space-y-4 pg-sidebar-panel" data-panel="element" data-active="false">

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

                                <div class="pg-props-tab-content" data-prop-content="advanced" data-active="false">
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
                <div id="sidebar-resizer"
                    class="absolute top-0 ltr:right-0 rtl:left-0 w-1.5 h-full select-none cursor-e-resize bg-transparent hover:bg-blue-400/40">
                </div>
            </aside>
        </main>

        <span class="hidden rotate-180 rtl:-left-3 -right-3"></span>
    </div>

</body>

</html>
