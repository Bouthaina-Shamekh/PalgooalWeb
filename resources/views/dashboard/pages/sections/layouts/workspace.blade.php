{{-- Canonical controller-driven admin sections workspace. Deprecated admin Livewire section editors are retained only for fallback safety and are not routed. --}}
@php
    $translation = $page->translation();
    $pageTitle = $translation?->title ?? t('dashboard.Sections_Workspace', 'Sections Workspace');
    $frontUrl =
        $workspaceFrontUrl ?? ($page->is_home ? url('/') : ($translation?->slug ? url($translation->slug) : url('/')));
    $workspaceShellBackUrl = $workspaceShellBackUrl ?? route('dashboard.pages.index');
    $workspaceShellBackLabel = $workspaceShellBackLabel ?? t('dashboard.Back_To_Pages', 'Back to pages');
    $workspaceVisualBuilderUrl = $workspaceVisualBuilderUrl ?? null;
    $workspaceMode = $workspaceMode ?? 'admin';
    $isClientWorkspace = $workspaceMode === 'client';
    $workspaceModeLabel =
        $workspaceModeLabel ?? ($workspaceMode === 'client' ? t('dashboard.Client_Homepage_Editor', 'Client homepage editor') : t('dashboard.Admin_Workspace', 'Admin workspace'));
    $workspaceModeDisplayLabel = $isClientWorkspace ? t('dashboard.Site_Editor', 'Site Editor') : $workspaceModeLabel;
    $workspaceTitleSuffix = $isClientWorkspace ? t('dashboard.Page_Editor', 'Page Editor') : t('dashboard.Sections_Workspace', 'Sections Workspace');
    $workspacePreviewLabel = $isClientWorkspace ? t('dashboard.View_Page', 'View Page') : t('dashboard.Preview', 'Preview');
    $workspaceShowSidebarLabel = $isClientWorkspace ? t('dashboard.Show_Blocks', 'Show Blocks') : t('dashboard.Show_Sidebar', 'Show Sidebar');
    $workspaceHideSidebarLabel = $isClientWorkspace ? t('dashboard.Hide_Blocks', 'Hide Blocks') : t('dashboard.Hide_Sidebar', 'Hide Sidebar');
    $workspaceLanguages = collect($languages ?? [])
        ->filter(fn($language) => filled($language->code))
        ->values();
    $hasMultipleWorkspaceLanguages = $workspaceLanguages->count() > 1;
    $workspacePageSwitcher = $workspacePageSwitcher ?? [];
    $workspacePageOptions = collect(data_get($workspacePageSwitcher, 'pages', []))
        ->filter(fn($workspacePageOption) => filled(data_get($workspacePageOption, 'url')))
        ->values();
    $hasWorkspacePageSwitcher = $isClientWorkspace && $workspacePageOptions->isNotEmpty();
    $workspacePageSwitcherLabel = data_get($workspacePageSwitcher, 'label', t('dashboard.Page', 'Page'));
    $adminLogoPath = $settings?->admin_logo ?: $settings?->logo;
    $adminLogoHref = !empty($adminLogoPath)
        ? (\Illuminate\Support\Str::startsWith($adminLogoPath, ['http://', 'https://', '//'])
            ? $adminLogoPath
            : asset('storage/' . ltrim(preg_replace('#^storage/#', '', $adminLogoPath), '/')))
        : asset('assets/tamplate/images/logo.svg');
    $sectionsIconLibrary = collect(config('sections.icon_library', []))
        ->map(function (array $icon) {
            return [
                'label' => t($icon['label'] ?? '', $icon['label'] ?? ''),
                'value' => $icon['value'] ?? '',
                'keywords' => $icon['keywords'] ?? '',
            ];
        })
        ->values()
        ->all();
@endphp

<!doctype html>
<html lang="{{ app()->getLocale() }}" class="h-full" dir="{{ current_dir() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (session('brand_settings_success'))
        <meta name="brand-settings-success" content="1">
    @endif

    <title>{{ $pageTitle }} - {{ $workspaceTitleSuffix }}</title>
    <link rel="icon" href="{{ $adminLogoHref }}">

    <link rel="stylesheet" href="{{ mix('assets/tamplate/css/app.css') }}" id="palgoals-app-css">
    <link rel="stylesheet" href="{{ asset('assets/dashboard/fonts/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/dashboard/css/sections-workspace.css') }}">

    {{-- ── Collapsible Field Groups — Phase 3 ─────────────────────────────── --}}
    <style>
        /* Remove native disclosure triangle in all browsers */
        details[data-group-key] > summary { list-style: none; }
        details[data-group-key] > summary::-webkit-details-marker { display: none; }
        /* Rotate chevron SVG when group is open */
        details[data-group-key][open] .group-chevron { transform: rotate(180deg); }
    </style>

    @stack('styles')
</head>

<body class="h-full bg-slate-100 text-slate-900">
    <div id="sections-workspace-shell" class="sections-workspace-shell flex h-screen flex-col overflow-hidden">
        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur shadow-sm">
            @if ($isClientWorkspace)
                <div style="height:3px;background:#2563eb;"></div>
            @else
                <div style="height:3px;background:#1e293b;"></div>
            @endif
            <div class="px-4 py-2 lg:px-6">
                <div class="flex flex-wrap items-center justify-between gap-2 xl:flex-nowrap">
                    <div class="flex min-w-0 items-center gap-3 rtl:flex-row-reverse">
                        <a href="{{ $workspaceShellBackUrl }}"
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50 hover:shadow-md"
                            aria-label="{{ $workspaceShellBackLabel }}" title="{{ $workspaceShellBackLabel }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rtl:rotate-180" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10.5 19.5L3 12l7.5-7.5M3 12h18" />
                            </svg>
                        </a>

                        <div class="flex min-w-0 flex-wrap items-center gap-2 rtl:flex-row-reverse">
                            <h1 class="sr-only">{{ $pageTitle }}</h1>
                            @if ($hasWorkspacePageSwitcher)
                                <div
                                    class="inline-flex max-w-full items-center gap-2 rounded-full border border-slate-200 bg-slate-100/90 px-2 py-2 shadow-sm rtl:flex-row-reverse">
                                    <span
                                        class="shrink-0 px-2 text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-500">
                                        {{ $workspacePageSwitcherLabel }}
                                    </span>
                                    <div class="relative min-w-[11rem] max-w-[15rem] sm:max-w-[19rem]">
                                        <select onchange="if (this.value) { window.location.href = this.value; }"
                                            class="h-10 w-full appearance-none rounded-full border border-slate-200 bg-white text-sm font-semibold text-slate-900 shadow-sm transition outline-none hover:border-slate-300 focus:border-sky-300 focus:ring-2 focus:ring-sky-100 rtl:pl-10 rtl:pr-4 ltr:pl-4 ltr:pr-10"
                                            aria-label="{{ t('dashboard.Switch_Page', 'Switch page') }}">
                                            @foreach ($workspacePageOptions as $workspacePageOption)
                                                <option value="{{ $workspacePageOption['url'] }}"
                                                    @selected(!empty($workspacePageOption['active']))>
                                                    {{ $workspacePageOption['label'] }}@if (!empty($workspacePageOption['is_home']))
                                                        • {{ t('common.Home', 'Home') }}
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="pointer-events-none absolute top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 rtl:left-3 ltr:right-3"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </div>
                                    @if (filled($workspaceModeDisplayLabel))
                                        @if ($isClientWorkspace)
                                            <span class="rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-blue-700">
                                                {{ $workspaceModeDisplayLabel }}
                                            </span>
                                        @else
                                            <span class="rounded-full border border-slate-300 bg-slate-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-600">
                                                {{ $workspaceModeDisplayLabel }}
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            @else
                                <div
                                    class="inline-flex max-w-full items-center gap-2 rounded-full border border-slate-200 bg-slate-100/90 px-4 py-2 shadow-sm rtl:flex-row-reverse">
                                    <h1 class="truncate text-sm font-semibold text-slate-900 lg:text-[15px]">
                                        {{ $pageTitle }}
                                    </h1>
                                    @if ($page->is_home)
                                        <span
                                            class="rounded-full bg-white px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-500">
                                            {{ t('common.Home', 'Home') }}
                                        </span>
                                    @endif
                                    @if (filled($workspaceModeDisplayLabel))
                                        @if ($isClientWorkspace)
                                            <span class="rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-blue-700">
                                                {{ $workspaceModeDisplayLabel }}
                                            </span>
                                        @else
                                            <span class="rounded-full border border-slate-300 bg-slate-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-600">
                                                {{ $workspaceModeDisplayLabel }}
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            @endif

                            @if ($hasMultipleWorkspaceLanguages)
                                <x-lang.language-switcher variant="builder"
                                    buttonClass="inline-flex h-10 items-center gap-2 rounded-full border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                                    menuClass="absolute mt-2 min-w-[11rem] rounded-2xl border border-slate-200 bg-white p-2 shadow-xl z-40 rtl:right-0 rtl:left-auto ltr:left-0 ltr:right-auto"
                                    itemClass="block w-full rounded-xl px-3 py-2 text-sm transition hover:bg-slate-50 ltr:text-left rtl:text-right"
                                    activeItemClass="bg-slate-100 font-semibold text-slate-900" />
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 rtl:flex-row-reverse xl:flex-nowrap xl:justify-end">
                        @hasSection('workspace-header-toolbar')
                            @yield('workspace-header-toolbar')
                        @endif

                        <div
                            class="sections-header-cluster flex flex-wrap items-center gap-2 rounded-[1.75rem] border border-slate-200 bg-slate-100/90 p-1 shadow-inner rtl:flex-row-reverse">
                            <a href="{{ $frontUrl }}" target="_blank"
                                class="{{ $isClientWorkspace ? 'inline-flex h-11 w-11 items-center justify-center rounded-full text-slate-700 transition hover:bg-white hover:shadow-sm' : 'inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-white hover:shadow-sm' }}"
                                aria-label="{{ $workspacePreviewLabel }}" title="{{ $workspacePreviewLabel }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 12S5.25 5.25 12 5.25 21.75 12 21.75 12 18.75 18.75 12 18.75 2.25 12 2.25 12Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z" />
                                </svg>
                                @unless ($isClientWorkspace)
                                    {{ $workspacePreviewLabel }}
                                @endunless
                            </a>

                            @if (filled($workspaceVisualBuilderUrl))
                                <a href="{{ $workspaceVisualBuilderUrl }}"
                                    class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-white hover:shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3.75 5.25h16.5v10.5H3.75V5.25Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 18.75h6M12 15.75v3" />
                                    </svg>
                                    {{ t('dashboard.Visual_Builder', 'Visual Builder') }}
                                </a>
                            @endif
                        </div>

                        @hasSection('workspace-header-actions')
                            <div
                                class="sections-header-cluster flex flex-wrap items-center gap-2 rounded-[1.75rem] border border-slate-200 bg-white p-1 shadow-sm rtl:flex-row-reverse">
                                @yield('workspace-header-actions')
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-hidden">
            <div class="sections-workspace-panels">
                <button type="button" id="sections-sidebar-open-btn" class="sections-sidebar-open-button"
                    aria-controls="sections-workspace-sidebar" aria-expanded="false"
                    aria-label="{{ $workspaceShowSidebarLabel }}" title="{{ $workspaceShowSidebarLabel }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6" />
                    </svg>
                </button>

                <section class="sections-workspace-main workspace-scrollbar overflow-y-auto px-4 py-5 lg:px-6">
                    @yield('workspace-main')
                </section>

                <div class="sections-workspace-sidebar-shell">
                    <button type="button" id="sections-sidebar-hide-btn" class="sections-sidebar-handle"
                        aria-controls="sections-workspace-sidebar" aria-expanded="true"
                        aria-label="{{ $workspaceHideSidebarLabel }}" title="{{ $workspaceHideSidebarLabel }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6" />
                        </svg>
                    </button>

                    <aside id="sections-workspace-sidebar"
                        class="sections-workspace-sidebar workspace-scrollbar border-t border-slate-200 bg-white/90 overflow-y-auto px-4 py-5 lg:px-6">
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
        window.sectionsShowAlert = function(options = {}) {
            const tone = options.tone === 'success' ? 'success' : 'error';
            const title = String(options.title || (tone === 'success' ? @json(t('common.Success', 'Success')) :
                @json(t('common.Something_Went_Wrong', 'Something went wrong'))));
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

            const html = messages.length ?
                `<div class="text-start"><ul style="margin:0;padding-inline-start:1.25rem;">${messages.map((message) => `<li>${String(message)
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')}</li>`).join('')}</ul></div>` :
                '';

            Swal.fire({
                icon: 'error',
                title,
                text: html ? undefined : (text || ''),
                html: html || undefined,
                confirmButtonText: @json(t('common.Ok', 'OK')),
                customClass: {
                    popup: 'rounded-[1.5rem]',
                    confirmButton: 'inline-flex items-center rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white',
                },
                buttonsStyling: false,
            });
        };

        document.addEventListener('DOMContentLoaded', function() {
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
                    title: @json(t('common.Error', 'Error')),
                    messages: [errorMessage],
                });
            }

            if (Array.isArray(validationErrors) && validationErrors.length > 0) {
                window.sectionsShowAlert({
                    tone: 'error',
                    title: @json(t('common.Please_Review_The_Form', 'Please review the form')),
                    messages: validationErrors,
                });
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script
        src="{{ asset('assets/dashboard/js/media-picker.js') }}?v={{ filemtime(public_path('assets/dashboard/js/media-picker.js')) }}"
        defer></script>
    @include('dashboard.partials.media-picker')
    @include('dashboard.pages.sections.partials.icon-library-modal')

    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const shell = document.getElementById('sections-workspace-shell');
            const hideButton = document.getElementById('sections-sidebar-hide-btn');
            const openButton = document.getElementById('sections-sidebar-open-btn');
            const storageKey = 'sections-workspace-sidebar-collapsed';
            const showSidebarLabel = @json($workspaceShowSidebarLabel);
            const hideSidebarLabel = @json($workspaceHideSidebarLabel);

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

            hideButton?.addEventListener('click', function() {
                applySidebarState(true);
                persistSidebarState(true);
            });

            openButton?.addEventListener('click', function() {
                applySidebarState(false);
                persistSidebarState(false);
            });

            window.addEventListener('media-picker-confirmed', function(event) {
                const targetInputId = event.detail?.targetInputId;
                if (!targetInputId) {
                    return;
                }

                const targetInput = document.getElementById(targetInputId);
                const currentGroup = targetInput?.closest?.('[data-shared-media-group]');
                const form = targetInput?.closest?.('[data-section-editor-form]');

                if (targetInput) {
                    targetInput.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                    targetInput.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
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
                    input.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                    input.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));

                    if (!preview) {
                        return;
                    }

                    preview.innerHTML = '';
                    items.forEach((item) => {
                        const wrapper = document.createElement('div');
                        wrapper.className =
                            'relative h-20 w-20 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900';

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
            window.initFieldTabs?.(document);
            window.initGroupAccordion?.(document);
            window.initSectionIconLibrary?.();
            window.initSectionFeatureRepeaters?.(document);
            window.initSectionOutputRepeaters?.(document);
            window.initSectionServiceRepeaters?.(document);
            window.initBuildStepRepeaters?.(document);
            window.initReviewRepeaters?.(document);
            window.initFooterLinkRepeaters?.(document);
            window.initDynamicRepeaters?.(document);
        });

        window.initSectionIconLibrary = function() {
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
                activeInput.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
                activeInput.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            };

            const renderLibrary = (query = '') => {
                const normalizedQuery = String(query || '').trim().toLowerCase();
                const filteredIcons = iconLibrary.filter((icon) => {
                    const haystack = `${icon.label} ${icon.value} ${icon.keywords || ''}`.toLowerCase();
                    return normalizedQuery === '' || haystack.includes(normalizedQuery);
                });

                grid.innerHTML = '';
                countLabel.textContent =
                    `${filteredIcons.length} ${filteredIcons.length === 1 ? @json(t('common.Icon', 'icon')) : @json(t('common.Icons', 'icons'))}`;
                emptyState.classList.toggle('hidden', filteredIcons.length > 0);
                grid.classList.toggle('hidden', filteredIcons.length === 0);
                clearButton.classList.toggle('hidden', activeValue === '');

                filteredIcons.forEach((icon) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.dataset.sectionIconOption = 'true';
                    button.dataset.sectionIconValue = icon.value;
                    button.className =
                        'sections-icon-library-tile flex flex-col items-start gap-2 rounded-2xl border border-slate-200 bg-white p-3 text-left transition hover:border-slate-300 hover:bg-slate-50 rtl:text-right';

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
                    trigger.closest('[data-output-item]') ||
                    trigger.closest('[data-service-item]') ||
                    trigger.closest('[data-build-step-item]') ||
                    trigger.closest('[data-dynamic-repeater-item]') ||
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

            searchInput.addEventListener('input', function() {
                renderLibrary(searchInput.value);
            });

            clearButton.addEventListener('click', function() {
                applyIconValue('');
                closeLibrary();
            });

            overlay.addEventListener('click', closeLibrary);

            modal.querySelectorAll('[data-close-section-icon-library]').forEach((button) => {
                button.addEventListener('click', closeLibrary);
            });

            document.addEventListener('click', function(event) {
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

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeLibrary();
                }
            });

            window.__sectionsIconLibraryBound = true;
        };

        window.initSectionEditorTabs = function(scope) {
            const root = scope instanceof Element || scope instanceof Document ? scope : document;
            const editorRoots = root.matches?.('[data-section-editor-form]') ? [root] :
                Array.from(root.querySelectorAll('[data-section-editor-form]'));

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
                    button.addEventListener('click', function() {
                        activateTab(button.dataset.tab || '');
                    });
                });

                const defaultTab = form.dataset.defaultEditorTab || buttons[0].dataset.tab || '';
                const hasDefaultButton = buttons.some((button) => button.dataset.tab === defaultTab);

                activateTab(hasDefaultButton ? defaultTab : (buttons[0].dataset.tab || ''));
                form.dataset.editorTabsBound = '1';
            });
        };

        // ── Content / Design Field Tab Switcher ─────────────────────────────
        // Registered as a global initializer so it works when the editor is
        // loaded via AJAX (innerHTML does not execute <script> tags).
        // Called by bindSectionEditor() in index.blade.php after fetch.
        window.initFieldTabs = function(scope) {
            const root = scope instanceof Element || scope instanceof Document ? scope : document;

            // Resolve section ID for localStorage key
            const form = root.matches?.('[data-section-editor-form]')
                ? root
                : root.querySelector('[data-section-editor-form]');
            const sectionId = form?.dataset?.sectionId || '0';
            const STORAGE_KEY = 'section-editor-tab-' + sectionId;

            // Find all unique TAB_IDs (one per locale, e.g. "field-tab-ar", "field-tab-en")
            const allBtns = Array.from(root.querySelectorAll('[data-field-tab-btn]'));
            const tabIds  = [...new Set(allBtns.map(function(b) { return b.dataset.fieldTabBtn; }).filter(Boolean))];

            if (tabIds.length === 0) {
                return;
            }

            const ACTIVE_BTN   = ['bg-white', 'shadow-sm', 'text-slate-900', 'font-semibold'];
            const INACTIVE_BTN = ['text-slate-500', 'font-medium'];
            const ACTIVE_BADGE   = ['bg-indigo-100', 'text-indigo-700'];
            const INACTIVE_BADGE = ['bg-slate-200', 'text-slate-500'];

            tabIds.forEach(function(tabId) {
                const btns   = Array.from(root.querySelectorAll('[data-field-tab-btn="'   + tabId + '"]'));
                const panels = Array.from(root.querySelectorAll('[data-field-tab-panel="' + tabId + '"]'));

                if (btns.length === 0) {
                    return;
                }

                function activateTab(target) {
                    btns.forEach(function(btn) {
                        const isActive = btn.dataset.fieldTab === target;
                        btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
                        if (isActive) {
                            ACTIVE_BTN.forEach(function(c)   { btn.classList.add(c); });
                            INACTIVE_BTN.forEach(function(c) { btn.classList.remove(c); });
                        } else {
                            INACTIVE_BTN.forEach(function(c) { btn.classList.add(c); });
                            ACTIVE_BTN.forEach(function(c)   { btn.classList.remove(c); });
                        }
                        // Phase B: badge colour
                        const badge = btn.querySelector('[data-field-tab-count]');
                        if (badge) {
                            if (isActive) {
                                ACTIVE_BADGE.forEach(function(c)   { badge.classList.add(c); });
                                INACTIVE_BADGE.forEach(function(c) { badge.classList.remove(c); });
                            } else {
                                INACTIVE_BADGE.forEach(function(c) { badge.classList.add(c); });
                                ACTIVE_BADGE.forEach(function(c)   { badge.classList.remove(c); });
                            }
                        }
                    });
                    // Toggle panel visibility (both stay in DOM — inputs still submit)
                    panels.forEach(function(panel) {
                        panel.classList.toggle('hidden', panel.dataset.fieldTab !== target);
                    });
                }

                // Phase A: restore last-used tab from localStorage
                var saved = null;
                try { saved = localStorage.getItem(STORAGE_KEY); } catch (e) {}
                if (saved === 'design' || saved === 'content') {
                    activateTab(saved);
                }

                // Attach click handlers
                btns.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var target = btn.dataset.fieldTab;
                        activateTab(target);
                        try { localStorage.setItem(STORAGE_KEY, target); } catch (e) {}
                    });
                });
            });
        };

        // ── Collapsible Field Groups Accordion ──────────────────────────────
        // Groups rendered as <details data-group-key="…"> in renderer.blade.php.
        // State stored per-section in localStorage so collapse preference
        // survives Save → Refresh cycles.
        //
        // localStorage key format:  section-group-state-{sectionId}
        // Value format:             { "intro": true, "cta": false, ... }
        //
        // Default (no saved state): first group open, all others closed.
        // Registered as a global so it works after AJAX innerHTML injection.
        // Called by bindSectionEditor() in index.blade.php after fetch.
        window.initGroupAccordion = function(scope) {
            const root = scope instanceof Element || scope instanceof Document ? scope : document;

            // Resolve section ID for the localStorage key
            const form = root.matches?.('[data-section-editor-form]')
                ? root
                : root.querySelector('[data-section-editor-form]');
            const sectionId = form?.dataset?.sectionId || '0';
            const STORAGE_KEY = 'section-group-state-' + sectionId;

            const allDetails = Array.from(root.querySelectorAll('details[data-group-key]'));
            if (allDetails.length === 0) { return; }

            // Load saved state
            var savedState = {};
            var hasSavedState = false;
            try {
                var raw = localStorage.getItem(STORAGE_KEY);
                if (raw) {
                    savedState = JSON.parse(raw);
                    hasSavedState = Object.keys(savedState).length > 0;
                }
            } catch (e) {}

            // Apply initial open/closed states
            allDetails.forEach(function(detail, index) {
                var key = detail.dataset.groupKey;
                // Saved state: use exact value.  New groups not yet in state: close them.
                // No saved state at all: open only the first group.
                detail.open = hasSavedState ? (savedState[key] === true) : (index === 0);
            });

            // Persist every toggle immediately
            allDetails.forEach(function(detail) {
                detail.addEventListener('toggle', function() {
                    savedState[detail.dataset.groupKey] = detail.open;
                    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(savedState)); } catch (e) {}
                });
            });
        };

        window.initSectionFeatureRepeaters = function(scope) {
            const root = scope instanceof Element || scope instanceof Document ? scope : document;
            const repeaters = root.matches?.('[data-feature-repeater]') ? [root] :
                Array.from(root.querySelectorAll('[data-feature-repeater]'));

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
                    svg = svg.replace(
                        /\s(?:href|xlink:href)\s*=\s*(?:"\s*javascript:[^"]*"|'\s*javascript:[^']*'|javascript:[^\s>]+)/gi,
                        '');

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
                        wrapper.className =
                            'relative h-14 w-14 overflow-hidden rounded-xl border border-slate-200 bg-slate-50';

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

                    if (!(mediaInput instanceof HTMLInputElement) || !(
                            mediaButton instanceof HTMLElement) || !(mediaPreview instanceof HTMLElement)) {
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
                        panel.classList.toggle('hidden', panel.dataset.featureIconPanel !==
                            activeSource);
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
                        summary.textContent = source === 'svg' ?
                            (svgValue ? @json(t('dashboard.Custom_Svg_Icon', 'Custom SVG icon')) : featureItemHint) :
                            source === 'media' ?
                            (mediaValue ? @json(t('dashboard.Svg_From_Media_Library', 'SVG from media library')) : featureItemHint) :
                            (iconValue ? @json(t('dashboard.Tabler_Icon_Selected', 'Tabler icon selected')) : featureItemHint);
                    }
                };

                const reindexItems = () => {
                    const items = Array.from(list.querySelectorAll('[data-feature-item]'));

                    items.forEach((item, index) => {
                        item.querySelectorAll('[data-name-template]').forEach((field) => {
                            const templateName = field.dataset.nameTemplate || '';
                            if (templateName) {
                                field.name = templateName.replace(/__INDEX__/g, String(
                                    index));
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

                    iconInput?.addEventListener('input', function() {
                        renderIconPreview(item);
                        refreshFeatureItemMeta(item);
                    });

                    iconSourceInput?.addEventListener('change', function() {
                        toggleFeatureIconPanels(item, getFeatureIconSource(item));
                        renderIconPreview(item);
                        refreshFeatureItemMeta(item);
                    });

                    iconSvgInput?.addEventListener('input', function() {
                        renderIconPreview(item);
                        refreshFeatureItemMeta(item);
                    });

                    iconMediaInput?.addEventListener('input', function() {
                        renderIconPreview(item);
                        refreshFeatureItemMeta(item);
                    });

                    iconMediaInput?.addEventListener('change', function() {
                        renderIconPreview(item);
                        refreshFeatureItemMeta(item);
                    });

                    textInput?.addEventListener('input', function() {
                        refreshFeatureItemMeta(item);
                    });

                    removeButton?.addEventListener('click', function() {
                        item.remove();
                        reindexItems();
                    });

                    duplicateButton?.addEventListener('click', function() {
                        const createdItem = createFeatureItem({
                            text: textInput?.value || '',
                            icon: iconInput?.value || '',
                            iconSource: getFeatureIconSource(item),
                            iconSvg: iconSvgInput?.value || '',
                            iconMedia: iconMediaInput?.value || '',
                            mediaPreviewUrl: mediaPreview?.querySelector('img')
                                ?.getAttribute('src') || '',
                        });

                        setFeatureExpanded(createdItem, true, true);
                    });

                    toggleButton?.addEventListener('click', function() {
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
                        iconSourceInput.value = ['class', 'svg', 'media'].includes(seed.iconSource) ? seed
                            .iconSource : 'class';
                    }

                    if (iconSvgInput && typeof seed.iconSvg === 'string') {
                        iconSvgInput.value = seed.iconSvg;
                    }

                    if (iconMediaInput && typeof seed.iconMedia === 'string') {
                        iconMediaInput.value = seed.iconMedia;
                    }

                    renderMediaPreview(mediaPreview, typeof seed.mediaPreviewUrl === 'string' && seed
                        .mediaPreviewUrl ? [seed.mediaPreviewUrl] : []);
                    toggleFeatureIconPanels(item, getFeatureIconSource(item));
                    renderIconPreview(item);
                    reindexItems();
                    setFeatureExpanded(item, true, true);

                    return item;
                };

                addButtons.forEach((button) => {
                    button.addEventListener('click', function() {
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

        window.initSectionOutputRepeaters = function(scope) {
            const root = scope instanceof Element || scope instanceof Document ? scope : document;
            const repeaters = root.matches?.('[data-output-repeater]') ? [root] :
                Array.from(root.querySelectorAll('[data-output-repeater]'));

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
                        wrapper.className =
                            'relative h-14 w-14 overflow-hidden rounded-xl border border-slate-200 bg-slate-50';

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

                    if (!(mediaInput instanceof HTMLInputElement) || !(
                            mediaButton instanceof HTMLElement) || !(mediaPreview instanceof HTMLElement)) {
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
                        panel.classList.toggle('hidden', panel.dataset.outputIconPanel !==
                            activeSource);
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
                        summary.textContent = source === 'media' ?
                            (mediaValue ? @json(t('dashboard.Svg_From_Media_Library', 'SVG from media library')) : outputItemHint) :
                            (iconValue ? @json(t('dashboard.Tabler_Icon_Selected', 'Tabler icon selected')) : (textValue ?
                                @json(t('dashboard.Visible_In_The_Outputs_List', 'Visible in the outputs list')) : outputItemHint));
                    }
                };

                const reindexItems = () => {
                    const items = Array.from(list.querySelectorAll('[data-output-item]'));

                    items.forEach((item, index) => {
                        item.querySelectorAll('[data-name-template]').forEach((field) => {
                            const templateName = field.dataset.nameTemplate || '';
                            if (templateName) {
                                field.name = templateName.replace(/__INDEX__/g, String(
                                    index));
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

                    textInput?.addEventListener('input', function() {
                        refreshOutputItemMeta(item);
                    });

                    iconInput?.addEventListener('input', function() {
                        renderOutputIconPreview(item);
                        refreshOutputItemMeta(item);
                    });

                    iconSourceInput?.addEventListener('change', function() {
                        toggleOutputIconPanels(item, getOutputIconSource(item));
                        renderOutputIconPreview(item);
                        refreshOutputItemMeta(item);
                    });

                    iconMediaInput?.addEventListener('input', function() {
                        renderOutputIconPreview(item);
                        refreshOutputItemMeta(item);
                    });

                    iconMediaInput?.addEventListener('change', function() {
                        renderOutputIconPreview(item);
                        refreshOutputItemMeta(item);
                    });

                    removeButton?.addEventListener('click', function() {
                        item.remove();
                        reindexItems();
                    });

                    duplicateButton?.addEventListener('click', function() {
                        const createdItem = createOutputItem({
                            text: textInput?.value || '',
                            icon: iconInput?.value || '',
                            iconSource: getOutputIconSource(item),
                            iconMedia: iconMediaInput?.value || '',
                            mediaPreviewUrl: mediaPreview?.querySelector('img')
                                ?.getAttribute('src') || '',
                        });

                        setOutputExpanded(createdItem, true, true);
                    });

                    toggleButton?.addEventListener('click', function() {
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
                        iconSourceInput.value = ['class', 'media'].includes(seed.iconSource) ? seed
                            .iconSource : 'class';
                    }

                    if (iconMediaInput && typeof seed.iconMedia === 'string') {
                        iconMediaInput.value = seed.iconMedia;
                    }

                    renderMediaPreview(mediaPreview, typeof seed.mediaPreviewUrl === 'string' && seed
                        .mediaPreviewUrl ? [seed.mediaPreviewUrl] : []);
                    toggleOutputIconPanels(item, getOutputIconSource(item));
                    renderOutputIconPreview(item);
                    reindexItems();
                    setOutputExpanded(item, true, true);

                    return item;
                };

                addButtons.forEach((button) => {
                    button.addEventListener('click', function() {
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

        window.initSectionServiceRepeaters = function(scope) {
            const root = scope instanceof Element || scope instanceof Document ? scope : document;
            const repeaters = root.matches?.('[data-service-repeater]') ? [root] :
                Array.from(root.querySelectorAll('[data-service-repeater]'));

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
                        wrapper.className =
                            'relative h-14 w-14 overflow-hidden rounded-xl border border-slate-200 bg-slate-50';

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

                    if (!(mediaInput instanceof HTMLInputElement) || !(
                            mediaButton instanceof HTMLElement) || !(mediaPreview instanceof HTMLElement)) {
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
                        panel.classList.toggle('hidden', panel.dataset.serviceIconPanel !==
                            activeSource);
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

                    preview.innerHTML =
                        '<svg width="10" height="13" viewBox="0 0 10 13" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M9.75 6.49512L0 12.9903V-7.34329e-05L9.75 6.49512Z" fill="#BA112C"></path></svg>';
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
                        summary.textContent = source === 'media' ?
                            (mediaValue ? @json(t('dashboard.Svg_From_Media_Library', 'SVG from media library')) : serviceItemHint) :
                            (iconValue ? @json(t('dashboard.Tabler_Icon_Selected', 'Tabler icon selected')) : (textValue ?
                                @json(t('dashboard.Uses_The_Default_Service_Marker', 'Uses the default service marker')) : serviceItemHint));
                    }
                };

                const reindexItems = () => {
                    const items = Array.from(list.querySelectorAll('[data-service-item]'));

                    items.forEach((item, index) => {
                        item.querySelectorAll('[data-name-template]').forEach((field) => {
                            const templateName = field.dataset.nameTemplate || '';
                            if (templateName) {
                                field.name = templateName.replace(/__INDEX__/g, String(
                                    index));
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

                    textInput?.addEventListener('input', function() {
                        refreshServiceItemMeta(item);
                    });

                    iconInput?.addEventListener('input', function() {
                        renderServiceIconPreview(item);
                        refreshServiceItemMeta(item);
                    });

                    iconSourceInput?.addEventListener('change', function() {
                        toggleServiceIconPanels(item, getServiceIconSource(item));
                        renderServiceIconPreview(item);
                        refreshServiceItemMeta(item);
                    });

                    iconMediaInput?.addEventListener('input', function() {
                        renderServiceIconPreview(item);
                        refreshServiceItemMeta(item);
                    });

                    iconMediaInput?.addEventListener('change', function() {
                        renderServiceIconPreview(item);
                        refreshServiceItemMeta(item);
                    });

                    removeButton?.addEventListener('click', function() {
                        item.remove();
                        reindexItems();
                    });

                    duplicateButton?.addEventListener('click', function() {
                        const createdItem = createServiceItem({
                            text: textInput?.value || '',
                            icon: iconInput?.value || '',
                            iconSource: getServiceIconSource(item),
                            iconMedia: iconMediaInput?.value || '',
                            mediaPreviewUrl: mediaPreview?.querySelector('img')
                                ?.getAttribute('src') || '',
                        });

                        setServiceExpanded(createdItem, true, true);
                    });

                    toggleButton?.addEventListener('click', function() {
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
                        iconSourceInput.value = ['class', 'media'].includes(seed.iconSource) ? seed
                            .iconSource : 'class';
                    }

                    if (iconMediaInput && typeof seed.iconMedia === 'string') {
                        iconMediaInput.value = seed.iconMedia;
                    }

                    renderMediaPreview(mediaPreview, typeof seed.mediaPreviewUrl === 'string' && seed
                        .mediaPreviewUrl ? [seed.mediaPreviewUrl] : []);
                    toggleServiceIconPanels(item, getServiceIconSource(item));
                    renderServiceIconPreview(item);
                    reindexItems();
                    setServiceExpanded(item, true, true);

                    return item;
                };

                addButtons.forEach((button) => {
                    button.addEventListener('click', function() {
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

        window.initBuildStepRepeaters = function(scope) {
            const root = scope instanceof Element || scope instanceof Document ? scope : document;
            const repeaters = root.matches?.('[data-build-step-repeater]') ? [root] :
                Array.from(root.querySelectorAll('[data-build-step-repeater]'));

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
                    svg = svg.replace(
                        /\s(?:href|xlink:href)\s*=\s*(?:"\s*javascript:[^"]*"|'\s*javascript:[^']*'|javascript:[^\s>]+)/gi,
                        '');

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
                        wrapper.className =
                            'relative h-14 w-14 overflow-hidden rounded-xl border border-slate-200 bg-slate-50';

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

                    if (!(mediaInput instanceof HTMLInputElement) || !(
                            mediaButton instanceof HTMLElement) || !(mediaPreview instanceof HTMLElement)) {
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
                        panel.classList.toggle('hidden', panel.dataset.buildStepIconPanel !==
                            activeSource);
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
                        let summaryText = source === 'svg' ?
                            (svgValue ? @json(t('dashboard.Custom_Svg_Icon', 'Custom SVG icon')) : buildStepItemHint) :
                            source === 'media' ?
                            (mediaValue ? @json(t('dashboard.Svg_From_Media_Library', 'SVG from media library')) : buildStepItemHint) :
                            (iconValue ? @json(t('dashboard.Tabler_Icon_Selected', 'Tabler icon selected')) : buildStepItemHint);

                        if (isAccent) {
                            summaryText = summaryText === buildStepItemHint ?
                                @json(t('dashboard.Highlighted_In_Red', 'Highlighted in red')) :
                                `${summaryText} • ${@json(t('dashboard.Highlighted_In_Red', 'Highlighted in red'))}`;
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
                                field.name = templateName.replace(/__INDEX__/g, String(
                                    index));
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

                    iconInput?.addEventListener('input', function() {
                        renderIconPreview(item);
                        refreshBuildStepMeta(item);
                    });

                    iconSourceInput?.addEventListener('change', function() {
                        toggleBuildStepIconPanels(item, getBuildStepIconSource(item));
                        renderIconPreview(item);
                        refreshBuildStepMeta(item);
                    });

                    iconSvgInput?.addEventListener('input', function() {
                        renderIconPreview(item);
                        refreshBuildStepMeta(item);
                    });

                    iconMediaInput?.addEventListener('input', function() {
                        renderIconPreview(item);
                        refreshBuildStepMeta(item);
                    });

                    iconMediaInput?.addEventListener('change', function() {
                        renderIconPreview(item);
                        refreshBuildStepMeta(item);
                    });

                    titleInput?.addEventListener('input', function() {
                        refreshBuildStepMeta(item);
                    });

                    item.querySelector('[data-build-step-field="accent"]')?.addEventListener('change',
                        function() {
                            refreshBuildStepMeta(item);
                        });

                    removeButton?.addEventListener('click', function() {
                        item.remove();
                        reindexItems();
                    });

                    duplicateButton?.addEventListener('click', function() {
                        createStepItem({
                            title: titleInput?.value || '',
                            icon: iconInput?.value || '',
                            iconSource: getBuildStepIconSource(item),
                            iconSvg: iconSvgInput?.value || '',
                            iconMedia: iconMediaInput?.value || '',
                            mediaPreviewUrl: mediaPreview?.querySelector('img')
                                ?.getAttribute('src') || '',
                            isAccent: item.querySelector('[data-build-step-field="accent"]')
                                ?.checked || false,
                        });

                        setBuildStepExpanded(createdItem, true, true);
                    });

                    toggleButton?.addEventListener('click', function() {
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
                        iconSourceInput.value = ['class', 'svg', 'media'].includes(seed.iconSource) ? seed
                            .iconSource : 'class';
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

                    renderMediaPreview(mediaPreview, typeof seed.mediaPreviewUrl === 'string' && seed
                        .mediaPreviewUrl ? [seed.mediaPreviewUrl] : []);
                    toggleBuildStepIconPanels(item, getBuildStepIconSource(item));
                    renderIconPreview(item);
                    reindexItems();
                    setBuildStepExpanded(item, true, true);

                    return item;
                };

                addButtons.forEach((button) => {
                    button.addEventListener('click', function() {
                        const item = createStepItem();
                        const titleInput = item?.querySelector(
                            '[data-build-step-field="title"]');

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

        window.initReviewRepeaters = function(scope) {
            const root = scope instanceof Element || scope instanceof Document ? scope : document;
            const repeaters = root.matches?.('[data-review-repeater]') ? [root] :
                Array.from(root.querySelectorAll('[data-review-repeater]'));

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
                                field.name = templateName.replace(/__INDEX__/g, String(
                                    index));
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

                    removeButton?.addEventListener('click', function() {
                        item.remove();
                        reindexItems();
                    });

                    duplicateButton?.addEventListener('click', function() {
                        createReviewItem({
                            name: nameInput?.value || '',
                            text: textInput?.value || '',
                            rating: ratingInput?.value || '5',
                            avatar: avatarInput?.value || '',
                            previewUrl: item.querySelector(
                                '[data-review-avatar-preview] img')?.getAttribute(
                                'src') || '',
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
                    button.addEventListener('click', function() {
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

        window.initFooterLinkRepeaters = function(scope) {
            const root = scope instanceof Element || scope instanceof Document ? scope : document;
            const repeaters = root.matches?.('[data-footer-link-repeater]') ? [root] :
                Array.from(root.querySelectorAll('[data-footer-link-repeater]'));

            repeaters.forEach((repeater) => {
                if (repeater.dataset.footerLinkRepeaterBound === '1') {
                    return;
                }

                const list = repeater.querySelector('[data-footer-link-items]');
                const template = repeater.querySelector('template[data-footer-link-item-template]');
                const emptyState = repeater.querySelector('[data-footer-link-empty]');
                const addButtons = Array.from(repeater.querySelectorAll('[data-add-footer-link]'));
                const itemLabel = repeater.dataset.footerLinkItemLabel || 'Link';

                if (!list || !template) {
                    repeater.dataset.footerLinkRepeaterBound = '1';
                    return;
                }

                const reindexItems = () => {
                    const items = Array.from(list.querySelectorAll('[data-footer-link-item]'));

                    items.forEach((item, index) => {
                        item.querySelectorAll('[data-name-template]').forEach((field) => {
                            const templateName = field.dataset.nameTemplate || '';
                            if (templateName) {
                                field.name = templateName.replace(/__INDEX__/g, String(
                                    index));
                            }
                        });

                        const title = item.querySelector('[data-footer-link-title]');
                        if (title) {
                            title.textContent = `${itemLabel} ${index + 1}`;
                        }
                    });

                    if (emptyState) {
                        emptyState.classList.toggle('hidden', items.length > 0);
                    }
                };

                const bindItem = (item) => {
                    if (!(item instanceof HTMLElement) || item.dataset.footerLinkItemBound === '1') {
                        return;
                    }

                    const removeButton = item.querySelector('[data-remove-footer-link]');
                    const duplicateButton = item.querySelector('[data-duplicate-footer-link]');

                    removeButton?.addEventListener('click', function() {
                        item.remove();
                        reindexItems();
                    });

                    duplicateButton?.addEventListener('click', function() {
                        createItem({
                            label: item.querySelector('[data-footer-link-field="label"]')
                                ?.value || '',
                            url: item.querySelector('[data-footer-link-field="url"]')
                                ?.value || '',
                        });
                    });

                    item.dataset.footerLinkItemBound = '1';
                };

                const createItem = (seed = {}) => {
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = template.innerHTML.trim();

                    const item = wrapper.firstElementChild;
                    if (!(item instanceof HTMLElement)) {
                        return null;
                    }

                    list.appendChild(item);
                    bindItem(item);

                    const labelInput = item.querySelector('[data-footer-link-field="label"]');
                    const urlInput = item.querySelector('[data-footer-link-field="url"]');

                    if (labelInput && typeof seed.label === 'string') {
                        labelInput.value = seed.label;
                    }

                    if (urlInput && typeof seed.url === 'string') {
                        urlInput.value = seed.url;
                    }

                    reindexItems();

                    return item;
                };

                addButtons.forEach((button) => {
                    button.addEventListener('click', function() {
                        const item = createItem();
                        const labelInput = item?.querySelector(
                            '[data-footer-link-field="label"]');

                        if (labelInput instanceof HTMLElement) {
                            window.setTimeout(() => labelInput.focus(), 30);
                        }
                    });
                });

                Array.from(list.querySelectorAll('[data-footer-link-item]')).forEach(bindItem);

                reindexItems();
                repeater.dataset.footerLinkRepeaterBound = '1';
            });
        };

        window.initDynamicRepeaters = function(scope) {
            const root = scope instanceof Element || scope instanceof Document ?
                scope :
                document;

            const repeaters = root.matches?.('[data-dynamic-repeater]') ? [root] :
                Array.from(root.querySelectorAll('[data-dynamic-repeater]'));

            const createUniqueId = () => `dynamic_repeater_${Math.random().toString(36).slice(2, 10)}`;
            const sanitizeIconClass = (value) => String(value || '')
                .replace(/[^A-Za-z0-9\-_ ]/g, '')
                .replace(/\s+/g, ' ')
                .trim();
            const allowedIconSources = ['class', 'media', 'svg'];

            repeaters.forEach((repeater) => {
                if (repeater.dataset.dynamicRepeaterBound === '1') {
                    return;
                }

                const list = repeater.querySelector('[data-dynamic-repeater-items]');
                const template = repeater.querySelector('template[data-dynamic-repeater-template]');
                const emptyState = repeater.querySelector('[data-dynamic-repeater-empty]');
                const footerAdd = repeater.querySelector('[data-dynamic-repeater-footer-add]');
                const addButtons = Array.from(repeater.querySelectorAll('[data-add-dynamic-repeater-item]'));

                if (!list || !template) {
                    repeater.dataset.dynamicRepeaterBound = '1';
                    return;
                }

                const getIconSource = (item) => {
                    const sourceInput = item.querySelector('[data-dynamic-repeater-field="icon_source"]');
                    const source = String(sourceInput?.value || 'class').trim();

                    return allowedIconSources.includes(source) ? source : 'class';
                };

                const ensureMediaTargets = (item) => {
                    item.querySelectorAll('[data-dynamic-repeater-media-picker-button]').forEach((button) => {
                        const fieldKey = button.dataset.dynamicRepeaterField || '';

                        if (!fieldKey) {
                            return;
                        }

                        const input = item.querySelector(
                            `[data-dynamic-repeater-media-input][data-dynamic-repeater-field="${fieldKey}"]`
                        );
                        const preview = item.querySelector(
                            `[data-dynamic-repeater-media-preview][data-dynamic-repeater-field="${fieldKey}"]`
                        );

                        if (!(input instanceof HTMLInputElement) || !(button instanceof HTMLElement)) {
                            return;
                        }

                        if (!input.id || input.id.includes('__INDEX__')) {
                            input.id = `${createUniqueId()}_input`;
                        }

                        if (preview instanceof HTMLElement) {
                            if (!preview.id || preview.id.includes('__INDEX__')) {
                                preview.id = `${createUniqueId()}_preview`;
                            }

                            button.dataset.targetPreview = preview.id;
                        } else {
                            delete button.dataset.targetPreview;
                        }

                        button.dataset.targetInput = input.id;
                        button.dataset.multiple = 'false';
                        button.dataset.storeValue = button.dataset.storeValue || 'id';
                    });
                };

                const toggleIconPanels = (item, source = null) => {
                    const sourceInput = item.querySelector('[data-dynamic-repeater-field="icon_source"]');

                    if (!sourceInput) {
                        return;
                    }

                    const activeSource = source || getIconSource(item);
                    item.querySelectorAll('[data-dynamic-repeater-icon-panel]').forEach((panel) => {
                        panel.classList.toggle('hidden', panel.dataset.dynamicRepeaterIconPanel !== activeSource);
                    });
                };

                /**
                 * Refresh the unified icon picker card UI for a repeater item.
                 * Reads from the hidden icon_source select, icon class input, and
                 * media preview div, then updates the card's preview box, status
                 * line, tab highlighting, and context-sensitive action buttons.
                 */
                const refreshIconCard = (item) => {
                    const card = item.querySelector('[data-icon-picker-card]');
                    if (!card) {
                        return;
                    }

                    const source = getIconSource(item);
                    const iconClassInput = item.querySelector('[data-dynamic-repeater-icon-class-field]');
                    const mediaInput = item.querySelector(
                        '[data-dynamic-repeater-media-input][data-dynamic-repeater-field="icon_media"]'
                    );
                    const previewBox = card.querySelector('[data-icon-card-preview]');
                    const statusEl = card.querySelector('[data-icon-card-status]');
                    const chooseIconBtn = card.querySelector('[data-icon-card-choose-icon]');
                    const chooseMediaBtn = card.querySelector('[data-icon-card-choose-media]');
                    const mediaPreviewDiv = card.querySelector('[data-icon-card-media-preview]');

                    const iconClass = sanitizeIconClass(iconClassInput?.value || '');
                    const mediaId = String(mediaInput?.value || '').trim();

                    // Update preview box
                    if (previewBox) {
                        previewBox.innerHTML = '';
                        if (source === 'class' && iconClass) {
                            const el = document.createElement('i');
                            el.className = `${iconClass} text-4xl leading-none`;
                            el.setAttribute('aria-hidden', 'true');
                            previewBox.appendChild(el);
                        } else if (source === 'media') {
                            // Read thumbnail from the hidden media preview div (populated by media-picker.js)
                            const img = mediaPreviewDiv?.querySelector('img');
                            if (img) {
                                const clone = img.cloneNode(true);
                                clone.className = 'h-20 w-20 object-contain p-1';
                                previewBox.appendChild(clone);
                            } else {
                                const el = document.createElement('i');
                                el.className = 'ti ti-photo text-3xl leading-none text-slate-300';
                                el.setAttribute('aria-hidden', 'true');
                                previewBox.appendChild(el);
                            }
                        } else if (source === 'svg') {
                            const el = document.createElement('i');
                            el.className = 'ti ti-code text-3xl leading-none text-slate-400';
                            el.setAttribute('aria-hidden', 'true');
                            previewBox.appendChild(el);
                        } else {
                            // Placeholder — nothing selected yet
                            const el = document.createElement('i');
                            el.className = 'ti ti-photo-off text-3xl leading-none text-slate-300';
                            el.setAttribute('aria-hidden', 'true');
                            previewBox.appendChild(el);
                        }
                    }

                    // statusEl: optional in redesigned card (no status line rendered).
                    // Guard is kept so the function works if the element ever exists.
                    if (statusEl) {
                        if (source === 'class') {
                            statusEl.textContent = iconClass || @json(t('dashboard.No_Icon_Selected', 'No icon selected'));
                        } else if (source === 'media') {
                            statusEl.textContent = mediaId
                                ? @json(t('dashboard.Media_File_Selected', 'Media file selected'))
                                : @json(t('dashboard.No_Media_Selected', 'No media selected'));
                        } else if (source === 'svg') {
                            statusEl.textContent = @json(t('dashboard.Edit_The_Inline_Svg_Field_Below', 'Edit the inline SVG field below'));
                        } else {
                            statusEl.textContent = @json(t('dashboard.No_Icon_Set', 'No icon set'));
                        }
                    }
                    // Both action buttons are always visible in the redesigned card;
                    // no show/hide toggling needed.
                    // mediaPreviewDiv stays permanently hidden — it is an internal
                    // container for media-picker.js and is never surfaced in the UI.
                };

                const reindexItems = () => {
                    const items = Array.from(list.querySelectorAll('[data-dynamic-repeater-item]'));

                    items.forEach((item, index) => {
                        item.querySelectorAll('[data-name-template]').forEach((field) => {
                            const templateName = field.dataset.nameTemplate || '';

                            if (templateName) {
                                field.name = templateName.replace(/__INDEX__/g, String(
                                    index));
                            }
                        });

                        const label = item.querySelector('[data-dynamic-repeater-item-label]');

                        if (label) {
                            if (!label.dataset.itemPrefix) {
                                label.dataset.itemPrefix = label.textContent
                                    .replace(/\s*\d+\s*$/, '')
                                    .trim();
                            }

                            label.textContent = `${label.dataset.itemPrefix} ${index + 1}`;
                        }
                    });

                    if (emptyState) {
                        emptyState.classList.toggle('hidden', items.length > 0);
                    }

                    if (footerAdd) {
                        footerAdd.classList.toggle('hidden', items.length === 0);
                    }
                };

                const bindToggle = (item) => {
                    const toggleButton = item.querySelector('[data-dynamic-repeater-toggle]');
                    const body = item.querySelector('[data-dynamic-repeater-item-body]');
                    const icon = item.querySelector('[data-dynamic-repeater-toggle-icon]');

                    if (!toggleButton || !body) {
                        return;
                    }

                    toggleButton.addEventListener('click', function() {
                        const expanded = toggleButton.getAttribute('aria-expanded') === 'true';
                        const next = !expanded;

                        body.classList.toggle('hidden', !next);
                        toggleButton.setAttribute('aria-expanded', String(next));

                        if (icon) {
                            icon.classList.toggle('rotate-180', next);
                        }
                    });
                };

                const bindItem = (item) => {
                    if (!(item instanceof HTMLElement) || item.dataset.dynamicRepeaterItemBound === '1') {
                        return;
                    }

                    ensureMediaTargets(item);

                    item.querySelectorAll('[data-dynamic-repeater-icon-class-field]').forEach((input) => {
                        if (!(input instanceof HTMLInputElement)) {
                            return;
                        }

                        const sanitized = sanitizeIconClass(input.value);
                        if (input.value !== sanitized) {
                            input.value = sanitized;
                        }

                        input.addEventListener('input', function() {
                            const next = sanitizeIconClass(input.value);
                            if (input.value !== next) {
                                input.value = next;
                            }
                        });

                        // Refresh card when the icon library modal writes a new value
                        input.addEventListener('change', () => refreshIconCard(item));
                    });

                    item.querySelector('[data-dynamic-repeater-field="icon_source"]')?.addEventListener('change',
                        function() {
                            toggleIconPanels(item, getIconSource(item));
                            refreshIconCard(item);
                        });

                    // Refresh card after media picker sets icon_media value.
                    // Use setTimeout so the preview div thumbnail (rendered by media-picker.js
                    // synchronously after dispatching 'change') is in the DOM first.
                    item.querySelector(
                        '[data-dynamic-repeater-media-input][data-dynamic-repeater-field="icon_media"]'
                    )?.addEventListener('change', () => window.setTimeout(() => refreshIconCard(item), 0));

                    // --- Icon picker card bindings ---
                    const pickerCard = item.querySelector('[data-icon-picker-card]');
                    if (pickerCard) {
                        // Icon Library button: set icon_source='class' before the global
                        // data-open-section-icon-library click delegation fires the modal.
                        // Element listeners fire before document listeners, so source is
                        // already 'class' when openLibrary() runs.
                        pickerCard.querySelector('[data-icon-card-choose-icon]')?.addEventListener('click', function() {
                            const sourceInput = item.querySelector(
                                '[data-dynamic-repeater-field="icon_source"]'
                            );
                            if (sourceInput && sourceInput.value !== 'class') {
                                sourceInput.value = 'class';
                                sourceInput.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        });

                        // Upload SVG button: set icon_source='media' before media-picker.js
                        // handles the btn-open-media-picker click.
                        pickerCard.querySelector('[data-icon-card-choose-media]')?.addEventListener('click', function() {
                            const sourceInput = item.querySelector(
                                '[data-dynamic-repeater-field="icon_source"]'
                            );
                            if (sourceInput && sourceInput.value !== 'media') {
                                sourceInput.value = 'media';
                                sourceInput.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        });

                        // Clear button — reset source to class, wipe icon class and media values
                        pickerCard.querySelector('[data-icon-card-clear]')?.addEventListener('click', function() {
                            const sourceInput = item.querySelector(
                                '[data-dynamic-repeater-field="icon_source"]'
                            );
                            const iconClassInput = item.querySelector(
                                '[data-dynamic-repeater-icon-class-field]'
                            );
                            const mediaInput = item.querySelector(
                                '[data-dynamic-repeater-media-input][data-dynamic-repeater-field="icon_media"]'
                            );
                            const mediaPreviewDiv = item.querySelector(
                                '[data-icon-card-media-preview]'
                            );

                            if (iconClassInput instanceof HTMLInputElement) {
                                iconClassInput.value = '';
                                iconClassInput.dispatchEvent(new Event('input', { bubbles: true }));
                                iconClassInput.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                            if (mediaInput instanceof HTMLInputElement) {
                                mediaInput.value = '';
                                mediaInput.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                            if (mediaPreviewDiv instanceof HTMLElement) {
                                mediaPreviewDiv.innerHTML = '';
                            }
                            if (sourceInput) {
                                sourceInput.value = 'class';
                                sourceInput.dispatchEvent(new Event('change', { bubbles: true }));
                            } else {
                                toggleIconPanels(item, 'class');
                                refreshIconCard(item);
                            }
                        });
                    }

                    const removeButton = item.querySelector('[data-remove-dynamic-repeater-item]');
                    const duplicateButton = item.querySelector('[data-duplicate-dynamic-repeater-item]');

                    removeButton?.addEventListener('click', function() {
                        item.remove();
                        reindexItems();
                    });

                    duplicateButton?.addEventListener('click', function() {
                        const seed = {};
                        const mediaPreviewHtml = {};

                        item.querySelectorAll('[data-name-template]').forEach((field) => {
                            const templateName = field.dataset.nameTemplate || '';
                            const match = templateName.match(/\[([^\[\]]+)\]$/);

                            if (!match) {
                                return;
                            }

                            const key = match[1];

                            if (field.type === 'checkbox') {
                                if (field.checked) {
                                    seed[key] = '1';
                                }
                            } else if (field.type === 'hidden') {
                                // Include media inputs and hidden icon class fields (picker card UX)
                                if (field.hasAttribute('data-dynamic-repeater-media-input') ||
                                    field.hasAttribute('data-dynamic-repeater-icon-class-field')) {
                                    seed[key] = field.value;
                                }
                            } else {
                                seed[key] = field.value;
                            }
                        });

                        item.querySelectorAll('[data-dynamic-repeater-media-preview]').forEach((preview) => {
                            const key = preview.dataset.dynamicRepeaterField || '';

                            if (key) {
                                mediaPreviewHtml[key] = preview.innerHTML;
                            }
                        });

                        if (Object.keys(mediaPreviewHtml).length > 0) {
                            seed.__mediaPreviewHtml = mediaPreviewHtml;
                        }

                        createItem(seed);
                    });

                    bindToggle(item);
                    toggleIconPanels(item, getIconSource(item));
                    refreshIconCard(item);
                    item.dataset.dynamicRepeaterItemBound = '1';
                };

                const createItem = (seed = {}) => {
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = template.innerHTML.trim();

                    const item = wrapper.firstElementChild;

                    if (!(item instanceof HTMLElement)) {
                        return null;
                    }

                    const body = item.querySelector('[data-dynamic-repeater-item-body]');
                    const toggleButton = item.querySelector('[data-dynamic-repeater-toggle]');
                    const icon = item.querySelector('[data-dynamic-repeater-toggle-icon]');

                    if (body) {
                        body.classList.remove('hidden');
                    }

                    if (toggleButton) {
                        toggleButton.setAttribute('aria-expanded', 'true');
                    }

                    if (icon) {
                        icon.classList.add('rotate-180');
                    }

                    list.appendChild(item);
                    bindItem(item);

                    if (Object.keys(seed).length > 0) {
                        item.querySelectorAll('[data-name-template]').forEach((field) => {
                            const templateName = field.dataset.nameTemplate || '';
                            const match = templateName.match(/\[([^\[\]]+)\]$/);

                            if (!match) {
                                return;
                            }

                            const key = match[1];

                            if (!(key in seed)) {
                                return;
                            }

                            if (field.type === 'checkbox') {
                                field.checked = seed[key] === '1';
                            } else if (field.type === 'hidden') {
                                // Apply media inputs and hidden icon class fields (picker card UX)
                                if (field.hasAttribute('data-dynamic-repeater-media-input') ||
                                    field.hasAttribute('data-dynamic-repeater-icon-class-field')) {
                                    field.value = seed[key];
                                }
                            } else {
                                field.value = seed[key];
                            }
                        });
                    }

                    if (seed.__mediaPreviewHtml && typeof seed.__mediaPreviewHtml === 'object') {
                        Object.entries(seed.__mediaPreviewHtml).forEach(([key, html]) => {
                            const preview = item.querySelector(
                                `[data-dynamic-repeater-media-preview][data-dynamic-repeater-field="${key}"]`
                            );

                            if (preview instanceof HTMLElement && typeof html === 'string') {
                                preview.innerHTML = html;
                            }
                        });
                    }

                    toggleIconPanels(item, getIconSource(item));
                    refreshIconCard(item);
                    reindexItems();

                    const firstInput = item.querySelector(
                        'input[type="text"], input[type="url"], textarea');

                    if (firstInput instanceof HTMLElement) {
                        window.setTimeout(() => firstInput.focus(), 30);
                    }

                    return item;
                };

                addButtons.forEach((button) => {
                    button.addEventListener('click', function() {
                        createItem();
                    });
                });

                Array.from(list.querySelectorAll('[data-dynamic-repeater-item]')).forEach(bindItem);

                reindexItems();
                repeater.dataset.dynamicRepeaterBound = '1';
            });
        };
    </script>
</body>
</html>
