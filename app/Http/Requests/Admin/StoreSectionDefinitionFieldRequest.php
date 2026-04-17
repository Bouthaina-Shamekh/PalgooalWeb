<?php

namespace App\Http\Requests\Admin;

use App\Models\Sections\SectionDefinition;
use App\Models\Sections\SectionDefinitionField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSectionDefinitionFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SectionDefinitionField::class) ?? false;
    }

    public function rules(): array
    {
        /** @var \App\Models\Sections\SectionDefinition|null $sectionDefinition */
        $sectionDefinition = $this->route('sectionDefinition');

        $isRepeater = $this->input('type') === SectionDefinitionField::FIELD_TYPE_REPEATER;

        return [
            'key' => [
                'required',
                'string',
                'max:150',
                'regex:/^[a-z0-9_.-]+$/',
                Rule::unique('section_definition_fields', 'field_key')
                    ->where('section_definition_id', $sectionDefinition?->id),
            ],
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(SectionDefinitionField::supportedFieldTypes())],
            'group' => ['nullable', 'string', 'max:100'],
            'is_translatable' => ['sometimes', 'boolean'],
            'is_required' => ['sometimes', 'boolean'],
            'validation_rules' => ['nullable', 'string'],
            'default_value_shared' => ['nullable'],
            'default_value_translations' => ['nullable', 'array'],
            'default_value_translations.*' => ['nullable'],
            'options' => ['nullable', 'string'],
            'settings' => ['nullable', 'json'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            // Repeater item schema — submitted as a nested array when field type = repeater.
            // When type IS repeater: item_schema is required and each row must have a
            // non-empty key and a recognised type. This produces a visible validation error
            // instead of silently discarding rows in normalizeItemSchemaForPersistence().
            // When type is NOT repeater: all rules are nullable so any stale DOM rows that
            // still submit pass validation and are discarded safely.
            'item_schema' => $isRepeater ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
            'item_schema.*.key' => $isRepeater
                ? ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/']
                : ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'item_schema.*.label' => ['nullable', 'string', 'max:255'],
            'item_schema.*.type' => $isRepeater
                ? ['required', 'string', Rule::in(SectionDefinitionField::repeaterSubFieldTypes())]
                : ['nullable', 'string', Rule::in(SectionDefinitionField::repeaterSubFieldTypes())],
            'item_schema.*.required' => ['nullable', 'boolean'],
            'item_schema.*.translatable' => ['nullable', 'boolean'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'key' => strtolower(trim((string) $this->input('key', ''))),
            'label' => trim((string) $this->input('label', '')),
            'group' => $this->nullableTrimmedString('group'),
            'type' => trim((string) $this->input('type', SectionDefinitionField::FIELD_TYPE_TEXT)),
            'validation_rules' => $this->nullableTextarea('validation_rules'),
            'options' => $this->nullableTextarea('options'),
            'settings' => $this->nullableTextarea('settings'),
            'is_translatable' => $this->boolean('is_translatable'),
            'is_required' => $this->boolean('is_required'),
            'sort_order' => $this->filled('sort_order') ? (int) $this->input('sort_order') : 0,
            // Keep item_schema as-is (already an array from form inputs);
            // null-coerce anything that isn't an array so validation sees nullable.
            'item_schema' => is_array($this->input('item_schema')) ? $this->input('item_schema') : null,
        ]);
    }

    protected function nullableTrimmedString(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : $value;
    }

    protected function nullableTextarea(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : $value;
    }
}
