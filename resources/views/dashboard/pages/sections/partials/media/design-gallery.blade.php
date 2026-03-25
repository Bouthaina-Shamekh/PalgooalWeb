<div class="lg:col-span-2">
    <div class="mb-3">
        <label class="block text-sm font-medium text-slate-700">{{ __('Design Gallery') }}</label>
        <p class="mt-1 text-xs text-slate-500">
            {{ __('Choose the six images used in the two-row design portfolio grid.') }}
        </p>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div>
            <x-dashboard.media-picker :name="'translations[' . $code . '][content][image_one]'" :label="__('Image 1')" :button-text="__('Choose From Media Library')" :value="$mobileAppImageOneValue"
                :preview-urls="$mobileAppImageOnePreviewUrls" :multiple="false" store-value="id"
                data-shared-media-group="design-showcase-image-one" />
        </div>

        <div>
            <x-dashboard.media-picker :name="'translations[' . $code . '][content][image_two]'" :label="__('Image 2')" :button-text="__('Choose From Media Library')" :value="$mobileAppImageTwoValue"
                :preview-urls="$mobileAppImageTwoPreviewUrls" :multiple="false" store-value="id"
                data-shared-media-group="design-showcase-image-two" />
        </div>

        <div>
            <x-dashboard.media-picker :name="'translations[' . $code . '][content][image_three]'" :label="__('Image 3')" :button-text="__('Choose From Media Library')" :value="$mobileAppImageThreeValue"
                :preview-urls="$mobileAppImageThreePreviewUrls" :multiple="false" store-value="id"
                data-shared-media-group="design-showcase-image-three" />
        </div>

        <div>
            <x-dashboard.media-picker :name="'translations[' . $code . '][content][image_four]'" :label="__('Image 4')" :button-text="__('Choose From Media Library')" :value="$designImageFourValue"
                :preview-urls="$designImageFourPreviewUrls" :multiple="false" store-value="id"
                data-shared-media-group="design-showcase-image-four" />
        </div>

        <div>
            <x-dashboard.media-picker :name="'translations[' . $code . '][content][image_five]'" :label="__('Image 5')" :button-text="__('Choose From Media Library')" :value="$designImageFiveValue"
                :preview-urls="$designImageFivePreviewUrls" :multiple="false" store-value="id"
                data-shared-media-group="design-showcase-image-five" />
        </div>

        <div>
            <x-dashboard.media-picker :name="'translations[' . $code . '][content][image_six]'" :label="__('Image 6')" :button-text="__('Choose From Media Library')" :value="$designImageSixValue"
                :preview-urls="$designImageSixPreviewUrls" :multiple="false" store-value="id"
                data-shared-media-group="design-showcase-image-six" />
        </div>
    </div>

    <p class="mt-2 text-xs text-slate-500">
        {{ __('These gallery images are shared across all languages for this section.') }}
    </p>
</div>
