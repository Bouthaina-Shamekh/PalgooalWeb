<?php

namespace App\Support\Sections;

use App\Models\Sections\SectionDefinition;
use App\Models\Sections\SectionDefinitionField;

/**
 * Phase 5D — Normalize definition-driven dynamic section content before persistence.
 *
 * This normalizer is applied after the legacy type-based normalizeContentByType()
 * pass and only when a section is explicitly linked to a SectionDefinition. It:
 *
 *  - keeps only field keys declared in the definition (unknown keys are dropped)
 *  - normalizes scalar values by their declared field_type
 *  - normalizes repeater field arrays item-by-item using item_schema
 *  - removes repeater rows where all meaningful sub-fields are blank
 *  - preserves item order from the submitted array
 *
 * Shared vs translatable scope is handled upstream via syncDynamicDefinitionSharedContent()
 * in SectionController before this normalizer runs.
 */
class DynamicSectionContentNormalizer
{
    /**
     * Normalize raw submitted content using the definition's field declarations.
     *
     * Fields absent from the submission are omitted from the result (not forced
     * to null) so existing saved content is not overwritten by missing inputs.
     * Fields present in the submission but not declared in the definition are
     * silently discarded.
     *
     * @param  array<string, mixed>            $content     Raw submitted content for one locale.
     * @param  SectionDefinition               $definition  Must have 'fields' relation loaded.
     * @return array<string, mixed>
     */
    public function normalize(array $content, SectionDefinition $definition): array
    {
        $fields = $definition->relationLoaded('fields')
            ? $definition->fields
            : $definition->fields()->where('is_active', true)->orderBy('sort_order')->get();

        $normalized = [];

        foreach ($fields as $field) {
            /** @var SectionDefinitionField $field */
            $key = (string) $field->field_key;

            if (! array_key_exists($key, $content)) {
                // Not submitted — leave absent so the caller can distinguish
                // "not submitted" from "submitted as empty".
                continue;
            }

            $raw = $content[$key];

            if ($field->field_type === SectionDefinitionField::FIELD_TYPE_REPEATER) {
                $normalized[$key] = $this->normalizeRepeaterField($raw, $field->getItemSchema());
            } else {
                $normalized[$key] = $this->normalizeScalarValue($raw, $field->field_type);
            }
        }

        return $normalized;
    }

    // -------------------------------------------------------------------------
    // Repeater normalization
    // -------------------------------------------------------------------------

    /**
     * Normalize a repeater field value into a clean, re-indexed array of items.
     *
     * Non-array input and empty arrays both yield []. Rows where every
     * meaningful sub-field is blank are silently dropped.
     *
     * @param  array<int, array<string, mixed>>  $itemSchema
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeRepeaterField(mixed $raw, array $itemSchema): array
    {
        if (! is_array($raw) || $raw === []) {
            return [];
        }

        $items = [];

        foreach ($raw as $row) {
            if (! is_array($row)) {
                // Skip malformed rows (scalar values or null in the item list)
                continue;
            }

            $normalized = $this->normalizeRepeaterItem($row, $itemSchema);

            if ($this->isEmptyItem($normalized, $itemSchema)) {
                // Drop all-blank rows so they do not pollute saved content
                continue;
            }

            $items[] = $normalized;
        }

        // Re-index to keep a clean 0-based JSON array
        return array_values($items);
    }

    /**
     * Normalize a single repeater row against the declared item_schema.
     *
     * Only sub-keys declared in item_schema are kept. Unknown sub-keys from
     * the form submission are discarded. Sub-field values are normalized by
     * their declared type using the same scalar normalizer as top-level fields.
     *
     * When item_schema is empty (not yet declared by the developer), the row
     * is returned as an empty array — the isEmptyItem check will then discard
     * it, which is the safest no-schema behaviour.
     *
     * @param  array<string, mixed>              $row
     * @param  array<int, array<string, mixed>>  $itemSchema
     * @return array<string, mixed>
     */
    protected function normalizeRepeaterItem(array $row, array $itemSchema): array
    {
        if ($itemSchema === []) {
            return [];
        }

        $normalized = [];

        foreach ($itemSchema as $subField) {
            $subKey  = (string) ($subField['key']  ?? '');
            $subType = (string) ($subField['type'] ?? SectionDefinitionField::FIELD_TYPE_TEXT);

            if ($subKey === '') {
                continue;
            }

            // Use null (not missing) as the default so normalization can coerce it
            $rawValue = array_key_exists($subKey, $row) ? $row[$subKey] : null;

            $normalized[$subKey] = $this->normalizeScalarValue($rawValue, $subType);
        }

        return $normalized;
    }

    /**
     * Return true when every meaningful sub-field in a normalized item is blank.
     *
     * Rules:
     *  - boolean sub-fields are never blank (false is a valid authored value).
     *  - media sub-fields are blank only when null (0 would be invalid anyway).
     *  - all other types are blank when null or empty string.
     *
     * @param  array<string, mixed>              $item
     * @param  array<int, array<string, mixed>>  $itemSchema
     */
    protected function isEmptyItem(array $item, array $itemSchema): bool
    {
        if ($itemSchema === []) {
            // No schema — no declared fields, so there is nothing meaningful
            return true;
        }

        foreach ($itemSchema as $subField) {
            $subKey  = (string) ($subField['key']  ?? '');
            $subType = (string) ($subField['type'] ?? SectionDefinitionField::FIELD_TYPE_TEXT);

            if ($subKey === '') {
                continue;
            }

            // Booleans are always considered present (false is intentional)
            if ($subType === SectionDefinitionField::FIELD_TYPE_BOOLEAN) {
                return false;
            }

            $value = $item[$subKey] ?? null;

            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Scalar normalization by type
    // -------------------------------------------------------------------------

    /**
     * Normalize a scalar field value to a type-appropriate clean value.
     *
     * text / textarea / richtext / url → trimmed string or null
     * boolean  → PHP bool (false is always preserved)
     * media    → positive integer ID or null (arrays/objects discarded)
     * select   → trimmed scalar string ('' for blank/null)
     * number   → trimmed string or null (numeric coercion left to validation)
     * repeater → not expected here; passed through unchanged
     * unknown  → passed through unchanged (safe no-op)
     */
    public function normalizeScalarValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            SectionDefinitionField::FIELD_TYPE_TEXT,
            SectionDefinitionField::FIELD_TYPE_TEXTAREA,
            SectionDefinitionField::FIELD_TYPE_RICHTEXT,
            SectionDefinitionField::FIELD_TYPE_URL    => $this->normalizeStringValue($value),

            SectionDefinitionField::FIELD_TYPE_BOOLEAN => $this->normalizeBooleanValue($value),

            SectionDefinitionField::FIELD_TYPE_MEDIA   => $this->normalizeMediaValue($value),

            SectionDefinitionField::FIELD_TYPE_SELECT  => $this->normalizeSelectValue($value),

            SectionDefinitionField::FIELD_TYPE_NUMBER  => $this->normalizeNumberValue($value),

            // Unknown type — pass through without modification so future types
            // do not break persistence before their normalizer is added.
            default => $value,
        };
    }

    /**
     * Trim a string value; return null when blank.
     */
    protected function normalizeStringValue(mixed $value): ?string
    {
        if ($value === null || $value === false) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }

    /**
     * Coerce any truthy/falsy input to a strict PHP bool.
     *
     * HTML checkboxes submit '1' when checked and are absent when unchecked,
     * so both '1'/'0' and true/false are accepted.
     */
    protected function normalizeBooleanValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    /**
     * Normalize a media reference to a positive integer ID or null.
     *
     * Arrays, objects, non-numeric strings, and zero/negative values are all
     * discarded as malformed. Only a positive integer (or numeric string) is
     * accepted as a valid media ID.
     */
    protected function normalizeMediaValue(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        if (is_array($value) || is_object($value)) {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }

    /**
     * Normalize a select value to a trimmed scalar string.
     *
     * Returns '' for null/non-scalar so that "nothing selected" is represented
     * consistently rather than as null.
     */
    protected function normalizeSelectValue(mixed $value): string
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return '';
        }

        return trim((string) $value);
    }

    /**
     * Normalize a number value to a trimmed string or null.
     *
     * The string form is preserved (not cast to int/float) so that validation
     * rules defined by the field author remain in control of numeric coercion.
     */
    protected function normalizeNumberValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
