<?php

namespace App\Support\Sections;

use App\Models\Sections\SectionDefinitionField;
use Illuminate\Support\Collection;

/**
 * Build and normalize admin form data for developer section definition fields.
 *
 * This keeps locale-aware default-value shaping and metadata normalization out
 * of Blade views and away from low-level controller orchestration.
 */
class SectionDefinitionFieldFormDataFactory
{
    /**
     * Return human-readable field type options for the admin form.
     *
     * The order here determines the order rendered in the type <select>.
     * Repeater is listed last as it requires item_schema configuration
     * (Phase 5B) before it becomes useful in the dynamic editor.
     *
     * @return array<string, string>
     */
    public function fieldTypeOptions(): array
    {
        return [
            SectionDefinitionField::FIELD_TYPE_TEXT => __('Text'),
            SectionDefinitionField::FIELD_TYPE_TEXTAREA => __('Textarea'),
            SectionDefinitionField::FIELD_TYPE_RICHTEXT => __('Rich Text'),
            SectionDefinitionField::FIELD_TYPE_URL => __('URL'),
            SectionDefinitionField::FIELD_TYPE_MEDIA => __('Media'),
            SectionDefinitionField::FIELD_TYPE_NUMBER => __('Number'),
            SectionDefinitionField::FIELD_TYPE_BOOLEAN => __('Boolean'),
            SectionDefinitionField::FIELD_TYPE_SELECT => __('Select'),
            // Phase 5A: repeater is a recognized type. The item_schema editor
            // UI and the dynamic editor rendering panel are deferred to Phase 5B.
            SectionDefinitionField::FIELD_TYPE_REPEATER => __('Repeater'),
        ];
    }

    /**
     * Return human-readable type options for repeater sub-fields.
     *
     * Derived from fieldTypeOptions() filtered to the allowed sub-field type list,
     * preserving insertion order (richtext and repeater are intentionally absent).
     *
     * @return array<string, string>
     */
    public function repeaterSubFieldTypeOptions(): array
    {
        $allowed = array_flip(SectionDefinitionField::repeaterSubFieldTypes());

        return array_filter(
            $this->fieldTypeOptions(),
            fn (string $key) => isset($allowed[$key]),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * Prepare view-ready values for the create/edit field form.
     *
     * @param  array<int, array{code: string, label: string}>  $locales
     * @return array<string, mixed>
     */
    public function build(SectionDefinitionField $field, array $locales): array
    {
        $defaultValue = is_array($field->default_value) ? $field->default_value : [];

        return [
            'fieldTypeOptions' => $this->fieldTypeOptions(),
            'selectedFieldType' => old('type', $field->field_type ?: SectionDefinitionField::FIELD_TYPE_TEXT),
            'sharedDefaultValue' => old('default_value_shared', $defaultValue['value'] ?? null),
            'translatableDefaultValues' => $this->translatableDefaultValues($locales, $defaultValue),
            'optionsTextarea' => old('options', $this->optionsTextarea($field->options)),
            'validationRulesTextarea' => old('validation_rules', $this->validationRulesTextarea($field->validation_rules)),
            'settingsJson' => old('settings', $this->settingsJson($field->settings)),
            // Repeater item schema: old() takes precedence on failed submission,
            // falling back to the saved schema from the model accessor.
            'repeaterItemSchema' => $this->repeaterItemSchemaForForm($field),
            'repeaterSubFieldTypeOptions' => $this->repeaterSubFieldTypeOptions(),
        ];
    }

    /**
     * Normalize validated UI payload into persistable field attributes.
     *
     * @param  array<string, mixed>  $validated
     * @param  array<int, string>  $localeCodes
     * @return array<string, mixed>
     */
    public function persistableAttributes(array $validated, array $localeCodes): array
    {
        $fieldType = (string) $validated['type'];
        $isTranslatable = (bool) ($validated['is_translatable'] ?? false);

        return [
            'field_key' => $validated['key'],
            'label' => $validated['label'],
            'group_name' => $validated['group'] ?? null,
            'field_type' => $fieldType,
            'field_scope' => $isTranslatable
                ? SectionDefinitionField::FIELD_SCOPE_TRANSLATABLE
                : SectionDefinitionField::FIELD_SCOPE_SHARED,
            'default_value' => $this->normalizeDefaultValue($validated, $fieldType, $isTranslatable, $localeCodes),
            'options' => $this->normalizeOptions($validated['options'] ?? null),
            'settings' => $this->normalizeSettings($validated['settings'] ?? null),
            'validation_rules' => $this->normalizeValidationRules($validated['validation_rules'] ?? null),
            'is_required' => (bool) ($validated['is_required'] ?? false),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            // Repeater item schema — null for non-repeater types; stored in the
            // existing `schema` JSON column under the key `item_schema`.
            'schema' => $this->normalizeItemSchemaForPersistence($validated, $fieldType),
        ];
    }

    /**
     * @param  array<int, array{code: string, label: string}>  $locales
     * @param  array<string, mixed>  $defaultValue
     * @return array<string, mixed>
     */
    protected function translatableDefaultValues(array $locales, array $defaultValue): array
    {
        return Collection::make($locales)
            ->mapWithKeys(function (array $locale) use ($defaultValue) {
                $code = (string) ($locale['code'] ?? '');

                return [
                    $code => old("default_value_translations.$code", $defaultValue[$code] ?? null),
                ];
            })
            ->all();
    }

    protected function optionsTextarea(mixed $options): string
    {
        $items = Collection::make(is_array($options) ? $options : [])
            ->map(function ($item) {
                if (! is_array($item)) {
                    return is_scalar($item) ? trim((string) $item) : '';
                }

                $value = trim((string) ($item['value'] ?? ''));
                $label = trim((string) ($item['label'] ?? ''));

                if ($value === '' && $label === '') {
                    return '';
                }

                return $label !== '' && $label !== $value
                    ? $value . '|' . $label
                    : $value;
            })
            ->filter()
            ->values()
            ->all();

        return implode("\n", $items);
    }

    protected function validationRulesTextarea(mixed $rules): string
    {
        return Collection::make(is_array($rules) ? $rules : [])
            ->map(fn ($rule) => is_scalar($rule) ? trim((string) $rule) : '')
            ->filter()
            ->implode("\n");
    }

    protected function settingsJson(mixed $settings): string
    {
        if (! is_array($settings) || $settings === []) {
            return '';
        }

        return (string) json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<int, string>  $localeCodes
     * @return array<string, mixed>|null
     */
    protected function normalizeDefaultValue(
        array $validated,
        string $fieldType,
        bool $isTranslatable,
        array $localeCodes,
    ): ?array {
        if ($isTranslatable) {
            $translations = [];
            $rawTranslations = is_array($validated['default_value_translations'] ?? null)
                ? $validated['default_value_translations']
                : [];

            foreach ($localeCodes as $localeCode) {
                $normalized = $this->normalizeScalarDefaultValue($rawTranslations[$localeCode] ?? null, $fieldType);

                if ($normalized !== null) {
                    $translations[$localeCode] = $normalized;
                }
            }

            return $translations === [] ? null : $translations;
        }

        $normalized = $this->normalizeScalarDefaultValue($validated['default_value_shared'] ?? null, $fieldType);

        return $normalized === null ? null : ['value' => $normalized];
    }

    protected function normalizeOptions(?string $rawOptions): ?array
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $rawOptions) ?: [];

        $options = Collection::make($lines)
            ->map(function (string $line) {
                $line = trim($line);

                if ($line === '') {
                    return null;
                }

                [$value, $label] = array_pad(explode('|', $line, 2), 2, null);

                $value = trim((string) $value);
                $label = trim((string) ($label ?? $value));

                if ($value === '') {
                    return null;
                }

                return [
                    'value' => $value,
                    'label' => $label !== '' ? $label : $value,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return $options === [] ? null : $options;
    }

    protected function normalizeSettings(?string $rawSettings): ?array
    {
        $rawSettings = trim((string) $rawSettings);

        if ($rawSettings === '') {
            return null;
        }

        $decoded = json_decode($rawSettings, true);

        return is_array($decoded) ? $decoded : null;
    }

    protected function normalizeValidationRules(?string $rawRules): ?array
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $rawRules) ?: [];

        $rules = Collection::make($lines)
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();

        return $rules === [] ? null : $rules;
    }

    /**
     * Return the repeater item_schema ready for the create/edit form.
     *
     * old() takes precedence on a failed validation round-trip so the user's
     * in-progress edits are preserved. Falls back to the normalized schema
     * already stored on the model (via repeaterItemSchema()).
     *
     * @return array<int, array{key: string, label: string, type: string, required: bool, translatable: bool, options?: string}>
     */
    protected function repeaterItemSchemaForForm(SectionDefinitionField $field): array
    {
        $oldItemSchema = old('item_schema');

        if (is_array($oldItemSchema)) {
            $allowedSubTypes = SectionDefinitionField::repeaterSubFieldTypes();

            return Collection::make($oldItemSchema)
                ->filter(fn ($item) => is_array($item))
                ->map(function (array $item) use ($allowedSubTypes): ?array {
                    $key  = trim((string) ($item['key'] ?? ''));
                    $type = trim((string) ($item['type'] ?? 'text'));

                    if ($key === '' || ! in_array($type, $allowedSubTypes, true)) {
                        return null;
                    }

                    $normalized = [
                        'key'          => $key,
                        'label'        => trim((string) ($item['label'] ?? '')),
                        'type'         => $type,
                        'required'     => (bool) ($item['required'] ?? false),
                        'translatable' => (bool) ($item['translatable'] ?? true),
                    ];

                    $options = trim((string) ($item['options'] ?? ''));

                    if ($options !== '') {
                        $normalized['options'] = $options;
                    }

                    return $normalized;
                })
                ->filter()
                ->values()
                ->all();
        }

        return $field->repeaterItemSchema();
    }

    /**
     * Normalize the raw item_schema payload for persistence in the `schema` column.
     *
     * Returns null for non-repeater field types so the column is left untouched.
     * For repeater fields, returns `['item_schema' => [...]]` with invalid rows
     * silently discarded (empty key, unrecognized type).
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>|null
     */
    protected function normalizeItemSchemaForPersistence(array $validated, string $fieldType): ?array
    {
        if ($fieldType !== SectionDefinitionField::FIELD_TYPE_REPEATER) {
            return null;
        }

        $rawItems = is_array($validated['item_schema'] ?? null) ? $validated['item_schema'] : [];
        $allowedSubTypes = SectionDefinitionField::repeaterSubFieldTypes();

        $items = Collection::make($rawItems)
            ->map(function (mixed $item) use ($allowedSubTypes): ?array {
                if (! is_array($item)) {
                    return null;
                }

                $key  = trim((string) ($item['key'] ?? ''));
                $type = trim((string) ($item['type'] ?? ''));

                // Both key and type are required for a valid sub-field entry.
                // Validation in the Request classes guarantees this when coming
                // from the admin form (type = repeater), but we guard here too
                // so any programmatic caller gets a clean, predictable result.
                if ($key === '' || ! in_array($type, $allowedSubTypes, true)) {
                    return null;
                }

                $normalized = [
                    'key'          => $key,
                    'label'        => trim((string) ($item['label'] ?? $key)) ?: $key,
                    'type'         => $type,
                    'required'     => (bool) ($item['required'] ?? false),
                    'translatable' => (bool) ($item['translatable'] ?? true),
                ];

                $options = trim((string) ($item['options'] ?? ''));

                if ($options !== '') {
                    $normalized['options'] = $options;
                }

                return $normalized;
            })
            ->filter()
            ->values()
            ->all();

        // Never persist an empty item_schema for a repeater field — it would
        // cause the section editor to show "No sub-fields defined" and make the
        // field completely unusable. Validation should have caught this already,
        // but this guard ensures no code path can create a broken state.
        if ($items === []) {
            throw new \LogicException(
                'Cannot persist a repeater field with an empty item_schema. '
                . 'Add at least one sub-field with a non-empty key and a recognised type '
                . '(' . implode(', ', $allowedSubTypes) . ').'
            );
        }

        return ['item_schema' => $items];
    }

    protected function normalizeScalarDefaultValue(mixed $value, string $fieldType): mixed
    {
        if ($fieldType === SectionDefinitionField::FIELD_TYPE_BOOLEAN) {
            if ($value === null || $value === '') {
                return null;
            }

            return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        }

        if ($fieldType === SectionDefinitionField::FIELD_TYPE_NUMBER) {
            if ($value === null || $value === '') {
                return null;
            }

            return is_numeric($value) ? 0 + $value : null;
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        return $value === '' ? null : $value;
    }
}
