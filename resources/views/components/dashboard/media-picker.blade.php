@props([
    'name',
    'label' => null,
    'buttonText' => null,
    'multiple' => false,
    'storeValue' => 'id',
    'value' => null,
    'previewUrls' => [],
])

@php
    $rawId = $attributes->get('id');
    $inputId = $rawId ?: 'mp_' . uniqid();
    $previewId = $inputId . '_preview';
    $containerAttributes = $attributes->except('id');
    $isMultiple = (bool) $multiple;
    $buttonText = $buttonText ?: __('Choose From Media Library');

    if ($isMultiple) {
        if (is_string($value)) {
            $idsArray = array_filter(explode(',', $value));
        } elseif (is_array($value)) {
            $idsArray = $value;
        } else {
            $idsArray = [];
        }

        $inputValue = implode(',', $idsArray);
    } else {
        $inputValue = is_array($value) ? (reset($value) ?: '') : ($value ?? '');
    }

    if ($previewUrls instanceof \Illuminate\Support\Collection) {
        $previewUrls = $previewUrls->all();
    }
@endphp

<div {{ $containerAttributes->class('col-span-6') }}>
    @if ($label)
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
            {{ $label }}
        </label>
    @endif

    <input
        type="hidden"
        id="{{ $inputId }}"
        name="{{ $name }}"
        value="{{ $inputValue }}"
    >

    <button
        type="button"
        class="btn-open-media-picker inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
        data-target-input="{{ $inputId }}"
        data-target-preview="{{ $previewId }}"
        data-multiple="{{ $isMultiple ? 'true' : 'false' }}"
        data-store-value="{{ $storeValue }}"
    >
        {{ $buttonText }}
    </button>

    <div id="{{ $previewId }}" class="mt-2 flex flex-wrap gap-2">
        @foreach ($previewUrls as $url)
            @if ($url)
                <div class="relative h-20 w-20 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                    <img src="{{ $url }}" alt="" class="h-full w-full object-cover">
                </div>
            @endif
        @endforeach
    </div>
</div>
