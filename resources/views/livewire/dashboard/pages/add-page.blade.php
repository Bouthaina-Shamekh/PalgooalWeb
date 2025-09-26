<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- [ breadcrumb ] start -->
    <div class="page-header col-span-3">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.languages.index') }}">{{ t('dashboard.All_Pages', 'ALL Pages') }}</a></li>
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
        $activeLanguageName = $activeLanguage?->native ?? $activeLanguage?->name ?? strtoupper($activeLang);
    @endphp
    <div class="col-span-3">
        <div class="bg-blue-100 text-blue-900 px-4 py-2 rounded">
            {{ __('You are editing the :name content', ['name' => $activeLanguageName]) }}
        </div>
    </div>

    <div class="col-span-2">
        <div class="card p-6 space-y-6">
            <h2 class="text-lg font-bold">{{ __('Add Page') }}</h2>

            <div>
                <ul class="flex border-b mb-4 space-x-2 rtl:space-x-reverse">
                    @foreach($languages as $index => $lang)
                        <li>
                            <button type="button"
                                wire:click="$set('activeLang', '{{ $lang->code }}')"
                                class="px-4 py-2 rounded-t {{ $activeLang === $lang->code ? 'bg-white border-t border-l border-r font-bold' : 'bg-gray-200' }}">
                                {{ $lang->name }}
                            </button>
                        </li>
                    @endforeach
                </ul>

                @foreach($languages as $lang)
                    <div wire:key="page-add-lang-{{ $lang->code }}">
                        @if($activeLang === $lang->code)
                            @php
                                $langCode = $lang->code;
                                $ogImageValue = data_get($translations, $langCode . '.og_image');
                            @endphp
                            <div class="space-y-4">
                                <div>
                                    <label class="block mb-1 font-semibold">{{ __('Page Title') }} ({{ $langCode }})</label>
                                    <input type="text" wire:model.defer="translations.{{ $langCode }}.title"
                                        class="w-full border p-2 rounded mb-1" placeholder="{{ __('Page Title') }}">
                                    @error("translations.{$langCode}.title")
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block mb-1 font-semibold">Slug ({{ $langCode }})</label>
                                    <input type="text" wire:model.defer="translations.{{ $langCode }}.slug"
                                        class="w-full border p-2 rounded mb-1" placeholder="page-slug">
                                    <p class="text-xs text-gray-500">{{ __('Use lowercase letters, numbers, and dashes only. Leave empty to auto-generate for the main language.') }}</p>
                                    @error("translations.{$langCode}.slug")
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block mb-1 font-semibold">{{ __('Page Content') }} ({{ $langCode }})</label>
                                    <textarea wire:model.defer="translations.{{ $langCode }}.content"
                                        class="w-full border p-2 rounded h-40"
                                        placeholder="{{ __('Page Content') }}"></textarea>
                                    @error("translations.{$langCode}.content")
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="border-t pt-4 space-y-3">
                                    <h3 class="text-sm font-semibold text-gray-600">{{ __('SEO Meta') }}</h3>

                                    <div>
                                        <label class="block mb-1 font-semibold">{{ __('Meta Title') }} ({{ $langCode }})</label>
                                        <input type="text" wire:model.defer="translations.{{ $langCode }}.meta_title"
                                            class="w-full border p-2 rounded mb-1" placeholder="{{ __('Meta Title') }}">
                                        @error("translations.{$langCode}.meta_title")
                                            <span class="text-red-500 text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block mb-1 font-semibold">{{ __('Meta Description') }} ({{ $langCode }})</label>
                                        <textarea wire:model.defer="translations.{{ $langCode }}.meta_description"
                                            class="w-full border p-2 rounded h-24"
                                            placeholder="{{ __('Short description for search engines') }}"></textarea>
                                        <p class="text-xs text-gray-500">{{ __('Aim for 50-160 characters. Leave empty to reuse the title.') }}</p>
                                        @error("translations.{$langCode}.meta_description")
                                            <span class="text-red-500 text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block mb-1 font-semibold">{{ __('Meta Keywords') }} ({{ $langCode }})</label>
                                        <input type="text" wire:model.defer="translations.{{ $langCode }}.meta_keywords"
                                            class="w-full border p-2 rounded mb-1" placeholder="keyword 1, keyword 2">
                                        <p class="text-xs text-gray-500">{{ __('Separate keywords with a comma or Arabic comma (?).') }}</p>
                                        @error("translations.{$langCode}.meta_keywords")
                                            <span class="text-red-500 text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block mb-1 font-semibold">{{ __('Open Graph Image URL') }} ({{ $langCode }})</label>
                                        <input type="text" wire:model.defer="translations.{{ $langCode }}.og_image"
                                            class="w-full border p-2 rounded mb-1" placeholder="https://example.com/og-image.jpg">
                                        <p class="text-xs text-gray-500">{{ __('Use a full URL or a Storage link generated via asset()/Storage::url(). Recommended size 1200x630px.') }}</p>
                                        @error("translations.{$langCode}.og_image")
                                            <span class="text-red-500 text-sm">{{ $message }}</span>
                                        @enderror

                                        @if($ogImageValue)
                                            <div class="mt-2">
                                                <p class="text-xs text-gray-500 mb-1">{{ __('Preview') }}</p>
                                                <img src="{{ $ogImageValue }}" alt="OG image preview" class="max-h-32 rounded border">
                                            </div>
                                        @endif
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
            <h3 class="font-semibold">{{ __('Publishing Options') }}</h3>

            <div>
                <label class="block font-semibold">{{ __('Status') }}</label>
                <label class="flex items-center gap-2">
                    <input type="radio" wire:model="is_active" value="0" class="form-radio">
                    <span>{{ __('Draft') }}</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" wire:model="is_active" value="1" class="form-radio">
                    <span>{{ __('Published') }}</span>
                </label>
            </div>

            <div>
                <label class="block font-semibold">{{ __('Publish Date') }}</label>
                <input type="datetime-local" wire:model="published_at" class="w-full border p-2 rounded">
            </div>

            <button type="button" wire:click="save"
                class="w-full bg-primary text-white py-2 rounded hover:bg-primary/80 transition">
                {{ __('Publish') }}
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
