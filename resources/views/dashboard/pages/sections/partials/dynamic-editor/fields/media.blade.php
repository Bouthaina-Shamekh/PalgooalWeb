<div class="{{ $field['wrapperClass'] }}">
    <x-dashboard.media-picker :name="$field['name']" :label="$field['label']" :button-text="__('Choose From Media Library')"
        :value="$field['value']" :preview-urls="$field['previewUrls']" :multiple="false" store-value="id" />

    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-500">
        @if (! $field['isTranslatable'])
            <span class="rounded-full bg-slate-100 px-2 py-0.5 font-medium text-slate-600">
                {{ __('Shared') }}
            </span>
        @endif

        @if (filled($field['helpText']))
            <span>{{ $field['helpText'] }}</span>
        @endif
    </div>
</div>
