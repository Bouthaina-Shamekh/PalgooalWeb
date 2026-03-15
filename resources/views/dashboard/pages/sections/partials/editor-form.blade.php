@php
    use App\Models\Media;

    $formId = $formId ?? 'section-edit-form';
    $formAction = $formAction ?? route('dashboard.pages.sections.update', [$page, $section]);
    $formClass = $formClass ?? 'space-y-6';
    $surfaceClass = $surfaceClass ?? 'rounded-3xl border border-slate-200 bg-white shadow-sm';
    $sectionHeaderClass = $sectionHeaderClass ?? 'border-b border-slate-200 px-5 py-4 lg:px-6';
    $sectionBodyClass = $sectionBodyClass ?? 'p-5 lg:p-6';
    $settingsGridClass = $settingsGridClass ?? 'grid grid-cols-1 gap-5 lg:grid-cols-2';
    $contentGridClass = $contentGridClass ?? 'grid grid-cols-1 gap-5 lg:grid-cols-2';
    $showOrderField = $showOrderField ?? true;
    $feedbackMessage = $feedbackMessage ?? null;
    $feedbackTone = $feedbackTone ?? 'success';
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();
@endphp

<form
    id="{{ $formId }}"
    method="POST"
    action="{{ $formAction }}"
    class="{{ $formClass }}"
    data-section-editor-form
    data-section-id="{{ $section->id }}"
>
    @csrf
    @method('PUT')

    @php
        $feedbackVisible = $viewErrors->any() || filled($feedbackMessage);
        $feedbackClasses = $feedbackTone === 'error'
            ? 'border-red-200 bg-red-50 text-red-800'
            : 'border-emerald-200 bg-emerald-50 text-emerald-800';
        $selectedType = old('type', $section->type);
        $isHeroCampaign = $selectedType === 'hero_campaign';
        $showEyebrowField = $selectedType === 'hero_default';
        $showDescriptionField = $isHeroCampaign;
        $showFeaturesHeadingField = $isHeroCampaign;
        $showSecondaryButtonFields = $selectedType === 'hero_default';
        $showFeaturesTextareaField = in_array($selectedType, ['hero_default', 'hero_campaign', 'features_grid'], true);
        $showMediaTypeField = $selectedType === 'hero_default';
        $showMediaUrlField = in_array($selectedType, ['hero_default', 'hero_campaign'], true);
    @endphp

    <div
        data-section-editor-feedback
        class="{{ $feedbackVisible ? '' : 'hidden ' }}rounded-2xl border px-4 py-3 text-sm {{ $feedbackVisible ? $feedbackClasses : 'border-slate-200 bg-slate-50 text-slate-600' }}"
    >
        @if ($feedbackVisible)
            <ul class="space-y-1" data-section-editor-feedback-list>
                @if (filled($feedbackMessage))
                    <li>{{ $feedbackMessage }}</li>
                @endif

                @foreach ($viewErrors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @else
            <ul class="hidden space-y-1" data-section-editor-feedback-list></ul>
        @endif
    </div>

    <div class="{{ $surfaceClass }}">
        <div class="{{ $sectionHeaderClass }}">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('Section Settings') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Update the type, variant, and visibility for this section.') }}</p>
        </div>

        <div class="{{ $sectionBodyClass }}">
            <div class="{{ $settingsGridClass }}">
                <div>
                    <label class="block text-sm font-medium text-slate-700">{{ __('Section Type') }}</label>
                    <select
                        name="type"
                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                    >
                        @foreach ($sectionTypes as $value => $meta)
                            <option value="{{ $value }}" {{ old('type', $section->type) === $value ? 'selected' : '' }}>
                                {{ $meta['label'] ?? $value }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">{{ __('Variant') }}</label>
                    <input
                        type="text"
                        name="variant"
                        value="{{ old('variant', $section->variant) }}"
                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                        placeholder="default / minimal / v2"
                    >
                </div>

                @if ($showOrderField)
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('Display Order') }}</label>
                        <input
                            type="number"
                            name="order"
                            value="{{ old('order', $section->order ?? 1) }}"
                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                        >
                    </div>
                @endif

                <div class="flex items-center">
                    <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            class="rounded border-slate-300"
                            {{ old('is_active', $section->is_active) ? 'checked' : '' }}
                        >
                        {{ __('Active on frontend') }}
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="{{ $surfaceClass }}">
        <div class="{{ $sectionHeaderClass }}">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('Section Content') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Edit localized content for each language.') }}</p>
        </div>

        <div class="{{ $sectionBodyClass }}">
            <div class="mb-5 border-b border-slate-200">
                <nav class="-mb-px flex flex-wrap gap-2" aria-label="Language tabs">
                    @foreach ($languages as $index => $language)
                        @php
                            $active = $index === 0;
                        @endphp
                        <button
                            type="button"
                            class="rounded-t-2xl border-b-2 px-4 py-2 text-sm font-medium transition {{ $active ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800' }}"
                            data-editor-tab-button
                            data-tab="lang-{{ $language->code }}"
                        >
                            {{ $language->name }} ({{ $language->code }})
                        </button>
                    @endforeach
                </nav>
            </div>

            @foreach ($languages as $index => $language)
                @php
                    $code = $language->code;
                    $translation = $section->translations->firstWhere('locale', $code);
                    $content = $translation?->content ?? [];
                    $stringifyValue = static fn ($value) => is_scalar($value) ? (string) $value : '';
                    $primaryButton = is_array($content['primary_button'] ?? null) ? $content['primary_button'] : [];
                    $secondaryButton = is_array($content['secondary_button'] ?? null) ? $content['secondary_button'] : [];

                    $featuresTextarea = old("translations.$code.content.features_textarea");

                    if ($featuresTextarea === null) {
                        if (!empty($content['features']) && is_array($content['features'])) {
                            $featuresTextarea = implode("\n", $content['features']);
                        } else {
                            $featuresTextarea = '';
                        }
                    }

                    $sectionTitleValue = $stringifyValue(old("translations.$code.title", $translation->title ?? ''));
                    $eyebrowValue = $stringifyValue(old("translations.$code.content.eyebrow", $content['eyebrow'] ?? ''));
                    $heroTitleValue = $stringifyValue(old("translations.$code.content.title", $content['title'] ?? ''));
                    $subtitleValue = $stringifyValue(old("translations.$code.content.subtitle", $content['subtitle'] ?? ''));
                    $descriptionValue = $stringifyValue(old("translations.$code.content.description", $content['description'] ?? ''));
                    $featuresHeadingValue = $stringifyValue(old("translations.$code.content.features_heading", $content['features_heading'] ?? ''));
                    $primaryButtonLabelValue = $stringifyValue(old("translations.$code.content.primary_button.label", $primaryButton['label'] ?? ''));
                    $primaryButtonUrlValue = $stringifyValue(old("translations.$code.content.primary_button.url", $primaryButton['url'] ?? ''));
                    $secondaryButtonLabelValue = $stringifyValue(old("translations.$code.content.secondary_button.label", $secondaryButton['label'] ?? ''));
                    $secondaryButtonUrlValue = $stringifyValue(old("translations.$code.content.secondary_button.url", $secondaryButton['url'] ?? ''));
                    $mediaUrlValue = $stringifyValue(old("translations.$code.content.media_url", $content['media_url'] ?? ''));
                    $mediaTypeOld = old("translations.$code.content.media_type", $content['media_type'] ?? 'image');
                    $campaignIllustrationValue = old("translations.$code.content.media_url", $content['media_url'] ?? null);
                    $campaignIllustrationPreviewUrls = [];

                    if ($isHeroCampaign) {
                        if (is_numeric($campaignIllustrationValue)) {
                            $mediaItem = Media::find((int) $campaignIllustrationValue);
                            $campaignIllustrationPreviewUrls = $mediaItem?->url ? [$mediaItem->url] : [];
                        } elseif (is_string($campaignIllustrationValue) && $campaignIllustrationValue !== '') {
                            $campaignIllustrationPreviewUrls = [
                                \Illuminate\Support\Str::startsWith($campaignIllustrationValue, ['http://', 'https://', '//', '/', 'data:'])
                                    ? $campaignIllustrationValue
                                    : asset($campaignIllustrationValue),
                            ];
                        }
                    }
                @endphp

                <div
                    id="lang-{{ $code }}"
                    data-editor-tab-panel
                    class="{{ $index === 0 ? '' : 'hidden' }}"
                >
                    <input type="hidden" name="translations[{{ $code }}][locale]" value="{{ $code }}">

                    <div class="{{ $contentGridClass }}">
                        <div class="lg:col-span-2">
                            <div class="flex items-center justify-between gap-3">
                                <label class="block text-sm font-medium text-slate-700">
                                    {{ $isHeroCampaign ? __('Internal Label') : __('Section Title') }} ({{ $code }})
                                </label>
                                @if ($isHeroCampaign)
                                    <span class="text-xs font-medium text-slate-400">{{ __('Used only in the workspace list') }}</span>
                                @endif
                            </div>
                            <input
                                type="text"
                                name="translations[{{ $code }}][title]"
                                value="{{ $sectionTitleValue }}"
                                class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                            >
                            @if ($isHeroCampaign)
                                <p class="mt-2 text-xs text-slate-500">{{ __('The front design uses the fields below, not this internal label.') }}</p>
                            @endif
                        </div>

                        @if ($isHeroCampaign)
                            <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                {{ __('This hero uses one CTA button, a benefit grid, and one side illustration. Fill only the content that appears in the design.') }}
                            </div>
                        @endif

                        @if ($showEyebrowField)
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Eyebrow') }}</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][eyebrow]"
                                    value="{{ $eyebrowValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                {{ $isHeroCampaign ? __('Main Title - Line 1') : __('Main Title') }}
                            </label>
                            <input
                                type="text"
                                name="translations[{{ $code }}][content][title]"
                                value="{{ $heroTitleValue }}"
                                class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                            >
                        </div>

                        <div class="{{ $isHeroCampaign ? '' : 'lg:col-span-2' }}">
                            <label class="block text-sm font-medium text-slate-700">
                                {{ $isHeroCampaign ? __('Main Title - Line 2') : __('Subtitle') }}
                            </label>
                            <textarea
                                name="translations[{{ $code }}][content][subtitle]"
                                rows="3"
                                class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                            >{{ $subtitleValue }}</textarea>
                        </div>

                        @if ($showDescriptionField)
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">{{ __('Description') }}</label>
                                <textarea
                                    name="translations[{{ $code }}][content][description]"
                                    rows="4"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >{{ $descriptionValue }}</textarea>
                            </div>
                        @endif

                        @if ($showFeaturesHeadingField)
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">{{ __('Features Heading') }}</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][features_heading]"
                                    value="{{ $featuresHeadingValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                {{ $isHeroCampaign ? __('CTA Button Label') : __('Primary Button Label') }}
                            </label>
                            <input
                                type="text"
                                name="translations[{{ $code }}][content][primary_button][label]"
                                value="{{ $primaryButtonLabelValue }}"
                                class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                {{ $isHeroCampaign ? __('CTA Button URL') : __('Primary Button URL') }}
                            </label>
                            <input
                                type="text"
                                name="translations[{{ $code }}][content][primary_button][url]"
                                value="{{ $primaryButtonUrlValue }}"
                                class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                            >
                        </div>

                        @if ($showSecondaryButtonFields)
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Secondary Button Label') }}</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][secondary_button][label]"
                                    value="{{ $secondaryButtonLabelValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Secondary Button URL') }}</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][secondary_button][url]"
                                    value="{{ $secondaryButtonUrlValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>
                        @endif

                        @if ($showFeaturesTextareaField)
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">
                                    {{ $isHeroCampaign ? __('Campaign Features') : __('Features (each line = one bullet)') }}
                                </label>
                                <textarea
                                    name="translations[{{ $code }}][content][features_textarea]"
                                    rows="{{ $isHeroCampaign ? '8' : '5' }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >{{ $featuresTextarea }}</textarea>
                                <p class="mt-2 text-xs text-slate-500">
                                    {{ $isHeroCampaign ? __('Each line becomes one campaign feature item in the two-column grid.') : __('Each line will be converted to a feature item.') }}
                                </p>
                            </div>
                        @endif

                        @if ($showMediaTypeField)
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Media Type') }}</label>
                                <select
                                    name="translations[{{ $code }}][content][media_type]"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                                    <option value="image" {{ $mediaTypeOld === 'image' ? 'selected' : '' }}>Image</option>
                                    <option value="video" {{ $mediaTypeOld === 'video' ? 'selected' : '' }}>Video</option>
                                </select>
                            </div>
                        @endif

                        @if ($showMediaUrlField)
                            @if ($isHeroCampaign)
                                <div class="lg:col-span-2">
                                    <x-dashboard.media-picker
                                        :name="'translations['.$code.'][content][media_url]'"
                                        :label="__('Illustration')"
                                        :button-text="__('Choose From Media Library')"
                                        :value="$campaignIllustrationValue"
                                        :preview-urls="$campaignIllustrationPreviewUrls"
                                        :multiple="false"
                                        store-value="id"
                                        data-shared-media-group="hero-campaign-illustration"
                                    />
                                    <p class="mt-2 text-xs text-slate-500">{{ __('This illustration is shared across all languages for this hero.') }}</p>
                                </div>
                            @else
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">{{ __('Media URL') }}</label>
                                    <input
                                        type="text"
                                        name="translations[{{ $code }}][content][media_url]"
                                        value="{{ $mediaUrlValue }}"
                                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                    >
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</form>
