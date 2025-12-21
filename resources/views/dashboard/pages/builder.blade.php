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
            min-height: 420px;
            width: 100%;
            margin-inline: auto;
            align-items: stretch;
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
            width: 100%;
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
        /* Fluid blocks (e.g., hero) take full width without white wrapper */
        .builder-block-fluid {
            width: 100%;
            background: transparent;
            border: none;
            padding: 0;
            box-shadow: none;
            border-radius: 0;
        }
        .builder-block-fluid > * {
            border-radius: 0;
        }
        /* Preview toggles */
        .builder-preview-toggle {
            border: 1px solid #e2e8f0;
            background: #fff;
            border-radius: 999px;
            padding: 4px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }
        .builder-preview-btn {
            border: none;
            background: transparent;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
        }
        .builder-preview-btn.active {
            background: linear-gradient(90deg, #0ea5e9, #6366f1);
            color: #fff;
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.25);
        }
        /* Preview widths */
        #page-builder-root.preview-desktop #builder-stage {
            max-width: 100%;
        }
        #page-builder-root.preview-tablet #builder-stage {
            max-width: 820px;
        }
        #page-builder-root.preview-mobile #builder-stage {
            max-width: 480px;
        }
        .builder-
        utton {
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
            z-index: 30;
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
        .block-actions {
            flex-wrap: wrap;
        }
        .block-actions .muted {
            color: #475569;
        }
        /* Inline modal editor */
        .builder-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.35);
            backdrop-filter: blur(3px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999;
            padding: 16px;
        }
        .builder-modal {
            width: min(560px, 100%);
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
            border: 1px solid #e2e8f0;
            padding: 20px;
        }
        .builder-modal h3 {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 12px;
        }
        .builder-modal .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 12px;
        }
        .builder-modal label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
        }
        .builder-modal input,
        .builder-modal textarea {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 14px;
            color: #0f172a;
            background: #f8fafc;
        }
        .builder-modal textarea {
            min-height: 90px;
            resize: vertical;
        }
        .builder-modal .actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 6px;
        }
        .builder-modal button {
            border-radius: 999px;
            border: 1px solid #e2e8f0;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .builder-modal .btn-secondary {
            background: #fff;
            color: #0f172a;
        }
        .builder-modal .btn-primary {
            background: linear-gradient(90deg, #0ea5e9, #6366f1);
            color: #fff;
            border: none;
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.25);
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
        /* Header layout */
        .builder-header {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
        }
        .builder-header .cluster {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .builder-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
        }
        .builder-chip .label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #475569;
        }
        .builder-chip .value {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }
        .builder-save-wrap {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #f8fafc;
            padding: 8px;
            border-radius: 999px;
            border: 1px solid #e2e8f0;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6), 0 8px 18px rgba(15, 23, 42, 0.1);
        }
        .builder-preview-stack {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            position: relative;
        }
        .preview-toggle {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            cursor: pointer;
            font-weight: 700;
            color: #0f172a;
        }
        .preview-menu {
            position: absolute;
            top: 110%;
            inset-inline-start: 0;
            min-width: 180px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
            padding: 6px;
            display: none;
            z-index: 40;
        }
        .preview-menu.open {
            display: block;
        }
        .preview-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 10px;
            cursor: pointer;
            border: none;
            background: transparent;
            width: 100%;
            text-align: start;
            font-weight: 600;
            color: #0f172a;
        }
        .preview-item:hover,
        .preview-item.active {
            background: linear-gradient(90deg, #0ea5e9, #6366f1);
            color: #fff;
        }
        /* Outline panel */
        .builder-outline {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 14px;
            padding: 10px;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        }
        .builder-outline-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .builder-outline-empty {
            padding: 10px;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            background: #fff;
            text-align: center;
        }
        .builder-outline-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
            cursor: grab;
            transition: box-shadow 0.15s ease, transform 0.15s ease;
        }
        .builder-outline-item:hover {
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
            transform: translateY(-1px);
        }
        .builder-outline-item:active {
            cursor: grabbing;
        }
        .builder-outline-handle {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background: linear-gradient(180deg, #e2e8f0, #cbd5e1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #334155;
        }
        .builder-outline-meta {
            display: flex;
            flex-direction: column;
            gap: 2px;
            flex: 1;
            min-width: 0;
        }
        .builder-outline-title {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .builder-outline-type {
            font-size: 11px;
            color: #64748b;
        }
        .builder-outline-actions {
            display: inline-flex;
            gap: 6px;
        }
        .builder-outline-btn {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #0f172a;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s ease, box-shadow 0.15s ease;
        }
        .builder-outline-btn:hover {
            background: #e2e8f0;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
        }
        /* Tabs */
        .builder-tabs {
            display: inline-flex;
            background: #e2e8f0;
            padding: 4px;
            border-radius: 999px;
            gap: 4px;
        }
        .builder-tab {
            border: none;
            background: transparent;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            color: #475569;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .builder-tab.active {
            background: linear-gradient(90deg, #0ea5e9, #6366f1);
            color: #fff;
            box-shadow: 0 8px 18px rgba(79, 70, 229, 0.25);
        }
        .builder-tab-content {
            display: none;
        }
        .builder-tab-content.active {
            display: block;
        }
        /* Outline editor panel */
        .outline-editor {
            border: 1px solid #e2e8f0;
            background: #fff;
            border-radius: 14px;
            padding: 12px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
            margin-top: 12px;
        }
        .outline-editor h3 {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
        }
        .outline-editor .field {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-top: 10px;
        }
        .outline-editor label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
        }
        .outline-editor input,
        .outline-editor textarea,
        .outline-editor select {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 13px;
            background: #f8fafc;
        }
        .outline-editor textarea {
            min-height: 70px;
            resize: vertical;
        }
        .outline-editor .actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 12px;
        }
        .outline-editor .btn {
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 700;
            background: #fff;
            cursor: pointer;
        }
        .outline-editor .btn-primary {
            background: linear-gradient(90deg, #0ea5e9, #6366f1);
            color: #fff;
            border: none;
            box-shadow: 0 10px 18px rgba(79, 70, 229, 0.18);
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
            <div class="mx-auto max-w-6xl px-4">
                <div class="builder-header">
                    <div class="cluster rtl:flex-row-reverse">
                        <a href="{{ route('dashboard.pages.index') }}"
                           class="px-3 py-2 rounded-full text-sm font-medium text-slate-700 border border-slate-200 bg-white hover:bg-slate-50 transition flex items-center gap-2 rtl:flex-row-reverse"
                           aria-label="{{ __('Back to pages') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12l7.5-7.5M3 12h18" />
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
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.5h16.5m-16.5 0A1.5 1.5 0 002.25 6v12A1.5 1.5 0 003.75 19.5h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5m-16.5 0V6M20.25 4.5V6m-16.5 0h16.5M6 18.75h12" />
                            </svg>
                            <span class="builder-preview-label" data-preview-label>Desktop</span>
                        </button>
                        <div class="preview-menu" id="preview-menu">
                            <button type="button" class="preview-item builder-preview-btn active" data-preview="desktop">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5.25A1.5 1.5 0 014.5 3.75h15a1.5 1.5 0 011.5 1.5v11.5a1.5 1.5 0 01-1.5 1.5h-15A1.5 1.5 0 013 16.75z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19.5h6" />
                                </svg>
                                <span>Desktop</span>
                            </button>
                            <button type="button" class="preview-item builder-preview-btn" data-preview="tablet">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 4.5h10.5a1.5 1.5 0 011.5 1.5v12a1.5 1.5 0 01-1.5 1.5H6.75A1.5 1.5 0 015.25 18V6a1.5 1.5 0 011.5-1.5z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5.25h6" />
                                </svg>
                                <span>Tablet</span>
                            </button>
                            <button type="button" class="preview-item builder-preview-btn" data-preview="mobile">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75h9a1.5 1.5 0 011.5 1.5v13.5a1.5 1.5 0 01-1.5 1.5h-9a1.5 1.5 0 01-1.5-1.5V5.25a1.5 1.5 0 011.5-1.5z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 18.75h3" />
                                </svg>
                                <span>Mobile</span>
                            </button>
                        </div>
                    </div>

                    <div class="cluster rtl:flex-row-reverse">
                        <a href="{{ $frontUrl }}" target="_blank" rel="noopener"
                           class="px-4 py-2 rounded-full text-sm font-medium text-slate-700 border border-slate-200 bg-white hover:bg-slate-50 transition flex items-center gap-2 rtl:flex-row-reverse">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5H19.5V10.5M19.5 4.5L12 12M6.75 6.75h-1.5A1.5 1.5 0 003.75 8.25v9A1.5 1.5 0 005.25 18.75h9a1.5 1.5 0 001.5-1.5v-1.5" />
                            </svg>
                            <span class="hidden sm:inline">{{ __('Open live page') }}</span>
                        </a>
                        <div class="builder-save-wrap">
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
                </div>
            </div>
        </header>

        <main class="flex-1 flex bg-slate-50">
            <section class="flex-1 p-6 order-2">
                    <div class="h-full rounded-2xl border border-slate-200 bg-white shadow-sm builder-canvas">
                        <div class="h-full overflow-auto p-4">
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
                        <div id="builder-stage" class="builder-stage w-full"></div>
                    </div>
                </div>
            </section>
            <aside class="w-[320px] order-1 border-r border-slate-200 rtl:border-l rtl:border-r-0 bg-white/90 backdrop-blur p-4 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="builder-tabs" role="tablist">
                        <button type="button" class="builder-tab" data-tab-target="palette" role="tab" aria-selected="false">{{ __('Blocks') }}</button>
                        <button type="button" class="builder-tab active" data-tab-target="outline" role="tab" aria-selected="true">{{ __('Outline') }}</button>
                    </div>
                    <span class="text-[11px] text-slate-500 hidden" data-tab-helper="palette">{{ __('Drag to canvas') }}</span>
                    <span class="text-[11px] text-slate-500" data-tab-helper="outline">{{ __('Reorder & jump') }}</span>
                </div>
                <div class="builder-tab-content active" data-tab-content="palette">
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
                        <button type="button" draggable="true" class="builder-block-btn text-left rounded-xl border border-slate-200 bg-white hover:bg-slate-50 p-3 shadow-sm transition" data-block="support-hero" data-title="{{ __('Support hero') }}">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-slate-800">{{ __('Support hero') }}</span>
                                <span class="text-xs font-semibold text-slate-400">H</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">{{ __('Large hero with background') }}</p>
                        </button>
                        <button type="button" draggable="true" class="builder-block-btn text-left rounded-xl border border-slate-200 bg-white hover:bg-slate-50 p-3 shadow-sm transition" data-block="hero-template" data-title="{{ __('Template hero') }}">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-slate-800">{{ __('Template hero') }}</span>
                                <span class="text-xs font-semibold text-slate-400">H</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">{{ __('Hero with CTA buttons') }}</p>
                        </button>
                    </div>
                </div>
                <div class="builder-tab-content" data-tab-content="outline">
                    <div class="builder-outline" id="builder-outline">
                        <div id="builder-outline-empty" class="builder-outline-empty text-xs text-slate-500">
                            {{ __('No blocks yet') }}
                        </div>
                        <div id="builder-outline-list" class="builder-outline-list"></div>
                    </div>
                    <div id="outline-editor" class="outline-editor hidden">
                        <div class="flex items-center justify-between">
                            <h3 data-outline-editor-title>{{ __('Edit block') }}</h3>
                            <button type="button" class="btn" data-outline-editor-back>{{ __('Back to outline') }}</button>
                        </div>
                        <div id="outline-editor-fields"></div>
                        <div class="actions">
                            <button type="button" class="btn" data-outline-editor-back>{{ __('Cancel') }}</button>
                            <button type="button" class="btn-primary btn" data-outline-editor-save>{{ __('Save') }}</button>
                        </div>
                    </div>
                </div>
            </aside>
        </main>
    </div>
</body>
</html>
