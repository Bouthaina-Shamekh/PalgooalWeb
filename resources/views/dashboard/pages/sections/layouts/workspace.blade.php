@php
    $translation = $page->translation();
    $pageTitle = $translation?->title ?? __('Sections Workspace');
    $frontUrl = $page->is_home ? url('/') : ($translation?->slug ? url($translation->slug) : url('/'));
    $workspaceLanguages = collect($languages ?? [])->filter(fn ($language) => filled($language->code))->values();
    $hasMultipleWorkspaceLanguages = $workspaceLanguages->count() > 1;
@endphp

<!doctype html>
<html lang="{{ app()->getLocale() }}" class="h-full" dir="{{ current_dir() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $pageTitle }} - {{ __('Sections Workspace') }}</title>

    <link rel="stylesheet" href="{{ mix('assets/tamplate/css/app.css') }}" id="palgoals-app-css">

    <style>
        .workspace-scrollbar::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        .workspace-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }

        .workspace-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sections-workspace-panels {
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .sections-workspace-main {
            min-width: 0;
            flex: 1 1 auto;
        }

        .sections-workspace-sidebar-shell {
            position: relative;
            flex: 0 0 auto;
            overflow: visible;
        }

        .sections-workspace-sidebar {
            flex: 0 0 auto;
            height: 100%;
            border-color: #e2e8f0;
        }

        .sections-workspace-shell.is-sidebar-collapsed .sections-workspace-sidebar-shell {
            display: none;
        }

        .sections-sidebar-open-button {
            position: absolute;
            top: 50%;
            z-index: 12;
            display: none;
            height: 3rem;
            width: 1.125rem;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            background: #ffffff;
            color: #334155;
            box-shadow: 0 8px 20px -14px rgba(15, 23, 42, 0.28), 0 2px 6px rgba(15, 23, 42, 0.08);
            transform: translateY(-50%);
            transition: transform 180ms ease, box-shadow 180ms ease, background-color 180ms ease, color 180ms ease;
        }

        .sections-workspace-shell.is-sidebar-collapsed .sections-sidebar-open-button {
            display: inline-flex;
        }

        .sections-sidebar-open-button:hover,
        .sections-sidebar-handle:hover {
            background: #f8fafc;
            color: #0f172a;
            transform: translateY(-50%) scale(1.05);
            box-shadow: 0 12px 24px -16px rgba(15, 23, 42, 0.32), 0 4px 10px rgba(15, 23, 42, 0.1);
        }

        .sections-sidebar-open-button:focus-visible,
        .sections-sidebar-handle:focus-visible {
            outline: 2px solid #cbd5e1;
            outline-offset: 3px;
        }

        .sections-sidebar-handle {
            position: absolute;
            top: 50%;
            z-index: 10;
            display: inline-flex;
            height: 3rem;
            width: 1.125rem;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            background: #ffffff;
            color: #334155;
            box-shadow: 0 8px 20px -14px rgba(15, 23, 42, 0.28), 0 2px 6px rgba(15, 23, 42, 0.08);
            transform: translateY(-50%);
            transition: transform 180ms ease, box-shadow 180ms ease, background-color 180ms ease, color 180ms ease;
        }

        html[dir="ltr"] .sections-sidebar-open-button {
            left: -0.5625rem;
        }

        html[dir="ltr"] .sections-sidebar-handle {
            right: -0.5625rem;
        }

        html[dir="rtl"] .sections-sidebar-open-button {
            right: -0.5625rem;
        }

        html[dir="rtl"] .sections-sidebar-handle {
            left: -0.5625rem;
        }

        html[dir="ltr"] .sections-sidebar-open-button svg,
        html[dir="rtl"] .sections-sidebar-handle svg {
            transform: rotate(180deg);
        }

        .sections-header-cluster > a,
        .sections-header-cluster > button,
        .sections-header-cluster > form > button {
            min-height: 2.75rem;
        }

        @media (min-width: 1280px) {
            html[dir="ltr"] .sections-workspace-panels,
            html[dir="rtl"] .sections-workspace-panels {
                flex-direction: row-reverse;
            }

            .sections-workspace-sidebar-shell {
                width: 24rem;
                min-width: 24rem;
            }

            html[dir="ltr"] .sections-workspace-sidebar {
                border-right-width: 1px;
            }

            html[dir="rtl"] .sections-workspace-sidebar {
                border-left-width: 1px;
            }
        }
    </style>

    @stack('styles')
</head>
<body class="h-full bg-slate-100 text-slate-900">
    <div id="sections-workspace-shell" class="sections-workspace-shell flex h-screen flex-col overflow-hidden">
        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur shadow-sm">
            <div class="px-4 py-2 lg:px-6">
                <div class="flex flex-wrap items-center justify-between gap-2 xl:flex-nowrap">
                    <div class="flex min-w-0 items-center gap-3 rtl:flex-row-reverse">
                        <a
                            href="{{ route('dashboard.pages.index') }}"
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50 hover:shadow-md"
                            aria-label="{{ __('Back to pages') }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12l7.5-7.5M3 12h18" />
                            </svg>
                        </a>

                        <div class="flex min-w-0 flex-wrap items-center gap-2 rtl:flex-row-reverse">
                            <div class="inline-flex max-w-full items-center gap-2 rounded-full border border-slate-200 bg-slate-100/90 px-4 py-2 shadow-sm rtl:flex-row-reverse">
                                <h1 class="truncate text-sm font-semibold text-slate-900 lg:text-[15px]">
                                    {{ $pageTitle }}
                                </h1>
                                @if ($page->is_home)
                                    <span class="rounded-full bg-white px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-500">
                                        {{ __('Home') }}
                                    </span>
                                @endif
                            </div>

                            @if ($hasMultipleWorkspaceLanguages)
                                <x-lang.language-switcher
                                    variant="builder"
                                    buttonClass="inline-flex h-10 items-center gap-2 rounded-full border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                                    menuClass="absolute mt-2 min-w-[11rem] rounded-2xl border border-slate-200 bg-white p-2 shadow-xl z-40 rtl:right-0 rtl:left-auto ltr:left-0 ltr:right-auto"
                                    itemClass="block w-full rounded-xl px-3 py-2 text-sm transition hover:bg-slate-50 ltr:text-left rtl:text-right"
                                    activeItemClass="bg-slate-100 font-semibold text-slate-900"
                                />
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 rtl:flex-row-reverse xl:flex-nowrap xl:justify-end">
                        @hasSection('workspace-header-toolbar')
                            @yield('workspace-header-toolbar')
                        @endif

                        <div class="sections-header-cluster flex flex-wrap items-center gap-2 rounded-[1.75rem] border border-slate-200 bg-slate-100/90 p-1 shadow-inner rtl:flex-row-reverse">
                            <a
                                href="{{ $frontUrl }}"
                                target="_blank"
                                class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-white hover:shadow-sm"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12S5.25 5.25 12 5.25 21.75 12 21.75 12 18.75 18.75 12 18.75 2.25 12 2.25 12Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z" />
                                </svg>
                                {{ __('Preview') }}
                            </a>

                            <a
                                href="{{ route('dashboard.pages.builder', $page) }}"
                                class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-white hover:shadow-sm"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5v10.5H3.75V5.25Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 18.75h6M12 15.75v3" />
                                </svg>
                                {{ __('Visual Builder') }}
                            </a>
                        </div>

                        @hasSection('workspace-header-actions')
                            <div class="sections-header-cluster flex flex-wrap items-center gap-2 rounded-[1.75rem] border border-slate-200 bg-white p-1 shadow-sm rtl:flex-row-reverse">
                                @yield('workspace-header-actions')
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-hidden">
            <div class="sections-workspace-panels">
                <button
                    type="button"
                    id="sections-sidebar-open-btn"
                    class="sections-sidebar-open-button"
                    aria-controls="sections-workspace-sidebar"
                    aria-expanded="false"
                    aria-label="{{ __('Show Sidebar') }}"
                    title="{{ __('Show Sidebar') }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6" />
                    </svg>
                </button>

                <section class="sections-workspace-main workspace-scrollbar overflow-y-auto px-4 py-5 lg:px-6">
                    @yield('workspace-main')
                </section>

                <div class="sections-workspace-sidebar-shell">
                    <button
                        type="button"
                        id="sections-sidebar-hide-btn"
                        class="sections-sidebar-handle"
                        aria-controls="sections-workspace-sidebar"
                        aria-expanded="true"
                        aria-label="{{ __('Hide Sidebar') }}"
                        title="{{ __('Hide Sidebar') }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6" />
                        </svg>
                    </button>

                    <aside id="sections-workspace-sidebar" class="sections-workspace-sidebar workspace-scrollbar border-t border-slate-200 bg-white/90 overflow-y-auto px-4 py-5 lg:px-6">
                        @yield('workspace-sidebar')
                    </aside>
                </div>
            </div>
        </main>
    </div>

    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const shell = document.getElementById('sections-workspace-shell');
            const hideButton = document.getElementById('sections-sidebar-hide-btn');
            const openButton = document.getElementById('sections-sidebar-open-btn');
            const storageKey = 'sections-workspace-sidebar-collapsed';
            const showSidebarLabel = @json(__('Show Sidebar'));
            const hideSidebarLabel = @json(__('Hide Sidebar'));

            if (!shell || (!hideButton && !openButton)) {
                return;
            }

            const applySidebarState = (collapsed) => {
                shell.classList.toggle('is-sidebar-collapsed', collapsed);

                if (hideButton) {
                    hideButton.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                    hideButton.setAttribute('aria-label', collapsed ? showSidebarLabel : hideSidebarLabel);
                    hideButton.title = collapsed ? showSidebarLabel : hideSidebarLabel;
                }

                if (openButton) {
                    openButton.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                    openButton.setAttribute('aria-label', collapsed ? showSidebarLabel : hideSidebarLabel);
                    openButton.title = collapsed ? showSidebarLabel : hideSidebarLabel;
                }
            };

            const persistSidebarState = (collapsed) => {
                try {
                    window.localStorage.setItem(storageKey, collapsed ? '1' : '0');
                } catch (error) {
                    // Ignore storage failures and keep the UI responsive.
                }
            };

            const readInitialState = () => {
                try {
                    return window.localStorage.getItem(storageKey) === '1';
                } catch (error) {
                    return false;
                }
            };

            applySidebarState(readInitialState());

            hideButton?.addEventListener('click', function () {
                applySidebarState(true);
                persistSidebarState(true);
            });

            openButton?.addEventListener('click', function () {
                applySidebarState(false);
                persistSidebarState(false);
            });
        });
    </script>
</body>
</html>
