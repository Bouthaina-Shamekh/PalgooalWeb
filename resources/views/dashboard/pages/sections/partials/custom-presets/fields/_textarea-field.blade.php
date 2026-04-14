@props([
    'label',
    'name',
    'value'       => '',
    'rows'        => 4,
    'placeholder' => '',
    'colSpan'     => 'lg:col-span-2',
])
<div @if ($colSpan) class="{{ $colSpan }}" @endif>
    <label class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    <textarea name="{{ $name }}"
        rows="{{ $rows }}"
        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif>{{ $value }}</textarea>
</div>
