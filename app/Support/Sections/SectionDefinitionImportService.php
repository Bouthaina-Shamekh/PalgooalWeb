<?php

namespace App\Support\Sections;

use App\Models\Sections\SectionDefinition;
use App\Models\Sections\SectionDefinitionField;
use App\Models\Sections\Template as SectionTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SectionDefinitionImportService
{
    public const STRATEGY_SKIP_EXISTING = 'skip_existing';
    public const STRATEGY_UPDATE_EXISTING = 'update_existing';

    /**
     * @return array<string, mixed>
     */
    public function decodeJson(string $json): array
    {
        $payload = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($payload)) {
            throw new \InvalidArgumentException(__('The uploaded file is not valid JSON.'));
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function preview(array $payload): array
    {
        $definitions = $this->rawDefinitions($payload);
        $items = collect($definitions)
            ->map(fn ($definition, int $index): array => $this->previewDefinition($definition, $index))
            ->values();

        return [
            'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
            'items' => $items->all(),
            'summary' => [
                'total' => $items->count(),
                'new' => $items->where('status', 'new')->count(),
                'updates' => $items->where('status', 'update')->count(),
                'invalid' => $items->where('status', 'invalid')->count(),
                'skipped' => $items->where('status', 'skipped')->count(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function import(array $payload, string $strategy): array
    {
        $strategy = in_array($strategy, [self::STRATEGY_SKIP_EXISTING, self::STRATEGY_UPDATE_EXISTING], true)
            ? $strategy
            : self::STRATEGY_SKIP_EXISTING;

        $preview = $this->preview($payload);
        $definitions = $this->rawDefinitions($payload);
        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'invalid' => (int) ($preview['summary']['invalid'] ?? 0),
        ];

        DB::transaction(function () use ($definitions, $preview, $strategy, &$stats): void {
            foreach ($preview['items'] as $item) {
                $index = (int) $item['index'];

                if (($item['status'] ?? null) === 'invalid') {
                    continue;
                }

                $definitionPayload = $this->normalizeDefinition($definitions[$index] ?? []);
                $existing = $this->findExistingDefinition(
                    $definitionPayload['section_key'],
                    $definitionPayload['template_key'],
                );

                if ($existing instanceof SectionDefinition) {
                    if ($strategy === self::STRATEGY_SKIP_EXISTING) {
                        $stats['skipped']++;
                        continue;
                    }

                    $this->updateDefinition($existing, $definitionPayload);
                    $stats['updated']++;

                    continue;
                }

                $this->createDefinition($definitionPayload);
                $stats['created']++;
            }
        });

        return $stats;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, mixed>
     */
    protected function rawDefinitions(array $payload): array
    {
        return is_array($payload['definitions'] ?? null) ? array_values($payload['definitions']) : [];
    }

    protected function previewDefinition(mixed $definition, int $index): array
    {
        $errors = $this->validateDefinition($definition);
        $sectionKey = is_array($definition) ? trim((string) ($definition['section_key'] ?? $definition['key'] ?? '')) : '';
        $templateKey = is_array($definition) ? trim((string) ($definition['template_key'] ?? '')) : '';
        $label = is_array($definition) ? trim((string) ($definition['label'] ?? $definition['name'] ?? '')) : '';
        $existing = $errors === [] ? $this->findExistingDefinition($sectionKey, $templateKey) : null;

        $status = match (true) {
            $errors !== [] => 'invalid',
            $existing instanceof SectionDefinition => 'update',
            default => 'new',
        };

        return [
            'index' => $index,
            'section_key' => $sectionKey,
            'template_key' => $templateKey,
            'label' => $label,
            'fields_count' => is_array($definition['fields'] ?? null) ? count($definition['fields']) : 0,
            'status' => $status,
            'existing_id' => $existing?->id,
            'errors' => $errors,
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function validateDefinition(mixed $definition): array
    {
        $errors = [];

        if (! is_array($definition)) {
            return [__('Definition entry must be an object.')];
        }

        $sectionKey = trim((string) ($definition['section_key'] ?? $definition['key'] ?? ''));
        $templateKey = trim((string) ($definition['template_key'] ?? ''));
        $label = trim((string) ($definition['label'] ?? $definition['name'] ?? ''));
        $fields = $definition['fields'] ?? [];

        if ($sectionKey === '' || preg_match('/^[a-z0-9_-]+$/', $sectionKey) !== 1) {
            $errors[] = __('section_key is required and may only contain lowercase letters, numbers, underscores, and dashes.');
        }

        if ($templateKey !== '' && ! SectionTemplateRegistry::isValidTemplateKey($templateKey)) {
            $errors[] = __('template_key may only contain lowercase letters, numbers, underscores, and dashes.');
        }

        if ($label === '') {
            $errors[] = __('label is required.');
        }

        if (! is_array($fields)) {
            $errors[] = __('fields must be an array.');
        } else {
            $fieldKeys = [];

            foreach (array_values($fields) as $fieldIndex => $field) {
                foreach ($this->validateField($field, $fieldIndex) as $fieldError) {
                    $errors[] = $fieldError;
                }

                if (is_array($field)) {
                    $fieldKey = trim((string) ($field['field_key'] ?? $field['key'] ?? ''));

                    if ($fieldKey !== '') {
                        $fieldKeys[] = $fieldKey;
                    }
                }
            }

            $duplicates = Collection::make($fieldKeys)
                ->duplicates()
                ->unique()
                ->values()
                ->all();

            if ($duplicates !== []) {
                $errors[] = __('Duplicate field keys: :keys', ['keys' => implode(', ', $duplicates)]);
            }
        }

        $existingBySection = $sectionKey !== ''
            ? SectionDefinition::query()->where('section_key', $sectionKey)->first()
            : null;
        $existingByTemplate = $templateKey !== ''
            ? $this->findDefinitionByTemplateKey($templateKey)
            : null;

        if (
            $existingBySection instanceof SectionDefinition
            && $existingByTemplate instanceof SectionDefinition
            && (int) $existingBySection->id !== (int) $existingByTemplate->id
        ) {
            $errors[] = __('section_key and template_key match different existing definitions.');
        }

        return $errors;
    }

    /**
     * @return array<int, string>
     */
    protected function validateField(mixed $field, int $fieldIndex): array
    {
        $errors = [];

        if (! is_array($field)) {
            return [__('Field #:number must be an object.', ['number' => $fieldIndex + 1])];
        }

        $fieldKey = trim((string) ($field['field_key'] ?? $field['key'] ?? ''));
        $fieldType = trim((string) ($field['field_type'] ?? $field['type'] ?? ''));
        $fieldScope = trim((string) ($field['field_scope'] ?? $field['scope'] ?? SectionDefinitionField::FIELD_SCOPE_TRANSLATABLE));

        if ($fieldKey === '' || preg_match('/^[a-z0-9_.-]+$/', $fieldKey) !== 1) {
            $errors[] = __('Field #:number has an invalid field_key.', ['number' => $fieldIndex + 1]);
        }

        if (! in_array($fieldType, SectionDefinitionField::supportedFieldTypes(), true)) {
            $errors[] = __('Field ":key" has an unsupported field_type.', ['key' => $fieldKey ?: '#' . ($fieldIndex + 1)]);
        }

        if (! in_array($fieldScope, [SectionDefinitionField::FIELD_SCOPE_SHARED, SectionDefinitionField::FIELD_SCOPE_TRANSLATABLE], true)) {
            $errors[] = __('Field ":key" has an invalid field_scope.', ['key' => $fieldKey ?: '#' . ($fieldIndex + 1)]);
        }

        if ($fieldType === SectionDefinitionField::FIELD_TYPE_REPEATER) {
            $itemSchema = is_array($field['schema']['item_schema'] ?? null) ? $field['schema']['item_schema'] : [];

            if ($itemSchema === []) {
                $errors[] = __('Repeater field ":key" must include schema.item_schema.', ['key' => $fieldKey]);
            }

            foreach ($itemSchema as $schemaIndex => $subField) {
                if (! is_array($subField)) {
                    $errors[] = __('Repeater field ":key" sub-field #:number must be an object.', [
                        'key' => $fieldKey,
                        'number' => $schemaIndex + 1,
                    ]);
                    continue;
                }

                $subKey = trim((string) ($subField['key'] ?? ''));
                $subType = trim((string) ($subField['type'] ?? ''));

                if ($subKey === '' || preg_match('/^[a-z0-9_]+$/', $subKey) !== 1) {
                    $errors[] = __('Repeater field ":key" has an invalid sub-field key.', ['key' => $fieldKey]);
                }

                if (! in_array($subType, SectionDefinitionField::repeaterSubFieldTypes(), true)) {
                    $errors[] = __('Repeater field ":key" has an unsupported sub-field type.', ['key' => $fieldKey]);
                }
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function normalizeDefinition(array $payload): array
    {
        return [
            'section_key' => strtolower(trim((string) ($payload['section_key'] ?? $payload['key'] ?? ''))),
            'template_key' => strtolower(trim((string) ($payload['template_key'] ?? ''))) ?: null,
            'label' => trim((string) ($payload['label'] ?? $payload['name'] ?? '')),
            'description' => $this->nullableString($payload['description'] ?? null),
            'category' => $this->nullableString($payload['category'] ?? null),
            'editor_mode' => SectionDefinition::EDITOR_MODE_DYNAMIC,
            'settings' => $this->arrayOrEmpty($payload['settings'] ?? null),
            'schema' => $this->arrayOrEmpty($payload['schema'] ?? null),
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'is_visible' => (bool) ($payload['is_visible'] ?? true),
            'sort_order' => max(0, (int) ($payload['sort_order'] ?? 0)),
            'templates' => is_array($payload['templates'] ?? null) ? array_values($payload['templates']) : [],
            'fields' => collect(is_array($payload['fields'] ?? null) ? $payload['fields'] : [])
                ->map(fn ($field): array => $this->normalizeField(is_array($field) ? $field : []))
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function normalizeField(array $payload): array
    {
        return [
            'field_key' => strtolower(trim((string) ($payload['field_key'] ?? $payload['key'] ?? ''))),
            'label' => trim((string) ($payload['label'] ?? $payload['field_key'] ?? $payload['key'] ?? '')),
            'group_name' => $this->nullableString($payload['group_name'] ?? $payload['group'] ?? null),
            'help_text' => $this->nullableString($payload['help_text'] ?? null),
            'field_type' => trim((string) ($payload['field_type'] ?? $payload['type'] ?? SectionDefinitionField::FIELD_TYPE_TEXT)),
            'field_scope' => trim((string) ($payload['field_scope'] ?? $payload['scope'] ?? SectionDefinitionField::FIELD_SCOPE_TRANSLATABLE)),
            'default_value' => $this->arrayOrEmpty($payload['default_value'] ?? null),
            'options' => $this->arrayOrEmpty($payload['options'] ?? null),
            'settings' => $this->arrayOrEmpty($payload['settings'] ?? null),
            'schema' => $this->arrayOrEmpty($payload['schema'] ?? null),
            'is_required' => (bool) ($payload['is_required'] ?? false),
            'validation_rules' => $this->arrayOrEmpty($payload['validation_rules'] ?? null),
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'sort_order' => max(0, (int) ($payload['sort_order'] ?? 0)),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function createDefinition(array $payload): SectionDefinition
    {
        $definition = SectionDefinition::query()->create($this->definitionAttributes($payload));

        $this->syncTemplate($definition, $payload);
        $this->syncFields($definition, $payload['fields']);

        return $definition;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function updateDefinition(SectionDefinition $definition, array $payload): void
    {
        $definition->update($this->definitionAttributes($payload));

        $this->syncTemplate($definition, $payload);
        $this->syncFields($definition, $payload['fields']);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function definitionAttributes(array $payload): array
    {
        return [
            'section_key' => $payload['section_key'],
            'label' => $payload['label'],
            'description' => $payload['description'],
            'category' => $payload['category'],
            'editor_mode' => SectionDefinition::EDITOR_MODE_DYNAMIC,
            'settings' => $payload['settings'],
            'schema' => $payload['schema'],
            'is_active' => $payload['is_active'],
            'is_visible' => $payload['is_visible'],
            'sort_order' => $payload['sort_order'],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function syncTemplate(SectionDefinition $definition, array $payload): void
    {
        $templateKey = $payload['template_key'];

        if (! $templateKey) {
            $definition->templates()->sync([]);
            return;
        }

        $templatePayload = collect($payload['templates'])
            ->first(fn ($template): bool => is_array($template) && ($template['template_key'] ?? null) === $templateKey);
        $templatePayload = is_array($templatePayload) ? $templatePayload : [];
        $registryConfig = SectionTemplateRegistry::describe($templateKey, $definition->category) ?? [];

        $template = SectionTemplate::query()->updateOrCreate(
            ['template_key' => $templateKey],
            [
                'label' => $templatePayload['label'] ?? $registryConfig['label'] ?? $templateKey,
                'description' => $templatePayload['description'] ?? null,
                'category' => $templatePayload['category'] ?? $registryConfig['category'] ?? $definition->category,
                'settings' => is_array($templatePayload['settings'] ?? null) ? $templatePayload['settings'] : [],
                'schema' => is_array($templatePayload['schema'] ?? null) ? $templatePayload['schema'] : [],
                'is_active' => (bool) ($templatePayload['is_active'] ?? true),
                'is_visible' => (bool) ($templatePayload['is_visible'] ?? true),
                'sort_order' => max(0, (int) ($templatePayload['sort_order'] ?? 0)),
            ],
        );

        $definition->templates()->sync([
            $template->id => ['sort_order' => 0],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    protected function syncFields(SectionDefinition $definition, array $fields): void
    {
        foreach ($fields as $fieldPayload) {
            $definition->fields()->updateOrCreate(
                ['field_key' => $fieldPayload['field_key']],
                [
                    'label' => $fieldPayload['label'],
                    'group_name' => $fieldPayload['group_name'],
                    'help_text' => $fieldPayload['help_text'],
                    'field_type' => $fieldPayload['field_type'],
                    'field_scope' => $fieldPayload['field_scope'],
                    'default_value' => $fieldPayload['default_value'],
                    'options' => $fieldPayload['options'],
                    'settings' => $fieldPayload['settings'],
                    'schema' => $fieldPayload['schema'],
                    'is_required' => $fieldPayload['is_required'],
                    'validation_rules' => $fieldPayload['validation_rules'],
                    'is_active' => $fieldPayload['is_active'],
                    'sort_order' => $fieldPayload['sort_order'],
                ],
            );
        }
    }

    protected function findExistingDefinition(string $sectionKey, ?string $templateKey): ?SectionDefinition
    {
        $definition = $sectionKey !== ''
            ? SectionDefinition::query()->where('section_key', $sectionKey)->first()
            : null;

        if ($definition instanceof SectionDefinition) {
            return $definition;
        }

        return $templateKey ? $this->findDefinitionByTemplateKey($templateKey) : null;
    }

    protected function findDefinitionByTemplateKey(string $templateKey): ?SectionDefinition
    {
        return SectionDefinition::query()
            ->whereHas('templates', fn ($query) => $query->where('template_key', $templateKey))
            ->first();
    }

    protected function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @return array<mixed>
     */
    protected function arrayOrEmpty(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
