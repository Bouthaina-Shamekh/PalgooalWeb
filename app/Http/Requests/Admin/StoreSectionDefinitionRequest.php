<?php

namespace App\Http\Requests\Admin;

use App\Models\Sections\SectionDefinition;
use App\Support\Sections\SectionTemplateRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSectionDefinitionRequest extends FormRequest
{
    /**
     * Deny by default unless the authenticated user can create section
     * definitions through the bound policy.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', SectionDefinition::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'key' => [
                'required',
                'string',
                'max:150',
                'regex:/^[a-z0-9_-]+$/',
                Rule::unique('section_definitions', 'section_key'),
            ],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'preview_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')],
            'template_key' => [
                'nullable',
                'string',
                'max:150',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    if (! SectionTemplateRegistry::isValidTemplateKey((string) $value)) {
                        $fail(t('dashboard.Template_Key_Invalid', 'Template Key may only contain lowercase letters, numbers, underscores, and dashes.'));
                    }
                },
            ],
            'editor_mode' => [
                'nullable',
                'string',
                Rule::in([SectionDefinition::EDITOR_MODE_DYNAMIC]),
            ],
            'is_active' => ['sometimes', 'boolean'],
            'is_visible_in_library' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'visibility_scope' => [
                'sometimes',
                'string',
                Rule::in([
                    SectionDefinition::SCOPE_BOTH,
                    SectionDefinition::SCOPE_ADMIN_ONLY,
                    SectionDefinition::SCOPE_CLIENT_ONLY,
                    SectionDefinition::SCOPE_HIDDEN,
                ]),
            ],
        ];
    }

    /**
     * Normalize simple UI booleans and developer keys before validation.
     */
    public function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->normalizeString('name'),
            'key' => $this->normalizeKey('key'),
            'description' => $this->normalizeNullableString('description'),
            'category' => $this->normalizeNullableString('category'),
            'preview_media_id' => $this->normalizeNullableInteger('preview_media_id'),
            'template_key' => $this->normalizeNullableKey('template_key'),
            'editor_mode' => SectionDefinition::EDITOR_MODE_DYNAMIC,
            'is_active' => $this->boolean('is_active'),
            'is_visible_in_library' => $this->boolean('is_visible_in_library'),
            'sort_order' => $this->filled('sort_order') ? (int) $this->input('sort_order') : 0,
            'visibility_scope' => $this->input('visibility_scope', SectionDefinition::SCOPE_BOTH),
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

    protected function normalizeNullableKey(string $key): ?string
    {
        $value = trim(strtolower((string) $this->input($key, '')));

        return $value === '' ? null : $value;
    }

    protected function normalizeNullableInteger(string $key): ?int
    {
        $value = trim((string) $this->input($key, ''));

        return is_numeric($value) ? (int) $value : null;
    }

    protected function normalizeKey(string $key): string
    {
        return strtolower(trim((string) $this->input($key, '')));
    }
}
