{{-- Main orchestrator setup --}}
@php
    use App\Support\Sections\SectionMediaPreviewBuilder;

    $workspaceRoutePrefix = $workspaceRoutePrefix ?? 'dashboard.pages.sections.';
    $workspaceRouteBaseParameters = $workspaceRouteBaseParameters ?? ['page' => $page];
    $workspaceRouteFor =
        $workspaceRouteFor ??
        fn(string $name, array $extra = [], bool $absolute = true) => route(
            $workspaceRoutePrefix . $name,
            array_merge($workspaceRouteBaseParameters, $extra),
            $absolute,
        );
    $formId = $formId ?? 'section-edit-form';
    $formAction = $formAction ?? $workspaceRouteFor('update', ['section' => $section], false);
    $saveAction = $saveAction ?? $formAction;
    $formClass = $formClass ?? 'space-y-6';
    $formMethod = $formMethod ?? 'POST';
    $formMethodSpoof = $formMethodSpoof ?? 'PUT';
    $preventNativeSubmit = $preventNativeSubmit ?? false;
    $surfaceClass = $surfaceClass ?? 'rounded-3xl border border-slate-200 bg-white shadow-sm';
    $sectionHeaderClass = $sectionHeaderClass ?? 'border-b border-slate-200 px-5 py-4 lg:px-6';
    $sectionBodyClass = $sectionBodyClass ?? 'p-5 lg:p-6';
    $settingsGridClass = $settingsGridClass ?? 'grid grid-cols-1 gap-5 lg:grid-cols-2';
    $contentGridClass = $contentGridClass ?? 'grid grid-cols-1 gap-5 lg:grid-cols-2';
    $showOrderField = $showOrderField ?? true;
    $feedbackMessage = $feedbackMessage ?? null;
    $feedbackTone = $feedbackTone ?? 'success';
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();
    $editorState = $editorState ?? [];
    $editorDefaultLocale = $editorState['defaultLocale'] ?? app()->getLocale();
    $mediaPreviewBuilder = app(SectionMediaPreviewBuilder::class);
    $workspaceMode = $workspaceMode ?? 'admin';
    $isClientWorkspace = $workspaceMode === 'client';
    $displayOrderLabel = $isClientWorkspace ? __('Block Order') : __('Display Order');
    $displayOrderHelp = $isClientWorkspace ? __('Lower numbers appear earlier on the page.') : null;
    $activeToggleLabel = $isClientWorkspace ? __('Show this block on your website') : __('Active on frontend');
    $contentSectionLabel = $isClientWorkspace ? __('Block Content') : __('Section Content');
    $contentSectionHelp = $isClientWorkspace
        ? __('Update the text, media, and settings for this block in each language.')
        : __('Edit localized content for each language.');
    $footerEditorConfig = is_array($editorState['footerEditorConfig'] ?? null)
        ? $editorState['footerEditorConfig']
        : [];
    $contentGroupLabel = __('Content');
    $footerLinksGroupLabel = (string) ($footerEditorConfig['footerLinksGroupLabel'] ?? __('Footer Links'));
    $socialLinksGroupLabel = (string) ($footerEditorConfig['socialLinksGroupLabel'] ?? __('Social Links'));
    $footerLinksFieldLabel = (string) ($footerEditorConfig['footerLinksFieldLabel'] ?? __('Footer Links'));
    $footerLinksItemLabel = (string) ($footerEditorConfig['footerLinksItemLabel'] ?? __('Link'));
    $copyrightFieldLabel = (string) ($footerEditorConfig['copyrightFieldLabel'] ?? __('Copyright Line'));
    $socialFieldLabels = is_array($footerEditorConfig['socialFieldLabels'] ?? null)
        ? $footerEditorConfig['socialFieldLabels']
        : [];
@endphp

<form id="{{ $formId }}" method="{{ strtoupper($formMethod) }}" action="{{ $formAction }}"
    class="{{ $formClass }}" data-section-editor-form data-section-id="{{ $section->id }}"
    data-default-editor-tab="lang-{{ $editorDefaultLocale }}" data-save-action="{{ $saveAction }}"
    @if ($preventNativeSubmit) onsubmit="return false;" @endif>
    @csrf
    @if ($formMethodSpoof)
        @method($formMethodSpoof)
    @endif

    @php
        $feedbackVisible = $viewErrors->any() || filled($feedbackMessage);
        $feedbackClasses =
            $feedbackTone === 'error'
                ? 'border-red-200 bg-red-50 text-red-800'
                : 'border-emerald-200 bg-emerald-50 text-emerald-800';
        $selectedType = $editorState['selectedType'] ?? $section->type;
        $isShellHeader = $selectedType === 'site_header';
        $isShellFooter = $selectedType === 'site_footer';
        $showShellPrimaryButtonFields = $isShellHeader;
        $showShellMainTitleField = $isShellHeader;
        $showShellFooterLinksField = $isShellFooter;
        $showShellFooterSocialFields = $isShellFooter;
        $selectedFooterVariant = old('variant', $section->variant ?: 'simple_social');
    @endphp

    {{-- Editor feedback surface --}}
    <div data-section-editor-feedback
        class="hidden rounded-2xl border px-4 py-3 text-sm {{ $feedbackVisible ? $feedbackClasses : 'border-slate-200 bg-slate-50 text-slate-600' }}">
        <ul class="{{ $feedbackVisible ? '' : 'hidden ' }}space-y-1" data-section-editor-feedback-list>
            @if (filled($feedbackMessage))
                <li>{{ $feedbackMessage }}</li>
            @endif

            @foreach ($viewErrors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>

    {{-- Form settings surface --}}
    <div class="{{ $surfaceClass }}">
        <div class="{{ $sectionBodyClass }}">
            <input type="hidden" name="type" value="{{ $selectedType }}">
            <input type="hidden" name="section_definition_id" value="{{ $section->section_definition_id }}">

            <div class="{{ $settingsGridClass }}">
                @if ($isShellFooter)
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('Footer Layout') }}</label>
                        <select name="variant"
                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                            <option value="simple_social"
                                {{ $selectedFooterVariant === 'simple_social' ? 'selected' : '' }}>
                                {{ __('Social icons + copyright') }}
                            </option>
                            <option value="links_social"
                                {{ $selectedFooterVariant === 'links_social' ? 'selected' : '' }}>
                                {{ __('Links + social icons + copyright') }}
                            </option>
                        </select>
                        <p class="mt-2 text-xs text-slate-500">
                            {{ __('Choose the footer style the client wants. The links field below is used only in the links layout.') }}
                        </p>
                    </div>
                @else
                    <input type="hidden" name="variant" value="{{ old('variant', $section->variant) }}">
                @endif

                @if ($showOrderField)
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ $displayOrderLabel }}</label>
                        <input type="number" name="order" value="{{ old('order', $section->order ?? 1) }}"
                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                        @if (filled($displayOrderHelp))
                            <p class="mt-2 text-xs text-slate-500">{{ $displayOrderHelp }}</p>
                        @endif
                    </div>
                @endif

                <div class="flex items-center">
                    <label
                        class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                        <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300"
                            {{ old('is_active', $section->is_active) ? 'checked' : '' }}>
                        {{ $activeToggleLabel }}
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- Localized content surface --}}
    <div class="{{ $surfaceClass }}">
        <div class="{{ $sectionHeaderClass }}">
            <h2 class="text-lg font-semibold text-slate-900">{{ $contentSectionLabel }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $contentSectionHelp }}</p>
        </div>

        <div class="{{ $sectionBodyClass }}">
            {{-- Per-language editor navigation --}}
            <div class="mb-5 border-b border-slate-200">
                <nav class="-mb-px flex flex-wrap gap-2" aria-label="Language tabs">
                    @foreach ($languages as $index => $language)
                        @php
                            $active = $language->code === $editorDefaultLocale;
                        @endphp
                        <button type="button"
                            class="rounded-t-2xl border-b-2 px-4 py-2 text-sm font-medium transition {{ $active ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800' }}"
                            data-editor-tab-button data-tab="lang-{{ $language->code }}">
                            {{ $language->name }} ({{ $language->code }})
                        </button>
                    @endforeach
                </nav>
            </div>

            {{-- Per-language hydration and rendering loop --}}
            @foreach ($languages as $index => $language)
                @php
                    $code = $language->code;
                    $localeScalarValues = $editorState['localeScalarValues'][$code] ?? [];

                    $sectionTitleValue = $localeScalarValues['sectionTitleValue'] ?? '';
                    $heroTitleValue = $localeScalarValues['heroTitleValue'] ?? '';
                    $primaryButtonLabelValue = $localeScalarValues['primaryButtonLabelValue'] ?? '';
                    $primaryButtonUrlValue = $localeScalarValues['primaryButtonUrlValue'] ?? '';
                    $primaryButtonNewTabValue = (bool) ($localeScalarValues['primaryButtonNewTabValue'] ?? false);
                    $footerCopyrightValue = $localeScalarValues['footerCopyrightValue'] ?? '';
                    $footerLinkItems = $localeScalarValues['footerLinkItems'] ?? [];
                    $footerFacebookUrlValue = $localeScalarValues['footerFacebookUrlValue'] ?? '';
                    $footerInstagramUrlValue = $localeScalarValues['footerInstagramUrlValue'] ?? '';
                    $footerXUrlValue = $localeScalarValues['footerXUrlValue'] ?? '';
                    $footerGithubUrlValue = $localeScalarValues['footerGithubUrlValue'] ?? '';
                    $footerYoutubeUrlValue = $localeScalarValues['footerYoutubeUrlValue'] ?? '';
                    $headerLogoValue = $localeScalarValues['headerLogoValue'] ?? null;
                    $headerLogoPreviewUrls = $editorState['localeHeaderLogoPreviewUrls'][$code] ?? [];
                @endphp

                <div id="lang-{{ $code }}" data-editor-tab-panel
                    class="{{ $code === $editorDefaultLocale ? '' : 'hidden' }}">
                    <input type="hidden" name="translations[{{ $code }}][locale]"
                        value="{{ $code }}">

                    {{-- Shell editor legacy compatibility only. Normal admin page sections should never fall through here once linked to a definition. --}}
                    <div class="{{ $contentGridClass }}">
                        <input type="hidden" name="translations[{{ $code }}][title]"
                            value="{{ $sectionTitleValue }}">

                        @include('dashboard.pages.sections.partials.blocks.header-branding-fields', [
                            'code' => $code,
                            'selectedType' => $selectedType,
                            'heroTitleValue' => $heroTitleValue,
                            'headerLogoValue' => $headerLogoValue,
                            'headerLogoPreviewUrls' => $headerLogoPreviewUrls,
                            'renderBrandIdentityFields' => false,
                            'renderMainTitleField' => $showShellMainTitleField,
                            'isHeroCampaign' => false,
                            'isProgrammingShowcase' => false,
                            'isMobileAppShowcase' => false,
                            'isDesignShowcase' => false,
                            'isDigitalMarketingShowcase' => false,
                            'isReviewsShowcase' => false,
                            'isOurWorkShowcase' => false,
                            'isDomainsShowcase' => false,
                            'isTemplatesSliderShowcase' => false,
                            'isTemplatesListingShowcase' => false,
                        ])

                        @if ($showShellPrimaryButtonFields)
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">
                                    {{ __('Header Button Label') }}
                                </label>
                                <input type="text"
                                    name="translations[{{ $code }}][content][primary_button][label]"
                                    value="{{ $primaryButtonLabelValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                            </div>

                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">
                                    {{ __('Header Button URL') }}
                                </label>
                                <input type="text"
                                    name="translations[{{ $code }}][content][primary_button][url]"
                                    value="{{ $primaryButtonUrlValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                            </div>

                            <div class="lg:col-span-2">
                                <label
                                    class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                                    <input type="hidden"
                                        name="translations[{{ $code }}][content][primary_button][new_tab]"
                                        value="0">
                                    <input type="checkbox"
                                        name="translations[{{ $code }}][content][primary_button][new_tab]"
                                        value="1" class="rounded border-slate-300"
                                        {{ $primaryButtonNewTabValue ? 'checked' : '' }}>
                                    {{ __('Open header button in a new tab') }}
                                </label>
                            </div>
                        @endif

                        {{-- Footer-specific repeater/layout logic remains inline here. --}}
                        @if ($showShellFooterLinksField)
                            <div class="lg:col-span-2" data-footer-link-repeater
                                data-schema-group-label="{{ $footerLinksGroupLabel }}" data-schema-field="footer_links"
                                data-schema-field-label="{{ $footerLinksFieldLabel }}"
                                data-footer-link-item-label="{{ $footerLinksItemLabel }}"
                                data-footer-link-item-hint="{{ __('Add the label and destination for this footer link.') }}">
                                <div class="mb-4 flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ $footerLinksFieldLabel }}</label>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ __('This is used in the links footer layout. Add each link with a label and URL.') }}
                                        </p>
                                    </div>
                                    <button type="button" data-add-footer-link
                                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 rtl:flex-row-reverse">
                                        <span class="text-sm leading-none">+</span>
                                        <span>{{ __('Add link') }}</span>
                                    </button>
                                </div>

                                <div class="space-y-3" data-footer-link-items>
                                    @foreach ($footerLinkItems as $footerLinkIndex => $footerLinkItem)
                                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                                            data-footer-link-item>
                                            <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                <div
                                                    class="inline-flex items-center gap-2 text-xs font-medium uppercase tracking-[0.2em] text-slate-400 rtl:flex-row-reverse">
                                                    <span data-footer-link-title>{{ __('Link') }}
                                                        {{ $footerLinkIndex + 1 }}</span>
                                                </div>

                                                <div class="flex items-center gap-2 rtl:flex-row-reverse">
                                                    <button type="button" data-duplicate-footer-link
                                                        class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                                                        {{ __('Duplicate') }}
                                                    </button>
                                                    <button type="button" data-remove-footer-link
                                                        class="rounded-xl border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:border-red-300 hover:bg-red-100">
                                                        {{ __('Remove') }}
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-slate-700">{{ __('Label') }}</label>
                                                    <input type="text" data-footer-link-field="label"
                                                        data-name-template="translations[{{ $code }}][content][footer_links][__INDEX__][label]"
                                                        value="{{ $footerLinkItem['label'] ?? '' }}"
                                                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                                                </div>

                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-slate-700">{{ __('URL') }}</label>
                                                    <input type="text" data-footer-link-field="url"
                                                        data-name-template="translations[{{ $code }}][content][footer_links][__INDEX__][url]"
                                                        value="{{ $footerLinkItem['url'] ?? '' }}"
                                                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                        placeholder="https://example.com/page">
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div data-footer-link-empty
                                    class="{{ count($footerLinkItems) > 0 ? 'hidden ' : '' }}rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                                    {{ __('No footer links added yet. Add your first link to show it in the links footer layout.') }}
                                </div>

                                <template data-footer-link-item-template>
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                                        data-footer-link-item>
                                        <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                            <div
                                                class="inline-flex items-center gap-2 text-xs font-medium uppercase tracking-[0.2em] text-slate-400 rtl:flex-row-reverse">
                                                <span data-footer-link-title>{{ __('Link') }}</span>
                                            </div>

                                            <div class="flex items-center gap-2 rtl:flex-row-reverse">
                                                <button type="button" data-duplicate-footer-link
                                                    class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                                                    {{ __('Duplicate') }}
                                                </button>
                                                <button type="button" data-remove-footer-link
                                                    class="rounded-xl border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:border-red-300 hover:bg-red-100">
                                                    {{ __('Remove') }}
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-slate-700">{{ __('Label') }}</label>
                                                <input type="text" data-footer-link-field="label"
                                                    data-name-template="translations[{{ $code }}][content][footer_links][__INDEX__][label]"
                                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                                            </div>

                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-slate-700">{{ __('URL') }}</label>
                                                <input type="text" data-footer-link-field="url"
                                                    data-name-template="translations[{{ $code }}][content][footer_links][__INDEX__][url]"
                                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                    placeholder="https://example.com/page">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        @endif

                        {{-- Extracted footer/social configuration block --}}
                        @if ($showShellFooterSocialFields)
                            @include('dashboard.pages.sections.partials.blocks.site-footer-social-fields', [
                                'code' => $code,
                                'footerCopyrightValue' => $footerCopyrightValue,
                                'footerFacebookUrlValue' => $footerFacebookUrlValue,
                                'footerInstagramUrlValue' => $footerInstagramUrlValue,
                                'footerXUrlValue' => $footerXUrlValue,
                                'footerGithubUrlValue' => $footerGithubUrlValue,
                                'footerYoutubeUrlValue' => $footerYoutubeUrlValue,
                                'copyrightFieldLabel' => $copyrightFieldLabel,
                                'socialLinksGroupLabel' => $socialLinksGroupLabel,
                                'socialFieldLabels' => $socialFieldLabels,
                            ])
                        @endif

                    </div>
                </div>
            @endforeach
        </div>
    </div>
</form>
