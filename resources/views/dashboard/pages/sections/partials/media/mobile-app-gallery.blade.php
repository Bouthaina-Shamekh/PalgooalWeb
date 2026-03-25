<div class="lg:col-span-2">
    <div class="rounded-[1.75rem] bg-slate-50/80 p-4 lg:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <label class="block text-sm font-medium text-slate-700">{{ __('Mobile App Gallery') }}</label>
                <p class="mt-1 text-xs text-slate-500">
                    {{ __('Choose the three app screenshots exactly in the order they appear in the frontend grid.') }}
                </p>
            </div>

            <span
                class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-500 shadow-sm ring-1 ring-slate-200">
                {{ __('Shared across all languages') }}
            </span>
        </div>

        <div class="mt-4 space-y-4">
            <div class="rounded-3xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-slate-400">
                            {{ __('Screen 01') }}</p>
                        <h4 class="mt-1 text-sm font-semibold text-slate-900">{{ __('First Screenshot') }}</h4>
                        <p class="mt-1 text-xs text-slate-500">{{ __('Appears in the first slot of the gallery.') }}</p>
                    </div>

                    <span
                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-50 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">1</span>
                </div>

                <x-dashboard.media-picker :name="'translations[' . $code . '][content][image_one]'" :label="__('First Screenshot')" :button-text="__('Choose Screenshot')" :value="$mobileAppImageOneValue"
                    :preview-urls="$mobileAppImageOnePreviewUrls" :multiple="false" store-value="id"
                    data-shared-media-group="mobile-app-showcase-image-one" />
            </div>

            <div class="rounded-3xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-slate-400">
                            {{ __('Screen 02') }}</p>
                        <h4 class="mt-1 text-sm font-semibold text-slate-900">{{ __('Second Screenshot') }}</h4>
                        <p class="mt-1 text-xs text-slate-500">{{ __('Appears in the middle slot of the gallery.') }}
                        </p>
                    </div>

                    <span
                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-50 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">2</span>
                </div>

                <x-dashboard.media-picker :name="'translations[' . $code . '][content][image_two]'" :label="__('Second Screenshot')" :button-text="__('Choose Screenshot')" :value="$mobileAppImageTwoValue"
                    :preview-urls="$mobileAppImageTwoPreviewUrls" :multiple="false" store-value="id"
                    data-shared-media-group="mobile-app-showcase-image-two" />
            </div>

            <div class="rounded-3xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-slate-400">
                            {{ __('Screen 03') }}</p>
                        <h4 class="mt-1 text-sm font-semibold text-slate-900">{{ __('Third Screenshot') }}</h4>
                        <p class="mt-1 text-xs text-slate-500">{{ __('Appears in the third slot of the gallery.') }}
                        </p>
                    </div>

                    <span
                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-50 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">3</span>
                </div>

                <x-dashboard.media-picker :name="'translations[' . $code . '][content][image_three]'" :label="__('Third Screenshot')" :button-text="__('Choose Screenshot')" :value="$mobileAppImageThreeValue"
                    :preview-urls="$mobileAppImageThreePreviewUrls" :multiple="false" store-value="id"
                    data-shared-media-group="mobile-app-showcase-image-three" />
            </div>
        </div>
    </div>
</div>
