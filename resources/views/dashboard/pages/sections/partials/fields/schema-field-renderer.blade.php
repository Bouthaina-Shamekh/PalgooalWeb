@php
    $fieldType = $fieldType ?? null;

    $baseProps = [
        'label' => $label,
        'name' => $name,
        'value' => $value,
        'placeholder' => $placeholder ?? null,
        'schemaField' => $schemaField ?? null,
        'wrapperClass' => $wrapperClass ?? '',
    ];
@endphp

@if ($fieldType === 'textarea')
    @php
        $textareaProps = array_merge($baseProps, [
            'rows' => $rows ?? 4,
        ]);

        if (isset($labelClass)) {
            $textareaProps['labelClass'] = $labelClass;
        }

        if (isset($textareaClass)) {
            $textareaProps['textareaClass'] = $textareaClass;
        }
    @endphp

    @include('dashboard.pages.sections.partials.fields.schema-textarea', $textareaProps)
@elseif (in_array($fieldType, ['text', 'url'], true))
    @php
        $inputProps = array_merge($baseProps, [
            'type' => $fieldType,
        ]);

        if (isset($labelClass)) {
            $inputProps['labelClass'] = $labelClass;
        }

        if (isset($inputClass)) {
            $inputProps['inputClass'] = $inputClass;
        }
    @endphp

    @include('dashboard.pages.sections.partials.fields.schema-text-input', $inputProps)
@else
    <!-- Unsupported schema field type. -->
@endif
