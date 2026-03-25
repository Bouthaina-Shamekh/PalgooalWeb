<div class="lg:col-span-2">
    <div class="mb-3">
        <label class="block text-sm font-medium text-slate-700">{{ __('Technology Logos') }}</label>
        <p class="mt-1 text-xs text-slate-500">
            {{ __('Choose all stack logos from the media library. They will render in one horizontal strip and stay shared across all languages.') }}
        </p>
    </div>

    <x-dashboard.media-picker :name="'translations[' . $code . '][content][logos]'" :label="__('Stack Logos')" :button-text="__('Choose From Media Library')" :value="$techStackLogosValueForComponent" :preview-urls="$techStackLogoPreviewUrls"
        :multiple="true" store-value="id" data-shared-media-group="tech-stack-showcase-logos" />
</div>
