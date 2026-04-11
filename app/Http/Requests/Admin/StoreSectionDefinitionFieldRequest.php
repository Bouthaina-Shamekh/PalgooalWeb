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
