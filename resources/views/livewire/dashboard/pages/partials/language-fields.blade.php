@php
    $langCode = $lang->code;
    $panelId = 'lang-panel-' . $langCode;
    $ogImageValue = data_get($translations, $langCode . '.og_image');
    $hasOgImage = filled($ogImageValue);
@endphp

<div id="{{ $panelId }}" role="tabpanel" aria-labelledby="lang-tab-{{ $langCode }}"
    wire:key="page-add-lang-{{ $langCode }}" @class(['mt-4 space-y-4', 'hidden' => ! $isActive])>
    <div class="space-y-4">
        <div class="space-y-1">
            <label for="title_{{ $langCode }}" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">
                {{ t('dashboard.Page_Title', 'Page Title') }} ({{ $langCode }})
            </label>
            <input id="title_{{ $langCode }}" type="text" wire:model.defer="translations.{{ $langCode }}.title"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                placeholder="{{ t('dashboard.Page_Title', 'Page Title') }}">
            @error("translations.{$langCode}.title")
                <span class="text-xs text-rose-500">{{ $message }}</span>
            @enderror
        </div>

        <div class="space-y-1">
            <label for="slug_{{ $langCode }}" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">
                Slug ({{ $langCode }})
            </label>
            <input id="slug_{{ $langCode }}" type="text" wire:model.defer="translations.{{ $langCode }}.slug"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                placeholder="page-slug" aria-describedby="slug-help-{{ $langCode }}">
            <p id="slug-help-{{ $langCode }}" class="text-xs text-slate-500 dark:text-slate-400">
                {{ __('Use lowercase letters, numbers, and dashes only. Leave empty to auto-generate for the main language.') }}
            </p>
            @error("translations.{$langCode}.slug")
                <span class="text-xs text-rose-500">{{ $message }}</span>
            @enderror
        </div>

        <div class="space-y-1">
            <label for="content_{{ $langCode }}" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">
                {{ t('dashboard.Page_Content', 'Page Content') }} ({{ $langCode }})
            </label>
            <textarea id="content_{{ $langCode }}" wire:model.defer="translations.{{ $langCode }}.content"
                class="h-48 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                placeholder="{{ t('dashboard.Page_Content', 'Page Content') }}"></textarea>
            @error("translations.{$langCode}.content")
                <span class="text-xs text-rose-500">{{ $message }}</span>
            @enderror
        </div>

        <div class="space-y-3 border-t border-slate-200 pt-4 dark:border-slate-700">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-400">
                {{ t('dashboard.SEO_Meta', 'SEO Meta') }}
            </h3>

            <div class="space-y-1">
                <label for="meta_title_{{ $langCode }}" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">
                    {{ t('dashboard.Meta_Title', 'Meta Title') }} ({{ $langCode }})
                </label>
                <input id="meta_title_{{ $langCode }}" type="text"
                    wire:model.defer="translations.{{ $langCode }}.meta_title"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                    placeholder="{{ t('dashboard.Meta_Title', 'Meta Title') }}">
                @error("translations.{$langCode}.meta_title")
                    <span class="text-xs text-rose-500">{{ $message }}</span>
                @enderror
            </div>

            <div class="space-y-1">
                <label for="meta_description_{{ $langCode }}"
                    class="block text-sm font-semibold text-slate-700 dark:text-slate-200">
                    {{ t('dashboard.Meta_Description', 'Meta Description') }} ({{ $langCode }})
                </label>
                <textarea id="meta_description_{{ $langCode }}" rows="2"
                    wire:model.defer="translations.{{ $langCode }}.meta_description"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                    placeholder="{{ t('dashboard.Short_description_for_search_engines', 'Short description for search engines') }}"></textarea>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    {{ t('dashboard.Aim_for_50_160_characters', 'Aim for 50-160 characters. Leave empty to reuse the title.') }}
                </p>
                @error("translations.{$langCode}.meta_description")
                    <span class="text-xs text-rose-500">{{ $message }}</span>
                @enderror
            </div>

            <div class="space-y-1">
                <label for="meta_keywords_{{ $langCode }}"
                    class="block text-sm font-semibold text-slate-700 dark:text-slate-200">
                    {{ t('dashboard.Meta_Keywords', 'Meta Keywords') }} ({{ $langCode }})
                </label>
                <input id="meta_keywords_{{ $langCode }}" type="text"
                    wire:model.defer="translations.{{ $langCode }}.meta_keywords"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                    placeholder="keyword 1, keyword 2">
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    {{ t('dashboard.Separate_keywords_with_commas', 'Separate keywords with a comma or Arabic comma (،).') }}
                </p>
                @error("translations.{$langCode}.meta_keywords")
                    <span class="text-xs text-rose-500">{{ $message }}</span>
                @enderror
            </div>

            <div class="space-y-1">
                <label for="og_image_input_{{ $langCode }}"
                    class="block text-sm font-semibold text-slate-700 dark:text-slate-200">
                    {{ t('dashboard.Open_Graph_Image_URL', 'Open Graph Image URL') }} ({{ $langCode }})
                </label>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <input id="og_image_input_{{ $langCode }}" type="text"
                        wire:model.defer="translations.{{ $langCode }}.og_image"
                        class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                        placeholder="https://example.com/og-image.jpg" data-media-input="{{ $langCode }}">
                    <button type="button"
                        class="inline-flex items-center justify-center rounded-lg border border-primary px-3 py-2 text-sm font-medium text-primary transition hover:bg-primary/5 focus:outline-none focus:ring-2 focus:ring-primary/40 dark:border-indigo-400 dark:text-indigo-300 dark:hover:bg-indigo-500/10"
                        data-media-modal="pageMediaModal" data-media-locale="{{ $langCode }}">
                        {{ t('dashboard.Choose_from_media', 'Choose from media') }}
                    </button>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    {{ t('dashboard.Use_a_full_URL_or_a_Storage_link_generated_via_asset_Storage_url_Recommended_size_1200x630px', 'Use a full URL or a Storage link generated via asset()/Storage::url(). Recommended size 1200x630px.') }}
                </p>
                @error("translations.{$langCode}.og_image")
                    <span class="text-xs text-rose-500">{{ $message }}</span>
                @enderror

                <div class="mt-2" data-media-preview-wrapper="{{ $langCode }}">
                    <p class="text-xs text-slate-500 dark:text-slate-400" @class(['mb-1', 'hidden' => ! $hasOgImage])>
                        {{ t('dashboard.Preview', 'Preview') }}
                    </p>
                    <img src="{{ $ogImageValue ?: '' }}" alt="OG image preview"
                        data-media-preview="{{ $langCode }}"
                        @class(['max-h-32 rounded-lg border border-slate-200 object-cover shadow-sm dark:border-slate-700', 'hidden' => ! $hasOgImage])>
                </div>
            </div>
        </div>
    </div>
</div>
