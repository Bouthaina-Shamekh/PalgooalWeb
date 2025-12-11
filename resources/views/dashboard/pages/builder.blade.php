{{-- resources/views/dashboard/pages/builder.blade.php --}}
@php
    $translation = $page->translation();
    $pageTitle   = $translation?->title ?? __('Page Builder');
    $frontUrl    = $page->is_home
        ? url('/')
        : ($translation?->slug ? url($translation->slug) : url('/'));
    $availableLocales = $page->translations->pluck('locale')->filter()->unique();
    $currentLocale    = app()->getLocale();
    $hasMultipleLocales = $availableLocales->count() > 1;
    $canvasStyles = [
        mix('assets/tamplate/css/app.css'), // الملف اللي فيه Tailwind v4 وتصميم الواجهة
    ];
@endphp

<!doctype html>
<html lang="{{ app()->getLocale() }}" class="h-full" dir="{{ current_dir() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }} - {{ __('Visual Builder') }}</title>
    <link rel="stylesheet" href="{{ mix('assets/tamplate/css/app.css') }}">
    @vite('resources/js/dashboard/page-builder.js')
    <style>
        /* Force light canvas background */
        #gjs,
        .gjs-canvas,
        .gjs-cv-canvas,
        .gjs-frame,
        .gjs-canvas__frames {
            background: #f8fafc !important;
        }
    </style>
</head>
<body class="h-full bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100">
    <div id="page-builder-root"
         class="h-full flex flex-col"
         data-load-url="{{ route('dashboard.pages.builder.data', $page) }}"
         data-save-url="{{ route('dashboard.pages.builder.data.save', $page) }}"
         data-preview-url="{{ $frontUrl }}"
         data-builder-url="{{ route('dashboard.pages.builder', $page) }}"
         data-page-id="{{ $page->id }}"
          data-canvas-styles='@json($canvasStyles)'
          data-canvas-styles='@json([mix("assets/tamplate/css/app.css")])'
         >

        <header class="pointer-events-none fixed top-4 left-0 right-0 z-30 flex justify-center px-3">
            <div class="pointer-events-auto w-full max-w-5xl rounded-full bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl border border-white/40 dark:border-white/10 shadow-xl px-3 sm:px-5 py-2 flex items-center gap-3 md:gap-4 rtl:flex-row-reverse">
                {{-- Left controls --}}
                <div class="flex items-center gap-2 flex-shrink-0 rtl:flex-row-reverse">
                    <a href="{{ route('dashboard.pages.index') }}"
                       class="px-4 py-2 rounded-full text-sm font-medium text-slate-700 dark:text-slate-100 border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750 transition flex items-center gap-2 rtl:flex-row-reverse"
                       aria-label="{{ __('Back to pages') }}"
                       id="builder-back-link">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12l7.5-7.5M3 12h18" />
                        </svg>
                        <span class="hidden sm:inline">{{ __('Back to pages') }}</span>
                    </a>

                    <button id="builder-save" type="button"
                        class="px-5 py-2 rounded-full text-sm font-semibold text-white bg-gradient-to-r from-[#6D28D9] via-[#9333EA] to-[#EC4899] shadow-md hover:shadow-lg transition flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 4.5h9l3 3v9a3 3 0 01-3 3h-9a3 3 0 01-3-3v-9a3 3 0 013-3z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 4.5v5.25H9V4.5" />
                        </svg>
                        <span>{{ __('Save') }}</span>
                    </button>
                    <div id="builder-save-status"
                         class="flex items-center gap-1 text-[11px] font-medium text-slate-600 dark:text-slate-200 px-2 py-1 rounded-full bg-white/70 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/80"
                         aria-live="polite">
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse" data-status-dot></span>
                        <span data-status-text>{{ __('Unsaved') }}</span>
                        <span class="text-slate-400 dark:text-slate-500">·</span>
                        <span class="text-slate-500 dark:text-slate-400" data-status-time>--:--</span>
                    </div>

                    <a href="{{ $frontUrl }}" target="_blank" rel="noopener"
                       class="ml-1 px-4 py-2 rounded-full text-sm font-medium text-slate-700 dark:text-slate-100 border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750 transition flex items-center gap-2 rtl:flex-row-reverse">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5H19.5V10.5M19.5 4.5L12 12M6.75 6.75h-1.5A1.5 1.5 0 003.75 8.25v9A1.5 1.5 0 005.25 18.75h9a1.5 1.5 0 001.5-1.5v-1.5" />
                        </svg>
                        <span class="hidden sm:inline">{{ __('Open live page') }}</span>
                    </a>
                </div>

                {{-- Device toggles --}}
                <div class="flex-1 min-w-0 flex items-center justify-center">
                    <div class="relative">
                        <button type="button" id="device-menu-toggle"
                            class="flex items-center gap-2 px-3 py-2 rounded-full border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-800/80 backdrop-blur hover:bg-white dark:hover:bg-slate-800 transition shadow-sm text-slate-700 dark:text-slate-100">
                            <span class="flex items-center gap-1">
                                <svg data-device-icon class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5v9.75H3.75zM9.75 18.75h4.5" />
                                </svg>
                                <span class="text-xs font-semibold" data-device-label>{{ __('Desktop') }}</span>
                            </span>
                            <svg class="h-3 w-3 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" />
                            </svg>
                        </button>
                        <div id="device-menu"
                             class="hidden absolute left-1/2 -translate-x-1/2 mt-2 w-44 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-lg overflow-hidden z-40">
                            <div class="py-1 text-sm text-slate-700 dark:text-slate-100">
                                <button type="button" data-device="Desktop" class="device-menu-item w-full px-3 py-2 flex items-center gap-2 hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5v9.75H3.75zM9.75 18.75h4.5" />
                                    </svg>
                                    <span>{{ __('Desktop') }}</span>
                                </button>
                                <button type="button" data-device="Tablet" class="device-menu-item w-full px-3 py-2 flex items-center gap-2 hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 4.5h9A1.5 1.5 0 0118 6v12a1.5 1.5 0 01-1.5 1.5h-9A1.5 1.5 0 016 18V6A1.5 1.5 0 017.5 4.5zM10.5 18.75h3" />
                                    </svg>
                                    <span>{{ __('Tablet') }}</span>
                                </button>
                                <button type="button" data-device="Mobile" class="device-menu-item w-full px-3 py-2 flex items-center gap-2 hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5h6A1.5 1.5 0 0116.5 6v12a1.5 1.5 0 01-1.5 1.5H9A1.5 1.5 0 017.5 18V6A1.5 1.5 0 019 4.5zM10.5 18.75h3" />
                                    </svg>
                                    <span>{{ __('Mobile') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Page identity / avatar --}}
                <div class="flex items-center gap-2 md:gap-3 flex-shrink-0 rtl:flex-row-reverse">
                    <div class="relative hidden md:block">
                        <button id="builder-preview" type="button"
                            class="w-10 h-10 rounded-full border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-750 transition flex items-center justify-center"
                            aria-label="{{ __('Preview') }}"
                            title="{{ __('Preview (inline)') }}">
                            <svg data-icon-preview-on xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
                            </svg>
                            <svg data-icon-preview-off xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.477 10.477a3 3 0 014.243 4.243" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.228 6.228A10.451 10.451 0 0112 5.25c6 0 9.75 6.75 9.75 6.75a12.318 12.318 0 01-2.202 2.88m-3.222 2.295A10.451 10.451 0 0112 18.75c-6 0-9.75-6.75-9.75-6.75a12.32 12.32 0 012.774-3.579" />
                            </svg>
                        </button>
                        <div id="preview-menu" class="hidden absolute right-0 mt-2 w-52 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden z-40">
                            <div class="px-3 py-2 text-xs text-slate-600 dark:text-slate-300 flex items-center justify-between">
                                <span>{{ __('Status') }}</span>
                                <span data-preview-status class="font-semibold text-slate-900 dark:text-slate-100">{{ __('Inline off') }}</span>
                            </div>
                            <div class="border-t border-slate-200 dark:border-slate-800"></div>
                            <button id="preview-inline-btn" type="button" class="w-full px-4 py-2 text-sm text-left hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-100 flex items-center gap-2">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
                                </svg>
                                <span>{{ __('Inline preview') }}</span>
                            </button>
                            <button id="preview-newtab-btn" type="button" class="w-full px-4 py-2 text-sm text-left hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-100 flex items-center gap-2">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5H19.5V10.5M19.5 4.5L12 12M6.75 6.75h-1.5A1.5 1.5 0 003.75 8.25v9A1.5 1.5 0 005.25 18.75h9a1.5 1.5 0 001.5-1.5v-1.5" />
                                </svg>
                                <span>{{ __('Preview in new tab') }}</span>
                            </button>
                        </div>
                    </div>

                    <button id="builder-sections-toggle" type="button"
                        class="w-10 h-10 rounded-full border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-750 transition flex items-center justify-center"
                        aria-label="{{ __('Sections') }}">
                        <svg data-icon-sections-open xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h4v4H4V6zm6 0h4v4h-4V6zm6 0h4v4h-4V6zM4 12h4v4H4v-4zm6 0h4v4h-4v-4zm6 0h4v4h-4v-4z" />
                        </svg>
                        <svg data-icon-sections-close xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M6 18L18 6" />
                        </svg>
                    </button>

                    <button type="button"
                        class="w-10 h-10 rounded-full border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-750 transition flex items-center justify-center"
                        aria-label="{{ __('Page settings') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75a2.25 2.25 0 100 4.5 2.25 2.25 0 000-4.5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12a7.5 7.5 0 01-.231 1.822l1.6 1.238a.75.75 0 01.176 1.017l-1.5 2.598a.75.75 0 01-.95.3l-1.887-.755a7.522 7.522 0 01-1.577.915l-.285 2.01a.75.75 0 01-.743.645h-3a.75.75 0 01-.743-.645l-.285-2.01a7.522 7.522 0 01-1.577-.915l-1.887.755a.75.75 0 01-.95-.3l-1.5-2.598a.75.75 0 01.176-1.017l1.6-1.238A7.5 7.5 0 014.5 12c0-.622.08-1.225.231-1.822l-1.6-1.238a.75.75 0 01-.176-1.017l1.5-2.598a.75.75 0 01.95-.3l1.887.755c.48-.372 1.02-.686 1.577-.915l.285-2.01A.75.75 0 0110.5 2.25h3a.75.75 0 01.743.645l.285 2.01c.557.229 1.097.543 1.577.915l1.887-.755a.75.75 0 01.95.3l1.5 2.598a.75.75 0 01-.176 1.017l-1.6 1.238c.151.597.231 1.2.231 1.822z" />
                        </svg>
                    </button>

                    <button type="button"
                        id="builder-shortcuts-toggle"
                        class="hidden md:flex w-10 h-10 rounded-full border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition items-center justify-center"
                        aria-label="{{ __('Shortcuts') }}"
                        aria-expanded="false"
                        aria-controls="shortcuts-modal">
                        <span class="text-base font-semibold">⌘</span>
                    </button>

                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-semibold bg-gradient-to-r from-[#6D28D9] via-[#9333EA] to-[#EC4899] shadow-sm">
                        P
                    </div>
                </div>
            </div>
        </header>

        {{-- Full-screen canvas --}}
        <main class="relative h-[calc(100vh-72px)] overflow-hidden bg-slate-100 dark:bg-slate-950 flex flex-col flex-1 min-h-0  ">
            <div class="px-4 pt-3 pb-2 flex flex-wrap items-center gap-3 justify-between">
                <div class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-white/90 dark:bg-slate-900/85 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Page') }}</span>
                    <span class="text-sm font-semibold text-slate-900 dark:text-slate-50">{{ $pageTitle }}</span>
                    <span class="text-slate-300 dark:text-slate-600">•</span>
                    <span class="text-xs font-semibold text-primary-700 dark:text-primary-200">{{ strtoupper($currentLocale) }}</span>
                </div>
                @if ($hasMultipleLocales)
                    <div class="flex items-center gap-2">
                        <label for="builder-locale-switch" class="text-xs font-semibold text-slate-600 dark:text-slate-200">
                            {{ __('Language') }}
                        </label>
                        <select id="builder-locale-switch"
                                class="text-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-slate-800 dark:text-slate-100 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 dark:focus:ring-primary-800">
                            @foreach ($availableLocales as $locale)
                                <option value="{{ $locale }}" @selected($locale === $currentLocale)>{{ strtoupper($locale) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            {{-- Canvas wrapper keeps GrapesJS pinned to viewport while drawers slide over it --}}
            <div class="relative h-full w-full flex-1">
                <div class="absolute inset-0">
                    <div id="gjs" class="h-full w-full"></div>
                </div>
            </div>

            {{-- Left Drawer: Sections & Blocks --}}
            <aside id="drawer-blocks"
                   class="fixed top-[72px] bottom-0 left-0 rtl:left-auto rtl:right-0 w-[300px] bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 shadow-xl transform transition-transform duration-300 -translate-x-full rtl:translate-x-full z-20 flex flex-col">
                <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                            {{ __('Sections & Blocks') }}
                        </div>
                        <button type="button" class="text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white" data-close-drawer="blocks">
                            ✕
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        {{ __('Drag a section to the canvas') }}
                    </p>
                </div>
                <div class="px-3 pt-2 pb-1 flex items-center gap-1">
                    <button type="button" data-blocks-tab="sections"
                        class="blocks-tab px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">
                        {{ __('Sections') }}
                    </button>
                    <button type="button" data-blocks-tab="blocks"
                        class="blocks-tab px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">
                        {{ __('Blocks') }}
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-3 space-y-2">
                    <div id="gjs-sections" class="blocks-tab-panel hidden text-sm text-slate-600 dark:text-slate-300 space-y-2">
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ __('Sections list will appear here (coming soon).') }}
                        </p>
                    </div>
                    <div id="gjs-blocks" class="blocks-tab-panel space-y-2"></div>
                </div>
            </aside>

            {{-- Right Drawer: Settings --}}
            <aside id="drawer-settings"
                   class="fixed top-[72px] bottom-0 right-0 rtl:right-auto rtl:left-0 w-[300px] bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-xl transform transition-transform duration-300 translate-x-full rtl:-translate-x-full z-20 flex flex-col">
                <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <div class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                            {{ __('Section settings') }}
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 rtl:text-right ltr:text-left">
                            {{ __('Configure content and layout for the selected section.') }}
                        </p>
                    </div>
                    <button type="button" class="text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white" data-close-drawer="settings">
                        ✕
                    </button>
                </div>
                <div class="px-4 pt-3 border-b border-slate-200 dark:border-slate-800 space-y-2">
                    <div class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                        {{ __('Section title') }}
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">
                        {{ __('(will show selected section title/type here)') }}
                    </div>
                </div>
                <div class="px-4 py-2 border-b border-slate-200 dark:border-slate-800 flex items-center gap-2 text-sm font-semibold text-slate-600 dark:text-slate-200">
                    <button class="px-3 py-1.5 rounded-lg bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-200">
                        {{ __('Content') }}
                    </button>
                    <button class="px-3 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800">
                        {{ __('Layout') }}
                    </button>
                    <button class="px-3 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800">
                        {{ __('Advanced') }}
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-4 text-sm text-slate-600 dark:text-slate-300 rtl:text-right ltr:text-left">
                    <div class="h-full border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-xl p-5 flex flex-col items-center justify-center text-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5.25l3 5.25H9l3-5.25zM12 18.75v-8.25" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-base font-semibold text-slate-800 dark:text-slate-100">
                                {{ __('Select a block on the canvas') }}
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                {{ __('Click any block to edit its content and layout here.') }}
                            </p>
                        </div>
                        <button id="settings-empty-learn"
                            type="button"
                            class="px-4 py-2 rounded-lg text-xs font-semibold text-white bg-gradient-to-r from-[#6D28D9] via-[#9333EA] to-[#EC4899] shadow-sm hover:shadow-md transition">
                            {{ __('Learn how') }}
                        </button>
                    </div>
                </div>
            </aside>
        </main>
    </div>

    {{-- Shortcuts modal --}}
    <div id="shortcuts-modal" class="hidden fixed inset-0 z-50 items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="shortcuts-title">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" data-close-shortcuts></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800">
                <div>
                    <div id="shortcuts-title" class="text-sm font-semibold text-slate-900 dark:text-slate-50">{{ __('Keyboard shortcuts') }}</div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('Speed up navigation, devices, and save actions.') }}</p>
                </div>
                <button type="button" class="text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white" data-close-shortcuts aria-label="{{ __('Close') }}">
                    ✕
                </button>
            </div>
            <div class="p-5 space-y-3 text-sm text-slate-700 dark:text-slate-100">
                <div class="flex items-center justify-between gap-3">
                    <span>{{ __('Save page') }}</span>
                    <span class="flex items-center gap-1 text-[11px] font-semibold text-slate-700 dark:text-slate-200">
                        <span class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">Ctrl/Cmd</span>
                        <span>+</span>
                        <span class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">S</span>
                    </span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span>{{ __('Desktop preview') }}</span>
                    <span class="flex items-center gap-1 text-[11px] font-semibold text-slate-700 dark:text-slate-200">
                        <span class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">Ctrl/Cmd</span>
                        <span>+</span>
                        <span class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">Shift</span>
                        <span>+</span>
                        <span class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">1</span>
                    </span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span>{{ __('Tablet preview') }}</span>
                    <span class="flex items-center gap-1 text-[11px] font-semibold text-slate-700 dark:text-slate-200">
                        <span class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">Ctrl/Cmd</span>
                        <span>+</span>
                        <span class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">Shift</span>
                        <span>+</span>
                        <span class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">2</span>
                    </span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span>{{ __('Mobile preview') }}</span>
                    <span class="flex items-center gap-1 text-[11px] font-semibold text-slate-700 dark:text-slate-200">
                        <span class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">Ctrl/Cmd</span>
                        <span>+</span>
                        <span class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">Shift</span>
                        <span>+</span>
                        <span class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">3</span>
                    </span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span>{{ __('Back to pages') }}</span>
                    <span class="flex items-center gap-1 text-[11px] font-semibold text-slate-700 dark:text-slate-200">
                        <span class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">Ctrl/Cmd</span>
                        <span>+</span>
                        <span class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">Shift</span>
                        <span>+</span>
                        <span class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">B</span>
                    </span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span>{{ __('Toggle shortcuts') }}</span>
                    <span class="flex items-center gap-1 text-[11px] font-semibold text-slate-700 dark:text-slate-200">
                        <span class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">Ctrl/Cmd</span>
                        <span>+</span>
                        <span class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">/</span>
                    </span>
                </div>
            </div>
            <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                <span>{{ __('Press Esc to close') }}</span>
                <button type="button" class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-100" data-close-shortcuts>
                    {{ __('Close') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Unsaved changes modal for preview --}}
    <div id="unsaved-preview-modal" class="hidden fixed inset-0 z-50 items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="unsaved-preview-title">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" data-close-unsaved></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl">
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <div id="unsaved-preview-title" class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                        {{ __('Unsaved changes') }}
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        {{ __('You have unsaved edits. How do you want to preview?') }}
                    </p>
                </div>
                <button type="button" class="text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white" data-close-unsaved aria-label="{{ __('Close') }}">
                    ✕
                </button>
            </div>
            <div class="p-5 space-y-3 text-sm text-slate-700 dark:text-slate-100">
                <button id="unsaved-preview-save"
                    type="button"
                    class="w-full px-4 py-3 rounded-xl bg-primary-600 text-white font-semibold shadow-sm hover:bg-primary-700 transition">
                    {{ __('Save then preview') }}
                </button>
                <button id="unsaved-preview-skip"
                    type="button"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    {{ __('Preview without saving') }}
                </button>
            </div>
            <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400 text-center">
                {{ __('You can always come back and save after previewing.') }}
            </div>
        </div>
    </div>

    {{-- Snackbar --}}
    <div id="builder-snackbar" class="hidden fixed bottom-4 left-1/2 -translate-x-1/2 z-50">
        <div class="flex items-center gap-2 px-4 py-3 rounded-xl shadow-2xl border text-sm font-medium transition"
             data-snackbar-body>
            <span class="w-2 h-2 rounded-full" data-snackbar-dot></span>
            <span data-snackbar-text></span>
        </div>
    </div>
</body>
</html>
