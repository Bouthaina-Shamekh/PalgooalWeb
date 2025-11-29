@props([
    'name',                 // اسم الحقل في الفورم (إجباري)
    'label' => null,        // عنوان الحقل
    'buttonText' => 'اختر من مكتبة الوسائط', // نص الزر
    'multiple' => false,    // هل الاختيار متعدد؟
    'value' => null,        // القيمة الحالية (id أو "1,2,3")
    'previewUrls' => [],    // مصفوفة روابط للمعاينة المبدئية
])

@php
    // نقرأ الـ id القادم من الـ attributes (مثلاً featured_image_id)
    $rawId = $attributes->get('id');

    // هذا هو الـ id الذي سيُستخدم للـ <input> المخفي
    $inputId = $rawId ?: 'mp_' . uniqid();
    $previewId = $inputId . '_preview';

    // نتأكد أن الـ wrapper ما ياخذش نفس الـ id عشان ما يكونش عندنا عنصرين بنفس الـ id
    $containerAttributes = $attributes->except('id');

    $isMultiple = (bool) $multiple;

    // تجهيز قيمة الـ input حسب نوع الحقل
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
        // single
        if (is_array($value)) {
            $inputValue = reset($value) ?: '';
        } else {
            $inputValue = $value ?? '';
        }
    }

    // نتأكد أن الـ previewUrls مصفوفة عادية
    if ($previewUrls instanceof \Illuminate\Support\Collection) {
        $previewUrls = $previewUrls->all();
    }
@endphp

<div {{ $containerAttributes->class('col-span-6') }}>
    @if($label)
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
            {{ $label }}
        </label>
    @endif

    {{-- input مخفي لحفظ IDs --}}
    <input
        type="hidden"
        id="{{ $inputId }}"
        name="{{ $name }}"
        value="{{ $inputValue }}"
    >

    {{-- زر فتح الـ Media Picker --}}
    <button
        type="button"
        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 btn-open-media-picker"
        data-target-input="{{ $inputId }}"
        data-target-preview="{{ $previewId }}"
        data-multiple="{{ $isMultiple ? 'true' : 'false' }}"
    >
        {{ $buttonText }}
    </button>

    {{-- منطقة المعاينة --}}
    <div id="{{ $previewId }}" class="mt-2 flex flex-wrap gap-2">
        @foreach($previewUrls as $url)
            @if($url)
                <div class="relative w-20 h-20 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                    <img src="{{ $url }}" alt="" class="w-full h-full object-cover">
                </div>
            @endif
        @endforeach
    </div>
</div>
