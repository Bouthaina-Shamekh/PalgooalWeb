<?php

namespace App\Http\Requests\Admin;

use App\Models\Sections\SectionDefinition;
use App\Support\Sections\SectionTemplateRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSectionDefinitionRequest extends FormRequest
{
    /**
     * Deny by default unless the authenticated user can edit this definition
     * through the bound policy.
     */
    public function authorize(): bool
    {
        $sectionDefinition = $this->route('sectionDefinition');

        return $sectionDefinition instanceof SectionDefinition
            ? ($this->user()?->can('edit', $sectionDefinition) ?? false)
            : false;
    }

    public function rules(): array
    {
        /** @var \App\Models\Sections\SectionDefinition|null $sectionDefinition */
        $sectionDefinition = $this->route('sectionDefinition');
        $allowedTemplateKeys = array_keys(SectionTemplateRegistry::all());
        $currentTemplateKey = $sectionDefinition?->templates()->orderByPivot('sort_order')->first()?->template_key;

        if (is_string($currentTemplateKey) && $currentTemplateKey !== '') {
            $allowedTemplateKeys[] = $currentTemplateKey;
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'key' => [
                'required',
                'string',
                'max:150',
                'regex:/^[a-z0-9_-]+$/',
                Rule::unique('section_definitions', 'section_key')->ignore($sectionDefinition?->id),
            ],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'template_key' => [
                'nullable',
                'string',
                Rule::in($allowedTemplateKeys),
            ],
            'editor_mode' => [
                'required',
                'string',
                Rule::in([
                    SectionDefinition::EDITOR_MODE_DYNAMIC,
                    SectionDefinition::EDITOR_MODE_CUSTOM_PRESET,
                ]),
            ],
            'custom_editor_key' => ['nullable', 'string', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
            'is_visible_in_library' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Normalize simple UI booleans and developer keys before validation.
     */
    public function prepareForValidation(): void
    {
        $editorMode = trim((string) $this->input('editor_mode', SectionDefinition::EDITOR_MODE_DYNAMIC));

        if ($editorMode === 'custom') {
            $editorMode = SectionDefinition::EDITOR_MODE_CUSTOM_PRESET;
        }

        $this->merge([
            'name' => $this->normalizeString('name'),
            'key' => $this->normalizeKey('key'),
            'description' => $this->normalizeNullableString('description'),
            'category' => $this->normalizeNullableString('category'),
            'template_key' => $this->normalizeNullableString('template_key'),
            'editor_mode' => $editorMode,
            'custom_editor_key' => $this->normalizeNullableString('custom_editor_key'),
            'is_active' => $this->boolean('is_active'),
            'is_visible_in_library' => $this->boolean('is_visible_in_library'),
            'sort_order' => $this->filled('sort_order') ? (int) $this->input('sort_order') : 0,
        ]);
    }

    protected function normalizeString(string $key): string
    {
        return trim((string) $this->input($key, ''));
    }

    protected function normalizeNullableString(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : $value;
    }

    protected function normalizeKey(string $key): string
    {
        return strtolower(trim((string) $this->input($key, '')));
    }
}
