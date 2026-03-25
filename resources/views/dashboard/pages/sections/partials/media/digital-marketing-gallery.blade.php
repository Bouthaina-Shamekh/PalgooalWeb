<div class="lg:col-span-2">
    <div class="mb-3">
        <label class="block text-sm font-medium text-slate-700">{{ __('Marketing Gallery') }}</label>
        <p class="mt-1 text-xs text-slate-500">
            {{ __('Choose the two images shown in the digital marketing gallery.') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div>
            <x-dashboard.media-picker :name="'translations[' . $code . '][content][image_one]'" :label="__('Image 1')" :button-text="__('Choose From Media Library')" :value="$mobileAppImageOneValue"
                :preview-urls="$mobileAppImageOnePreviewUrls" :multiple="false" store-value="id"
                data-shared-media-group="digital-marketing-showcase-image-one" />
        </div>

        <div>
            <x-dashboard.media-picker :name="'translations[' . $code . '][content][image_two]'" :label="__('Image 2')" :button-text="__('Choose From Media Library')" :value="$mobileAppImageTwoValue"
                :preview-urls="$mobileAppImageTwoPreviewUrls" :multiple="false" store-value="id"
                data-shared-media-group="digital-marketing-showcase-image-two" />
        </div>
    </div>

    <p class="mt-2 text-xs text-slate-500">
        {{ __('These gallery images are shared across all languages for this section.') }}
    </p>
</div>
