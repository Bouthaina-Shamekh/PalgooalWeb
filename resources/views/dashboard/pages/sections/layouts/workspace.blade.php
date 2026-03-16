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

    <script>
        window.MEDIA_CONFIG = window.MEDIA_CONFIG || {};
        window.MEDIA_CONFIG.baseUrl = window.MEDIA_CONFIG.baseUrl || @json(url('admin/media'));
        window.MEDIA_CONFIG.csrfToken = window.MEDIA_CONFIG.csrfToken || @json(csrf_token());
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="{{ asset('assets/dashboard/js/media-picker.js') }}?v={{ filemtime(public_path('assets/dashboard/js/media-picker.js')) }}" defer></script>
    @include('dashboard.partials.media-picker')

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
            window.initSectionFeatureRepeaters?.(document);
            window.initBuildStepRepeaters?.(document);
            window.initReviewRepeaters?.(document);
        });

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

            repeaters.forEach((repeater) => {
                if (repeater.dataset.featureRepeaterBound === '1') {
                    return;
                }

                const list = repeater.querySelector('[data-feature-items]');
                const template = repeater.querySelector('template[data-feature-item-template]');
                const emptyState = repeater.querySelector('[data-feature-empty]');
                const addButtons = Array.from(repeater.querySelectorAll('[data-add-feature-item]'));

                if (!list || !template) {
                    repeater.dataset.featureRepeaterBound = '1';
                    return;
                }

                const sanitizeIconClass = (value) => String(value || '')
                    .replace(/[^A-Za-z0-9\-_ ]/g, '')
                    .replace(/\s+/g, ' ')
                    .trim();

                const renderIconPreview = (item) => {
                    const preview = item.querySelector('[data-feature-icon-preview]');
                    const input = item.querySelector('[data-feature-field="icon"]');

                    if (!preview || !input) {
                        return;
                    }

                    const iconClass = sanitizeIconClass(input.value);
                    if (input.value !== iconClass) {
                        input.value = iconClass;
                    }

                    preview.innerHTML = '';

                    const icon = document.createElement('i');
                    icon.className = `${iconClass || 'ti ti-check'} text-xl leading-none`;
                    icon.setAttribute('aria-hidden', 'true');
                    preview.appendChild(icon);
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
                    });

                    if (emptyState) {
                        emptyState.classList.toggle('hidden', items.length > 0);
                    }
                };

                const bindFeatureItem = (item) => {
                    if (!(item instanceof HTMLElement) || item.dataset.featureItemBound === '1') {
                        return;
                    }

                    const iconInput = item.querySelector('[data-feature-field="icon"]');
                    const textInput = item.querySelector('[data-feature-field="text"]');
                    const removeButton = item.querySelector('[data-remove-feature-item]');
                    const duplicateButton = item.querySelector('[data-duplicate-feature-item]');
                    const presetButtons = Array.from(item.querySelectorAll('[data-feature-icon-preset]'));

                    iconInput?.addEventListener('input', function () {
                        renderIconPreview(item);
                    });

                    removeButton?.addEventListener('click', function () {
                        item.remove();
                        reindexItems();
                    });

                    duplicateButton?.addEventListener('click', function () {
                        createFeatureItem({
                            text: textInput?.value || '',
                            icon: iconInput?.value || '',
                        });
                    });

                    presetButtons.forEach((button) => {
                        button.addEventListener('click', function () {
                            if (!iconInput) {
                                return;
                            }

                            iconInput.value = button.dataset.featureIconValue || '';
                            renderIconPreview(item);
                            iconInput.dispatchEvent(new Event('input', { bubbles: true }));
                        });
                    });

                    renderIconPreview(item);
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

                    if (textInput && typeof seed.text === 'string') {
                        textInput.value = seed.text;
                    }

                    if (iconInput && typeof seed.icon === 'string') {
                        iconInput.value = sanitizeIconClass(seed.icon);
                    }

                    renderIconPreview(item);
                    reindexItems();

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

        window.initBuildStepRepeaters = function (scope) {
            const root = scope instanceof Element || scope instanceof Document ? scope : document;
            const repeaters = root.matches?.('[data-build-step-repeater]')
                ? [root]
                : Array.from(root.querySelectorAll('[data-build-step-repeater]'));

            repeaters.forEach((repeater) => {
                if (repeater.dataset.buildStepRepeaterBound === '1') {
                    return;
                }

                const list = repeater.querySelector('[data-build-step-items]');
                const template = repeater.querySelector('template[data-build-step-item-template]');
                const emptyState = repeater.querySelector('[data-build-step-empty]');
                const addButtons = Array.from(repeater.querySelectorAll('[data-add-build-step]'));

                if (!list || !template) {
                    repeater.dataset.buildStepRepeaterBound = '1';
                    return;
                }

                const sanitizeIconClass = (value) => String(value || '')
                    .replace(/[^A-Za-z0-9\-_ ]/g, '')
                    .replace(/\s+/g, ' ')
                    .trim();

                const renderIconPreview = (item) => {
                    const preview = item.querySelector('[data-build-step-icon-preview]');
                    const input = item.querySelector('[data-build-step-field="icon"]');

                    if (!preview || !input) {
                        return;
                    }

                    const iconClass = sanitizeIconClass(input.value);
                    if (input.value !== iconClass) {
                        input.value = iconClass;
                    }

                    preview.innerHTML = '';

                    const icon = document.createElement('i');
                    icon.className = `${iconClass || 'ti ti-search'} text-2xl leading-none`;
                    icon.setAttribute('aria-hidden', 'true');
                    preview.appendChild(icon);
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
                    });

                    if (emptyState) {
                        emptyState.classList.toggle('hidden', items.length > 0);
                    }
                };

                const bindStepItem = (item) => {
                    if (!(item instanceof HTMLElement) || item.dataset.buildStepItemBound === '1') {
                        return;
                    }

                    const iconInput = item.querySelector('[data-build-step-field="icon"]');
                    const titleInput = item.querySelector('[data-build-step-field="title"]');
                    const removeButton = item.querySelector('[data-remove-build-step]');
                    const duplicateButton = item.querySelector('[data-duplicate-build-step]');
                    const presetButtons = Array.from(item.querySelectorAll('[data-build-step-icon-preset]'));

                    iconInput?.addEventListener('input', function () {
                        renderIconPreview(item);
                    });

                    removeButton?.addEventListener('click', function () {
                        item.remove();
                        reindexItems();
                    });

                    duplicateButton?.addEventListener('click', function () {
                        createStepItem({
                            title: titleInput?.value || '',
                            icon: iconInput?.value || '',
                            isAccent: item.querySelector('[data-build-step-field="accent"]')?.checked || false,
                        });
                    });

                    presetButtons.forEach((button) => {
                        button.addEventListener('click', function () {
                            if (!iconInput) {
                                return;
                            }

                            iconInput.value = button.dataset.buildStepIconValue || '';
                            renderIconPreview(item);
                            iconInput.dispatchEvent(new Event('input', { bubbles: true }));
                        });
                    });

                    renderIconPreview(item);
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
                    const accentInput = item.querySelector('[data-build-step-field="accent"]');

                    if (titleInput && typeof seed.title === 'string') {
                        titleInput.value = seed.title;
                    }

                    if (iconInput && typeof seed.icon === 'string') {
                        iconInput.value = sanitizeIconClass(seed.icon);
                    }

                    if (accentInput) {
                        accentInput.checked = Boolean(seed.isAccent);
                    }

                    renderIconPreview(item);
                    reindexItems();

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
