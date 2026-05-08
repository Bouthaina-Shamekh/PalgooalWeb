<?php

namespace App\Http\Requests\Admin;

use App\Models\Sections\SectionDefinition;
use App\Models\Sections\SectionDefinitionField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSectionDefinitionFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        $field = $this->route('field');

        return $field instanceof SectionDefinitionField
            ? ($this->user()?->can('update', $field) ?? false)
            : false;
    }

    public function rules(): array
    {
        /** @var \App\Models\Sections\SectionDefinition|null $sectionDefinition */
        $sectionDefinition = $this->route('sectionDefinition');
        /** @var \App\Models\Sections\SectionDefinitionField|null $field */
        $field = $this->route('field');

        $isRepeater = $this->input('type') === SectionDefinitionField::FIELD_TYPE_REPEATER;

        return [
            'key' => [
                'required',
                'string',
                'max:150',
                'regex:/^[a-z0-9_.-]+$/',
                Rule::unique('section_definition_fields', 'field_key')
                    ->where('section_definition_id', $sectionDefinition?->id)
                    ->ignore($field?->id),
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
            // Repeater item schema — see StoreSectionDefinitionFieldRequest for full comment.
            'item_schema' => $isRepeater ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
            'item_schema.*.key' => $isRepeater
                ? ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/']
                : ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'item_schema.*.label' => ['nullable', 'string', 'max:255'],
            'item_schema.*.type' => $isRepeater
                ? ['required', 'string', Rule::in(SectionDefinitionField::repeaterSubFieldTypes())]
                : ['nullable', 'string', Rule::in(SectionDefinitionField::repeaterSubFieldTypes())],
            'item_schema.*.options' => ['nullable', 'string'],
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
