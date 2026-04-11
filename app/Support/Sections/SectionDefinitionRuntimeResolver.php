<?php

namespace App\Support\Sections;

use App\Models\Section;
use App\Models\Sections\SectionDefinition;
use App\Models\Sections\SectionDefinitionField;
use Illuminate\Support\Facades\Schema;

/**
 * Shared runtime rules for definition-driven section behavior.
 *
 * This class keeps the editor and frontend aligned on one decision:
 * a section instance may use the definition-driven path only when it is
 * explicitly linked to an active dynamic definition that also has a selected
 * template key.
 */
class SectionDefinitionRuntimeResolver
{
    /**
     * Determine whether the relational definition-driven runtime can be used.
     */
    public function runtimeTablesAvailable(): bool
    {
        return Schema::hasTable('section_definitions')
            && Schema::hasTable('section_templates')
            && Schema::hasTable('section_definition_template')
            && Schema::hasColumn('sections', 'section_definition_id');
    }

    /**
     * Determine whether field-definition records are available.
     */
    public function fieldTablesAvailable(): bool
    {
        return Schema::hasTable('section_definition_fields');
    }

    /**
     * Resolve the linked definition only when it is eligible for the shared
     * definition-driven runtime.
     *
     * The same eligibility rule is used by both the admin editor and the
     * frontend renderer so neither side silently diverges.
     */
    public function resolveDynamicDefinition(Section $section): ?SectionDefinition
    {
        if (! $this->runtimeTablesAvailable() || ! $section->section_definition_id) {
            return null;
        }

        $query = $section->sectionDefinition()
            ->where('is_active', true)
            ->where('editor_mode', SectionDefinition::EDITOR_MODE_DYNAMIC)
            ->with([
                'templates' => fn ($templateQuery) => $templateQuery
                    ->where('is_active', true)
                    ->orderByPivot('sort_order')
                    ->orderBy('id'),
            ]);

        if ($this->fieldTablesAvailable()) {
            $query->with([
                'fields' => fn ($fieldQuery) => $fieldQuery
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ]);
        }

        $definition = $query->first();

        if (! $definition) {
            return null;
        }

        $templateKey = $definition->primaryTemplateKey();

        return is_string($templateKey) && trim($templateKey) !== ''
            ? $definition
            : null;
    }

    /**
     * Resolve a field default with the shared locale fallback contract:
     * current locale -> app fallback locale -> first available value.
     *
     * @return array{0: bool, 1: mixed}
     */
    public function resolvedDefaultValue(SectionDefinitionField $field, string $locale): array
    {
        $defaultValue = $field->default_value;

        if (! is_array($defaultValue)) {
            return [false, null];
        }

        if ($field->isTranslatable()) {
            if (array_key_exists($locale, $defaultValue)) {
                return [true, $defaultValue[$locale]];
            }

            $fallbackLocale = (string) config('app.fallback_locale', '');

            if ($fallbackLocale !== '' && array_key_exists($fallbackLocale, $defaultValue)) {
                return [true, $defaultValue[$fallbackLocale]];
            }

            if ($defaultValue !== []) {
                return [true, reset($defaultValue)];
            }

            return [false, null];
        }

        if (array_key_exists('value', $defaultValue)) {
            return [true, $defaultValue['value']];
        }

        return $defaultValue !== []
            ? [true, $defaultValue]
            : [false, null];
    }
}
