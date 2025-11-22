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
                <li class="breadcrumb-item" aria-current="page">
                    {{ $mode === 'edit' ? t('dashboard.Edit_Page', 'Edit Page') : t('dashboard.Add_Pages', 'Add Pages') }}
                </li>
            </ul>

            <div class="page-header-title flex items-center justify-between gap-3">
                <h2 class="mb-0">
                    {{ $mode === 'edit' ? t('dashboard.Edit_Page', 'Edit Page') : t('dashboard.Add_Pages', 'Add Pages') }}
                </h2>

                @if ($editingPageId && $mode === 'edit')
                    <button type="button" wire:click="$dispatch('open-sections-palette')"
                        class="bg-primary text-white px-4 py-2 rounded hover:bg-primary/90 transition">
                        + {{ t('dashboard.Add_Section', 'Add Section') }}
                    </button>
                @endif
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
            {{ __('You are editing the :name content', ['name' => $activeLanguageName]) }}
        </div>
    </div>

    <div class="col-span-2">
        <div class="card p-6 space-y-6">
            <h2 class="text-lg font-bold">
                {{ $mode === 'edit' ? t('dashboard.Edit_Page', 'Edit Page') : t('dashboard.Add_Page', 'Add Page') }}
            </h2>

            <div>
                <ul class="flex border-b mb-4 space-x-2 rtl:space-x-reverse">
                    @foreach ($languages as $index => $lang)
                        <li>
                            <button type="button" wire:click="$set('activeLang', '{{ $lang->code }}')"
                                class="px-4 py-2 rounded-t {{ $activeLang === $lang->code ? 'bg-white border-t border-l border-r font-bold' : 'bg-gray-200' }}">
                                {{ $lang->name }}
                            </button>
                        </li>
                    @endforeach
                </ul>

                @foreach ($languages as $lang)
                    <div wire:key="page-edit-lang-{{ $lang->code }}">
                        @if ($activeLang === $lang->code)
                            @php
                                $langCode = $lang->code;
                                $ogImageValue = data_get($translations, $langCode . '.og_image');
                            @endphp
                            <div class="space-y-4">
                                <div>
                                    <label
                                        class="block mb-1 font-semibold">{{ t('dashboard.Page_Title', 'Page Title') }}
                                        ({{ $langCode }})</label>
                                    <input type="text" wire:model.defer="translations.{{ $langCode }}.title"
                                        class="w-full border p-2 rounded mb-1"
                                        placeholder="{{ t('dashboard.Page_Title', 'Page Title') }}">
                                    @error("translations.{$langCode}.title")
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block mb-1 font-semibold">Slug ({{ $langCode }})</label>
                                    <input type="text" wire:model.defer="translations.{{ $langCode }}.slug"
                                        class="w-full border p-2 rounded mb-1" placeholder="page-slug">
                                    <p class="text-xs text-gray-500">
                                        {{ t('dashboard.Slug_Hint', 'Use lowercase letters, numbers, and dashes only. Leave empty to auto-generate for the main language.') }}
                                    </p>
                                    @error("translations.{$langCode}.slug")
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label
                                        class="block mb-1 font-semibold">{{ t('dashboard.Page_Content', 'Page Content') }}
                                        ({{ $langCode }})</label>
                                    <textarea wire:model.defer="translations.{{ $langCode }}.content" class="w-full border p-2 rounded h-40"
                                        placeholder="{{ t('dashboard.Page_Content', 'Page Content') }}"></textarea>
                                    @error("translations.{$langCode}.content")
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="border-t pt-4 space-y-3">
                                    <h3 class="text-sm font-semibold text-gray-600">
                                        {{ t('dashboard.SEO_Meta', 'SEO Meta') }}</h3>

                                    <div>
                                        <label
                                            class="block mb-1 font-semibold">{{ t('dashboard.Meta_Title', 'Meta Title') }}
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
                                        <label
                                            class="block mb-1 font-semibold">{{ t('dashboard.Meta_Description', 'Meta Description') }}
                                            ({{ $langCode }})</label>
                                        <textarea wire:model.defer="translations.{{ $langCode }}.meta_description" class="w-full border p-2 rounded h-24"
                                            placeholder="{{ t('dashboard.Short_Description', 'Short description for search engines') }}"></textarea>
                                        <p class="text-xs text-gray-500">
                                            {{ t('dashboard.Aim_Characters', 'Aim for 50-160 characters. Leave empty to reuse the title.') }}
                                        </p>
                                        @error("translations.{$langCode}.meta_description")
                                            <span class="text-red-500 text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label
                                            class="block mb-1 font-semibold">{{ t('dashboard.Meta_Keywords', 'Meta Keywords') }}
                                            ({{ $langCode }})</label>
                                        <input type="text"
                                            wire:model.defer="translations.{{ $langCode }}.meta_keywords"
                                            class="w-full border p-2 rounded mb-1" placeholder="keyword 1, keyword 2">
                                        <p class="text-xs text-gray-500">
                                            {{ t('dashboard.Separate_Keywords', 'Separate keywords with a comma or Arabic comma (?).') }}
                                        </p>
                                        @error("translations.{$langCode}.meta_keywords")
                                            <span class="text-red-500 text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label
                                            class="block mb-1 font-semibold">{{ t('dashboard.Open_Graph_Image_URL', 'Open Graph Image URL') }}
                                            ({{ $langCode }})</label>
                                        <div class="flex items-center gap-2">
                                            <input type="text"
                                                wire:model.defer="translations.{{ $langCode }}.og_image"
                                                class="w-full border p-2 rounded mb-1"
                                                placeholder="https://example.com/og-image.jpg"
                                                data-media-input="{{ $langCode }}"
                                                id="og_image_input_edit_{{ $langCode }}">
                                            <button type="button"
                                                class="px-3 py-2 text-sm font-medium border border-primary text-primary rounded hover:bg-primary/5"
                                                data-media-modal="pageMediaModal"
                                                data-media-locale="{{ $langCode }}">
                                                {{ t('dashboard.Choose_from_media', 'Choose from media') }}
                                            </button>
                                        </div>
                                        <p class="text-xs text-gray-500">
                                            {{ t('dashboard.Use_a_full_URL', 'Use a full URL or a Storage link generated via asset()/Storage::url(). Recommended size 1200x630px.') }}
                                        </p>
                                        @error("translations.{$langCode}.og_image")
                                            <span class="text-red-500 text-sm">{{ $message }}</span>
                                        @enderror


                                        @php $hasOgImage = !empty($ogImageValue); @endphp
                                        <div class="mt-2 {{ $hasOgImage ? '' : 'hidden' }}"
                                            data-media-preview-wrapper="{{ $langCode }}">
                                            <p class="text-xs text-gray-500 mb-1">
                                                {{ t('dashboard.Preview', 'Preview') }}</p>
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

            <button type="button" wire:click="save"
                class="w-full bg-primary text-white py-2 rounded hover:bg-primary/80 transition">
                {{ $mode === 'edit' ? t('dashboard.Update', 'Update') : t('dashboard.Publish', 'Publish') }}
            </button>

            @if ($mode === 'edit')
                <button type="button" wire:click="resetForm"
                    class="w-full bg-gray-200 text-gray-800 py-2 rounded hover:bg-gray-300 transition">
                    {{ t('dashboard.Cancel_Edit', 'Cancel Edit') }}
                </button>
            @endif
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

    @if ($editingPageId && $mode === 'edit')
        <div class="mt-10">
            <livewire:admin.sections :pageId="$editingPageId" />
        </div>
    @endif
</div>
