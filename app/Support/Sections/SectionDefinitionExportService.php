<?php

namespace App\Support\Sections;

use App\Models\Sections\SectionDefinition;
use Illuminate\Support\Carbon;

class SectionDefinitionExportService
{
    /**
     * @param  array<int, int>|null  $definitionIds
     * @return array<string, mixed>
     */
    public function export(?array $definitionIds = null): array
    {
        $query = SectionDefinition::query()
            ->with([
                'fields' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'templates' => fn ($query) => $query->orderByPivot('sort_order')->orderBy('id'),
            ])
            ->orderBy('sort_order')
            ->orderBy('id');

        if (is_array($definitionIds) && $definitionIds !== []) {
            $query->whereIn('id', array_values(array_unique(array_map('intval', $definitionIds))));
        }

        return [
            'metadata' => [
                'version' => 1,
                'exported_at' => Carbon::now()->toIso8601String(),
                'app' => config('app.name'),
                'environment' => app()->environment(),
            ],
            'definitions' => $query->get()
                ->map(fn (SectionDefinition $definition): array => $this->definitionPayload($definition))
                ->values()
                ->all(),
        ];
    }

    public function toJson(array $payload): string
    {
        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function filename(?array $definitionIds = null): string
    {
        $suffix = is_array($definitionIds) && $definitionIds !== [] ? 'selected' : 'all';

        return 'section-definitions-' . $suffix . '-' . now()->format('Ymd-His') . '.json';
    }

    protected function definitionPayload(SectionDefinition $definition): array
    {
        $templates = $definition->templates
            ->map(fn ($template): array => [
                'template_key' => $template->template_key,
                'label' => $template->label,
                'description' => $template->description,
                'category' => $template->category,
                'settings' => $template->settings ?? [],
                'schema' => $template->schema ?? [],
                'is_active' => (bool) $template->is_active,
                'is_visible' => (bool) $template->is_visible,
                'sort_order' => (int) ($template->pivot?->sort_order ?? $template->sort_order ?? 0),
            ])
            ->values()
            ->all();

        return [
            'section_key' => $definition->section_key,
            'template_key' => $definition->primaryTemplateKey(),
            'label' => $definition->label,
            'description' => $definition->description,
            'category' => $definition->category,
            'editor_mode' => SectionDefinition::EDITOR_MODE_DYNAMIC,
            'settings' => $definition->settings ?? [],
            'schema' => $definition->schema ?? [],
            'is_active' => (bool) $definition->is_active,
            'is_visible' => (bool) $definition->is_visible,
            'sort_order' => (int) $definition->sort_order,
            'templates' => $templates,
            'fields' => $definition->fields
                ->map(fn ($field): array => [
                    'field_key' => $field->field_key,
                    'label' => $field->label,
                    'group_name' => $field->group_name,
                    'help_text' => $field->help_text,
                    'field_type' => $field->field_type,
                    'field_scope' => $field->field_scope,
                    'default_value' => $field->default_value ?? [],
                    'options' => $field->options ?? [],
                    'settings' => $field->settings ?? [],
                    'schema' => $field->schema ?? [],
                    'is_required' => (bool) $field->is_required,
                    'validation_rules' => $field->validation_rules ?? [],
                    'is_active' => (bool) $field->is_active,
                    'sort_order' => (int) $field->sort_order,
                ])
                ->values()
                ->all(),
        ];
    }
}
