@php
    $translation = $page->translation();
    $pageTitle = $translation?->title ?? __('Sections Workspace');
    $frontUrl = $page->is_home ? url('/') : ($translation?->slug ? url($translation->slug) : url('/'));
    $workspaceLanguages = collect($languages ?? [])->filter(fn ($language) => filled($language->code))->values();
    $hasMultipleWorkspaceLanguages = $workspaceLanguages->count() > 1;
    $adminLogoPath = $settings?->admin_logo ?: $settings?->logo;
    $adminLogoHref = ! empty($adminLogoPath)
        ? (\Illuminate\Support\Str::startsWith($adminLogoPath, ['http://', 'https://', '//'])
            ? $adminLogoPath
            : asset('storage/' . ltrim(preg_replace('#^storage/#', '', $adminLogoPath), '/')))
        : asset('assets/tamplate/images/logo.svg');
    $sectionsIconLibrary = [
        ['label' => __('Template'), 'value' => 'ti ti-layout-grid', 'keywords' => 'template layout grid blocks cards'],
        ['label' => __('Hosting'), 'value' => 'ti ti-server', 'keywords' => 'hosting server infrastructure cloud'],
        ['label' => __('Settings'), 'value' => 'ti ti-settings', 'keywords' => 'settings config options setup'],
        ['label' => __('Mail'), 'value' => 'ti ti-mail', 'keywords' => 'mail email message inbox'],
        ['label' => __('Domain'), 'value' => 'ti ti-world', 'keywords' => 'domain world globe website internet'],
        ['label' => __('Support'), 'value' => 'ti ti-headset', 'keywords' => 'support help service call'],
        ['label' => __('Analysis'), 'value' => 'ti ti-search', 'keywords' => 'analysis inspect research search audit'],
        ['label' => __('Design'), 'value' => 'ti ti-palette', 'keywords' => 'design palette creative colors'],
        ['label' => __('Development'), 'value' => 'ti ti-code', 'keywords' => 'development code programming engineering'],
        ['label' => __('Testing'), 'value' => 'ti ti-test-pipe', 'keywords' => 'testing qa quality review bug'],
        ['label' => __('Launch'), 'value' => 'ti ti-rocket', 'keywords' => 'launch publish release growth'],
        ['label' => __('Mobile'), 'value' => 'ti ti-device-mobile', 'keywords' => 'mobile phone app smartphone'],
        ['label' => __('Desktop'), 'value' => 'ti ti-device-desktop', 'keywords' => 'desktop web laptop monitor'],
        ['label' => __('Marketing'), 'value' => 'ti ti-speakerphone', 'keywords' => 'marketing campaign ads announce'],
        ['label' => __('Store'), 'value' => 'ti ti-shopping-cart', 'keywords' => 'store shop ecommerce cart'],
        ['label' => __('Business'), 'value' => 'ti ti-briefcase', 'keywords' => 'business company service work'],
        ['label' => __('Team'), 'value' => 'ti ti-users', 'keywords' => 'team users people clients'],
        ['label' => __('Client'), 'value' => 'ti ti-user-star', 'keywords' => 'client customer testimonial review'],
        ['label' => __('Message'), 'value' => 'ti ti-message-circle', 'keywords' => 'message comment chat feedback'],
        ['label' => __('Checklist'), 'value' => 'ti ti-checklist', 'keywords' => 'checklist tasks process steps'],
        ['label' => __('Package'), 'value' => 'ti ti-package', 'keywords' => 'package box shipping product'],
        ['label' => __('Box'), 'value' => 'ti ti-box', 'keywords' => 'box package product item'],
        ['label' => __('Shield'), 'value' => 'ti ti-shield-check', 'keywords' => 'shield security trust safe'],
        ['label' => __('Lightning'), 'value' => 'ti ti-bolt', 'keywords' => 'fast speed bolt performance'],
        ['label' => __('Image'), 'value' => 'ti ti-photo', 'keywords' => 'image photo gallery media'],
        ['label' => __('Brush'), 'value' => 'ti ti-brush', 'keywords' => 'brush design art branding'],
        ['label' => __('Apps'), 'value' => 'ti ti-apps', 'keywords' => 'apps modules collection tools'],
        ['label' => __('Building'), 'value' => 'ti ti-building-store', 'keywords' => 'building store office branch'],
        ['label' => __('Chart'), 'value' => 'ti ti-chart-bar', 'keywords' => 'chart analytics data metrics'],
        ['label' => __('Seo'), 'value' => 'ti ti-chart-arrows-vertical', 'keywords' => 'seo rank growth analytics'],
    ];
@endphp

<!doctype html>
<html lang="{{ app()->getLocale() }}" class="h-full" dir="{{ current_dir() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $pageTitle }} - {{ __('Sections Workspace') }}</title>
    <link rel="icon" href="{{ $adminLogoHref }}">

    <link rel="stylesheet" href="{{ mix('assets/tamplate/css/app.css') }}" id="palgoals-app-css">
    <link rel="stylesheet" href="{{ asset('assets/dashboard/fonts/tabler-icons.min.css') }}">

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

        .sections-workspace-shell.is-section-editor-open .sections-workspace-sidebar {
            overflow: hidden;
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

        .sections-icon-library-tile.is-active {
            border-color: #0f172a;
            background: #f8fafc;
            box-shadow: 0 14px 30px -24px rgba(15, 23, 42, 0.35);
        }

        .sections-editor-icon-preview svg,
        .sections-editor-icon-preview img {
            max-width: 1.75rem;
            max-height: 1.75rem;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .sections-editor-icon-preview svg {
            display: block;
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

    <script>
        window.MEDIA_CONFIG = window.MEDIA_CONFIG || {};
        window.MEDIA_CONFIG.baseUrl = window.MEDIA_CONFIG.baseUrl || @json(url('admin/media'));
        window.MEDIA_CONFIG.csrfToken = window.MEDIA_CONFIG.csrfToken || @json(csrf_token());
    </script>
    <script src="{{ asset('assets/dashboard/js/plugins/sweetalert2.all.min.js') }}"></script>
    <script>
        window.sectionsShowAlert = function (options = {}) {
            const tone = options.tone === 'success' ? 'success' : 'error';
            const title = String(options.title || (tone === 'success' ? @json(__('Success')) : @json(__('Something went wrong'))));
            const messages = Array.isArray(options.messages) ? options.messages.filter(Boolean) : [];
            const text = String(options.text || '');

            if (typeof Swal === 'undefined') {
                const fallbackMessage = messages.length ? messages.join("\n") : text || title;
                window.alert(fallbackMessage);
                return;
            }

            if (tone === 'success') {
                const toast = Swal.mixin({
                    toast: true,
                    position: @json(current_dir() === 'rtl' ? 'top-start' : 'top-end'),
                    showConfirmButton: false,
                    timer: 2600,
                    timerProgressBar: true,
                });

                toast.fire({
                    icon: 'success',
                    title: messages[0] || text || title,
                });

                return;
            }

            const html = messages.length
                ? `<div class="text-start"><ul style="margin:0;padding-inline-start:1.25rem;">${messages.map((message) => `<li>${String(message)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')}</li>`).join('')}</ul></div>`
                : '';

            Swal.fire({
                icon: 'error',
                title,
                text: html ? undefined : (text || ''),
                html: html || undefined,
                confirmButtonText: @json(__('OK')),
                customClass: {
                    popup: 'rounded-[1.5rem]',
                    confirmButton: 'inline-flex items-center rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white',
                },
                buttonsStyling: false,
            });
        };

        document.addEventListener('DOMContentLoaded', function () {
            const successMessage = @json(session('success'));
            const errorMessage = @json(session('error'));
            const validationErrors = @json($errors->all());

            if (successMessage) {
                window.sectionsShowAlert({
                    tone: 'success',
                    messages: [successMessage],
                });
            }

            if (errorMessage) {
                window.sectionsShowAlert({
                    tone: 'error',
                    title: @json(__('Error')),
                    messages: [errorMessage],
                });
            }

            if (Array.isArray(validationErrors) && validationErrors.length > 0) {
                window.sectionsShowAlert({
                    tone: 'error',
                    title: @json(__('Please review the form')),
                    messages: validationErrors,
                });
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="{{ asset('assets/dashboard/js/media-picker.js') }}?v={{ filemtime(public_path('assets/dashboard/js/media-picker.js')) }}" defer></script>
    @include('dashboard.partials.media-picker')
    <div id="sections-icon-library-overlay" class="fixed inset-0 z-[70] hidden bg-slate-950/55"></div>
    <div id="sections-icon-library-modal" class="fixed inset-0 z-[71] hidden items-center justify-center p-4 lg:p-6" aria-hidden="true">
        <div class="flex max-h-[calc(100vh-2rem)] w-full max-w-5xl flex-col overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
            <div class="border-b border-slate-200 px-5 py-4 lg:px-6">
                <div class="flex items-start justify-between gap-4 rtl:flex-row-reverse">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">{{ __('Icon Library') }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Search and choose an icon for the current item.') }}</p>
                    </div>
                    <div class="flex items-center gap-2 rtl:flex-row-reverse">
                        <button
                            type="button"
                            data-section-icon-library-clear
                            class="hidden rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                        >
                            {{ __('Clear Icon') }}
                        </button>
                        <button
                            type="button"
                            data-close-section-icon-library
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50"
                            aria-label="{{ __('Close') }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex flex-1 flex-col overflow-hidden px-5 py-4 lg:px-6">
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 ltr:left-3 rtl:right-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m0 0A7.5 7.5 0 1 0 6 6a7.5 7.5 0 0 0 10.65 10.65Z" />
                    </svg>
                    <input
                        id="sections-icon-library-search"
                        type="text"
                        placeholder="{{ __('Search icons by name or use case') }}"
                        class="w-full rounded-full border border-slate-200 bg-white py-3 text-sm text-slate-700 outline-none transition focus:border-slate-400 ltr:pl-10 ltr:pr-4 ltr:text-left rtl:pl-4 rtl:pr-10 rtl:text-right"
                    >
                </div>

                <div class="mt-3 flex items-center justify-between gap-3 text-xs text-slate-500 rtl:flex-row-reverse">
                    <p>{{ __('Click any icon to apply it immediately to the current field.') }}</p>
                    <span id="sections-icon-library-count"></span>
                </div>

                <div
                    id="sections-icon-library-grid"
                    class="workspace-scrollbar mt-4 grid flex-1 grid-cols-2 gap-3 overflow-y-auto pb-1 pr-1 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
                ></div>

                <div
                    id="sections-icon-library-empty"
                    class="mt-4 hidden rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500"
                >
                    {{ __('No icons match this search yet.') }}
                </div>
            </div>
        </div>
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

            window.addEventListener('media-picker-confirmed', function (event) {
                const targetInputId = event.detail?.targetInputId;
                if (!targetInputId) {
                    return;
                }

                const targetInput = document.getElementById(targetInputId);
                const currentGroup = targetInput?.closest?.('[data-shared-media-group]');
                const form = targetInput?.closest?.('[data-section-editor-form]');

                if (targetInput) {
                    targetInput.dispatchEvent(new Event('input', { bubbles: true }));
                    targetInput.dispatchEvent(new Event('change', { bubbles: true }));
                }

                if (!currentGroup || !form) {
                    return;
                }

                const groupName = currentGroup.dataset.sharedMediaGroup;
                const values = Array.isArray(event.detail?.values) ? event.detail.values : [];
                const items = Array.isArray(event.detail?.items) ? event.detail.items : [];
                const nextValue = values.join(',');

                form.querySelectorAll(`[data-shared-media-group="${groupName}"]`).forEach((group) => {
                    const input = group.querySelector('input[type="hidden"]');
                    const preview = group.querySelector('[id$="_preview"]');

                    if (!input) {
                        return;
                    }

                    input.value = nextValue;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));

                    if (!preview) {
                        return;
                    }

                    preview.innerHTML = '';
                    items.forEach((item) => {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'relative h-20 w-20 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900';

                        const image = document.createElement('img');
                        image.src = item.url || '';
                        image.alt = item.name || '';
                        image.className = 'h-full w-full object-cover';

                        wrapper.appendChild(image);
                        preview.appendChild(wrapper);
                    });
                });
            });

            window.initSectionEditorTabs?.(document);
            window.initSectionIconLibrary?.();
            window.initSectionFeatureRepeaters?.(document);
            window.initSectionOutputRepeaters?.(document);
            window.initSectionServiceRepeaters?.(document);
            window.initBuildStepRepeaters?.(document);
            window.initReviewRepeaters?.(document);
        });

        window.initSectionIconLibrary = function () {
            if (window.__sectionsIconLibraryBound) {
                return;
            }

            const iconLibrary = @json($sectionsIconLibrary);
            const overlay = document.getElementById('sections-icon-library-overlay');
            const modal = document.getElementById('sections-icon-library-modal');
            const searchInput = document.getElementById('sections-icon-library-search');
            const grid = document.getElementById('sections-icon-library-grid');
            const emptyState = document.getElementById('sections-icon-library-empty');
            const countLabel = document.getElementById('sections-icon-library-count');
            const clearButton = modal?.querySelector('[data-section-icon-library-clear]');

            if (!overlay || !modal || !searchInput || !grid || !emptyState || !countLabel || !clearButton) {
                return;
            }

            const sanitizeIconClass = (value) => String(value || '')
                .replace(/[^A-Za-z0-9\-_ ]/g, '')
                .replace(/\s+/g, ' ')
                .trim();

            let activeInput = null;
            let activeValue = '';

            const closeLibrary = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                overlay.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
                activeInput = null;
                activeValue = '';
                searchInput.value = '';
            };

            const applyIconValue = (value) => {
                if (!(activeInput instanceof HTMLInputElement || activeInput instanceof HTMLTextAreaElement)) {
                    return;
                }

                activeInput.value = sanitizeIconClass(value);
                activeInput.dispatchEvent(new Event('input', { bubbles: true }));
                activeInput.dispatchEvent(new Event('change', { bubbles: true }));
            };

            const renderLibrary = (query = '') => {
                const normalizedQuery = String(query || '').trim().toLowerCase();
                const filteredIcons = iconLibrary.filter((icon) => {
                    const haystack = `${icon.label} ${icon.value} ${icon.keywords || ''}`.toLowerCase();
                    return normalizedQuery === '' || haystack.includes(normalizedQuery);
                });

                grid.innerHTML = '';
                countLabel.textContent = `${filteredIcons.length} ${filteredIcons.length === 1 ? @json(__('icon')) : @json(__('icons'))}`;
                emptyState.classList.toggle('hidden', filteredIcons.length > 0);
                grid.classList.toggle('hidden', filteredIcons.length === 0);
                clearButton.classList.toggle('hidden', activeValue === '');

                filteredIcons.forEach((icon) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.dataset.sectionIconOption = 'true';
                    button.dataset.sectionIconValue = icon.value;
                    button.className = 'sections-icon-library-tile flex flex-col items-start gap-2 rounded-2xl border border-slate-200 bg-white p-3 text-left transition hover:border-slate-300 hover:bg-slate-50 rtl:text-right';

                    if (icon.value === activeValue) {
                        button.classList.add('is-active');
                    }

                    button.innerHTML = `
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-50 text-slate-700">
                            <i class="${icon.value} text-lg leading-none" aria-hidden="true"></i>
                        </span>
                        <span class="block w-full truncate text-sm font-semibold text-slate-900">${icon.label}</span>
                        <span class="block w-full truncate text-xs text-slate-500">${icon.value}</span>
                    `;

                    grid.appendChild(button);
                });
            };

            const resolveInputFromTrigger = (trigger) => {
                if (!(trigger instanceof HTMLElement)) {
                    return null;
                }

                const selector = trigger.dataset.iconInputSelector || '';
                if (!selector) {
                    return null;
                }

                const container =
                    trigger.closest('[data-feature-item]') ||
                    trigger.closest('[data-build-step-item]') ||
                    trigger.closest('[data-section-editor-form]') ||
                    document;

                return container.querySelector(selector) || document.querySelector(selector);
            };

            const openLibrary = (trigger) => {
                const input = resolveInputFromTrigger(trigger);
                if (!input) {
                    return;
                }

                activeInput = input;
                activeValue = sanitizeIconClass(input.value);
                searchInput.value = '';
                renderLibrary('');

                overlay.classList.remove('hidden');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');

                window.setTimeout(() => {
                    searchInput.focus();
                    searchInput.select();
                }, 30);
            };

            searchInput.addEventListener('input', function () {
                renderLibrary(searchInput.value);
            });

            clearButton.addEventListener('click', function () {
                applyIconValue('');
                closeLibrary();
            });

            overlay.addEventListener('click', closeLibrary);

            modal.querySelectorAll('[data-close-section-icon-library]').forEach((button) => {
                button.addEventListener('click', closeLibrary);
            });

            document.addEventListener('click', function (event) {
                const openTrigger = event.target.closest('[data-open-section-icon-library]');
                if (openTrigger) {
                    event.preventDefault();
                    openLibrary(openTrigger);
                    return;
                }

                const optionTrigger = event.target.closest('[data-section-icon-option]');
                if (optionTrigger && !modal.classList.contains('hidden')) {
                    event.preventDefault();
                    applyIconValue(optionTrigger.dataset.sectionIconValue || '');
                    closeLibrary();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeLibrary();
                }
            });

            window.__sectionsIconLibraryBound = true;
        };

        window.initSectionEditorTabs = function (scope) {
            const root = scope instanceof Element || scope instanceof Document ? scope : document;
            const editorRoots = root.matches?.('[data-section-editor-form]')
                ? [root]
                : Array.from(root.querySelectorAll('[data-section-editor-form]'));

            editorRoots.forEach((form) => {
                if (form.dataset.editorTabsBound === '1') {
                    return;
                }

                const buttons = Array.from(form.querySelectorAll('[data-editor-tab-button]'));
                const panels = Array.from(form.querySelectorAll('[data-editor-tab-panel]'));

                if (!buttons.length || !panels.length) {
                    form.dataset.editorTabsBound = '1';
                    return;
                }

                const activateTab = (targetId) => {
                    buttons.forEach((button) => {
                        const isActive = button.dataset.tab === targetId;
                        button.classList.toggle('border-slate-900', isActive);
                        button.classList.toggle('text-slate-900', isActive);
                        button.classList.toggle('border-transparent', !isActive);
                        button.classList.toggle('text-slate-500', !isActive);
                    });

                    panels.forEach((panel) => {
                        panel.classList.toggle('hidden', panel.id !== targetId);
                    });
                };

                buttons.forEach((button) => {
                    button.addEventListener('click', function () {
                        activateTab(button.dataset.tab || '');
                    });
                });

                const defaultTab = form.dataset.defaultEditorTab || buttons[0].dataset.tab || '';
                const hasDefaultButton = buttons.some((button) => button.dataset.tab === defaultTab);

                activateTab(hasDefaultButton ? defaultTab : (buttons[0].dataset.tab || ''));
                form.dataset.editorTabsBound = '1';
            });
        };

        window.initSectionFeatureRepeaters = function (scope) {
            const root = scope instanceof Element || scope instanceof Document ? scope : document;
            const repeaters = root.matches?.('[data-feature-repeater]')
                ? [root]
                : Array.from(root.querySelectorAll('[data-feature-repeater]'));

            const createUniqueId = () => `feature_icon_${Math.random().toString(36).slice(2, 10)}`;

            repeaters.forEach((repeater) => {
                if (repeater.dataset.featureRepeaterBound === '1') {
                    return;
                }

                const list = repeater.querySelector('[data-feature-items]');
                const template = repeater.querySelector('template[data-feature-item-template]');
                const emptyState = repeater.querySelector('[data-feature-empty]');
                const addButtons = Array.from(repeater.querySelectorAll('[data-add-feature-item]'));
                const featureItemLabel = repeater.dataset.featureItemLabel || 'Feature';
                const featureItemHint = repeater.dataset.featureItemHint || 'Click to edit this feature';

                if (!list || !template) {
                    repeater.dataset.featureRepeaterBound = '1';
                    return;
                }

                const sanitizeIconClass = (value) => String(value || '')
                    .replace(/[^A-Za-z0-9\-_ ]/g, '')
                    .replace(/\s+/g, ' ')
                    .trim();

                const sanitizeInlineSvg = (value) => {
                    let svg = String(value || '').trim();
                    if (!svg || !/<svg\b/i.test(svg)) {
                        return '';
                    }

                    svg = svg.replace(/<\?(?:xml|php).*?\?>/gis, '');
                    svg = svg.replace(/<!DOCTYPE[^>]*>/gi, '');
                    svg = svg.replace(/<(script|style|foreignObject)\b.*?<\/\1>/gis, '');
                    svg = svg.replace(/\son[a-zA-Z-]+\s*=\s*(?:"[^"]*"|'[^']*'|[^\s>]+)/g, '');
                    svg = svg.replace(/\s(?:href|xlink:href)\s*=\s*(?:"\s*javascript:[^"]*"|'\s*javascript:[^']*'|javascript:[^\s>]+)/gi, '');

                    const match = svg.match(/<svg\b[\s\S]*<\/svg>/i);
                    if (!match) {
                        return '';
                    }

                    svg = match[0];

                    return svg.trim();
                };

                const renderMediaPreview = (container, urls = []) => {
                    if (!(container instanceof HTMLElement)) {
                        return;
                    }

                    container.innerHTML = '';
                    urls.filter(Boolean).forEach((url) => {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'relative h-14 w-14 overflow-hidden rounded-xl border border-slate-200 bg-slate-50';

                        const image = document.createElement('img');
                        image.src = url;
                        image.alt = '';
                        image.className = 'h-full w-full object-contain p-2';

                        wrapper.appendChild(image);
                        container.appendChild(wrapper);
                    });
                };

                const getFeatureIconSource = (item) => {
                    const sourceInput = item.querySelector('[data-feature-field="icon_source"]');
                    const source = String(sourceInput?.value || 'class').trim();

                    return ['class', 'svg', 'media'].includes(source) ? source : 'class';
                };

                const ensureFeatureIconMediaTargets = (item) => {
                    const mediaInput = item.querySelector('[data-feature-field="icon_media"]');
                    const mediaButton = item.querySelector('[data-feature-icon-media-button]');
                    const mediaPreview = item.querySelector('[data-feature-icon-media-preview]');

                    if (!(mediaInput instanceof HTMLInputElement) || !(mediaButton instanceof HTMLElement) || !(mediaPreview instanceof HTMLElement)) {
                        return;
                    }

                    if (!mediaInput.id) {
                        mediaInput.id = `${createUniqueId()}_input`;
                    }

                    if (!mediaPreview.id) {
                        mediaPreview.id = `${createUniqueId()}_preview`;
                    }

                    mediaButton.dataset.targetInput = mediaInput.id;
                    mediaButton.dataset.targetPreview = mediaPreview.id;
                    mediaButton.dataset.multiple = 'false';
                    mediaButton.dataset.storeValue = 'id';
                };

                const toggleFeatureIconPanels = (item, source = null) => {
                    const activeSource = source || getFeatureIconSource(item);
                    item.querySelectorAll('[data-feature-icon-panel]').forEach((panel) => {
                        panel.classList.toggle('hidden', panel.dataset.featureIconPanel !== activeSource);
                    });
                };

                const renderIconPreview = (item) => {
                    const preview = item.querySelector('[data-feature-icon-preview]');
                    const classInput = item.querySelector('[data-feature-field="icon"]');
                    const svgInput = item.querySelector('[data-feature-field="icon_svg"]');
                    const mediaPreview = item.querySelector('[data-feature-icon-media-preview]');
                    const source = getFeatureIconSource(item);

                    if (!preview || !classInput) {
                        return;
                    }

                    const iconClass = sanitizeIconClass(classInput.value);
                    if (classInput.value !== iconClass) {
                        classInput.value = iconClass;
                    }

                    const iconSvg = sanitizeInlineSvg(svgInput?.value || '');
                    const mediaUrl = mediaPreview?.querySelector('img')?.getAttribute('src') || '';

                    preview.innerHTML = '';

                    if (source === 'svg' && iconSvg) {
                        preview.innerHTML = iconSvg;
                        return;
                    }

                    if (source === 'media' && mediaUrl) {
                        const image = document.createElement('img');
                        image.src = mediaUrl;
                        image.alt = '';
                        image.className = 'h-full w-full object-contain';
                        preview.appendChild(image);
                        return;
                    }

                    const icon = document.createElement('i');
                    icon.className = `${iconClass || 'ti ti-check'} text-xl leading-none`;
                    icon.setAttribute('aria-hidden', 'true');
                    preview.appendChild(icon);
                };

                const setFeatureExpanded = (item, expanded, collapseOthers = false) => {
                    if (!(item instanceof HTMLElement)) {
                        return;
                    }

                    if (collapseOthers) {
                        Array.from(list.querySelectorAll('[data-feature-item]')).forEach((entry) => {
                            if (entry !== item) {
                                setFeatureExpanded(entry, false, false);
                            }
                        });
                    }

                    const body = item.querySelector('[data-feature-item-body]');
                    const toggle = item.querySelector('[data-feature-toggle]');
                    const toggleIcon = item.querySelector('[data-feature-toggle-icon]');

                    if (body) {
                        body.classList.toggle('hidden', !expanded);
                    }

                    if (toggle) {
                        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                    }

                    if (toggleIcon) {
                        toggleIcon.classList.toggle('rotate-180', expanded);
                    }
                };

                const refreshFeatureItemMeta = (item, index = null) => {
                    if (!(item instanceof HTMLElement)) {
                        return;
                    }

                    const textInput = item.querySelector('[data-feature-field="text"]');
                    const iconInput = item.querySelector('[data-feature-field="icon"]');
                    const iconSvgInput = item.querySelector('[data-feature-field="icon_svg"]');
                    const mediaPreview = item.querySelector('[data-feature-icon-media-preview]');
                    const title = item.querySelector('[data-feature-item-title]');
                    const summary = item.querySelector('[data-feature-item-summary]');
                    const textValue = String(textInput?.value || '').trim();
                    const iconValue = sanitizeIconClass(iconInput?.value || '');
                    const svgValue = sanitizeInlineSvg(iconSvgInput?.value || '');
                    const mediaValue = mediaPreview?.querySelector('img')?.getAttribute('src') || '';
                    const source = getFeatureIconSource(item);
                    const fallbackTitle = `${featureItemLabel} ${Number(index ?? 0) + 1}`;

                    if (title) {
                        title.textContent = textValue || fallbackTitle;
                    }

                    if (summary) {
                        summary.textContent = source === 'svg'
                            ? (svgValue ? @json(__('Custom SVG icon')) : featureItemHint)
                            : source === 'media'
                                ? (mediaValue ? @json(__('SVG from media library')) : featureItemHint)
                                : (iconValue ? @json(__('Tabler icon selected')) : featureItemHint);
                    }
                };

                const reindexItems = () => {
                    const items = Array.from(list.querySelectorAll('[data-feature-item]'));

                    items.forEach((item, index) => {
                        item.querySelectorAll('[data-name-template]').forEach((field) => {
                            const templateName = field.dataset.nameTemplate || '';
                            if (templateName) {
                                field.name = templateName.replace(/__INDEX__/g, String(index));
                            }
                        });

                        const numberBadge = item.querySelector('[data-feature-item-number]');
                        if (numberBadge) {
                            numberBadge.textContent = String(index + 1);
                        }

                        refreshFeatureItemMeta(item, index);
                    });

                    if (emptyState) {
                        emptyState.classList.toggle('hidden', items.length > 0);
                    }

                    const hasExpandedItem = items.some((item) => {
                        const body = item.querySelector('[data-feature-item-body]');
                        return body && !body.classList.contains('hidden');
                    });

                    if (!hasExpandedItem && items[0]) {
                        setFeatureExpanded(items[0], true, false);
                    }
                };

                const bindFeatureItem = (item) => {
                    if (!(item instanceof HTMLElement) || item.dataset.featureItemBound === '1') {
                        return;
                    }

                    ensureFeatureIconMediaTargets(item);

                    const iconInput = item.querySelector('[data-feature-field="icon"]');
                    const iconSourceInput = item.querySelector('[data-feature-field="icon_source"]');
                    const iconSvgInput = item.querySelector('[data-feature-field="icon_svg"]');
                    const iconMediaInput = item.querySelector('[data-feature-field="icon_media"]');
                    const textInput = item.querySelector('[data-feature-field="text"]');
                    const mediaPreview = item.querySelector('[data-feature-icon-media-preview]');
                    const removeButton = item.querySelector('[data-remove-feature-item]');
                    const duplicateButton = item.querySelector('[data-duplicate-feature-item]');
                    const toggleButton = item.querySelector('[data-feature-toggle]');

                    iconInput?.addEventListener('input', function () {
                        renderIconPreview(item);
                        refreshFeatureItemMeta(item);
                    });

                    iconSourceInput?.addEventListener('change', function () {
                        toggleFeatureIconPanels(item, getFeatureIconSource(item));
                        renderIconPreview(item);
                        refreshFeatureItemMeta(item);
                    });

                    iconSvgInput?.addEventListener('input', function () {
                        renderIconPreview(item);
                        refreshFeatureItemMeta(item);
                    });

                    iconMediaInput?.addEventListener('input', function () {
                        renderIconPreview(item);
                        refreshFeatureItemMeta(item);
                    });

                    iconMediaInput?.addEventListener('change', function () {
                        renderIconPreview(item);
                        refreshFeatureItemMeta(item);
                    });

                    textInput?.addEventListener('input', function () {
                        refreshFeatureItemMeta(item);
                    });

                    removeButton?.addEventListener('click', function () {
                        item.remove();
                        reindexItems();
                    });

                    duplicateButton?.addEventListener('click', function () {
                        const createdItem = createFeatureItem({
                            text: textInput?.value || '',
                            icon: iconInput?.value || '',
                            iconSource: getFeatureIconSource(item),
                            iconSvg: iconSvgInput?.value || '',
                            iconMedia: iconMediaInput?.value || '',
                            mediaPreviewUrl: mediaPreview?.querySelector('img')?.getAttribute('src') || '',
                        });

                        setFeatureExpanded(createdItem, true, true);
                    });

                    toggleButton?.addEventListener('click', function () {
                        const body = item.querySelector('[data-feature-item-body]');
                        const shouldExpand = body?.classList.contains('hidden') ?? true;
                        setFeatureExpanded(item, shouldExpand, shouldExpand);
                    });

                    toggleFeatureIconPanels(item, getFeatureIconSource(item));
                    renderIconPreview(item);
                    refreshFeatureItemMeta(item);
                    item.dataset.featureItemBound = '1';
                };

                const createFeatureItem = (seed = {}) => {
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = template.innerHTML.trim();

                    const item = wrapper.firstElementChild;
                    if (!(item instanceof HTMLElement)) {
                        return null;
                    }

                    list.appendChild(item);
                    bindFeatureItem(item);

                    const textInput = item.querySelector('[data-feature-field="text"]');
                    const iconInput = item.querySelector('[data-feature-field="icon"]');
                    const iconSourceInput = item.querySelector('[data-feature-field="icon_source"]');
                    const iconSvgInput = item.querySelector('[data-feature-field="icon_svg"]');
                    const iconMediaInput = item.querySelector('[data-feature-field="icon_media"]');
                    const mediaPreview = item.querySelector('[data-feature-icon-media-preview]');

                    if (textInput && typeof seed.text === 'string') {
                        textInput.value = seed.text;
                    }

                    if (iconInput && typeof seed.icon === 'string') {
                        iconInput.value = sanitizeIconClass(seed.icon);
                    }

                    if (iconSourceInput && typeof seed.iconSource === 'string') {
                        iconSourceInput.value = ['class', 'svg', 'media'].includes(seed.iconSource) ? seed.iconSource : 'class';
                    }

                    if (iconSvgInput && typeof seed.iconSvg === 'string') {
                        iconSvgInput.value = seed.iconSvg;
                    }

                    if (iconMediaInput && typeof seed.iconMedia === 'string') {
                        iconMediaInput.value = seed.iconMedia;
                    }

                    renderMediaPreview(mediaPreview, typeof seed.mediaPreviewUrl === 'string' && seed.mediaPreviewUrl ? [seed.mediaPreviewUrl] : []);
                    toggleFeatureIconPanels(item, getFeatureIconSource(item));
                    renderIconPreview(item);
                    reindexItems();
                    setFeatureExpanded(item, true, true);

                    return item;
                };

                addButtons.forEach((button) => {
                    button.addEventListener('click', function () {
                        const item = createFeatureItem();
                        const textInput = item?.querySelector('[data-feature-field="text"]');

                        if (textInput instanceof HTMLElement) {
                            window.setTimeout(() => textInput.focus(), 30);
                        }
                    });
                });

                Array.from(list.querySelectorAll('[data-feature-item]')).forEach(bindFeatureItem);

                if (typeof Sortable !== 'undefined' && list.dataset.featureSortableBound !== '1') {
                    Sortable.create(list, {
                        animation: 160,
                        handle: '[data-feature-drag-handle]',
                        ghostClass: 'sections-sortable-ghost',
                        chosenClass: 'sections-sortable-chosen',
                        dragClass: 'sections-sortable-drag',
                        onEnd: reindexItems,
                    });

                    list.dataset.featureSortableBound = '1';
                }

                reindexItems();
                repeater.dataset.featureRepeaterBound = '1';
            });
        };

        window.initSectionOutputRepeaters = function (scope) {
            const root = scope instanceof Element || scope instanceof Document ? scope : document;
            const repeaters = root.matches?.('[data-output-repeater]')
                ? [root]
                : Array.from(root.querySelectorAll('[data-output-repeater]'));

            const createUniqueId = () => `output_icon_${Math.random().toString(36).slice(2, 10)}`;

            repeaters.forEach((repeater) => {
                if (repeater.dataset.outputRepeaterBound === '1') {
                    return;
                }

                const list = repeater.querySelector('[data-output-items]');
                const template = repeater.querySelector('template[data-output-item-template]');
                const emptyState = repeater.querySelector('[data-output-empty]');
                const addButtons = Array.from(repeater.querySelectorAll('[data-add-output-item]'));
                const outputItemLabel = repeater.dataset.outputItemLabel || 'Output';
                const outputItemHint = repeater.dataset.outputItemHint || 'Click to edit this output';

                if (!list || !template) {
                    repeater.dataset.outputRepeaterBound = '1';
                    return;
                }

                const sanitizeIconClass = (value) => String(value || '')
                    .replace(/[^A-Za-z0-9\-_ ]/g, '')
                    .replace(/\s+/g, ' ')
                    .trim();

                const renderMediaPreview = (container, urls = []) => {
                    if (!(container instanceof HTMLElement)) {
                        return;
                    }

                    container.innerHTML = '';
                    urls.filter(Boolean).forEach((url) => {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'relative h-14 w-14 overflow-hidden rounded-xl border border-slate-200 bg-slate-50';

                        const image = document.createElement('img');
                        image.src = url;
                        image.alt = '';
                        image.className = 'h-full w-full object-contain p-2';

                        wrapper.appendChild(image);
                        container.appendChild(wrapper);
                    });
                };

                const getOutputIconSource = (item) => {
                    const sourceInput = item.querySelector('[data-output-field="icon_source"]');
                    const source = String(sourceInput?.value || 'class').trim();

                    return ['class', 'media'].includes(source) ? source : 'class';
                };

                const ensureOutputIconMediaTargets = (item) => {
                    const mediaInput = item.querySelector('[data-output-field="icon_media"]');
                    const mediaButton = item.querySelector('[data-output-icon-media-button]');
                    const mediaPreview = item.querySelector('[data-output-icon-media-preview]');

                    if (!(mediaInput instanceof HTMLInputElement) || !(mediaButton instanceof HTMLElement) || !(mediaPreview instanceof HTMLElement)) {
                        return;
                    }

                    if (!mediaInput.id) {
                        mediaInput.id = `${createUniqueId()}_input`;
                    }

                    if (!mediaPreview.id) {
                        mediaPreview.id = `${createUniqueId()}_preview`;
                    }

                    mediaButton.dataset.targetInput = mediaInput.id;
                    mediaButton.dataset.targetPreview = mediaPreview.id;
                    mediaButton.dataset.multiple = 'false';
                    mediaButton.dataset.storeValue = 'id';
                };

                const toggleOutputIconPanels = (item, source = null) => {
                    const activeSource = source || getOutputIconSource(item);
                    item.querySelectorAll('[data-output-icon-panel]').forEach((panel) => {
                        panel.classList.toggle('hidden', panel.dataset.outputIconPanel !== activeSource);
                    });
                };

                const renderOutputIconPreview = (item) => {
                    const preview = item.querySelector('[data-output-icon-preview]');
                    const classInput = item.querySelector('[data-output-field="icon"]');
                    const mediaPreview = item.querySelector('[data-output-icon-media-preview]');
                    const source = getOutputIconSource(item);

                    if (!(preview instanceof HTMLElement) || !(classInput instanceof HTMLInputElement)) {
                        return;
                    }

                    const iconClass = sanitizeIconClass(classInput.value);
                    if (classInput.value !== iconClass) {
                        classInput.value = iconClass;
                    }

                    const mediaUrl = mediaPreview?.querySelector('img')?.getAttribute('src') || '';
                    preview.innerHTML = '';

                    if (source === 'media' && mediaUrl) {
                        const image = document.createElement('img');
                        image.src = mediaUrl;
                        image.alt = '';
                        image.className = 'h-full w-full object-contain';
                        preview.appendChild(image);
                        return;
                    }

                    if (iconClass) {
                        const icon = document.createElement('i');
                        icon.className = `${iconClass} text-xl leading-none`;
                        icon.setAttribute('aria-hidden', 'true');
                        preview.appendChild(icon);
                        return;
                    }

                    const line = document.createElement('span');
                    line.className = 'h-0.5 w-5 rounded-full bg-red-brand';
                    preview.appendChild(line);
                };

                const setOutputExpanded = (item, expanded, collapseOthers = false) => {
                    if (!(item instanceof HTMLElement)) {
                        return;
                    }

                    if (collapseOthers) {
                        Array.from(list.querySelectorAll('[data-output-item]')).forEach((entry) => {
                            if (entry !== item) {
                                setOutputExpanded(entry, false, false);
                            }
                        });
                    }

                    const body = item.querySelector('[data-output-item-body]');
                    const toggle = item.querySelector('[data-output-toggle]');
                    const toggleIcon = item.querySelector('[data-output-toggle-icon]');

                    if (body) {
                        body.classList.toggle('hidden', !expanded);
                    }

                    if (toggle) {
                        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                    }

                    if (toggleIcon) {
                        toggleIcon.classList.toggle('rotate-180', expanded);
                    }
                };

                const refreshOutputItemMeta = (item, index = null) => {
                    if (!(item instanceof HTMLElement)) {
                        return;
                    }

                    const textInput = item.querySelector('[data-output-field="text"]');
                    const iconInput = item.querySelector('[data-output-field="icon"]');
                    const mediaPreview = item.querySelector('[data-output-icon-media-preview]');
                    const title = item.querySelector('[data-output-item-title]');
                    const summary = item.querySelector('[data-output-item-summary]');
                    const textValue = String(textInput?.value || '').trim();
                    const iconValue = sanitizeIconClass(iconInput?.value || '');
                    const mediaValue = mediaPreview?.querySelector('img')?.getAttribute('src') || '';
                    const source = getOutputIconSource(item);
                    const fallbackTitle = `${outputItemLabel} ${Number(index ?? 0) + 1}`;

                    if (title) {
                        title.textContent = textValue || fallbackTitle;
                    }

                    if (summary) {
                        summary.textContent = source === 'media'
                            ? (mediaValue ? @json(__('SVG from media library')) : outputItemHint)
                            : (iconValue ? @json(__('Tabler icon selected')) : (textValue ? @json(__('Visible in the outputs list')) : outputItemHint));
                    }
                };

                const reindexItems = () => {
                    const items = Array.from(list.querySelectorAll('[data-output-item]'));

                    items.forEach((item, index) => {
                        item.querySelectorAll('[data-name-template]').forEach((field) => {
                            const templateName = field.dataset.nameTemplate || '';
                            if (templateName) {
                                field.name = templateName.replace(/__INDEX__/g, String(index));
                            }
                        });

                        refreshOutputItemMeta(item, index);
                    });

                    if (emptyState) {
                        emptyState.classList.toggle('hidden', items.length > 0);
                    }

                    const hasExpandedItem = items.some((item) => {
                        const body = item.querySelector('[data-output-item-body]');
                        return body && !body.classList.contains('hidden');
                    });

                    if (!hasExpandedItem && items[0]) {
                        setOutputExpanded(items[0], true, false);
                    }
                };

                const bindOutputItem = (item) => {
                    if (!(item instanceof HTMLElement) || item.dataset.outputItemBound === '1') {
                        return;
                    }

                    const textInput = item.querySelector('[data-output-field="text"]');
                    const iconInput = item.querySelector('[data-output-field="icon"]');
                    const iconSourceInput = item.querySelector('[data-output-field="icon_source"]');
                    const iconMediaInput = item.querySelector('[data-output-field="icon_media"]');
                    const mediaPreview = item.querySelector('[data-output-icon-media-preview]');
                    const removeButton = item.querySelector('[data-remove-output-item]');
                    const duplicateButton = item.querySelector('[data-duplicate-output-item]');
                    const toggleButton = item.querySelector('[data-output-toggle]');

                    ensureOutputIconMediaTargets(item);

                    textInput?.addEventListener('input', function () {
                        refreshOutputItemMeta(item);
                    });

                    iconInput?.addEventListener('input', function () {
                        renderOutputIconPreview(item);
                        refreshOutputItemMeta(item);
                    });

                    iconSourceInput?.addEventListener('change', function () {
                        toggleOutputIconPanels(item, getOutputIconSource(item));
                        renderOutputIconPreview(item);
                        refreshOutputItemMeta(item);
                    });

                    iconMediaInput?.addEventListener('input', function () {
                        renderOutputIconPreview(item);
                        refreshOutputItemMeta(item);
                    });

                    iconMediaInput?.addEventListener('change', function () {
                        renderOutputIconPreview(item);
                        refreshOutputItemMeta(item);
                    });

                    removeButton?.addEventListener('click', function () {
                        item.remove();
                        reindexItems();
                    });

                    duplicateButton?.addEventListener('click', function () {
                        const createdItem = createOutputItem({
                            text: textInput?.value || '',
                            icon: iconInput?.value || '',
                            iconSource: getOutputIconSource(item),
                            iconMedia: iconMediaInput?.value || '',
                            mediaPreviewUrl: mediaPreview?.querySelector('img')?.getAttribute('src') || '',
                        });

                        setOutputExpanded(createdItem, true, true);
                    });

                    toggleButton?.addEventListener('click', function () {
                        const body = item.querySelector('[data-output-item-body]');
                        const shouldExpand = body?.classList.contains('hidden') ?? true;
                        setOutputExpanded(item, shouldExpand, shouldExpand);
                    });

                    toggleOutputIconPanels(item, getOutputIconSource(item));
                    renderOutputIconPreview(item);
                    refreshOutputItemMeta(item);
                    item.dataset.outputItemBound = '1';
                };

                const createOutputItem = (seed = {}) => {
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = template.innerHTML.trim();

                    const item = wrapper.firstElementChild;
                    if (!(item instanceof HTMLElement)) {
                        return null;
                    }

                    list.appendChild(item);
                    bindOutputItem(item);

                    const textInput = item.querySelector('[data-output-field="text"]');
                    const iconInput = item.querySelector('[data-output-field="icon"]');
                    const iconSourceInput = item.querySelector('[data-output-field="icon_source"]');
                    const iconMediaInput = item.querySelector('[data-output-field="icon_media"]');
                    const mediaPreview = item.querySelector('[data-output-icon-media-preview]');

                    if (textInput && typeof seed.text === 'string') {
                        textInput.value = seed.text;
                    }

                    if (iconInput && typeof seed.icon === 'string') {
                        iconInput.value = sanitizeIconClass(seed.icon);
                    }

                    if (iconSourceInput && typeof seed.iconSource === 'string') {
                        iconSourceInput.value = ['class', 'media'].includes(seed.iconSource) ? seed.iconSource : 'class';
                    }

                    if (iconMediaInput && typeof seed.iconMedia === 'string') {
                        iconMediaInput.value = seed.iconMedia;
                    }

                    renderMediaPreview(mediaPreview, typeof seed.mediaPreviewUrl === 'string' && seed.mediaPreviewUrl ? [seed.mediaPreviewUrl] : []);
                    toggleOutputIconPanels(item, getOutputIconSource(item));
                    renderOutputIconPreview(item);
                    reindexItems();
                    setOutputExpanded(item, true, true);

                    return item;
                };

                addButtons.forEach((button) => {
                    button.addEventListener('click', function () {
                        const item = createOutputItem();
                        const textInput = item?.querySelector('[data-output-field="text"]');

                        if (textInput instanceof HTMLElement) {
                            window.setTimeout(() => textInput.focus(), 30);
                        }
                    });
                });

                Array.from(list.querySelectorAll('[data-output-item]')).forEach(bindOutputItem);

                if (typeof Sortable !== 'undefined' && list.dataset.outputSortableBound !== '1') {
                    Sortable.create(list, {
                        animation: 160,
                        handle: '[data-output-drag-handle]',
                        ghostClass: 'sections-sortable-ghost',
                        chosenClass: 'sections-sortable-chosen',
                        dragClass: 'sections-sortable-drag',
                        onEnd: reindexItems,
                    });

                    list.dataset.outputSortableBound = '1';
                }

                reindexItems();
                repeater.dataset.outputRepeaterBound = '1';
            });
        };

        window.initSectionServiceRepeaters = function (scope) {
            const root = scope instanceof Element || scope instanceof Document ? scope : document;
            const repeaters = root.matches?.('[data-service-repeater]')
                ? [root]
                : Array.from(root.querySelectorAll('[data-service-repeater]'));

            const createUniqueId = () => `service_icon_${Math.random().toString(36).slice(2, 10)}`;

            repeaters.forEach((repeater) => {
                if (repeater.dataset.serviceRepeaterBound === '1') {
                    return;
                }

                const list = repeater.querySelector('[data-service-items]');
                const template = repeater.querySelector('template[data-service-item-template]');
                const emptyState = repeater.querySelector('[data-service-empty]');
                const addButtons = Array.from(repeater.querySelectorAll('[data-add-service-item]'));
                const serviceItemLabel = repeater.dataset.serviceItemLabel || 'Service';
                const serviceItemHint = repeater.dataset.serviceItemHint || 'Click to edit this service';

                if (!list || !template) {
                    repeater.dataset.serviceRepeaterBound = '1';
                    return;
                }

                const sanitizeIconClass = (value) => String(value || '')
                    .replace(/[^A-Za-z0-9\-_ ]/g, '')
                    .replace(/\s+/g, ' ')
                    .trim();

                const renderMediaPreview = (container, urls = []) => {
                    if (!(container instanceof HTMLElement)) {
                        return;
                    }

                    container.innerHTML = '';
                    urls.filter(Boolean).forEach((url) => {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'relative h-14 w-14 overflow-hidden rounded-xl border border-slate-200 bg-slate-50';

                        const image = document.createElement('img');
                        image.src = url;
                        image.alt = '';
                        image.className = 'h-full w-full object-contain p-2';

                        wrapper.appendChild(image);
                        container.appendChild(wrapper);
                    });
                };

                const getServiceIconSource = (item) => {
                    const sourceInput = item.querySelector('[data-service-field="icon_source"]');
                    const source = String(sourceInput?.value || 'class').trim();

                    return ['class', 'media'].includes(source) ? source : 'class';
                };

                const ensureServiceIconMediaTargets = (item) => {
                    const mediaInput = item.querySelector('[data-service-field="icon_media"]');
                    const mediaButton = item.querySelector('[data-service-icon-media-button]');
                    const mediaPreview = item.querySelector('[data-service-icon-media-preview]');

                    if (!(mediaInput instanceof HTMLInputElement) || !(mediaButton instanceof HTMLElement) || !(mediaPreview instanceof HTMLElement)) {
                        return;
                    }

                    if (!mediaInput.id) {
                        mediaInput.id = `${createUniqueId()}_input`;
                    }

                    if (!mediaPreview.id) {
                        mediaPreview.id = `${createUniqueId()}_preview`;
                    }

                    mediaButton.dataset.targetInput = mediaInput.id;
                    mediaButton.dataset.targetPreview = mediaPreview.id;
                    mediaButton.dataset.multiple = 'false';
                    mediaButton.dataset.storeValue = 'id';
                };

                const toggleServiceIconPanels = (item, source = null) => {
                    const activeSource = source || getServiceIconSource(item);
                    item.querySelectorAll('[data-service-icon-panel]').forEach((panel) => {
                        panel.classList.toggle('hidden', panel.dataset.serviceIconPanel !== activeSource);
                    });
                };

                const renderServiceIconPreview = (item) => {
                    const preview = item.querySelector('[data-service-icon-preview]');
                    const classInput = item.querySelector('[data-service-field="icon"]');
                    const mediaPreview = item.querySelector('[data-service-icon-media-preview]');
                    const source = getServiceIconSource(item);

                    if (!(preview instanceof HTMLElement) || !(classInput instanceof HTMLInputElement)) {
                        return;
                    }

                    const iconClass = sanitizeIconClass(classInput.value);
                    if (classInput.value !== iconClass) {
                        classInput.value = iconClass;
                    }

                    const mediaUrl = mediaPreview?.querySelector('img')?.getAttribute('src') || '';
                    preview.innerHTML = '';

                    if (source === 'media' && mediaUrl) {
                        const image = document.createElement('img');
                        image.src = mediaUrl;
                        image.alt = '';
                        image.className = 'h-full w-full object-contain';
                        preview.appendChild(image);
                        return;
                    }

                    if (iconClass) {
                        const icon = document.createElement('i');
                        icon.className = `${iconClass} text-xl leading-none`;
                        icon.setAttribute('aria-hidden', 'true');
                        preview.appendChild(icon);
                        return;
                    }

                    preview.innerHTML = '<svg width="10" height="13" viewBox="0 0 10 13" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M9.75 6.49512L0 12.9903V-7.34329e-05L9.75 6.49512Z" fill="#BA112C"></path></svg>';
                };

                const setServiceExpanded = (item, expanded, collapseOthers = false) => {
                    if (!(item instanceof HTMLElement)) {
                        return;
                    }

                    if (collapseOthers) {
                        Array.from(list.querySelectorAll('[data-service-item]')).forEach((entry) => {
                            if (entry !== item) {
                                setServiceExpanded(entry, false, false);
                            }
                        });
                    }

                    const body = item.querySelector('[data-service-item-body]');
                    const toggle = item.querySelector('[data-service-toggle]');
                    const toggleIcon = item.querySelector('[data-service-toggle-icon]');

                    if (body) {
                        body.classList.toggle('hidden', !expanded);
                    }

                    if (toggle) {
                        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                    }

                    if (toggleIcon) {
                        toggleIcon.classList.toggle('rotate-180', expanded);
                    }
                };

                const refreshServiceItemMeta = (item, index = null) => {
                    if (!(item instanceof HTMLElement)) {
                        return;
                    }

                    const textInput = item.querySelector('[data-service-field="text"]');
                    const iconInput = item.querySelector('[data-service-field="icon"]');
                    const mediaPreview = item.querySelector('[data-service-icon-media-preview]');
                    const title = item.querySelector('[data-service-item-title]');
                    const summary = item.querySelector('[data-service-item-summary]');
                    const textValue = String(textInput?.value || '').trim();
                    const iconValue = sanitizeIconClass(iconInput?.value || '');
                    const mediaValue = mediaPreview?.querySelector('img')?.getAttribute('src') || '';
                    const source = getServiceIconSource(item);
                    const fallbackTitle = `${serviceItemLabel} ${Number(index ?? 0) + 1}`;

                    if (title) {
                        title.textContent = textValue || fallbackTitle;
                    }

                    if (summary) {
                        summary.textContent = source === 'media'
                            ? (mediaValue ? @json(__('SVG from media library')) : serviceItemHint)
                            : (iconValue ? @json(__('Tabler icon selected')) : (textValue ? @json(__('Uses the default service marker')) : serviceItemHint));
                    }
                };

                const reindexItems = () => {
                    const items = Array.from(list.querySelectorAll('[data-service-item]'));

                    items.forEach((item, index) => {
                        item.querySelectorAll('[data-name-template]').forEach((field) => {
                            const templateName = field.dataset.nameTemplate || '';
                            if (templateName) {
                                field.name = templateName.replace(/__INDEX__/g, String(index));
                            }
                        });

                        refreshServiceItemMeta(item, index);
                    });

                    if (emptyState) {
                        emptyState.classList.toggle('hidden', items.length > 0);
                    }

                    const hasExpandedItem = items.some((item) => {
                        const body = item.querySelector('[data-service-item-body]');
                        return body && !body.classList.contains('hidden');
                    });

                    if (!hasExpandedItem && items[0]) {
                        setServiceExpanded(items[0], true, false);
                    }
                };

                const bindServiceItem = (item) => {
                    if (!(item instanceof HTMLElement) || item.dataset.serviceItemBound === '1') {
                        return;
                    }

                    const textInput = item.querySelector('[data-service-field="text"]');
                    const iconInput = item.querySelector('[data-service-field="icon"]');
                    const iconSourceInput = item.querySelector('[data-service-field="icon_source"]');
                    const iconMediaInput = item.querySelector('[data-service-field="icon_media"]');
                    const mediaPreview = item.querySelector('[data-service-icon-media-preview]');
                    const removeButton = item.querySelector('[data-remove-service-item]');
                    const duplicateButton = item.querySelector('[data-duplicate-service-item]');
                    const toggleButton = item.querySelector('[data-service-toggle]');

                    ensureServiceIconMediaTargets(item);

                    textInput?.addEventListener('input', function () {
                        refreshServiceItemMeta(item);
                    });

                    iconInput?.addEventListener('input', function () {
                        renderServiceIconPreview(item);
                        refreshServiceItemMeta(item);
                    });

                    iconSourceInput?.addEventListener('change', function () {
                        toggleServiceIconPanels(item, getServiceIconSource(item));
                        renderServiceIconPreview(item);
                        refreshServiceItemMeta(item);
                    });

                    iconMediaInput?.addEventListener('input', function () {
                        renderServiceIconPreview(item);
                        refreshServiceItemMeta(item);
                    });

                    iconMediaInput?.addEventListener('change', function () {
                        renderServiceIconPreview(item);
                        refreshServiceItemMeta(item);
                    });

                    removeButton?.addEventListener('click', function () {
                        item.remove();
                        reindexItems();
                    });

                    duplicateButton?.addEventListener('click', function () {
                        const createdItem = createServiceItem({
                            text: textInput?.value || '',
                            icon: iconInput?.value || '',
                            iconSource: getServiceIconSource(item),
                            iconMedia: iconMediaInput?.value || '',
                            mediaPreviewUrl: mediaPreview?.querySelector('img')?.getAttribute('src') || '',
                        });

                        setServiceExpanded(createdItem, true, true);
                    });

                    toggleButton?.addEventListener('click', function () {
                        const body = item.querySelector('[data-service-item-body]');
                        const shouldExpand = body?.classList.contains('hidden') ?? true;
                        setServiceExpanded(item, shouldExpand, shouldExpand);
                    });

                    toggleServiceIconPanels(item, getServiceIconSource(item));
                    renderServiceIconPreview(item);
                    refreshServiceItemMeta(item);
                    item.dataset.serviceItemBound = '1';
                };

                const createServiceItem = (seed = {}) => {
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = template.innerHTML.trim();

                    const item = wrapper.firstElementChild;
                    if (!(item instanceof HTMLElement)) {
                        return null;
                    }

                    list.appendChild(item);
                    bindServiceItem(item);

                    const textInput = item.querySelector('[data-service-field="text"]');
                    const iconInput = item.querySelector('[data-service-field="icon"]');
                    const iconSourceInput = item.querySelector('[data-service-field="icon_source"]');
                    const iconMediaInput = item.querySelector('[data-service-field="icon_media"]');
                    const mediaPreview = item.querySelector('[data-service-icon-media-preview]');

                    if (textInput && typeof seed.text === 'string') {
                        textInput.value = seed.text;
                    }

                    if (iconInput && typeof seed.icon === 'string') {
                        iconInput.value = sanitizeIconClass(seed.icon);
                    }

                    if (iconSourceInput && typeof seed.iconSource === 'string') {
                        iconSourceInput.value = ['class', 'media'].includes(seed.iconSource) ? seed.iconSource : 'class';
                    }

                    if (iconMediaInput && typeof seed.iconMedia === 'string') {
                        iconMediaInput.value = seed.iconMedia;
                    }

                    renderMediaPreview(mediaPreview, typeof seed.mediaPreviewUrl === 'string' && seed.mediaPreviewUrl ? [seed.mediaPreviewUrl] : []);
                    toggleServiceIconPanels(item, getServiceIconSource(item));
                    renderServiceIconPreview(item);
                    reindexItems();
                    setServiceExpanded(item, true, true);

                    return item;
                };

                addButtons.forEach((button) => {
                    button.addEventListener('click', function () {
                        const item = createServiceItem();
                        const textInput = item?.querySelector('[data-service-field="text"]');

                        if (textInput instanceof HTMLElement) {
                            window.setTimeout(() => textInput.focus(), 30);
                        }
                    });
                });

                Array.from(list.querySelectorAll('[data-service-item]')).forEach(bindServiceItem);

                if (typeof Sortable !== 'undefined' && list.dataset.serviceSortableBound !== '1') {
                    Sortable.create(list, {
                        animation: 160,
                        handle: '[data-service-drag-handle]',
                        ghostClass: 'sections-sortable-ghost',
                        chosenClass: 'sections-sortable-chosen',
                        dragClass: 'sections-sortable-drag',
                        onEnd: reindexItems,
                    });

                    list.dataset.serviceSortableBound = '1';
                }

                reindexItems();
                repeater.dataset.serviceRepeaterBound = '1';
            });
        };

        window.initBuildStepRepeaters = function (scope) {
            const root = scope instanceof Element || scope instanceof Document ? scope : document;
            const repeaters = root.matches?.('[data-build-step-repeater]')
                ? [root]
                : Array.from(root.querySelectorAll('[data-build-step-repeater]'));

            const createUniqueId = () => `build_step_icon_${Math.random().toString(36).slice(2, 10)}`;

            repeaters.forEach((repeater) => {
                if (repeater.dataset.buildStepRepeaterBound === '1') {
                    return;
                }

                const list = repeater.querySelector('[data-build-step-items]');
                const template = repeater.querySelector('template[data-build-step-item-template]');
                const emptyState = repeater.querySelector('[data-build-step-empty]');
                const addButtons = Array.from(repeater.querySelectorAll('[data-add-build-step]'));
                const buildStepItemLabel = repeater.dataset.buildStepItemLabel || 'Step';
                const buildStepItemHint = repeater.dataset.buildStepItemHint || 'Click to edit this step';

                if (!list || !template) {
                    repeater.dataset.buildStepRepeaterBound = '1';
                    return;
                }

                const sanitizeIconClass = (value) => String(value || '')
                    .replace(/[^A-Za-z0-9\-_ ]/g, '')
                    .replace(/\s+/g, ' ')
                    .trim();

                const sanitizeInlineSvg = (value) => {
                    let svg = String(value || '').trim();
                    if (!svg || !/<svg\b/i.test(svg)) {
                        return '';
                    }

                    svg = svg.replace(/<\?(?:xml|php).*?\?>/gis, '');
                    svg = svg.replace(/<!DOCTYPE[^>]*>/gi, '');
                    svg = svg.replace(/<(script|style|foreignObject)\b.*?<\/\1>/gis, '');
                    svg = svg.replace(/\son[a-zA-Z-]+\s*=\s*(?:"[^"]*"|'[^']*'|[^\s>]+)/g, '');
                    svg = svg.replace(/\s(?:href|xlink:href)\s*=\s*(?:"\s*javascript:[^"]*"|'\s*javascript:[^']*'|javascript:[^\s>]+)/gi, '');

                    const match = svg.match(/<svg\b[\s\S]*<\/svg>/i);
                    if (!match) {
                        return '';
                    }

                    svg = match[0];

                    return svg.trim();
                };

                const renderMediaPreview = (container, urls = []) => {
                    if (!(container instanceof HTMLElement)) {
                        return;
                    }

                    container.innerHTML = '';
                    urls.filter(Boolean).forEach((url) => {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'relative h-14 w-14 overflow-hidden rounded-xl border border-slate-200 bg-slate-50';

                        const image = document.createElement('img');
                        image.src = url;
                        image.alt = '';
                        image.className = 'h-full w-full object-contain p-2';

                        wrapper.appendChild(image);
                        container.appendChild(wrapper);
                    });
                };

                const getBuildStepIconSource = (item) => {
                    const sourceInput = item.querySelector('[data-build-step-field="icon_source"]');
                    const source = String(sourceInput?.value || 'class').trim();

                    return ['class', 'svg', 'media'].includes(source) ? source : 'class';
                };

                const ensureBuildStepIconMediaTargets = (item) => {
                    const mediaInput = item.querySelector('[data-build-step-field="icon_media"]');
                    const mediaButton = item.querySelector('[data-build-step-icon-media-button]');
                    const mediaPreview = item.querySelector('[data-build-step-icon-media-preview]');

                    if (!(mediaInput instanceof HTMLInputElement) || !(mediaButton instanceof HTMLElement) || !(mediaPreview instanceof HTMLElement)) {
                        return;
                    }

                    if (!mediaInput.id) {
                        mediaInput.id = `${createUniqueId()}_input`;
                    }

                    if (!mediaPreview.id) {
                        mediaPreview.id = `${createUniqueId()}_preview`;
                    }

                    mediaButton.dataset.targetInput = mediaInput.id;
                    mediaButton.dataset.targetPreview = mediaPreview.id;
                    mediaButton.dataset.multiple = 'false';
                    mediaButton.dataset.storeValue = 'id';
                };

                const toggleBuildStepIconPanels = (item, source = null) => {
                    const activeSource = source || getBuildStepIconSource(item);
                    item.querySelectorAll('[data-build-step-icon-panel]').forEach((panel) => {
                        panel.classList.toggle('hidden', panel.dataset.buildStepIconPanel !== activeSource);
                    });
                };

                const renderIconPreview = (item) => {
                    const preview = item.querySelector('[data-build-step-icon-preview]');
                    const classInput = item.querySelector('[data-build-step-field="icon"]');
                    const svgInput = item.querySelector('[data-build-step-field="icon_svg"]');
                    const mediaPreview = item.querySelector('[data-build-step-icon-media-preview]');
                    const source = getBuildStepIconSource(item);

                    if (!preview || !classInput) {
                        return;
                    }

                    const iconClass = sanitizeIconClass(classInput.value);
                    if (classInput.value !== iconClass) {
                        classInput.value = iconClass;
                    }

                    const iconSvg = sanitizeInlineSvg(svgInput?.value || '');
                    const mediaUrl = mediaPreview?.querySelector('img')?.getAttribute('src') || '';

                    preview.innerHTML = '';

                    if (source === 'svg' && iconSvg) {
                        preview.innerHTML = iconSvg;
                        return;
                    }

                    if (source === 'media' && mediaUrl) {
                        const image = document.createElement('img');
                        image.src = mediaUrl;
                        image.alt = '';
                        image.className = 'h-full w-full object-contain';
                        preview.appendChild(image);
                        return;
                    }

                    const icon = document.createElement('i');
                    icon.className = `${iconClass || 'ti ti-search'} text-2xl leading-none`;
                    icon.setAttribute('aria-hidden', 'true');
                    preview.appendChild(icon);
                };

                const setBuildStepExpanded = (item, expanded, collapseOthers = false) => {
                    if (!(item instanceof HTMLElement)) {
                        return;
                    }

                    if (collapseOthers) {
                        Array.from(list.querySelectorAll('[data-build-step-item]')).forEach((entry) => {
                            if (entry !== item) {
                                setBuildStepExpanded(entry, false, false);
                            }
                        });
                    }

                    const body = item.querySelector('[data-build-step-item-body]');
                    const toggle = item.querySelector('[data-build-step-toggle]');
                    const toggleIcon = item.querySelector('[data-build-step-toggle-icon]');

                    if (body) {
                        body.classList.toggle('hidden', !expanded);
                    }

                    if (toggle) {
                        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                    }

                    if (toggleIcon) {
                        toggleIcon.classList.toggle('rotate-180', expanded);
                    }
                };

                const refreshBuildStepMeta = (item, index = null) => {
                    if (!(item instanceof HTMLElement)) {
                        return;
                    }

                    const titleInput = item.querySelector('[data-build-step-field="title"]');
                    const iconInput = item.querySelector('[data-build-step-field="icon"]');
                    const iconSvgInput = item.querySelector('[data-build-step-field="icon_svg"]');
                    const iconMediaPreview = item.querySelector('[data-build-step-icon-media-preview]');
                    const accentInput = item.querySelector('[data-build-step-field="accent"]');
                    const title = item.querySelector('[data-build-step-item-title]');
                    const summary = item.querySelector('[data-build-step-item-summary]');
                    const titleValue = String(titleInput?.value || '').trim();
                    const iconValue = sanitizeIconClass(iconInput?.value || '');
                    const svgValue = sanitizeInlineSvg(iconSvgInput?.value || '');
                    const mediaValue = iconMediaPreview?.querySelector('img')?.getAttribute('src') || '';
                    const source = getBuildStepIconSource(item);
                    const isAccent = accentInput?.checked || false;
                    const fallbackTitle = `${buildStepItemLabel} ${Number(index ?? 0) + 1}`;

                    if (title) {
                        title.textContent = titleValue || fallbackTitle;
                    }

                    if (summary) {
                        let summaryText = source === 'svg'
                            ? (svgValue ? @json(__('Custom SVG icon')) : buildStepItemHint)
                            : source === 'media'
                                ? (mediaValue ? @json(__('SVG from media library')) : buildStepItemHint)
                                : (iconValue ? @json(__('Tabler icon selected')) : buildStepItemHint);

                        if (isAccent) {
                            summaryText = summaryText === buildStepItemHint
                                ? @json(__('Highlighted in red'))
                                : `${summaryText} • ${@json(__('Highlighted in red'))}`;
                        }

                        summary.textContent = summaryText;
                    }
                };

                const reindexItems = () => {
                    const items = Array.from(list.querySelectorAll('[data-build-step-item]'));

                    items.forEach((item, index) => {
                        item.querySelectorAll('[data-name-template]').forEach((field) => {
                            const templateName = field.dataset.nameTemplate || '';
                            if (templateName) {
                                field.name = templateName.replace(/__INDEX__/g, String(index));
                            }
                        });

                        refreshBuildStepMeta(item, index);
                    });

                    if (emptyState) {
                        emptyState.classList.toggle('hidden', items.length > 0);
                    }

                    const hasExpandedItem = items.some((item) => {
                        const body = item.querySelector('[data-build-step-item-body]');
                        return body && !body.classList.contains('hidden');
                    });

                    if (!hasExpandedItem && items[0]) {
                        setBuildStepExpanded(items[0], true, false);
                    }
                };

                const bindStepItem = (item) => {
                    if (!(item instanceof HTMLElement) || item.dataset.buildStepItemBound === '1') {
                        return;
                    }

                    ensureBuildStepIconMediaTargets(item);

                    const iconInput = item.querySelector('[data-build-step-field="icon"]');
                    const iconSourceInput = item.querySelector('[data-build-step-field="icon_source"]');
                    const iconSvgInput = item.querySelector('[data-build-step-field="icon_svg"]');
                    const iconMediaInput = item.querySelector('[data-build-step-field="icon_media"]');
                    const titleInput = item.querySelector('[data-build-step-field="title"]');
                    const mediaPreview = item.querySelector('[data-build-step-icon-media-preview]');
                    const removeButton = item.querySelector('[data-remove-build-step]');
                    const duplicateButton = item.querySelector('[data-duplicate-build-step]');
                    const toggleButton = item.querySelector('[data-build-step-toggle]');

                    iconInput?.addEventListener('input', function () {
                        renderIconPreview(item);
                        refreshBuildStepMeta(item);
                    });

                    iconSourceInput?.addEventListener('change', function () {
                        toggleBuildStepIconPanels(item, getBuildStepIconSource(item));
                        renderIconPreview(item);
                        refreshBuildStepMeta(item);
                    });

                    iconSvgInput?.addEventListener('input', function () {
                        renderIconPreview(item);
                        refreshBuildStepMeta(item);
                    });

                    iconMediaInput?.addEventListener('input', function () {
                        renderIconPreview(item);
                        refreshBuildStepMeta(item);
                    });

                    iconMediaInput?.addEventListener('change', function () {
                        renderIconPreview(item);
                        refreshBuildStepMeta(item);
                    });

                    titleInput?.addEventListener('input', function () {
                        refreshBuildStepMeta(item);
                    });

                    item.querySelector('[data-build-step-field="accent"]')?.addEventListener('change', function () {
                        refreshBuildStepMeta(item);
                    });

                    removeButton?.addEventListener('click', function () {
                        item.remove();
                        reindexItems();
                    });

                    duplicateButton?.addEventListener('click', function () {
                        createStepItem({
                            title: titleInput?.value || '',
                            icon: iconInput?.value || '',
                            iconSource: getBuildStepIconSource(item),
                            iconSvg: iconSvgInput?.value || '',
                            iconMedia: iconMediaInput?.value || '',
                            mediaPreviewUrl: mediaPreview?.querySelector('img')?.getAttribute('src') || '',
                            isAccent: item.querySelector('[data-build-step-field="accent"]')?.checked || false,
                        });

                        setBuildStepExpanded(createdItem, true, true);
                    });

                    toggleButton?.addEventListener('click', function () {
                        const body = item.querySelector('[data-build-step-item-body]');
                        const shouldExpand = body?.classList.contains('hidden') ?? true;
                        setBuildStepExpanded(item, shouldExpand, shouldExpand);
                    });

                    toggleBuildStepIconPanels(item, getBuildStepIconSource(item));
                    renderIconPreview(item);
                    refreshBuildStepMeta(item);
                    item.dataset.buildStepItemBound = '1';
                };

                const createStepItem = (seed = {}) => {
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = template.innerHTML.trim();

                    const item = wrapper.firstElementChild;
                    if (!(item instanceof HTMLElement)) {
                        return null;
                    }

                    list.appendChild(item);
                    bindStepItem(item);

                    const titleInput = item.querySelector('[data-build-step-field="title"]');
                    const iconInput = item.querySelector('[data-build-step-field="icon"]');
                    const iconSourceInput = item.querySelector('[data-build-step-field="icon_source"]');
                    const iconSvgInput = item.querySelector('[data-build-step-field="icon_svg"]');
                    const iconMediaInput = item.querySelector('[data-build-step-field="icon_media"]');
                    const mediaPreview = item.querySelector('[data-build-step-icon-media-preview]');
                    const accentInput = item.querySelector('[data-build-step-field="accent"]');

                    if (titleInput && typeof seed.title === 'string') {
                        titleInput.value = seed.title;
                    }

                    if (iconInput && typeof seed.icon === 'string') {
                        iconInput.value = sanitizeIconClass(seed.icon);
                    }

                    if (iconSourceInput && typeof seed.iconSource === 'string') {
                        iconSourceInput.value = ['class', 'svg', 'media'].includes(seed.iconSource) ? seed.iconSource : 'class';
                    }

                    if (iconSvgInput && typeof seed.iconSvg === 'string') {
                        iconSvgInput.value = seed.iconSvg;
                    }

                    if (iconMediaInput && typeof seed.iconMedia === 'string') {
                        iconMediaInput.value = seed.iconMedia;
                    }

                    if (accentInput) {
                        accentInput.checked = Boolean(seed.isAccent);
                    }

                    renderMediaPreview(mediaPreview, typeof seed.mediaPreviewUrl === 'string' && seed.mediaPreviewUrl ? [seed.mediaPreviewUrl] : []);
                    toggleBuildStepIconPanels(item, getBuildStepIconSource(item));
                    renderIconPreview(item);
                    reindexItems();
                    setBuildStepExpanded(item, true, true);

                    return item;
                };

                addButtons.forEach((button) => {
                    button.addEventListener('click', function () {
                        const item = createStepItem();
                        const titleInput = item?.querySelector('[data-build-step-field="title"]');

                        if (titleInput instanceof HTMLElement) {
                            window.setTimeout(() => titleInput.focus(), 30);
                        }
                    });
                });

                Array.from(list.querySelectorAll('[data-build-step-item]')).forEach(bindStepItem);

                if (typeof Sortable !== 'undefined' && list.dataset.buildStepSortableBound !== '1') {
                    Sortable.create(list, {
                        animation: 160,
                        handle: '[data-build-step-drag-handle]',
                        ghostClass: 'sections-sortable-ghost',
                        chosenClass: 'sections-sortable-chosen',
                        dragClass: 'sections-sortable-drag',
                        onEnd: reindexItems,
                    });

                    list.dataset.buildStepSortableBound = '1';
                }

                reindexItems();
                repeater.dataset.buildStepRepeaterBound = '1';
            });
        };

        window.initReviewRepeaters = function (scope) {
            const root = scope instanceof Element || scope instanceof Document ? scope : document;
            const repeaters = root.matches?.('[data-review-repeater]')
                ? [root]
                : Array.from(root.querySelectorAll('[data-review-repeater]'));

            const createUniqueId = () => `review_media_${Math.random().toString(36).slice(2, 10)}`;

            repeaters.forEach((repeater) => {
                if (repeater.dataset.reviewRepeaterBound === '1') {
                    return;
                }

                const list = repeater.querySelector('[data-review-items]');
                const template = repeater.querySelector('template[data-review-item-template]');
                const emptyState = repeater.querySelector('[data-review-empty]');
                const addButtons = Array.from(repeater.querySelectorAll('[data-add-review-item]'));

                if (!list || !template) {
                    repeater.dataset.reviewRepeaterBound = '1';
                    return;
                }

                const renderAvatarPreview = (item, imageUrl = '') => {
                    const preview = item.querySelector('[data-review-avatar-preview]');
                    if (!preview) {
                        return;
                    }

                    preview.innerHTML = '';

                    if (imageUrl) {
                        const image = document.createElement('img');
                        image.src = imageUrl;
                        image.alt = '';
                        image.className = 'h-full w-full object-cover';
                        preview.appendChild(image);
                        return;
                    }

                    const icon = document.createElement('i');
                    icon.className = 'ti ti-user text-3xl leading-none';
                    icon.setAttribute('aria-hidden', 'true');
                    preview.appendChild(icon);
                };

                const ensureAvatarPickerTargets = (item) => {
                    const avatarInput = item.querySelector('[data-review-avatar-input]');
                    const avatarButton = item.querySelector('[data-review-avatar-button]');
                    const avatarPreview = item.querySelector('[data-review-avatar-preview]');

                    if (!avatarInput || !avatarButton || !avatarPreview) {
                        return;
                    }

                    const baseId = createUniqueId();
                    avatarInput.id = `${baseId}_input`;
                    avatarPreview.id = `${baseId}_preview`;
                    avatarButton.dataset.targetInput = avatarInput.id;
                    avatarButton.dataset.targetPreview = avatarPreview.id;
                    avatarButton.dataset.multiple = 'false';
                    avatarButton.dataset.storeValue = 'id';
                };

                const reindexItems = () => {
                    const items = Array.from(list.querySelectorAll('[data-review-item]'));

                    items.forEach((item, index) => {
                        item.querySelectorAll('[data-name-template]').forEach((field) => {
                            const templateName = field.dataset.nameTemplate || '';
                            if (templateName) {
                                field.name = templateName.replace(/__INDEX__/g, String(index));
                            }
                        });
                    });

                    if (emptyState) {
                        emptyState.classList.toggle('hidden', items.length > 0);
                    }
                };

                const bindReviewItem = (item) => {
                    if (!(item instanceof HTMLElement) || item.dataset.reviewItemBound === '1') {
                        return;
                    }

                    ensureAvatarPickerTargets(item);

                    const nameInput = item.querySelector('[data-review-field="name"]');
                    const textInput = item.querySelector('[data-review-field="text"]');
                    const ratingInput = item.querySelector('[data-review-field="rating"]');
                    const avatarInput = item.querySelector('[data-review-avatar-input]');
                    const avatarPreviewImage = item.querySelector('[data-review-avatar-preview] img');
                    const removeButton = item.querySelector('[data-remove-review-item]');
                    const duplicateButton = item.querySelector('[data-duplicate-review-item]');

                    if (!avatarPreviewImage) {
                        renderAvatarPreview(item);
                    }

                    removeButton?.addEventListener('click', function () {
                        item.remove();
                        reindexItems();
                    });

                    duplicateButton?.addEventListener('click', function () {
                        createReviewItem({
                            name: nameInput?.value || '',
                            text: textInput?.value || '',
                            rating: ratingInput?.value || '5',
                            avatar: avatarInput?.value || '',
                            previewUrl: item.querySelector('[data-review-avatar-preview] img')?.getAttribute('src') || '',
                        });
                    });

                    item.dataset.reviewItemBound = '1';
                };

                const createReviewItem = (seed = {}) => {
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = template.innerHTML.trim();

                    const item = wrapper.firstElementChild;
                    if (!(item instanceof HTMLElement)) {
                        return null;
                    }

                    list.appendChild(item);
                    bindReviewItem(item);

                    const nameInput = item.querySelector('[data-review-field="name"]');
                    const textInput = item.querySelector('[data-review-field="text"]');
                    const ratingInput = item.querySelector('[data-review-field="rating"]');
                    const avatarInput = item.querySelector('[data-review-avatar-input]');

                    if (nameInput && typeof seed.name === 'string') {
                        nameInput.value = seed.name;
                    }

                    if (textInput && typeof seed.text === 'string') {
                        textInput.value = seed.text;
                    }

                    if (ratingInput && typeof seed.rating !== 'undefined') {
                        ratingInput.value = String(seed.rating || '5');
                    }

                    if (avatarInput && typeof seed.avatar === 'string') {
                        avatarInput.value = seed.avatar;
                    }

                    renderAvatarPreview(item, typeof seed.previewUrl === 'string' ? seed.previewUrl : '');
                    reindexItems();

                    return item;
                };

                addButtons.forEach((button) => {
                    button.addEventListener('click', function () {
                        const item = createReviewItem();
                        const nameInput = item?.querySelector('[data-review-field="name"]');

                        if (nameInput instanceof HTMLElement) {
                            window.setTimeout(() => nameInput.focus(), 30);
                        }
                    });
                });

                Array.from(list.querySelectorAll('[data-review-item]')).forEach(bindReviewItem);

                if (typeof Sortable !== 'undefined' && list.dataset.reviewSortableBound !== '1') {
                    Sortable.create(list, {
                        animation: 160,
                        handle: '[data-review-drag-handle]',
                        ghostClass: 'sections-sortable-ghost',
                        chosenClass: 'sections-sortable-chosen',
                        dragClass: 'sections-sortable-drag',
                        onEnd: reindexItems,
                    });

                    list.dataset.reviewSortableBound = '1';
                }

                reindexItems();
                repeater.dataset.reviewRepeaterBound = '1';
            });
        };
    </script>
</body>
</html>
