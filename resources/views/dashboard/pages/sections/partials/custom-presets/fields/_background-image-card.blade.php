@props([
    'code',
    'heading',
    'description',
    'value',
    'previewUrls' => [],
    'fieldKey'    => 'background_image',
    'imageAlt'    => null,
    'imageAltKey' => null,
])
<div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/60 p-5">
    <div class="mb-4">
        <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">
            {{ $heading }}
        </h3>
        <p class="mt-1 text-sm text-slate-500">
            {{ $description }}
        </p>
    </div>

    <div class="grid grid-cols-1 gap-5">
        <div>
            <x-dashboard.media-picker
                :name="'translations[' . $code . '][content][' . $fieldKey . ']'"
                :label="__('Background Image')"
                :button-text="__('Choose From Media Library')"
                :value="$value"
                :preview-urls="$previewUrls"
                :multiple="false"
                store-value="id" />
        </div>

        @if ($imageAltKey !== null)
            <div>
                <label class="block text-sm font-medium text-slate-700">{{ __('Image Alt Text') }}</label>
                <input type="text"
                    name="translations[{{ $code }}][content][{{ $imageAltKey }}]"
                    value="{{ $imageAlt }}"
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                    placeholder="{{ __('e.g. Section background image') }}">
            </div>
        @endif
    </div>
</div>
