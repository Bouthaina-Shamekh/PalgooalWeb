@php
    $displayValue = is_bool($value)
        ? ($value ? '1' : '0')
        : ((is_scalar($value) || $value === null) ? (string) $value : '');
@endphp

<textarea
    id="{{ $inputId }}"
    name="{{ $inputName }}"
    class="form-control"
    rows="2"
    placeholder="{{ $placeholder ?? '' }}"
>{{ $displayValue }}</textarea>
