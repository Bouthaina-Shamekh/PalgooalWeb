{{-- resources/views/dashboard/pages/builder.blade.php --}}
@php
    $translation       = $page->translation();
    $pageTitle         = $translation?->title ?? __('Page Builder');
    $frontUrl          = $page->is_home
        ? url('/')
        : ($translation?->slug ? url($translation->slug) : url('/'));
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
    <link rel="stylesheet" href="{{ mix('assets/tamplate/css/app.css') }}">
    @vite('resources/js/dashboard/page-builder.js')
    <style>
        /* Temporary scaffolding until the new builder is implemented */
        .builder-canvas {
            background: radial-gradient(circle at 1px 1px, #e2e8f0 1px, transparent 0) 0 0/32px 32px,
                        linear-gradient(135deg, rgba(125, 211, 252, 0.08), rgba(79, 70, 229, 0.06));
        }
        .builder-stage {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .builder-block {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
            cursor: grab;
            position: relative;
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }
        .builder-block:active {
            cursor: grabbing;
        }
        .builder-block:hover {
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
            transform: translateY(-2px);
        }
        .builder-block h3 {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 8px;
        }
        .builder-block p {
            font-size: 14px;
            color: #475569;
            margin: 0;
        }
        .builder-block img {
            display: block;
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        .builder-button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
        }
        .builder-button.primary {
            background: #2563eb;
            color: #ffffff;
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.25);
        }
        .builder-button.outline {
            background: #ffffff;
            color: #1e3a8a;
            border: 1px solid #93c5fd;
        }
        .block-actions {
            position: absolute;
            top: 10px;
            inset-inline-end: 12px;
            display: inline-flex;
            gap: 6px;
            opacity: 0;
            transition: opacity 0.15s ease;
        }
        .builder-block:hover .block-actions {
            opacity: 1;
        }
        .block-actions button {
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #0f172a;
            border-radius: 999px;
            font-size: 12px;
            padding: 4px 8px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
        }

        /* Language switcher override (dashboard component inside builder) */
        .builder-lang {
            position: relative;
        }
        .builder-lang .pc-head-link {
            @apply inline-flex items-center gap-2 px-3 py-2 rounded-full border border-slate-200 bg-white text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 transition;
        }
        .builder-lang .dropdown-menu {
            display: none;
            position: absolute;
            inset: auto 0 auto auto;
            top: calc(100% + 10px);
            padding: 8px 10px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
            z-index: 50;
            direction: ltr;
            white-space: nowrap;
            gap: 8px;
            align-items: center;
        }
        .builder-lang.open .dropdown-menu {
            display: flex;
            flex-direction: row;
        }
        .builder-lang .dropdown-item {
            display: inline-flex !important;
            width: auto !important;
            @apply px-3 py-2 rounded-full text-sm text-slate-700 border border-slate-200 hover:bg-slate-100 items-center gap-2 leading-snug rtl:flex-row-reverse;
        }
        .builder-lang .dropdown-item[aria-current="page"],
        .builder-lang .dropdown-item.active {
            @apply bg-slate-100 font-semibold;
        }
        .builder-lang img {
            @apply inline-block w-4 h-4;
        }
    </style>
</head>
<body class="h-full bg-slate-50 text-slate-900">
    <div id="page-builder-root"
         class="min-h-screen flex flex-col"
         data-load-url="{{ route('dashboard.pages.builder.data', $page) }}"
         data-save-url="{{ route('dashboard.pages.builder.data.save', $page) }}"
         data-preview-url="{{ $frontUrl }}"
         data-builder-url="{{ route('dashboard.pages.builder', $page) }}"
         data-page-id="{{ $page->id }}">

        <header class="sticky top-0 z-30 w-full bg-white/90 backdrop-blur border-b border-slate-200 shadow-sm">
            <div class="mx-auto max-w-6xl px-4 py-3 flex items-center gap-3 justify-between">
                <div class="flex items-center gap-2 rtl:flex-row-reverse">
                    <a href="{{ route('dashboard.pages.index') }}"
                       class="px-3 py-2 rounded-full text-sm font-medium text-slate-700 border border-slate-200 bg-white hover:bg-slate-50 transition flex items-center gap-2 rtl:flex-row-reverse"
                       aria-label="{{ __('Back to pages') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12l7.5-7.5M3 12h18" />
                        </svg>
                        <span class="hidden sm:inline">{{ __('Back to pages') }}</span>
                    </a>
                    <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-full bg-white/80 border border-slate-200/80 shadow-sm">
                        <span class="text-[11px] uppercase tracking-wide text-slate-500">{{ __('Page') }}</span>
                        <span class="text-sm font-semibold text-slate-900">{{ $pageTitle }}</span>
                        <span class="text-slate-300">|</span>
                        <span class="text-xs font-semibold text-sky-700">{{ strtoupper($currentLocale) }}</span>
                    </div>
                    @if ($hasMultipleLocales)
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-slate-600">{{ __('Language') }}</span>
                            <div class="min-w-[140px] builder-lang">
                                <x-lang.language-switcher-dashboard />
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-2 rtl:flex-row-reverse">
                    <a href="{{ $frontUrl }}" target="_blank" rel="noopener"
                       class="px-4 py-2 rounded-full text-sm font-medium text-slate-700 border border-slate-200 bg-white hover:bg-slate-50 transition flex items-center gap-2 rtl:flex-row-reverse">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5H19.5V10.5M19.5 4.5L12 12M6.75 6.75h-1.5A1.5 1.5 0 003.75 8.25v9A1.5 1.5 0 005.25 18.75h9a1.5 1.5 0 001.5-1.5v-1.5" />
                        </svg>
                        <span class="hidden sm:inline">{{ __('Open live page') }}</span>
                    </a>
                    <button id="builder-save"
                        type="button"
                        class="px-5 py-2 rounded-full text-sm font-semibold text-white bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600 shadow-md hover:shadow-lg transition flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 4.5h9l3 3v9a3 3 0 01-3 3h-9a3 3 0 01-3-3v-9a3 3 0 013-3z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 4.5v5.25H9V4.5" />
                        </svg>
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
        </header>

        <main class="flex-1 flex bg-slate-50">
            <section class="flex-1 p-6 order-2">
                <div class="h-full rounded-2xl border border-slate-200 bg-white shadow-sm builder-canvas">
                    <div class="h-full overflow-auto p-8">
                        <div id="builder-empty-state" class="min-h-[420px] flex items-center justify-center text-center">
                            <div class="max-w-md space-y-3">
                                <p class="text-sm font-semibold text-slate-700">{{ __('Drag blocks to start building') }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ __('Use the Blocks panel to drag elements and reorder them on the canvas.') }}
                                </p>
                                <div class="inline-flex items-center gap-2 text-xs text-slate-500 bg-white/80 border border-slate-200 rounded-full px-3 py-1 shadow-sm">
                                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                                    <span>{{ __('Ready for content') }}</span>
                                </div>
                            </div>
                        </div>
                        <div id="builder-stage" class="builder-stage max-w-4xl mx-auto"></div>
                    </div>
                </div>
            </section>
            <aside class="w-[320px] order-1 border-r border-slate-200 rtl:border-l rtl:border-r-0 bg-white/90 backdrop-blur p-4 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-800">{{ __('Blocks') }}</h2>
                    <span class="text-[11px] text-slate-500">{{ __('Drag to canvas') }}</span>
                </div>
                <div class="grid gap-3">
                    <button type="button" draggable="true" class="builder-block-btn text-left rounded-xl border border-slate-200 bg-white hover:bg-slate-50 p-3 shadow-sm transition" data-block="text" data-title="{{ __('Text block') }}">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-slate-800">{{ __('Text') }}</span>
                            <span class="text-xs font-semibold text-slate-400">T</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">{{ __('Heading + paragraph') }}</p>
                    </button>
                    <button type="button" draggable="true" class="builder-block-btn text-left rounded-xl border border-slate-200 bg-white hover:bg-slate-50 p-3 shadow-sm transition" data-block="image" data-title="{{ __('Image block') }}">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-slate-800">{{ __('Image') }}</span>
                            <span class="text-xs font-semibold text-slate-400">I</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">{{ __('Single image with optional width') }}</p>
                    </button>
                    <button type="button" draggable="true" class="builder-block-btn text-left rounded-xl border border-slate-200 bg-white hover:bg-slate-50 p-3 shadow-sm transition" data-block="button" data-title="{{ __('Button block') }}">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-slate-800">{{ __('Button') }}</span>
                            <span class="text-xs font-semibold text-slate-400">B</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">{{ __('CTA link with style') }}</p>
                    </button>
                    <button type="button" draggable="true" class="builder-block-btn text-left rounded-xl border border-slate-200 bg-white hover:bg-slate-50 p-3 shadow-sm transition" data-block="section" data-title="{{ __('Section block') }}">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-slate-800">{{ __('Section') }}</span>
                            <span class="text-xs font-semibold text-slate-400">S</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">{{ __('Title, body, and background') }}</p>
                    </button>
                </div>
            </aside>
        </main>
    </div>
</body>
</html>
