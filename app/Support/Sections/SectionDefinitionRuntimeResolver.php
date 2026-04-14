<?php

namespace App\Support\Sections;

use App\Models\Section;
use App\Models\Sections\SectionDefinition;
use App\Models\Sections\SectionDefinitionField;
use Illuminate\Support\Facades\Schema;

/**
 * Shared runtime rules for definition-driven section behavior.
 *
 * Dynamic editor rendering remains stricter than frontend rendering:
 * - the dynamic editor only activates for linked active definitions whose
 *   editor_mode is `dynamic`
 * - frontend definition-driven rendering may use any linked active definition
 *   that has a selected primary template key
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
     * Resolve the linked definition for the dynamic editor path only.
     */
    public function resolveDynamicDefinition(Section $section): ?SectionDefinition
    {
        $definition = $this->resolveLinkedDefinition($section, SectionDefinition::EDITOR_MODE_DYNAMIC);

        return $this->hasPrimaryTemplate($definition)
            ? $definition
            : null;
    }

    /**
     * Resolve the linked definition for frontend definition-driven rendering.
     *
     * Unlike the dynamic editor path, frontend rendering should not exclude
     * linked custom-preset definitions when they already have a selected
     * primary template key.
     */
    public function resolveRenderableDefinition(Section $section): ?SectionDefinition
    {
        $definition = $this->resolveLinkedDefinition($section);

        return $this->hasPrimaryTemplate($definition)
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

    protected function resolveLinkedDefinition(
        Section $section,
        ?string $editorMode = null,
    ): ?SectionDefinition {
        if (! $this->runtimeTablesAvailable() || ! $section->section_definition_id) {
            return null;
        }

        $query = $section->sectionDefinition()
            ->where('is_active', true)
            ->with([
                'templates' => fn ($templateQuery) => $templateQuery
                    ->where('is_active', true)
                    ->orderByPivot('sort_order')
                    ->orderBy('id'),
            ]);

        if ($editorMode !== null) {
            $query->where('editor_mode', $editorMode);
        }

        if ($this->fieldTablesAvailable()) {
            $query->with([
                'fields' => fn ($fieldQuery) => $fieldQuery
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ]);
        }

        return $query->first();
    }

    protected function hasPrimaryTemplate(?SectionDefinition $definition): bool
    {
        if (! $definition instanceof SectionDefinition) {
            return false;
        }

        $templateKey = $definition->primaryTemplateKey();

        return is_string($templateKey) && trim($templateKey) !== '';
    }
}
