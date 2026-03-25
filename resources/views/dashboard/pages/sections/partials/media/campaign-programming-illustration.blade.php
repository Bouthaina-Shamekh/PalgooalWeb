<div class="lg:col-span-2">
    <x-dashboard.media-picker :name="'translations[' . $code . '][content][media_url]'" :label="$isProgrammingShowcase ? __('Featured Image') : __('Illustration')" :button-text="__('Choose From Media Library')" :value="$campaignIllustrationValue" :preview-urls="$campaignIllustrationPreviewUrls"
        :multiple="false" store-value="id"
        data-shared-media-group="{{ $isProgrammingShowcase ? 'programming-showcase-image' : 'hero-campaign-illustration' }}" />
    <p class="mt-2 text-xs text-slate-500">
        {{ $isProgrammingShowcase
            ? __('This featured image is shared across all languages for this section.')
            : __('This illustration is shared across all languages for this hero.') }}
    </p>
</div>
