<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- [ breadcrumb ] start -->
    <div class="page-header col-span-3">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a>
                </li>
                <li class="breadcrumb-item"><a
                        href="{{ route('dashboard.languages.index') }}">{{ t('dashboard.All_Pages', 'ALL Pages') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.Add_Pages', 'Add Pages') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Add_Pages', 'Add Pages') }}</h2>
            </div>
        </div>
    </div>
    <!-- [ breadcrumb ] end -->

    @php
        $activeLanguage = $languages->firstWhere('code', $activeLang);
        $activeLanguageName = $activeLanguage?->native ?? ($activeLanguage?->name ?? strtoupper($activeLang));
    @endphp
    <div class="col-span-3">
        <div class="bg-blue-100 text-blue-900 px-4 py-2 rounded">
            {{ t('dashboard.You_are_editing_the_content', 'You are editing the :name content', ['name' => $activeLanguageName]) }}
        </div>
    </div>

    <div class="col-span-2">
        <div class="card p-6 space-y-6">
            <h2 class="text-lg font-bold">{{ t('dashboard.Add_Page', 'Add Page') }}</h2>

            <div>
                <ul class="flex border-b mb-4 space-x-2 rtl:space-x-reverse" role="tablist">
                    @foreach ($languages as $index => $lang)
                        <li>
                            <button type="button" wire:click="$set('activeLang', '{{ $lang->code }}')" id="lang-tab-{{ $lang->code }}" role="tab" aria-controls="lang-panel-{{ $lang->code }}" aria-selected="{{ $activeLang === $lang->code ? 'true' : 'false' }}"
                                @class(['px-4 py-2 rounded-t transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400', 'bg-white text-slate-900 shadow-sm border border-slate-200 border-b-white font-semibold' => $activeLang === $lang->code, 'bg-slate-100 text-slate-500 hover:bg-slate-200 border border-transparent' => $activeLang !== $lang->code])>
                                {{ $lang->name }}
                            </button>
                        </li>
                    @endforeach
                </ul>

                @foreach ($languages as $lang)
                    <div wire:key="page-add-lang-{{ $lang->code }}" id="lang-panel-{{ $lang->code }}" role="tabpanel" aria-labelledby="lang-tab-{{ $lang->code }}">
                        @if ($activeLang === $lang->code)
                            @php
                                $langCode = $lang->code;
                                $ogImageValue = data_get($translations, $langCode . '.og_image');
                            @endphp
                            <div class="space-y-4">
                                <div>
                                    <label class="block mb-1 font-semibold">{{ t('dashboard.Page_Title', 'Page Title') }}
                                        ({{ $langCode }})</label>
                                    <input type="text" wire:model.defer="translations.{{ $langCode }}.title"
                                        class="w-full border p-2 rounded mb-1" placeholder="{{ t('dashboard.Page_Title', 'Page Title') }}">
                                    @error("translations.{$langCode}.title")
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block mb-1 font-semibold">Slug ({{ $langCode }})</label>
                                    <input type="text" wire:model.defer="translations.{{ $langCode }}.slug"
                                        class="w-full border p-2 rounded mb-1" placeholder="page-slug">
                                    <p class="text-xs text-gray-500">
                                        {{ __('Use lowercase letters, numbers, and dashes only. Leave empty to auto-generate for the main language.') }}
                                    </p>
                                    @error("translations.{$langCode}.slug")
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block mb-1 font-semibold">{{ t('dashboard.Page_Content', 'Page Content') }}
                                        ({{ $langCode }})</label>
                                    <textarea wire:model.defer="translations.{{ $langCode }}.content" class="w-full border p-2 rounded h-40"
                                        placeholder="{{ t('dashboard.Page_Content', 'Page Content') }}"></textarea>
                                    @error("translations.{$langCode}.content")
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="border-t pt-4 space-y-3">
                                    <h3 class="text-sm font-semibold text-gray-600">{{ t('dashboard.SEO_Meta', 'SEO Meta') }}</h3>

                                    <div>
                                        <label class="block mb-1 font-semibold">{{ t('dashboard.Meta_Title', 'Meta Title') }}
                                            ({{ $langCode }})</label>
                                        <input type="text"
                                            wire:model.defer="translations.{{ $langCode }}.meta_title"
                                            class="w-full border p-2 rounded mb-1"
                                            placeholder="{{ t('dashboard.Meta_Title', 'Meta Title') }}">
                                        @error("translations.{$langCode}.meta_title")
                                            <span class="text-red-500 text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block mb-1 font-semibold">{{ t('dashboard.Meta_Description', 'Meta Description') }}
                                            ({{ $langCode }})</label>
                                        <textarea wire:model.defer="translations.{{ $langCode }}.meta_description" class="w-full border p-2 rounded h-24"
                                            placeholder="{{ t('dashboard.Short_description_for_search_engines', 'Short description for search engines') }}"></textarea>
                                        <p class="text-xs text-gray-500">
                                            {{ t('dashboard.Aim_for_50_160_characters_Leave_empty_to_reuse_the_title', 'Aim for 50-160 characters. Leave empty to reuse the title.') }}</p>
                                        @error("translations.{$langCode}.meta_description")
                                            <span class="text-red-500 text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block mb-1 font-semibold">{{ t('dashboard.Meta_Keywords', 'Meta Keywords') }}
                                            ({{ $langCode }})</label>
                                        <input type="text"
                                            wire:model.defer="translations.{{ $langCode }}.meta_keywords"
                                            class="w-full border p-2 rounded mb-1" placeholder="keyword 1, keyword 2">
                                        <p class="text-xs text-gray-500">
                                            {{ t('dashboard.Separate_keywords_with_a_comma_or_Arabic_comma', 'Separate keywords with a comma or Arabic comma (?).') }}</p>
                                        @error("translations.{$langCode}.meta_keywords")
                                            <span class="text-red-500 text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block mb-1 font-semibold">{{ t('dashboard.Open_Graph_Image_URL', 'Open Graph Image URL') }}
                                            ({{ $langCode }})</label>
                                        <div class="flex items-center gap-2">
                                            <input type="text"
                                                wire:model.defer="translations.{{ $langCode }}.og_image"
                                                class="w-full border p-2 rounded mb-1"
                                                placeholder="https://example.com/og-image.jpg"
                                                data-media-input="{{ $langCode }}"
                                                id="og_image_input_{{ $langCode }}">
                                            <button type="button"
                                                class="px-3 py-2 text-sm font-medium border border-primary text-primary rounded hover:bg-primary/5"
                                                data-media-modal="pageMediaModal"
                                                data-media-locale="{{ $langCode }}">
                                                {{ t('dashboard.Choose_from_media', 'Choose from media') }}
                                            </button>
                                        </div>
                                        <p class="text-xs text-gray-500">
                                            {{ t('dashboard.Use_a_full_URL_or_a_Storage_link_generated_via_asset_Storage_url_Recommended_size_1200x630px', 'Use a full URL or a Storage link generated via asset()/Storage::url(). Recommended size 1200x630px.') }}
                                        </p>
                                        @error("translations.{$langCode}.og_image")
                                            <span class="text-red-500 text-sm">{{ $message }}</span>
                                        @enderror


                                        @php $hasOgImage = !empty($ogImageValue); @endphp
                                        <div class="mt-2 {{ $hasOgImage ? '' : 'hidden' }}"
                                            data-media-preview-wrapper="{{ $langCode }}">
                                            <p class="text-xs text-gray-500 mb-1">{{ t('dashboard.Preview', 'Preview') }}</p>
                                            <img src="{{ $ogImageValue ?: '' }}" alt="OG image preview"
                                                class="max-h-32 rounded border {{ $hasOgImage ? '' : 'hidden' }}"
                                                data-media-preview="{{ $langCode }}">
                                        </div>

                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card p-4 space-y-4">
            <h3 class="font-semibold">{{ t('dashboard.Publishing_Options', 'Publishing Options') }}</h3>

            <div>
                <label class="block font-semibold">{{ t('dashboard.Status', 'Status') }}</label>
                <label class="flex items-center gap-2">
                    <input type="radio" wire:model="is_active" value="0" class="form-radio">
                    <span>{{ t('dashboard.Draft', 'Draft') }}</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" wire:model="is_active" value="1" class="form-radio">
                    <span>{{ t('dashboard.Published', 'Published') }}</span>
                </label>
            </div>

            <div>
                <label class="block font-semibold">{{ t('dashboard.Publish_Date', 'Publish Date') }}</label>
                <input type="datetime-local" wire:model="published_at" class="w-full border p-2 rounded">
                @error('published_at')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

                        <button type="button" wire:click="save" wire:loading.attr="disabled" wire:loading.class="opacity-70 cursor-not-allowed" wire:target="save"
                class="w-full inline-flex items-center justify-center gap-2 rounded bg-primary py-2 text-sm font-semibold text-white transition hover:bg-primary/80 focus:outline-none focus:ring-2 focus:ring-primary/40 focus:ring-offset-2 disabled:opacity-70 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus:ring-offset-slate-900">
                <span wire:loading.remove wire:target="save">{{ t('dashboard.Publish', 'Publish') }}</span>
                <span wire:loading wire:target="save" class="flex items-center gap-2 text-white">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    {{ t('dashboard.Saving', 'Saving...') }}
                </span>
            </button>
        </div>

        @if (session()->has('success'))
            <div class="bg-green-100 text-green-800 p-2 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="bg-red-100 text-red-800 p-2 rounded">
                {{ session('error') }}
            </div>
        @endif
    </div>
</div>
