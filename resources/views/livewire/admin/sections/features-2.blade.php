<div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg mb-8 border border-gray-200 dark:border-gray-700 space-y-6">
    @if (session()->has('success'))
        <div class="flex items-center gap-3 bg-green-100 text-green-900 border border-green-300 dark:bg-green-800/20 dark:text-green-100 dark:border-green-600 px-4 py-3 rounded-lg shadow transition-all duration-300" role="alert">
            <svg class="w-5 h-5 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <div class="flex justify-between items-center">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white">{{ ucfirst(str_replace('-', ' ', $section->key)) }}</h3>
        <button onclick="confirmDeleteSection({{ $section->id }})" class="text-red-600 hover:underline text-sm">
            {{ t('section.Delete', 'Delete') }}
        </button>
    </div>

    <div class="col-span-12 md:col-span-2 mb-4">
        <label class="form-label">{{ t('section.Section_Arrangement', 'Section Arrangement') }}</label>
        <input type="number" wire:model.defer="order" class="form-control" placeholder="{{ t('section.Example:', 'Example: 1, 2, 3') }}" />
    </div>

    {{-- Tailwind safelist: bg-white dark:bg-gray-950 bg-gray-50 dark:bg-gray-900 bg-stone-50 dark:bg-stone-900 bg-slate-100 dark:bg-slate-900 bg-slate-900 text-white bg-zinc-900 bg-gray-950 bg-sky-50 dark:bg-sky-900 bg-blue-50 dark:bg-blue-900 bg-indigo-600 bg-violet-600 bg-purple-600 bg-amber-50 dark:bg-amber-900 bg-orange-500 bg-rose-50 dark:bg-rose-900 bg-rose-600 bg-emerald-50 dark:bg-emerald-900 bg-emerald-600 bg-teal-500 --}}
    <div class="flex flex-wrap gap-2 mt-4">
        @foreach ($languages as $lang)
            <button wire:click="setActiveLang('{{ $lang->code }}')"
                class="px-4 py-2 text-sm font-medium rounded-lg transition {{ $activeLang === $lang->code ? 'bg-primary text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white hover:bg-primary hover:text-white' }}">
                {{ $lang->native }}
            </button>
        @endforeach
    </div>

    <div class="mt-6 flex flex-wrap items-center gap-3 border-b border-gray-200 dark:border-gray-700 pb-3">
        <button type="button" wire:click="setActiveTab('content')"
            class="px-3 py-1.5 text-sm font-semibold rounded-md transition {{ $activeTab === 'content' ? 'bg-primary text-white shadow' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-primary/80 hover:text-white' }}">
            {{ t('section.Content_Tab', 'Content') }}
        </button>
        <button type="button" wire:click="setActiveTab('style')"
            class="px-3 py-1.5 text-sm font-semibold rounded-md transition {{ $activeTab === 'style' ? 'bg-primary text-white shadow' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-primary/80 hover:text-white' }}">
            {{ t('section.Style_Tab', 'Style') }}
        </button>
    </div>

    @if ($activeTab === 'content')
        <div wire:key="features-2-content-{{ $activeLang }}" class="grid grid-cols-12 gap-6">
            <div class="col-span-12 md:col-span-6 mb-4">
                <label class="form-label">{{ t('section.Title', 'Title') }}</label>
                <input type="text" wire:model.defer="translationsData.{{ $activeLang }}.title" class="form-control" placeholder="{{ t('section.Title', 'Title') }}" />
            </div>

            <div class="col-span-12 md:col-span-6 mb-4">
                <label class="form-label">{{ t('section.Brief_description', 'Brief description') }}</label>
                <input type="text" wire:model.defer="translationsData.{{ $activeLang }}.subtitle" class="form-control" placeholder="{{ t('section.Brief_description', 'Brief description') }}" />
            </div>

            <div class="col-span-12 md:col-span-6 mb-4">
                <label class="form-label">{{ t('section.Button_Text', 'Button Text') }}</label>
                <input type="text" wire:model.defer="translationsData.{{ $activeLang }}.button_text" class="form-control" placeholder="{{ t('section.Button_Text', 'Button Text') }}" />
            </div>

            <div class="col-span-12 md:col-span-6 mb-4">
                <label class="form-label">{{ t('section.Button_URL', 'Button URL') }}</label>
                <input type="text" wire:model.defer="translationsData.{{ $activeLang }}.button_url" class="form-control" placeholder="https://example.com" />
            </div>

            <div class="col-span-12 space-y-4">
                @foreach ($translationsData[$activeLang]['features'] ?? [] as $index => $feature)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start border border-dashed border-gray-300 dark:border-gray-700 p-4 rounded-xl relative bg-gray-50 dark:bg-gray-900/60">
                        <div class="md:col-span-1">
                            <label class="form-label">{{ t('section.Icon_html', 'Icon (SVG/HTML)') }}</label>
                            <textarea wire:model.defer="translationsData.{{ $activeLang }}.features.{{ $index }}.icon"
                                class="form-textarea w-full h-28 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800"
                                placeholder="<svg>...</svg>"></textarea>
                        </div>

                        <div>
                            <label class="form-label">{{ t('section.Feature_Title', 'Feature Title') }}</label>
                            <input type="text" wire:model.defer="translationsData.{{ $activeLang }}.features.{{ $index }}.title"
                                class="form-control" placeholder="{{ t('section.Feature_Title', 'Feature Title') }}" />
                        </div>

                        <div>
                            <label class="form-label">{{ t('section.Feature_Description', 'Feature Description') }}</label>
                            <textarea wire:model.defer="translationsData.{{ $activeLang }}.features.{{ $index }}.description"
                                class="form-textarea w-full h-24 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800"
                                placeholder="{{ t('section.Feature_Description', 'Feature Description') }}"></textarea>
                        </div>

                        <button type="button" wire:click="removeFeature('{{ $activeLang }}', {{ $index }})"
                            class="absolute -top-2 -left-2 bg-red-500 text-white text-sm rounded-full w-7 h-7 flex items-center justify-center hover:bg-red-600 transition">
                            &times;
                        </button>
                    </div>
                @endforeach

                <div class="text-end">
                    <button type="button" wire:click="addFeature('{{ $activeLang }}')" class="btn btn-primary">
                        {{ t('section.Add_Feature', '+Add Feature') }}
                    </button>
                </div>
            </div>
        </div>
    @else
        <div wire:key="features-2-style-{{ $activeLang }}" class="grid grid-cols-12 gap-6">
            @php
                $selectedVariant = $translationsData[$activeLang]['background_variant'] ?? 'white';
                $selectedPreset = $backgroundPresets[$selectedVariant] ?? $backgroundPresets['white'];
            @endphp

            <div class="col-span-12 lg:col-span-6 space-y-4">
                <label class="form-label">{{ t('section.Background_Color', 'Background Style') }}</label>

                <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex flex-col gap-4">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="h-12 w-12 rounded-full border border-black/10 dark:border-white/10 {{ $selectedPreset['classes'] ?? '' }}"
                                title="{{ strtoupper($selectedPreset['preview'] ?? '#FFFFFF') }}">
                            </span>
                            <div class="flex flex-col gap-1">
                                <div class="text-sm font-semibold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                                    {{ t('section.palette.' . $selectedVariant, $selectedPreset['label'] ?? 'White') }}
                                    <span class="text-xs font-normal text-gray-500 dark:text-gray-400">
                                        {{ strtoupper($selectedPreset['preview'] ?? '#FFFFFF') }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ t('section.Selected_Background', 'Selected background') }}
                                </div>
                            </div>
                        </div>
                        <button type="button"
                            wire:click="resetBackgroundVariant('{{ $activeLang }}')"
                            wire:loading.attr="disabled"
                            @if ($selectedVariant === 'white') disabled @endif
                            class="px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ t('section.Reset_to_default', 'Reset') }}
                        </button>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ t('section.Background_Color_Hint', 'Choose a color from the palette or reset to the default white background.') }}
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{ t('section.Select_Background_Variant', 'Choose a background variant') }}
                    </label>
                    <select
                        wire:model.live="translationsData.{{ $activeLang }}.background_variant"
                        class="form-select w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-gray-100 focus:ring focus:ring-primary/30 focus:border-primary"
                    >
                        @foreach ($backgroundGroups as $groupLabel => $keys)
                            <optgroup label="{{ t('section.palette.' . \Illuminate\Support\Str::slug($groupLabel), $groupLabel) }}">
                                @foreach ($keys as $presetKey)
                                    @php
                                        $preset = $backgroundPresets[$presetKey] ?? null;
                                    @endphp
                                    @continue(!$preset)
                                    <option value="{{ $presetKey }}">
                                        {{ t('section.palette.' . $presetKey, $preset['label']) }}
                                        ({{ strtoupper($preset['preview'] ?? '') }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ t('section.Background_Color_Help', 'Pick a preset from the list above or reset to the default white background.') }}
                </p>
            </div>

            <div class="col-span-12 lg:col-span-6">
                @php
                    $previewClasses = $selectedPreset['classes'] ?? $backgroundPresets['white']['classes'];
                @endphp
                <label class="form-label">{{ t('section.Live_Preview', 'Live preview') }}</label>
                <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 p-6 {{ $previewClasses }}">
                    <p class="text-sm {{ str_contains($previewClasses, 'text-white') ? 'text-white/80' : 'text-gray-700 dark:text-gray-200' }}">
                        {{ t('section.Style_Preview_Message', 'Preview how the section background will look.') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="text-end">
        <button wire:click="updateFeatures2Section" class="btn btn-primary">
            {{ t('section.Save_changes', 'Save changes') }}
        </button>
    </div>
</div>
