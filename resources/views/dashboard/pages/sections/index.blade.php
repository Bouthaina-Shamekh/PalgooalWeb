@php
    $pageTranslation = method_exists($page, 'translation') ? $page->translation() : null;
    $pageTitle = $pageTranslation?->title ?? ($page->slug ?? '#' . $page->id);
    $frontUrl = $workspaceFrontUrl ?? ($page->is_home ? url('/') : ($pageTranslation?->slug ?? null ? url($pageTranslation->slug) : url('/')));
    $workspaceRoutePrefix = $workspaceRoutePrefix ?? 'dashboard.pages.sections.';
    $workspaceRouteBaseParameters = $workspaceRouteBaseParameters ?? ['page' => $page];
    $workspaceRouteFor =
        $workspaceRouteFor
        ?? fn(string $name, array $extra = [], bool $absolute = true) => route(
            $workspaceRoutePrefix . $name,
            array_merge($workspaceRouteBaseParameters, $extra),
            $absolute,
        );
    $workspaceBuilderModeUrl = $workspaceBuilderModeUrl ?? route('dashboard.pages.builder-mode', $page);
    $workspaceMode = $workspaceMode ?? 'admin';
    $isClientWorkspace = $workspaceMode === 'client';
    $currentLocale = app()->getLocale();
    $groupedTypes = collect($sectionLibraryTypes ?? $sectionTypes ?? [])
        ->reject(fn($meta) => (bool) ($meta['library_hidden'] ?? false))
        ->groupBy(fn($meta) => $meta['category'] ?? 'other', true);
    $highlightSectionId = (int) request('highlight');
    $selectedSectionId = $highlightSectionId > 0 ? $highlightSectionId : (int) ($sections->first()->id ?? 0);
    $pageBuilderMode = in_array($page->builder_mode, ['visual', 'sections'], true) ? $page->builder_mode : 'sections';
    $isRtl = current_dir() === 'rtl';
    $drawerClosedTranslateClass = $isRtl ? '-translate-x-full' : 'translate-x-full';
    $previewBaseUrl = $workspaceRouteFor('preview', [], false);
    $previewUrl = $previewBaseUrl . ($selectedSectionId ? '?highlight=' . $selectedSectionId : '');
    $autoEditSectionId = (int) request('edit');
    $addSectionLabel = $isClientWorkspace ? t('dashboard.Add_Block', 'Add Block') : t('dashboard.Add_Section', 'Add Section');
    $refreshPreviewLabel = $isClientWorkspace ? t('dashboard.Reload_Preview', 'Reload Preview') : t('dashboard.Refresh_Preview', 'Refresh Preview');
    $emptyStateTitle = $isClientWorkspace ? t('dashboard.Start_By_Adding_First_Block', 'Start by adding your first block') : t('dashboard.Start_By_Adding_First_Section', 'Start by adding your first section');
    $emptyStateDescription = $isClientWorkspace
        ? t('dashboard.Empty_State_Block_Description', 'Choose a ready-made block such as a hero, features list, or call to action, then edit the content on the right.')
        : t('dashboard.Empty_State_Section_Description', 'Use the section library to create a ready-to-edit block instantly, then fine-tune it in the editor.');
    $openLibraryLabel = $isClientWorkspace ? t('dashboard.Browse_Blocks', 'Browse Blocks') : t('dashboard.Open_Section_Library', 'Open Section Library');
    $previewRefreshingLabel = $isClientWorkspace ? t('dashboard.Updating_Preview', 'Updating preview...') : t('dashboard.Refreshing_Preview', 'Refreshing preview...');
    $libraryTitle = $isClientWorkspace ? t('dashboard.Add_A_Block', 'Add a Block') : t('dashboard.Add_Section_To_Page', 'Add Section to Page');
    $libraryDescription = $isClientWorkspace
        ? t('dashboard.Library_Block_Description', 'Pick a block for this page. We will add it right away and open its settings so you can start editing.')
        : t('dashboard.Library_Section_Description', 'Choose a block and we will add it instantly, then open the editor for final customization.');
    $librarySearchPlaceholder = $isClientWorkspace ? t('dashboard.Search_Blocks', 'Search blocks') : t('dashboard.Search_Section_Types', 'Search section types');
    $libraryAddHint = $isClientWorkspace ? t('dashboard.Adds_To_Page_Right_Away', 'Adds to your page right away') : t('dashboard.Creates_Draft_Instantly', 'Creates a draft instantly');
    $libraryAddActionLabel = $isClientWorkspace ? t('dashboard.Use_Block', 'Use Block') : t('dashboard.Add', 'Add');
    $clientLibraryIntroTitle = t('dashboard.How_Block_Adding_Works', 'How block adding works');
    $clientLibraryIntroSteps = [
        t('dashboard.Library_Intro_Step_1', '1. Pick a block that matches the part of the page you want to create.'),
        t('dashboard.Library_Intro_Step_2', '2. The block is added to this page immediately.'),
        t('dashboard.Library_Intro_Step_3', '3. Its settings open right away so you can edit text, images, and buttons.'),
    ];
    $clientLibraryCategoryMeta = [
        'hero' => [
            'label' => t('dashboard.Category_Hero_Label', 'Page Start'),
            'description' => t('dashboard.Category_Hero_Description', 'Opening blocks for your main headline, call to action, and first impression.'),
        ],
        'services' => [
            'label' => t('dashboard.Category_Services_Label', 'What You Offer'),
            'description' => t('dashboard.Category_Services_Description', 'Blocks that explain services, departments, or capabilities in a clear way.'),
        ],
        'process' => [
            'label' => t('dashboard.Category_Process_Label', 'How It Works'),
            'description' => t('dashboard.Category_Process_Description', 'Step-by-step blocks that explain your workflow or service journey.'),
        ],
        'testimonials' => [
            'label' => t('dashboard.Category_Testimonials_Label', 'Trust & Reviews'),
            'description' => t('dashboard.Category_Testimonials_Description', 'Blocks that show customer feedback and build confidence.'),
        ],
        'portfolio' => [
            'label' => t('dashboard.Category_Portfolio_Label', 'Work Examples'),
            'description' => t('dashboard.Category_Portfolio_Description', 'Blocks for featured projects, case studies, or portfolio highlights.'),
        ],
        'pricing' => [
            'label' => t('dashboard.Category_Pricing_Label', 'Pricing'),
            'description' => t('dashboard.Category_Pricing_Description', 'Blocks that help visitors compare plans and choose an offer.'),
        ],
        'domains' => [
            'label' => t('dashboard.Category_Domains_Label', 'Domain Search'),
            'description' => t('dashboard.Category_Domains_Description', 'A ready-made search block for domain-related pages or landing sections.'),
        ],
        'templates' => [
            'label' => t('dashboard.Category_Templates_Label', 'Templates & Catalog'),
            'description' => t('dashboard.Category_Templates_Description', 'Blocks for showcasing template cards, listings, and previews.'),
        ],
        'features' => [
            'label' => t('dashboard.Category_Features_Label', 'Features'),
            'description' => t('dashboard.Category_Features_Description', 'Simple feature grids for key benefits, highlights, or selling points.'),
        ],
        'other' => [
            'label' => t('dashboard.Category_Other_Label', 'More Blocks'),
            'description' => t('dashboard.Category_Other_Description', 'Additional page blocks you can add and customize.'),
        ],
    ];
    $previewFrameTitleLabel = $isClientWorkspace ? t('dashboard.Live_Page_Preview', 'Live page preview') : t('dashboard.Live_Sections_Preview', 'Live sections preview');
    $sidebarIntroTitle = $isClientWorkspace
        ? ($workspaceContentLabel ?? ($page->is_home ? t('dashboard.Editing_Homepage', 'You are editing your homepage') : t('dashboard.Editing_This_Page', 'You are editing this page')))
        : null;
    $sidebarIntroDescription = $isClientWorkspace
        ? t('dashboard.Sidebar_Intro_Description', 'This workspace is focused on page blocks only. Add, reorder, rename, and edit blocks here without the extra admin controls.')
        : null;
    $pageStructureTitle = $isClientWorkspace ? t('dashboard.Page_Blocks', 'Page Blocks') : t('dashboard.Page_Elements', 'Page Elements');
    $pageStructureDescription = $isClientWorkspace
        ? t('dashboard.Page_Structure_Block_Description', 'Drag to reorder blocks, then open any block to edit its content.')
        : t('dashboard.Page_Structure_Section_Description', 'Customize this page sections and keep the structure organized.');
    $addNewElementLabel = $isClientWorkspace ? t('dashboard.Add_Block', 'Add Block') : t('dashboard.Add_New_Element', 'Add New Element');
    $noElementsLabel = $isClientWorkspace ? t('dashboard.No_Blocks_Added_Yet', 'No blocks have been added yet.') : t('dashboard.No_Elements_Added_Yet', 'No elements have been added yet.');
    $bottomTipLabel = $isClientWorkspace ? t('dashboard.Bottom_Tip_Block', 'Tip: open any block to edit text, images, buttons, and other page content.') : t('dashboard.Changes_Save_Automatically', 'Changes save automatically');
    $previewDraftUrl = $workspaceRouteFor('preview');
    $pageIsLive = (bool) $page->is_active;
    $publishStateBadge = $pageIsLive ? t('dashboard.Live', 'Live') : t('dashboard.Draft', 'Draft');
    $publishStateLabel = $pageIsLive ? t('dashboard.Visible_On_Your_Site', 'Visible on your site') : t('dashboard.Saved_As_Draft', 'Saved as draft');
    $publishStateDescription = $pageIsLive
        ? t('dashboard.Publish_State_Live_Description', 'Saved changes appear on the live page after you save. Use preview first if you want to double-check the page before sharing it.')
        : t('dashboard.Publish_State_Draft_Description', 'This page is currently hidden from visitors. You can still preview changes here, then make the page visible from your pages manager when you are ready.');
    $previewDraftLabel = t('dashboard.Preview_Draft', 'Preview Draft');
    $publishActionLabel = $pageIsLive ? t('dashboard.Publish_And_View_Live_Page', 'Publish & View Live Page') : t('dashboard.Hidden_From_Visitors', 'Hidden From Visitors');
    $publishHelperLabel = $pageIsLive
        ? t('dashboard.Publish_Helper_Live', 'Preview opens your in-progress version. Publish opens the live page your visitors can already see.')
        : t('dashboard.Publish_Helper_Draft', 'This page is hidden right now, so preview is the safest way to review it before making it visible.');
    $reloadPreviewShortLabel = $isClientWorkspace ? t('dashboard.Reload', 'Reload') : $refreshPreviewLabel;
@endphp

@extends('dashboard.pages.sections.layouts.workspace')

@push('styles')
    <style>
        .sections-workspace-main {
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
        }

        .sections-preview-shell-host {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }

        .sections-sortable-ghost {
            opacity: 0.55;
        }

        .sections-sortable-chosen {
            box-shadow: 0 18px 36px -24px rgba(15, 23, 42, 0.35);
        }

        .sections-sortable-drag {
            transform: rotate(1deg);
            box-shadow: 0 24px 48px -30px rgba(15, 23, 42, 0.4);
        }

        .sections-drag-handle {
            cursor: grab;
        }

        .sections-drag-handle:active {
            cursor: grabbing;
        }

        .sections-outline-item {
            position: relative;
            z-index: 0;
            cursor: pointer;
            background: #ffffff;
            box-shadow: 0 10px 24px -24px rgba(15, 23, 42, 0.18), 0 4px 10px rgba(15, 23, 42, 0.04);
        }

        .sections-outline-item:hover {
            background: #ffffff;
            box-shadow: 0 16px 32px -28px rgba(15, 23, 42, 0.2), 0 6px 14px rgba(15, 23, 42, 0.05);
        }

        .sections-outline-item.is-selected {
            background: #ffffff;
            box-shadow: 0 18px 34px -28px rgba(15, 23, 42, 0.22), 0 8px 18px rgba(15, 23, 42, 0.06);
        }

        .sections-outline-item:focus-within,
        .sections-outline-item.is-menu-open {
            z-index: 30;
        }

        .sections-preview-stage {
            overflow: hidden;
            flex: 1 1 auto;
            height: 100%;
            min-height: 38rem;
            display: block;
            padding: 0;
            background:
                radial-gradient(circle at top, rgba(148, 163, 184, 0.12), transparent 40%),
                linear-gradient(180deg, #eef4fa 0%, #f7fafc 100%);
        }

        .sections-preview-viewport {
            box-sizing: border-box;
            width: 100%;
            max-width: 100%;
            height: 100%;
            min-width: 0;
            margin: 0 auto;
            padding: 0;
            display: flex;
            transition: max-width 200ms ease, padding 200ms ease;
        }

        .sections-preview-viewport[data-device="desktop"] {
            max-width: 100%;
        }

        .sections-preview-viewport[data-device="tablet"] {
            max-width: 920px;
            padding: 1.25rem 0;
        }

        .sections-preview-viewport[data-device="mobile"] {
            max-width: 440px;
            padding: 1.25rem 0;
        }

        .sections-preview-frame {
            display: block;
            flex: 1 1 auto;
            width: 100%;
            height: 100%;
            min-height: 0;
            border: 0;
            background: #ffffff;
        }

        .sections-preview-viewport[data-device="tablet"] .sections-preview-frame,
        .sections-preview-viewport[data-device="mobile"] .sections-preview-frame {
            border-radius: 2rem;
            box-shadow: 0 28px 60px -36px rgba(15, 23, 42, 0.35);
        }

        .preview-device-button.is-active {
            background: #0f172a;
            color: #ffffff;
            box-shadow: 0 10px 24px -18px rgba(15, 23, 42, 0.55);
        }

        .sections-editor-loading {
            display: grid;
            place-items: center;
            min-height: 16rem;
            border: 1px dashed #cbd5e1;
            border-radius: 1.5rem;
            background: rgba(248, 250, 252, 0.92);
            color: #64748b;
        }

        @media (min-width: 1280px) {
            .sections-workspace-shell.is-section-editor-open .sections-workspace-sidebar-shell {
                position: relative;
                inset: auto;
                z-index: auto;
                width: clamp(19rem, 22vw, 21rem);
                min-width: clamp(19rem, 22vw, 21rem);
            }

            html[dir="ltr"] .sections-workspace-shell.is-section-editor-open .sections-workspace-sidebar-shell {
                right: auto;
            }

            html[dir="rtl"] .sections-workspace-shell.is-section-editor-open .sections-workspace-sidebar-shell {
                right: auto;
            }

            .sections-workspace-shell.is-section-editor-open .sections-workspace-sidebar {
                padding: 0.25rem;
                border-color: #e2e8f0;
                background: rgba(255, 255, 255, 0.96);
                height: 100%;
                box-shadow: none;
            }
        }
    </style>
@endpush

@section('workspace-header-actions')
    @if ($isClientWorkspace)
        <span
            class="inline-flex items-center gap-2 rounded-full border {{ $pageIsLive ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }} px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.18em]"
            aria-label="{{ $publishStateLabel }}"
            title="{{ $publishStateLabel }}"
        >
            <span class="h-2.5 w-2.5 rounded-full {{ $pageIsLive ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
            <span>{{ $publishStateBadge }}</span>
        </span>
        <a href="{{ $previewDraftUrl }}" target="_blank"
            class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-700 transition duration-200 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300"
            aria-label="{{ $previewDraftLabel }}"
            title="{{ $previewDraftLabel }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12S5.25 5.25 12 5.25 21.75 12 21.75 12 18.75 18.75 12 18.75 2.25 12 2.25 12Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z" />
            </svg>
        </a>
        @if ($pageIsLive)
            <a href="{{ $frontUrl }}" target="_blank"
                class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-emerald-600 text-white transition duration-200 hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300"
                aria-label="{{ $publishActionLabel }}"
                title="{{ $publishActionLabel }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5H19.5M19.5 4.5V10.5M19.5 4.5L10.5 13.5" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5V18.75A1.5 1.5 0 0 1 18 20.25H5.25A1.5 1.5 0 0 1 3.75 18.75V6A1.5 1.5 0 0 1 5.25 4.5H10.5" />
                </svg>
            </a>
        @else
            <span class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-amber-200 bg-amber-50 text-amber-700"
                aria-label="{{ $publishActionLabel }}"
                title="{{ $publishActionLabel }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A3.75 3.75 0 0 0 8.25 5.25V9m-.75 0h9A1.5 1.5 0 0 1 18 10.5v7.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 18V10.5A1.5 1.5 0 0 1 7.5 9Z" />
                </svg>
            </span>
        @endif
    @endif

    <button type="button" data-open-section-library
        class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition duration-200 hover:bg-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        <span>{{ $addSectionLabel }}</span>
    </button>
@endsection

@section('workspace-header-toolbar')
    <div class="flex flex-wrap items-center gap-2 rtl:flex-row-reverse xl:flex-nowrap">
        <div class="flex items-center gap-1 rounded-full bg-slate-100/90 p-1 shadow-inner rtl:flex-row-reverse">
            <button type="button" data-preview-device="desktop"
                class="preview-device-button is-active inline-flex items-center rounded-full px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-white hover:text-slate-900">{{ t('dashboard.Desktop', 'Desktop') }}</button>
            <button type="button" data-preview-device="tablet"
                class="preview-device-button inline-flex items-center rounded-full px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-white hover:text-slate-900">{{ t('dashboard.Tablet', 'Tablet') }}</button>
            <button type="button" data-preview-device="mobile"
                class="preview-device-button inline-flex items-center rounded-full px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-white hover:text-slate-900">{{ t('dashboard.Mobile', 'Mobile') }}</button>
        </div>

        <button type="button" data-refresh-sections-preview
            class="{{ $isClientWorkspace ? 'inline-flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-700 transition duration-200 hover:bg-slate-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300 disabled:pointer-events-none disabled:opacity-70' : 'inline-flex items-center rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300 disabled:pointer-events-none disabled:opacity-70' }}"
            aria-label="{{ $refreshPreviewLabel }}"
            title="{{ $refreshPreviewLabel }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 {{ $isClientWorkspace ? '' : 'ltr:mr-2 rtl:ml-2' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356m-1.636 14.287A9 9 0 0 1 5.106 5.36m13.788 13.283H13.9v-4.992" />
            </svg>
            @unless ($isClientWorkspace)
                {{ $reloadPreviewShortLabel }}
            @endunless
        </button>
    </div>
@endsection

@section('workspace-main')
    <div class="relative -mx-4 flex min-h-0 flex-1 flex-col lg:-mx-6">
        <div id="sections-preview-shell-host" class="sections-preview-shell-host">
            @if ($sections->isEmpty())
                <div
                    class="mx-4 rounded-[2rem] border border-dashed border-slate-300 bg-white/80 px-6 py-16 text-center lg:mx-6">
                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ $emptyStateTitle }}</h3>
                    <p class="mx-auto mt-2 max-w-2xl text-sm text-slate-500">
                        {{ $emptyStateDescription }}
                    </p>
                    @if ($isClientWorkspace)
                        <div class="mx-auto mt-5 grid max-w-3xl gap-3 text-left sm:grid-cols-3 rtl:text-right">
                            @foreach ($clientLibraryIntroSteps as $step)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm text-slate-600">
                                    {{ $step }}
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <div class="mt-6 flex flex-wrap items-center justify-center gap-3 rtl:flex-row-reverse">
                        <button type="button" data-open-section-library
                            class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition duration-200 hover:bg-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300">{{ $openLibraryLabel }}</button>
                    </div>
                </div>
            @else
                <div id="sections-preview-stage" class="sections-preview-stage">
                    <div id="sections-preview-viewport" class="sections-preview-viewport" data-device="desktop">
                        <iframe id="sections-preview-frame" class="sections-preview-frame" src="{{ $previewUrl }}"
                            data-base-url="{{ $previewBaseUrl }}" title="{{ $previewFrameTitleLabel }}"></iframe>
                    </div>
                </div>
            @endif
        </div>

        <div id="sections-preview-loading-overlay"
            class="absolute inset-0 z-20 hidden items-center justify-center bg-slate-100/80 backdrop-blur-sm">
            <div
                class="inline-flex items-center gap-3 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-lg">
                <span class="inline-flex h-5 w-5 animate-spin rounded-full border-2 border-slate-200 border-t-slate-900"></span>
                <span>{{ $previewRefreshingLabel }}</span>
            </div>
        </div>
    </div>

    {{-- Brand Settings drawer (only present when a subscription is associated) --}}
    @if (! empty($brandSettingsUpdateUrl) && isset($brandSettingsTheme))
        @include('dashboard.pages.sections.partials.brand-settings-drawer', [
            'brandSettingsUpdateUrl' => $brandSettingsUpdateUrl,
            'brandSettingsTheme'     => $brandSettingsTheme,
            'isClientWorkspace'      => $isClientWorkspace,
            'isRtl'                  => $isRtl,
        ])
    @endif

    <div id="section-library-overlay" class="fixed inset-0 z-[60] hidden bg-slate-950/55"></div>
    <aside id="section-library-drawer" data-closed-translate="{{ $drawerClosedTranslateClass }}"
        class="fixed inset-y-0 z-[61] flex w-full max-w-2xl flex-col border-slate-200 bg-white shadow-2xl transition-transform duration-200 {{ $isRtl ? 'left-0 border-r -translate-x-full' : 'right-0 border-l translate-x-full' }}"
        aria-hidden="true">
        <div class="border-b border-slate-200 px-5 py-4 lg:px-6">
            <div class="flex items-start justify-between gap-4 rtl:flex-row-reverse">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">{{ $libraryTitle }}</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $libraryDescription }}
                    </p>
                </div>
                <button type="button" data-close-section-library
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50"
                    aria-label="{{ t('common.Close', 'Close') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
        <div class="border-b border-slate-200 px-5 py-4 lg:px-6">
            <div class="flex items-center gap-3 rtl:flex-row-reverse">
                <div class="relative flex-1">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="pointer-events-none absolute top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 ltr:left-3 rtl:right-3"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m21 21-4.35-4.35m0 0A7.5 7.5 0 1 0 6 6a7.5 7.5 0 0 0 10.65 10.65Z" />
                    </svg>
                    <input id="section-library-search" type="text" placeholder="{{ $librarySearchPlaceholder }}"
                        class="w-full rounded-full border border-slate-200 bg-white py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400 ltr:pl-10 ltr:pr-4 ltr:text-left rtl:pl-4 rtl:pr-10 rtl:text-right">
                </div>
            </div>
            @if ($isClientWorkspace)
                <div class="mt-4 rounded-3xl border border-sky-200 bg-sky-50/70 p-4">
                    <h4 class="text-sm font-semibold text-slate-900">{{ $clientLibraryIntroTitle }}</h4>
                    <div class="mt-3 space-y-2">
                        @foreach ($clientLibraryIntroSteps as $step)
                            <p class="text-sm leading-6 text-slate-600">{{ $step }}</p>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        <div class="workspace-scrollbar flex-1 overflow-y-auto px-5 py-5 lg:px-6">
            <div class="space-y-6">
                @foreach ($groupedTypes as $category => $items)
                    @php
                        $categoryMeta = $isClientWorkspace
                            ? ($clientLibraryCategoryMeta[$category] ?? [
                                'label' => \Illuminate\Support\Str::headline($category),
                                'description' => t('dashboard.Ready_Made_Blocks_Hint', 'Ready-made blocks for this part of your page.'),
                            ])
                            : null;
                    @endphp
                    <section data-library-group>
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div>
                                <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">
                                    {{ $isClientWorkspace ? $categoryMeta['label'] : \Illuminate\Support\Str::headline($category) }}</h4>
                                @if ($isClientWorkspace)
                                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ $categoryMeta['description'] }}</p>
                                @endif
                            </div>
                            <span class="text-xs text-slate-400">{{ count($items) }} {{ $isClientWorkspace ? t('dashboard.Blocks', 'blocks') : t('dashboard.Types', 'types') }}</span>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            @foreach ($items as $type => $meta)
                                @php
                                    $previewAsset = !empty($meta['preview_url'])
                                        ? $meta['preview_url']
                                        : (!empty($meta['preview']) && file_exists(public_path($meta['preview']))
                                            ? asset($meta['preview'])
                                            : null);
                                    $cardDescription = $meta['description'] ?? t('dashboard.No_Description_Provided', 'No description provided.');
                                    $cardType = $meta['type'] ?? $type;
                                    $cardVariant = $meta['variant'] ?? null;
                                    $cardSectionDefinitionId = $meta['section_definition_id'] ?? null;
                                @endphp
                                <form action="{{ $workspaceRouteFor('quick-store', [], false) }}"
                                    method="POST" data-library-item
                                    data-library-text="{{ \Illuminate\Support\Str::lower($meta['label'] . ' ' . ($meta['description'] ?? '') . ' ' . $category) }}">
                                    @csrf
                                    <input type="hidden" name="type" value="{{ $cardType }}">
                                    @if (filled($cardSectionDefinitionId))
                                        <input type="hidden" name="section_definition_id"
                                            value="{{ $cardSectionDefinitionId }}">
                                    @endif
                                    @if (filled($cardVariant))
                                        <input type="hidden" name="variant" value="{{ $cardVariant }}">
                                    @endif
                                    <button type="submit"
                                        class="group flex h-full w-full flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white transform-gpu transition duration-200 ease-out hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300 active:scale-[0.99] disabled:translate-y-0 disabled:shadow-none ltr:text-left rtl:text-right">
                                        @if ($previewAsset)
                                            <div class="aspect-[16/10] overflow-hidden bg-slate-100"><img
                                                    src="{{ $previewAsset }}" alt="{{ $meta['label'] }}"
                                                    class="h-full w-full object-cover transition duration-200 group-hover:scale-[1.02]"
                                                    loading="lazy"></div>
                                        @else
                                            <div
                                                class="flex aspect-[16/10] items-center justify-center bg-slate-100 text-slate-400">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M3.75 7.5A2.25 2.25 0 0 1 6 5.25h12A2.25 2.25 0 0 1 20.25 7.5v9A2.25 2.25 0 0 1 18 18.75H6A2.25 2.25 0 0 1 3.75 16.5v-9Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m3.75 15 4.72-4.72a1.5 1.5 0 0 1 2.12 0L15 14.69l1.28-1.28a1.5 1.5 0 0 1 2.12 0l1.85 1.84" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M14.25 8.25h.008v.008h-.008V8.25Z" />
                                                </svg>
                                            </div>
                                        @endif
                                        <div class="flex flex-1 flex-col p-4">
                                            <div class="flex items-start justify-between gap-3 rtl:flex-row-reverse">
                                                <div>
                                                    <h5 class="text-sm font-semibold text-slate-900">{{ $meta['label'] }}
                                                    </h5>
                                                    <p class="mt-1 text-xs leading-5 text-slate-500">
                                                        {{ $cardDescription }}</p>
                                                </div>
                                                <span
                                                    class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-600">{{ $isClientWorkspace ? $categoryMeta['label'] : \Illuminate\Support\Str::headline($category) }}</span>
                                            </div>
                                            <div class="mt-4 flex items-center justify-between text-xs text-slate-500">
                                                <span>{{ $libraryAddHint }}</span><span
                                                    class="js-library-submit-label inline-flex items-center gap-2 font-semibold text-slate-900">{{ $libraryAddActionLabel }}</span>
                                            </div>
                                        </div>
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </aside>
@endsection

@section('workspace-sidebar')
    <div data-sections-sidebar-outline class="space-y-5">
        @if ($pageBuilderMode !== 'sections' && ! $isClientWorkspace)
            <div class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                <h3 class="text-base font-semibold text-slate-900">{{ t('dashboard.Sections_Not_Active_Builder_Yet', 'Sections are not the active builder yet') }}
                </h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    {{ t('dashboard.Switch_To_Sections_Builder_Hint', 'This page is still marked with the archived Visual Builder mode. Switch it to Sections Builder so these sections appear on the frontend.') }}
                </p>
                @if (filled($workspaceBuilderModeUrl))
                    <form action="{{ $workspaceBuilderModeUrl }}" method="POST" class="mt-4">
                        @csrf
                        <input type="hidden" name="builder_mode" value="sections">
                        <button type="submit"
                            class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">{{ t('dashboard.Switch_To_Sections_Builder', 'Switch to Sections Builder') }}</button>
                    </form>
                @endif
            </div>
        @endif

        @if ($isClientWorkspace)
            <div class="rounded-3xl border {{ $pageIsLive ? 'border-emerald-200 bg-emerald-50/60' : 'border-amber-200 bg-amber-50/60' }} p-4 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2 rtl:flex-row-reverse">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] {{ $pageIsLive ? 'text-emerald-700' : 'text-amber-700' }}">{{ t('dashboard.Editing', 'Editing') }}</p>
                            <span class="inline-flex rounded-full border {{ $pageIsLive ? 'border-emerald-200 bg-white text-emerald-700' : 'border-amber-200 bg-white text-amber-700' }} px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.18em]">
                                {{ $publishStateBadge }}
                            </span>
                        </div>
                        <h3 class="mt-2 text-base font-semibold text-slate-900">{{ $sidebarIntroTitle }}</h3>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ $pageIsLive ? t('dashboard.Page_Already_Live_Hint', 'This page is already live. Save changes, then preview or open it when needed.') : t('dashboard.Page_Still_Draft_Hint', 'This page is still in draft. Use preview to review it before making it visible.') }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-3 rtl:flex-row-reverse">
                    <a href="{{ $previewDraftUrl }}" target="_blank"
                        class="inline-flex items-center rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        {{ $previewDraftLabel }}
                    </a>

                    @if ($pageIsLive)
                        <a href="{{ $frontUrl }}" target="_blank"
                            class="inline-flex items-center rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            {{ t('dashboard.Open_Live_Page', 'Open Live Page') }}
                        </a>
                    @endif
                </div>
            </div>
        @endif

        <div class="ltr:text-left rtl:text-right">
            <h3 class="text-xl font-semibold text-slate-900">{{ $pageTitle }}</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                {{ $pageStructureDescription }}</p>
            <button type="button" data-open-section-library
                class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-slate-700 transition hover:text-slate-900 rtl:flex-row-reverse">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500 rtl:rotate-180" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6 4.5 12l6 6M19.5 12h-15" />
                </svg>
                <span>{{ $isClientWorkspace ? t('dashboard.Need_New_Block_Open_Library', 'Need a new block? Open the block library') : t('dashboard.Need_Help_Open_Section_Library', 'Need help? Open the section library') }}</span>
            </button>
        </div>

        <div class="border-t border-slate-200 pt-5">
            <div class="space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <h4 class="text-lg font-semibold text-slate-900">{{ $pageStructureTitle }}</h4>
                    <span data-sections-count
                        class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $sections->count() }}</span>
                </div>

                <button type="button" data-open-section-library
                    class="flex w-full items-center justify-center gap-2 rounded-2xl border border-dashed border-emerald-300 bg-emerald-50/50 px-4 py-4 text-sm font-semibold text-emerald-700 transition hover:border-emerald-400 hover:bg-emerald-50 rtl:flex-row-reverse">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="1.9">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>{{ $addNewElementLabel }}</span>
                </button>

                <div id="sections-outline-list" class="space-y-3" data-sections-sortable-sidebar
                    data-reorder-url="{{ $workspaceRouteFor('reorder', [], false) }}">
                    @forelse ($sections as $section)
                        @include('dashboard.pages.sections.partials.outline-item', [
                            'page' => $page,
                            'section' => $section,
                            'sectionTypes' => $sectionTypes,
                            'currentLocale' => $currentLocale,
                            'selectedSectionId' => $selectedSectionId,
                        ])
                    @empty
                        <div data-sections-empty-state
                            class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                            {{ $noElementsLabel }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="border-t border-slate-200 pt-4">
            <div
                class="flex items-center justify-center rounded-2xl bg-emerald-100 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ $bottomTipLabel }}
            </div>
        </div>

        @if (! empty($brandSettingsUpdateUrl) && isset($brandSettingsTheme))
            <div class="border-t border-slate-200 pt-4">
                <button type="button" data-open-brand-settings
                    class="group flex w-full items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 rtl:flex-row-reverse">
                    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-600 transition group-hover:bg-violet-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42" />
                        </svg>
                    </span>
                    <span class="flex-1 ltr:text-left rtl:text-right">
                        {{ $isClientWorkspace ? t('dashboard.Brand_Settings', 'Brand Settings') : t('dashboard.Theme_Settings', 'Theme Settings') }}
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0 text-slate-400 ltr:rotate-180 rtl:rotate-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                    </svg>
                </button>
            </div>
        @endif
    </div>

    <div data-sections-sidebar-editor class="hidden h-full"></div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const workspaceShell = document.getElementById('sections-workspace-shell');
            const overlay = document.getElementById('section-library-overlay');
            const drawer = document.getElementById('section-library-drawer');
            const hiddenTranslateClass = drawer?.dataset.closedTranslate || 'translate-x-full';
            const openButtons = document.querySelectorAll('[data-open-section-library]');
            const closeButtons = document.querySelectorAll('[data-close-section-library]');
            const searchInput = document.getElementById('section-library-search');
            const libraryItems = Array.from(document.querySelectorAll('[data-library-item]'));
            const libraryGroups = Array.from(document.querySelectorAll('[data-library-group]'));
            const libraryForms = Array.from(document.querySelectorAll('form[data-library-item]'));
            const mainSortableList = document.getElementById('sections-workspace-list');
            const sidebarSortableList = document.querySelector('[data-sections-sortable-sidebar]');
            const sidebarCountBadge = document.querySelector('[data-sections-count]');
            const sidebarEmptyState = document.querySelector('[data-sections-empty-state]');
            const sidebarOutlinePanel = document.querySelector('[data-sections-sidebar-outline]');
            const sidebarEditorPanel = document.querySelector('[data-sections-sidebar-editor]');
            const previewShellHost = document.getElementById('sections-preview-shell-host');
            const previewLoadingOverlay = document.getElementById('sections-preview-loading-overlay');
            let previewFrame = document.getElementById('sections-preview-frame');
            let previewViewport = document.getElementById('sections-preview-viewport');
            const previewDeviceButtons = Array.from(document.querySelectorAll('[data-preview-device]'));
            const previewRefreshButtons = Array.from(document.querySelectorAll('[data-refresh-sections-preview]'));
            const sidebarOpenButton = document.getElementById('sections-sidebar-open-btn');
            const reorderUrl = sidebarSortableList?.dataset.reorderUrl || '';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const reorderFailedMessage = @json($isClientWorkspace ? t('dashboard.Block_Order_Could_Not_Be_Updated', 'Block order could not be updated. Please try again.') : t('dashboard.Section_Order_Could_Not_Be_Updated', 'Section order could not be updated. Please try again.'));
            const quickAddFailedMessage = @json($isClientWorkspace ? t('dashboard.Block_Could_Not_Be_Added', 'Block could not be added. Please try again.') : t('dashboard.Section_Could_Not_Be_Added', 'Section could not be added. Please try again.'));
            const quickAddLoadingLabel = @json($isClientWorkspace ? t('dashboard.Adding_Block', 'Adding block...') : t('dashboard.Adding', 'Adding...'));
            const editorOpenFailedMessage = @json($isClientWorkspace ? t('dashboard.Block_Settings_Could_Not_Be_Opened', 'Block settings could not be opened. Please try again.') : t('dashboard.Section_Editor_Could_Not_Be_Opened', 'Section editor could not be opened. Please try again.'));
            const editorSaveFailedMessage = @json($isClientWorkspace ? t('dashboard.Block_Could_Not_Be_Updated', 'Block could not be updated. Please review the form and try again.') : t('dashboard.Section_Could_Not_Be_Updated', 'Section could not be updated. Please review the form and try again.'));
            const editorLoadingLabel = @json($isClientWorkspace ? t('dashboard.Opening_Block_Settings', 'Opening block settings...') : t('dashboard.Loading_Editor', 'Loading editor...'));
            const editorSaveSuccessMessage = @json($isClientWorkspace ? t('dashboard.Block_Updated_Successfully', 'Block has been updated successfully.') : t('dashboard.Section_Updated_Successfully', 'Section has been updated successfully.'));
            const editorSavedLabel = @json(t('common.Saved', 'Saved'));
            const successAlertTitle = @json(t('common.Success', 'Success'));
            const errorAlertTitle = @json(t('common.Error', 'Error'));
            const validationAlertTitle = @json(t('common.Please_Review_The_Form', 'Please review the form'));
            const activeStatusLabel = @json($isClientWorkspace ? t('dashboard.Visible', 'Visible') : t('dashboard.Active', 'Active'));
            const hiddenStatusLabel = @json(t('dashboard.Hidden', 'Hidden'));
            const autoEditSectionId = Number(@json($autoEditSectionId));
            const frameBaseUrl = @json($previewBaseUrl);
            const previewFrameTitle = @json($previewFrameTitleLabel);
            let currentSelectedSectionId = Number(@json($selectedSectionId));
            let previewRefreshFallbackTimer = null;

            const showSectionsAlert = (tone, messages, title = '') => {
                const safeMessages = Array.isArray(messages) ? messages.filter(Boolean) : [];

                if (typeof window.sectionsShowAlert === 'function') {
                    window.sectionsShowAlert({
                        tone,
                        title: title || (tone === 'success' ? successAlertTitle : errorAlertTitle),
                        messages: safeMessages,
                    });
                    return true;
                }

                if (safeMessages.length > 0) {
                    window.alert(safeMessages.join('\n'));
                }

                return false;
            };

            const getSidebarSectionItems = () => Array.from(document.querySelectorAll(
                '#sections-outline-list [data-section-id]'));

            const syncSidebarOutlineState = () => {
                const itemCount = getSidebarSectionItems().length;

                if (sidebarCountBadge) {
                    sidebarCountBadge.textContent = String(itemCount);
                }

                if (sidebarEmptyState) {
                    sidebarEmptyState.classList.toggle('hidden', itemCount > 0);
                }
            };

            const setSidebarItemSelectedState = (item, isSelected) => {
                if (!(item instanceof HTMLElement)) {
                    return;
                }

                item.classList.toggle('is-selected', isSelected);
                item.classList.toggle('border-slate-300', isSelected);
                item.classList.toggle('bg-slate-50', isSelected);
                item.classList.toggle('shadow-lg', isSelected);
                item.classList.toggle('-translate-y-0.5', isSelected);
            };

            const clearSidebarItemFeedback = (item) => {
                if (!(item instanceof HTMLElement)) {
                    return;
                }

                item.classList.remove('ring-2', 'ring-emerald-200', 'ring-sky-200', 'ring-offset-2', 'shadow-xl');
            };

            const flashSidebarItem = (item, tone = 'created') => {
                if (!(item instanceof HTMLElement)) {
                    return;
                }

                const flashClasses = tone === 'saved'
                    ? ['ring-2', 'ring-sky-200', 'ring-offset-2', 'shadow-xl']
                    : ['ring-2', 'ring-emerald-200', 'ring-offset-2', 'shadow-xl'];

                clearSidebarItemFeedback(item);

                if (item.__sectionsFeedbackTimer) {
                    window.clearTimeout(item.__sectionsFeedbackTimer);
                }

                item.classList.add(...flashClasses);
                item.__sectionsFeedbackTimer = window.setTimeout(() => {
                    item.classList.remove(...flashClasses);
                }, 1800);
            };

            const applySidebarSelection = (sectionId) => {
                getSidebarSectionItems().forEach((item) => {
                    setSidebarItemSelectedState(item, Number(item.dataset.sectionId || 0) === sectionId);
                });
            };

            const setEditorMode = (isOpen) => {
                workspaceShell?.classList.toggle('is-section-editor-open', isOpen);
                sidebarOutlinePanel?.classList.toggle('hidden', isOpen);
                sidebarEditorPanel?.classList.toggle('hidden', !isOpen);
            };

            const ensureSidebarVisible = () => {
                if (!workspaceShell?.classList.contains('is-sidebar-collapsed')) {
                    return;
                }

                if (sidebarOpenButton instanceof HTMLElement) {
                    sidebarOpenButton.click();
                    return;
                }

                workspaceShell.classList.remove('is-sidebar-collapsed');

                try {
                    window.localStorage.setItem('sections-workspace-sidebar-collapsed', '0');
                } catch (error) {
                    // Ignore storage failures and keep the UI responsive.
                }
            };

            const updateWorkspaceUrl = (editSectionId = null) => {
                const url = new URL(window.location.href);

                if (currentSelectedSectionId) {
                    url.searchParams.set('highlight', String(currentSelectedSectionId));
                } else {
                    url.searchParams.delete('highlight');
                }

                if (editSectionId) {
                    url.searchParams.set('edit', String(editSectionId));
                } else {
                    url.searchParams.delete('edit');
                }

                window.history.replaceState({}, '', url.toString());
            };

            const renderEditorFeedback = (root, tone, messages) => {
                const feedback = root?.querySelector('[data-section-editor-feedback]');
                const list = root?.querySelector('[data-section-editor-feedback-list]');
                const safeMessages = Array.isArray(messages) ? messages.filter(Boolean) : [];

                if (safeMessages.length === 0) {
                    if (list) {
                        list.replaceChildren();
                        list.classList.add('hidden');
                    }
                    if (feedback) {
                        feedback.classList.add('hidden');
                        feedback.classList.remove('border-red-200', 'bg-red-50', 'text-red-800',
                            'border-emerald-200', 'bg-emerald-50', 'text-emerald-800');
                    }
                    return;
                }

                if (showSectionsAlert(
                        tone,
                        safeMessages,
                        tone === 'error' ? validationAlertTitle : successAlertTitle
                    )) {
                    if (list) {
                        list.replaceChildren();
                        list.classList.add('hidden');
                    }
                    if (feedback) {
                        feedback.classList.add('hidden');
                        feedback.classList.remove('border-red-200', 'bg-red-50', 'text-red-800',
                            'border-emerald-200', 'bg-emerald-50', 'text-emerald-800');
                    }
                    return;
                }

                if (!feedback || !list) {
                    return;
                }

                list.replaceChildren();
                safeMessages.forEach((message) => {
                    const item = document.createElement('li');
                    item.textContent = message;
                    list.appendChild(item);
                });
                list.classList.remove('hidden');
                feedback.classList.remove('hidden');
                feedback.classList.remove('border-red-200', 'bg-red-50', 'text-red-800', 'border-emerald-200',
                    'bg-emerald-50', 'text-emerald-800');
                feedback.classList.add(
                    tone === 'error' ? 'border-red-200' : 'border-emerald-200',
                    tone === 'error' ? 'bg-red-50' : 'bg-emerald-50',
                    tone === 'error' ? 'text-red-800' : 'text-emerald-800'
                );
            };

            const updateSidebarSectionCard = (sectionData) => {
                if (!sectionData?.id) {
                    return null;
                }

                const article = document.querySelector(
                    `#sections-outline-list [data-section-id="${sectionData.id}"]`);
                if (!article) {
                    return null;
                }

                const title = article.querySelector('[data-section-title]');
                const typeLabel = article.querySelector('[data-section-type-label]');
                const status = article.querySelector('[data-section-status]');

                if (title && sectionData.title) {
                    title.textContent = sectionData.title;
                }

                if (typeLabel && sectionData.type_label) {
                    typeLabel.textContent = sectionData.type_label;
                }

                if (status) {
                    status.textContent = sectionData.is_active ? activeStatusLabel : hiddenStatusLabel;
                    status.classList.remove('bg-emerald-50', 'text-emerald-700', 'bg-rose-50', 'text-rose-700');
                    status.classList.add(sectionData.is_active ? 'bg-emerald-50' : 'bg-rose-50');
                    status.classList.add(sectionData.is_active ? 'text-emerald-700' : 'text-rose-700');
                }

                return article;
            };

            const createSidebarSectionItem = (sectionCardHtml) => {
                const template = document.createElement('template');
                template.innerHTML = String(sectionCardHtml || '').trim();

                const article = template.content.firstElementChild;

                return article instanceof HTMLElement && article.matches('[data-section-id]') ? article : null;
            };

            const postPreviewHighlight = (sectionId) => {
                if (!previewFrame?.contentWindow || !sectionId) {
                    return;
                }

                previewFrame.contentWindow.postMessage({
                    type: 'sections-preview:highlight',
                    sectionId: sectionId,
                }, window.location.origin);
            };

            const setPreviewRefreshing = (isRefreshing) => {
                if (previewRefreshFallbackTimer) {
                    window.clearTimeout(previewRefreshFallbackTimer);
                    previewRefreshFallbackTimer = null;
                }

                if (previewLoadingOverlay) {
                    previewLoadingOverlay.classList.toggle('hidden', !isRefreshing);
                    previewLoadingOverlay.classList.toggle('flex', isRefreshing);
                }

                previewRefreshButtons.forEach((button) => {
                    button.disabled = isRefreshing;
                    button.classList.toggle('opacity-70', isRefreshing);
                    button.classList.toggle('pointer-events-none', isRefreshing);
                });

            };

            const bindPreviewFrameLoadHandler = () => {
                if (!previewFrame || previewFrame.dataset.previewLoadBound === '1') {
                    return;
                }

                previewFrame.addEventListener('load', function() {
                    setPreviewRefreshing(false);

                    if (currentSelectedSectionId) {
                        window.setTimeout(() => postPreviewHighlight(currentSelectedSectionId), 120);
                    }
                });

                previewFrame.addEventListener('error', function() {
                    setPreviewRefreshing(false);
                });

                previewFrame.dataset.previewLoadBound = '1';
            };

            const getActivePreviewDevice = () => {
                return previewViewport?.dataset.device
                    || previewDeviceButtons.find((button) => button.classList.contains('is-active'))?.dataset.previewDevice
                    || 'desktop';
            };

            const ensurePreviewFrame = () => {
                if (previewFrame && previewViewport) {
                    return true;
                }

                if (!previewShellHost || !frameBaseUrl) {
                    return false;
                }

                const activeDevice = getActivePreviewDevice();
                previewShellHost.innerHTML = `
                    <div id="sections-preview-stage" class="sections-preview-stage">
                        <div id="sections-preview-viewport" class="sections-preview-viewport" data-device="${activeDevice}">
                            <iframe id="sections-preview-frame" class="sections-preview-frame" data-base-url="${frameBaseUrl}" title="${previewFrameTitle}"></iframe>
                        </div>
                    </div>
                `;

                previewFrame = document.getElementById('sections-preview-frame');
                previewViewport = document.getElementById('sections-preview-viewport');
                bindPreviewFrameLoadHandler();
                applyPreviewDevice(activeDevice);

                return Boolean(previewFrame && previewViewport);
            };

            const refreshPreviewFrame = () => {
                setPreviewRefreshing(true);

                if (!frameBaseUrl || !ensurePreviewFrame() || !previewFrame) {
                    setPreviewRefreshing(false);
                    return;
                }

                const url = new URL(frameBaseUrl, window.location.origin);
                if (currentSelectedSectionId) {
                    url.searchParams.set('highlight', String(currentSelectedSectionId));
                }

                previewRefreshFallbackTimer = window.setTimeout(() => {
                    setPreviewRefreshing(false);
                }, 12000);
                previewFrame.src = url.toString();
            };

            const focusSectionPreview = (sectionId, shouldReload = false) => {
                if (!sectionId) {
                    return;
                }

                currentSelectedSectionId = Number(sectionId);
                applySidebarSelection(currentSelectedSectionId);

                if (shouldReload) {
                    refreshPreviewFrame();
                    return;
                }

                postPreviewHighlight(currentSelectedSectionId);
            };

            const closeSectionEditor = () => {
                setEditorMode(false);
                if (sidebarEditorPanel) {
                    sidebarEditorPanel.innerHTML = '';
                }
                updateWorkspaceUrl(null);
            };

            const resetEditorSubmitButtons = (buttons) => {
                buttons.forEach((button) => {
                    if (!(button instanceof HTMLElement)) {
                        return;
                    }

                    if (!button.dataset.defaultLabelHtml) {
                        button.dataset.defaultLabelHtml = button.innerHTML;
                    }

                    if (button.__editorSuccessTimer) {
                        window.clearTimeout(button.__editorSuccessTimer);
                        button.__editorSuccessTimer = null;
                    }

                    button.innerHTML = button.dataset.defaultLabelHtml;
                    button.classList.remove('bg-emerald-600', 'hover:bg-emerald-600', 'shadow-lg');
                    button.classList.add('bg-slate-900', 'hover:bg-slate-800');
                });
            };

            const flashEditorSaveSuccess = (root) => {
                Array.from(root?.querySelectorAll('[data-section-editor-submit]') || []).forEach((button) => {
                    if (!(button instanceof HTMLElement)) {
                        return;
                    }

                    if (!button.dataset.defaultLabelHtml) {
                        button.dataset.defaultLabelHtml = button.innerHTML;
                    }

                    if (button.__editorSuccessTimer) {
                        window.clearTimeout(button.__editorSuccessTimer);
                    }

                    button.innerHTML = editorSavedLabel;
                    button.classList.remove('bg-slate-900', 'hover:bg-slate-800');
                    button.classList.add('bg-emerald-600', 'hover:bg-emerald-600', 'shadow-lg');
                    button.__editorSuccessTimer = window.setTimeout(() => {
                        resetEditorSubmitButtons([button]);
                    }, 1600);
                });
            };

            const bindSectionEditor = (root, sectionId) => {
                if (!root) {
                    return;
                }

                const form = root.querySelector('[data-section-editor-form]');
                if (!form) {
                    return;
                }

                let isSavingEditor = false;
                const handleEditorSave = async () => {
                    if (isSavingEditor) {
                        return;
                    }

                    isSavingEditor = true;
                    const submitButtons = Array.from(root.querySelectorAll(
                        '[data-section-editor-submit]'));
                    resetEditorSubmitButtons(submitButtons);
                    submitButtons.forEach((button) => {
                        button.disabled = true;
                        button.classList.add('opacity-70', 'pointer-events-none');
                    });

                    renderEditorFeedback(root, 'success', []);

                    try {
                        const formData = new FormData(form);
                        const saveUrl = form.dataset.saveAction || form.action;
                        const methodOverride = String(formData.get('_method') || 'PUT').toUpperCase();

                        const response = await fetch(saveUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-HTTP-Method-Override': methodOverride,
                            },
                            body: formData,
                        });

                        let payload = {};
                        let payloadParsed = false;
                        try {
                            payload = await response.json();
                            payloadParsed = true;
                        } catch (_) {
                            // Server returned non-JSON (e.g. session-expired redirect
                            // to login page, or a server-level 502/504 error page).
                            // Fall through with empty payload.
                        }

                        if (response.status === 419 || (!payloadParsed && !response.ok)) {
                            // 419 = CSRF / session expired. Non-JSON non-ok = server-level error.
                            throw new Error(@json(t('dashboard.Session_May_Have_Expired', 'Your session may have expired. Please refresh the page and try again.')));
                        }

                        if (response.status === 422) {
                            const validationMessages = Object.values(payload.errors || {}).flat();
                            renderEditorFeedback(root, 'error', validationMessages.length ?
                                validationMessages : [editorSaveFailedMessage]);
                            return;
                        }

                        if (!response.ok || !payload.ok) {
                            throw new Error(payload.message || 'editor_save_failed');
                        }

                        renderEditorFeedback(root, 'success', [payload.message ||
                            editorSaveSuccessMessage
                        ]);
                        const updatedSidebarItem = updateSidebarSectionCard(payload.section || {});
                        flashSidebarItem(updatedSidebarItem, 'saved');
                        flashEditorSaveSuccess(root);
                        focusSectionPreview(Number(payload.section?.id || sectionId), true);

                        const editorHeading = root.querySelector('[data-section-editor-heading]');
                        const editorType = root.querySelector('[data-section-editor-type]');
                        const editorStatus = root.querySelector('[data-section-editor-status]');

                        if (editorHeading && payload.section?.title) {
                            editorHeading.textContent = payload.section.title;
                        }

                        if (editorType && payload.section?.type_label) {
                            editorType.textContent = payload.section.type_label;
                        }

                        if (editorStatus) {
                            editorStatus.textContent = payload.section?.is_active ? activeStatusLabel :
                                hiddenStatusLabel;
                            editorStatus.classList.remove('bg-emerald-50', 'text-emerald-700',
                                'bg-rose-50', 'text-rose-700');
                            editorStatus.classList.add(payload.section?.is_active ? 'bg-emerald-50' :
                                'bg-rose-50');
                            editorStatus.classList.add(payload.section?.is_active ? 'text-emerald-700' :
                                'text-rose-700');
                        }
                    } catch (error) {
                        renderEditorFeedback(root, 'error', [
                            error?.message && error.message !== 'editor_save_failed' ?
                            error.message :
                            editorSaveFailedMessage,
                        ]);
                    } finally {
                        isSavingEditor = false;
                        submitButtons.forEach((button) => {
                            button.disabled = false;
                            button.classList.remove('opacity-70', 'pointer-events-none');
                        });
                    }
                };

                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    event.stopImmediatePropagation();
                    handleEditorSave();
                }, true);

                root.querySelectorAll('[data-section-editor-submit]').forEach((button) => {
                    button.addEventListener('click', function(event) {
                        event.preventDefault();
                        event.stopPropagation();
                        event.stopImmediatePropagation();
                        handleEditorSave();
                    });
                });

                const runEditorInitializer = (initializer) => {
                    if (typeof initializer !== 'function') {
                        return;
                    }

                    try {
                        initializer(root);
                    } catch (error) {
                        console.error('Section editor initializer failed.', error);
                    }
                };

                runEditorInitializer(window.initSectionEditorTabs);
                runEditorInitializer(window.initSectionFeatureRepeaters);
                runEditorInitializer(window.initSectionOutputRepeaters);
                runEditorInitializer(window.initSectionServiceRepeaters);
                runEditorInitializer(window.initBuildStepRepeaters);
                runEditorInitializer(window.initReviewRepeaters);
                runEditorInitializer(window.initDynamicRepeaters);

                root.querySelectorAll('[data-close-section-editor]').forEach((button) => {
                    button.addEventListener('click', closeSectionEditor);
                });
            };

            const openSectionEditor = async (sectionId, editorUrl, fallbackUrl = '', shouldPushState = true) => {
                if (!sidebarEditorPanel || !editorUrl) {
                    if (fallbackUrl) {
                        window.location.assign(fallbackUrl);
                    }
                    return;
                }

                currentSelectedSectionId = Number(sectionId || 0);
                applySidebarSelection(currentSelectedSectionId);
                closeAllSectionMenus();
                closeAllRenamePanels();
                setEditorMode(true);
                sidebarEditorPanel.innerHTML =
                    `<div class="sections-editor-loading">${editorLoadingLabel}</div>`;

                if (shouldPushState) {
                    updateWorkspaceUrl(currentSelectedSectionId);
                }

                try {
                    const response = await fetch(editorUrl, {
                        headers: {
                            'Accept': 'text/html',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('editor_open_failed');
                    }

                    sidebarEditorPanel.innerHTML = await response.text();
                    bindSectionEditor(sidebarEditorPanel, currentSelectedSectionId);
                } catch (error) {
                    setEditorMode(false);
                    sidebarEditorPanel.innerHTML = '';

                    if (fallbackUrl) {
                        window.location.assign(fallbackUrl);
                        return;
                    }

                    showSectionsAlert('error', [editorOpenFailedMessage], errorAlertTitle);
                }
            };

            const applyPreviewDevice = (device) => {
                if (!previewViewport) {
                    return;
                }

                previewViewport.dataset.device = device;

                previewDeviceButtons.forEach((button) => {
                    button.classList.toggle('is-active', button.dataset.previewDevice === device);
                });
            };

            const closeAllSectionMenus = () => {
                document.querySelectorAll('[data-section-menu]').forEach((menu) => {
                    menu.classList.add('hidden');
                });

                document.querySelectorAll('[data-section-id].is-menu-open').forEach((item) => {
                    item.classList.remove('is-menu-open');
                });

                document.querySelectorAll('[data-section-menu-button]').forEach((button) => {
                    button.setAttribute('aria-expanded', 'false');
                });
            };

            const closeAllRenamePanels = () => {
                document.querySelectorAll('[data-rename-panel]').forEach((panel) => {
                    panel.classList.add('hidden');
                });
            };

            const bindSidebarSectionItem = (item) => {
                if (!(item instanceof HTMLElement) || item.dataset.sectionItemBound === '1') {
                    return;
                }

                item.querySelector('[data-edit-section-button]')?.addEventListener('click', function(event) {
                    event.preventDefault();
                    event.stopPropagation();

                    const sectionId = Number(item.dataset.sectionId || 0);
                    const editorUrl = item.dataset.editSectionUrl || '';
                    const fallbackUrl = item.dataset.editSectionFallbackUrl || '';

                    if (!sectionId) {
                        return;
                    }

                    openSectionEditor(sectionId, editorUrl, fallbackUrl, true);
                });

                item.querySelector('[data-section-menu-button]')?.addEventListener('click', function(event) {
                    event.stopPropagation();

                    const menu = item.querySelector('[data-section-menu]');
                    if (!menu) {
                        return;
                    }

                    const isHidden = menu.classList.contains('hidden');

                    closeAllSectionMenus();

                    if (isHidden) {
                        menu.classList.remove('hidden');
                        item.classList.add('is-menu-open');
                        this.setAttribute('aria-expanded', 'true');
                    }
                });

                item.querySelector('[data-rename-toggle]')?.addEventListener('click', function(event) {
                    event.preventDefault();

                    const panel = item.querySelector('[data-rename-panel]');
                    const input = panel?.querySelector('[data-rename-input]');
                    const menu = item.querySelector('[data-section-menu]');
                    const menuButton = item.querySelector('[data-section-menu-button]');

                    if (!panel) {
                        return;
                    }

                    closeAllRenamePanels();

                    if (menu) {
                        menu.classList.add('hidden');
                    }

                    if (menuButton) {
                        menuButton.setAttribute('aria-expanded', 'false');
                    }

                    item.classList.remove('is-menu-open');

                    panel.classList.remove('hidden');

                    if (input) {
                        window.setTimeout(() => {
                            input.focus();
                            input.select();
                        }, 30);
                    }
                });

                item.querySelector('[data-rename-cancel]')?.addEventListener('click', function() {
                    item.querySelector('[data-rename-panel]')?.classList.add('hidden');
                });

                item.addEventListener('click', function(event) {
                    if (event.target.closest(
                            'a, button, form, input, textarea, select, [data-section-menu], [data-rename-panel]'
                        )) {
                        return;
                    }

                    focusSectionPreview(Number(item.dataset.sectionId || 0));
                });

                item.dataset.sectionItemBound = '1';
            };

            const upsertSidebarSectionCard = (sectionId, sectionCardHtml) => {
                if (!sectionId || !sidebarSortableList || !sectionCardHtml) {
                    return null;
                }

                const nextItem = createSidebarSectionItem(sectionCardHtml);
                if (!nextItem) {
                    return null;
                }

                const existingItem = sidebarSortableList.querySelector(`[data-section-id="${sectionId}"]`);

                if (existingItem) {
                    existingItem.replaceWith(nextItem);
                } else {
                    sidebarSortableList.appendChild(nextItem);
                }

                bindSidebarSectionItem(nextItem);
                syncSidebarOutlineState();
                flashSidebarItem(nextItem, existingItem ? 'saved' : 'created');

                return nextItem;
            };

            const openDrawer = () => {
                if (!overlay || !drawer) return;
                overlay.classList.remove('hidden');
                drawer.classList.remove(hiddenTranslateClass);
                drawer.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');
                if (searchInput) window.setTimeout(() => searchInput.focus(), 80);
            };

            const closeDrawer = () => {
                if (!overlay || !drawer) return;
                overlay.classList.add('hidden');
                drawer.classList.add(hiddenTranslateClass);
                drawer.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
            };

            openButtons.forEach((button) => button.addEventListener('click', openDrawer));
            closeButtons.forEach((button) => button.addEventListener('click', closeDrawer));
            if (overlay) overlay.addEventListener('click', closeDrawer);

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeDrawer();
                    closeAllSectionMenus();
                    closeAllRenamePanels();
                    if (!sidebarEditorPanel?.classList.contains('hidden')) {
                        closeSectionEditor();
                    }
                }
            });

            getSidebarSectionItems().forEach(bindSidebarSectionItem);

            previewRefreshButtons.forEach((button) => {
                button.addEventListener('click', function() {
                    refreshPreviewFrame();
                });
            });

            previewDeviceButtons.forEach((button) => {
                button.addEventListener('click', function() {
                    applyPreviewDevice(button.dataset.previewDevice || 'desktop');
                });
            });

            bindPreviewFrameLoadHandler();

            window.addEventListener('message', function(event) {
                if (event.origin !== window.location.origin) {
                    return;
                }

                const payload = event.data || {};
                if (payload.type === 'sections-preview:selected') {
                    const sectionId = Number(payload.sectionId || 0);
                    if (!sectionId) {
                        return;
                    }

                    currentSelectedSectionId = sectionId;
                    applySidebarSelection(sectionId);

                    const selectedSidebarItem = document.querySelector(
                        `#sections-outline-list [data-section-id="${sectionId}"]`);
                    selectedSidebarItem?.scrollIntoView({
                        block: 'nearest',
                        behavior: 'smooth',
                    });
                }
            });

            document.addEventListener('click', function(event) {
                if (!event.target.closest('[data-section-menu-button]') && !event.target.closest(
                        '[data-section-menu]')) {
                    closeAllSectionMenus();
                }
            });

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const query = searchInput.value.trim().toLowerCase();
                    libraryItems.forEach((item) => {
                        const haystack = item.getAttribute('data-library-text') || '';
                        item.classList.toggle('hidden', !(query === '' || haystack.includes(
                            query)));
                    });
                    libraryGroups.forEach((group) => {
                        const visibleItems = group.querySelectorAll(
                            '[data-library-item]:not(.hidden)');
                        group.classList.toggle('hidden', visibleItems.length === 0);
                    });
                });
            }

            libraryForms.forEach((form) => {
                form.addEventListener('submit', async function(event) {
                    event.preventDefault();

                    const submitButton = form.querySelector('button[type="submit"]');
                    const submitLabel = submitButton?.querySelector('.js-library-submit-label');
                    const originalButtonHtml = submitButton?.innerHTML || '';
                    const loadingIndicator = `
                        <span class="inline-flex items-center gap-2">
                            <span class="inline-flex h-3.5 w-3.5 animate-spin rounded-full border-2 border-slate-300 border-t-slate-900"></span>
                            <span>${quickAddLoadingLabel}</span>
                        </span>
                    `;

                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.classList.add('opacity-70', 'pointer-events-none', 'border-slate-300',
                            'bg-slate-50', 'shadow-lg');
                        submitButton.setAttribute('aria-busy', 'true');
                    }

                    if (submitLabel) {
                        submitLabel.innerHTML = loadingIndicator;
                    } else if (submitButton) {
                        submitButton.innerHTML = loadingIndicator;
                    }

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: new FormData(form),
                        });

                        const payload = await response.json().catch(() => ({}));

                        if (!response.ok || !payload.redirect_url || !payload.editor_url || !
                            payload.section_id || !payload.section_card_html) {
                            throw new Error(payload.message || 'quick_add_failed');
                        }

                        const insertedItem = upsertSidebarSectionCard(payload.section_id,
                            payload
                            .section_card_html);

                        currentSelectedSectionId = Number(payload.section_id);
                        applySidebarSelection(currentSelectedSectionId);
                        insertedItem?.scrollIntoView({
                            block: 'nearest',
                            behavior: 'smooth',
                        });

                        closeDrawer();
                        ensureSidebarVisible();
                        refreshPreviewFrame();
                        await openSectionEditor(payload.section_id, payload.editor_url, payload
                            .redirect_url, true);
                        focusSectionPreview(Number(payload.section_id));

                        if (payload.message) {
                            showSectionsAlert('success', [payload.message], successAlertTitle);
                        }
                    } catch (error) {
                        showSectionsAlert(
                            'error',
                            [
                                error?.message && error.message !== 'quick_add_failed' ?
                                error.message :
                                quickAddFailedMessage,
                            ],
                            errorAlertTitle
                        );
                    } finally {
                        if (submitButton) {
                            submitButton.disabled = false;
                            submitButton.classList.remove('opacity-70', 'pointer-events-none',
                                'border-slate-300', 'bg-slate-50', 'shadow-lg');
                            submitButton.removeAttribute('aria-busy');
                            submitButton.innerHTML = originalButtonHtml;
                        }
                    }
                });
            });

            if (typeof Sortable !== 'undefined' && reorderUrl) {
                const collectIds = (list) => {
                    if (!list) {
                        return [];
                    }

                    return Array.from(list.children)
                        .filter((item) => item instanceof HTMLElement && item.matches('[data-section-id]'))
                        .map((item) => Number(item.getAttribute('data-section-id') || 0))
                        .filter((id, index, ids) => id > 0 && ids.indexOf(id) === index);
                };

                const syncListOrder = (list, ids) => {
                    if (!list) return;

                    ids.forEach((id, index) => {
                        const item = list.querySelector(`[data-section-id="${id}"]`);
                        if (!item) return;

                        list.appendChild(item);

                        item.querySelectorAll('[data-section-order]').forEach((badge) => {
                            badge.textContent = String(index + 1);
                        });
                    });
                };

                let committedOrder = collectIds(sidebarSortableList || mainSortableList);

                const persistReorder = async (ids) => {
                    const response = await fetch(reorderUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            ids
                        }),
                    });

                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        throw new Error(payload.message || 'reorder_failed');
                    }

                    return payload;
                };

                const handleSortEnd = async (sourceList) => {
                    const ids = collectIds(sourceList);

                    syncListOrder(mainSortableList, ids);
                    syncListOrder(sidebarSortableList, ids);

                    try {
                        await persistReorder(ids);
                        committedOrder = ids;
                        refreshPreviewFrame();
                    } catch (error) {
                        syncListOrder(mainSortableList, committedOrder);
                        syncListOrder(sidebarSortableList, committedOrder);
                        showSectionsAlert(
                            'error',
                            [
                                error?.message && error.message !== 'reorder_failed' ?
                                error.message :
                                reorderFailedMessage,
                            ],
                            errorAlertTitle
                        );
                    }
                };

                const sortableOptions = (sourceList) => ({
                    animation: 180,
                    easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                    handle: '[data-drag-handle]',
                    ghostClass: 'sections-sortable-ghost',
                    chosenClass: 'sections-sortable-chosen',
                    dragClass: 'sections-sortable-drag',
                    onEnd: () => handleSortEnd(sourceList),
                });

                if (sidebarSortableList) {
                    Sortable.create(sidebarSortableList, sortableOptions(sidebarSortableList));
                }
            }

            applySidebarSelection(currentSelectedSectionId);
            syncSidebarOutlineState();
            applyPreviewDevice('desktop');

            if (autoEditSectionId) {
                const autoEditItem = document.querySelector(
                    `#sections-outline-list [data-section-id="${autoEditSectionId}"]`);
                if (autoEditItem) {
                    openSectionEditor(
                        autoEditSectionId,
                        autoEditItem.dataset.editSectionUrl || '',
                        autoEditItem.dataset.editSectionFallbackUrl || '',
                        false
                    );
                }
            }
        });
    </script>
@endpush
