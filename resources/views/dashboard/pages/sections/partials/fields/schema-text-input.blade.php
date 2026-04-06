@php
    $type = $type ?? 'text';
    $placeholder = $placeholder ?? null;
    $schemaField = $schemaField ?? null;
    $wrapperClass = $wrapperClass ?? '';
    $labelClass = $labelClass ?? 'block text-sm font-medium text-slate-700';
    $inputClass =
        $inputClass ??
        'mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900';
@endphp

<div @class([$wrapperClass])
    @if (filled($schemaField)) data-schema-field="{{ $schemaField }}" data-schema-field-label="{{ $label }}" @endif>
    <label class="{{ $labelClass }}">{{ $label }}</label>
    <input type="{{ $type }}" name="{{ $name }}" value="{{ $value }}" class="{{ $inputClass }}"
        @if (filled($placeholder)) placeholder="{{ $placeholder }}" @endif>
</div>
