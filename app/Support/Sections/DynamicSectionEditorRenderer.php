<?php

namespace App\Support\Sections;

use App\Models\Section;
use App\Models\Sections\SectionDefinition;
use App\Models\Sections\SectionDefinitionField;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Build a normalized dynamic-editor payload for definition-driven sections.
 *
 * This class does not replace the legacy editor. It only prepares a safe,
 * view-friendly rendering contract when a section instance is explicitly
 * linked to a dynamic section definition.
 */
class DynamicSectionEditorRenderer
{
    protected const FIELD_PARTIALS = [
        SectionDefinitionField::FIELD_TYPE_TEXT => 'dashboard.pages.sections.partials.dynamic-editor.fields.text',
        SectionDefinitionField::FIELD_TYPE_TEXTAREA => 'dashboard.pages.sections.partials.dynamic-editor.fields.textarea',
        SectionDefinitionField::FIELD_TYPE_RICHTEXT => 'dashboard.pages.sections.partials.dynamic-editor.fields.textarea',
        SectionDefinitionField::FIELD_TYPE_URL => 'dashboard.pages.sections.partials.dynamic-editor.fields.url',
        SectionDefinitionField::FIELD_TYPE_MEDIA => 'dashboard.pages.sections.partials.dynamic-editor.fields.media',
        SectionDefinitionField::FIELD_TYPE_NUMBER => 'dashboard.pages.sections.partials.dynamic-editor.fields.number',
        SectionDefinitionField::FIELD_TYPE_BOOLEAN => 'dashboard.pages.sections.partials.dynamic-editor.fields.boolean',
        SectionDefinitionField::FIELD_TYPE_SELECT => 'dashboard.pages.sections.partials.dynamic-editor.fields.select',
    ];

    public function __construct(
        protected SectionMediaPreviewBuilder $mediaPreviewBuilder,
        protected SectionDefinitionRuntimeResolver $runtimeResolver,
    ) {}

    /**
     * Resolve a dynamic editor payload for the current section when safe.
     *
     * The editor now follows the same runtime rule as the frontend:
     * only explicitly linked dynamic definitions with an assigned template key
     * may use the definition-driven path.
     *
     * @return array<string, mixed>|null
     */
    public function buildForSection(Section $section, iterable $languages): ?array
    {
        $definition = $this->runtimeResolver->resolveDynamicDefinition($section);

        if (! $definition) {
            return null;
        }

        return $this->build($section, $definition, $languages);
    }

    /**
     * Build a normalized locale-aware rendering payload for Blade.
     *
     * @return array<string, mixed>
     */
    public function build(Section $section, SectionDefinition $definition, iterable $languages): array
    {
        $languagesCollection = Collection::make($languages)->values();
        $defaultLocale = $this->resolveDefaultLocale($languagesCollection);
        $localeCodes = $languagesCollection
            ->pluck('code')
            ->map(fn($code) => (string) $code)
            ->filter()
            ->values()
            ->all();

        return [
            'enabled' => true,
            'defaultLocale' => $defaultLocale,
            'definition' => [
                'id' => $definition->id,
                'key' => $definition->section_key,
                'label' => $definition->label,
                'description' => $definition->description,
            ],
            'locales' => $languagesCollection
                ->mapWithKeys(function ($language) use ($section, $definition, $defaultLocale, $localeCodes) {
                    $locale = (string) $language->code;

                    return [
                        $locale => [
                            'code' => $locale,
                            'label' => (string) ($language->name ?? strtoupper($locale)),
                            'groups' => $this->buildGroupsForLocale(
                                $section,
                                $definition->fields,
                                $locale,
                                $defaultLocale,
                                $localeCodes,
                            ),
                        ],
                    ];
                })
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, SectionDefinitionField>  $fields
     * @param  array<int, string>  $localeCodes
     * @return array<int, array<string, mixed>>
     */
    protected function buildGroupsForLocale(
        Section $section,
        Collection $fields,
        string $locale,
        string $defaultLocale,
        array $localeCodes,
    ): array {
        return $fields
            ->groupBy(fn(SectionDefinitionField $field) => $field->group_name ?: 'general')
            ->map(function (Collection $groupFields, string $groupKey) use ($section, $locale, $defaultLocale, $localeCodes) {
                return [
                    'key' => $groupKey,
                    'label' => Str::headline(str_replace(['_', '-'], ' ', $groupKey)),
                    'fields' => $groupFields
                        ->map(fn(SectionDefinitionField $field) => $this->buildFieldPayload(
                            $section,
                            $field,
                            $locale,
                            $defaultLocale,
                            $localeCodes,
                        ))
                        ->filter()
                        ->values()
                        ->all(),
                ];
            })
            ->filter(fn(array $group) => $group['fields'] !== [])
            ->values()
            ->all();
    }

    /**
     * Normalize one field into a Blade-friendly rendering payload.
     *
     * Shared fields are rendered only on the default locale tab, then mirrored
     * into the other locale payloads via hidden replica inputs so the current
     * save pipeline can remain unchanged.
     *
     * @param  array<int, string>  $localeCodes
     * @return array<string, mixed>|null
     */
    protected function buildFieldPayload(
        Section $section,
        SectionDefinitionField $field,
        string $locale,
        string $defaultLocale,
        array $localeCodes,
    ): ?array {
        $isTranslatable = $field->isTranslatable();

        if (! $isTranslatable && $locale !== $defaultLocale) {
            return null;
        }

        $value = $this->resolveFieldValue($section, $field, $locale, $defaultLocale, $localeCodes);
        $settings = is_array($field->settings) ? $field->settings : [];
        $fieldType = $field->field_type === SectionDefinitionField::FIELD_TYPE_RICHTEXT
            ? SectionDefinitionField::FIELD_TYPE_TEXTAREA
            : $field->field_type;

        return [
            'fieldKey' => $field->field_key,
            'fieldType' => $field->field_type,
            'renderType' => $fieldType,
            'label' => $field->label,
            'name' => $this->inputName($field->field_key, $locale),
            'id' => $this->inputId($section->id, $field->field_key, $locale),
            'value' => $value,
            'placeholder' => $this->fieldPlaceholder($settings),
            'helpText' => $field->help_text,
            'isRequired' => (bool) $field->is_required,
            'isTranslatable' => $isTranslatable,
            'isRichText' => $field->field_type === SectionDefinitionField::FIELD_TYPE_RICHTEXT,
            'options' => $this->fieldOptions($field),
            'settings' => $settings,
            'rows' => $this->fieldRows($fieldType, $settings),
            'partial' => $this->fieldPartial($fieldType),
            'wrapperClass' => $this->fieldWrapperClass($fieldType),
            'previewUrls' => $fieldType === SectionDefinitionField::FIELD_TYPE_MEDIA
                ? $this->mediaPreviewBuilder->build($value)
                : [],
            'replicaInputs' => ! $isTranslatable
                ? $this->replicaInputs($field->field_key, $value, $localeCodes, $defaultLocale)
                : [],
        ];
    }

    /**
     * Resolve the current UI value with old() precedence, then saved content,
     * then normalized defaults from the definition.
     *
     * @param  array<int, string>  $localeCodes
     */
    protected function resolveFieldValue(
        Section $section,
        SectionDefinitionField $field,
        string $locale,
        string $defaultLocale,
        array $localeCodes,
    ): mixed {
        if ($field->isTranslatable()) {
            $oldValue = old('translations.' . $locale . '.content.' . $field->field_key);

            if ($oldValue !== null) {
                return $oldValue;
            }

            $savedValue = $this->savedFieldValue($section, $field->field_key, $locale);

            return $savedValue ?? $this->definitionDefaultValue($field, $locale);
        }

        foreach (array_unique(array_merge([$defaultLocale], $localeCodes)) as $candidateLocale) {
            $oldValue = old('translations.' . $candidateLocale . '.content.' . $field->field_key);

            if ($oldValue !== null) {
                return $oldValue;
            }
        }

        $savedValue = $this->sharedSavedFieldValue($section, $field->field_key, $defaultLocale, $localeCodes);

        return $savedValue ?? $this->definitionDefaultValue($field, $defaultLocale);
    }

    protected function savedFieldValue(Section $section, string $fieldKey, string $locale): mixed
    {
        $translation = $section->translations->firstWhere('locale', $locale) ?? $section->translation($locale);
        $content = is_array($translation?->content) ? $translation->content : [];

        return data_get($content, $fieldKey);
    }

    /**
     * @param  array<int, string>  $localeCodes
     */
    protected function sharedSavedFieldValue(
        Section $section,
        string $fieldKey,
        string $defaultLocale,
        array $localeCodes,
    ): mixed {
        foreach (array_unique(array_merge([$defaultLocale], $localeCodes)) as $candidateLocale) {
            $savedValue = $this->savedFieldValue($section, $fieldKey, $candidateLocale);

            if ($savedValue !== null) {
                return $savedValue;
            }
        }

        return null;
    }

    protected function definitionDefaultValue(SectionDefinitionField $field, string $locale): mixed
    {
        [$hasDefault, $defaultValue] = $this->runtimeResolver->resolvedDefaultValue($field, $locale);

        return $hasDefault ? $defaultValue : null;
    }

    protected function fieldPlaceholder(array $settings): ?string
    {
        $placeholder = data_get($settings, 'placeholder', data_get($settings, 'ui.placeholder'));

        return is_string($placeholder) && trim($placeholder) !== '' ? trim($placeholder) : null;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function fieldOptions(SectionDefinitionField $field): array
    {
        $options = is_array($field->options) ? $field->options : [];

        return Collection::make($options)
            ->map(function ($option) {
                if (! is_array($option)) {
                    return null;
                }

                $value = trim((string) ($option['value'] ?? ''));
                $label = trim((string) ($option['label'] ?? $value));

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
    }

    protected function fieldRows(string $fieldType, array $settings): int
    {
        if ($fieldType !== SectionDefinitionField::FIELD_TYPE_TEXTAREA) {
            return 3;
        }

        $rows = data_get($settings, 'rows', data_get($settings, 'ui.rows', 4));

        return max(3, (int) $rows);
    }

    protected function fieldPartial(string $fieldType): string
    {
        return self::FIELD_PARTIALS[$fieldType] ?? self::FIELD_PARTIALS[SectionDefinitionField::FIELD_TYPE_TEXT];
    }

    protected function fieldWrapperClass(string $fieldType): string
    {
        return in_array($fieldType, [
            SectionDefinitionField::FIELD_TYPE_TEXTAREA,
            SectionDefinitionField::FIELD_TYPE_MEDIA,
            SectionDefinitionField::FIELD_TYPE_BOOLEAN,
        ], true)
            ? 'lg:col-span-2'
            : '';
    }

    protected function inputName(string $fieldKey, string $locale): string
    {
        return 'translations[' . $locale . '][content]' . $this->fieldKeyToBracketSuffix($fieldKey);
    }

    protected function inputId(int $sectionId, string $fieldKey, string $locale): string
    {
        $sanitizedKey = str_replace(['.', '_'], '-', $fieldKey);

        return 'dynamic-section-field-' . $sectionId . '-' . $locale . '-' . $sanitizedKey;
    }

    protected function fieldKeyToBracketSuffix(string $fieldKey): string
    {
        return collect(explode('.', $fieldKey))
            ->filter(fn($segment) => $segment !== '')
            ->map(fn($segment) => '[' . $segment . ']')
            ->implode('');
    }

    /**
     * @param  array<int, string>  $localeCodes
     * @return array<int, array{name: string, value: string}>
     */
    protected function replicaInputs(
        string $fieldKey,
        mixed $value,
        array $localeCodes,
        string $defaultLocale,
    ): array {
        return Collection::make($localeCodes)
            ->reject(fn(string $locale) => $locale === $defaultLocale)
            ->map(fn(string $locale) => [
                'name' => $this->inputName($fieldKey, $locale),
                'value' => $this->serializeScalarValue($value),
            ])
            ->values()
            ->all();
    }

    protected function serializeScalarValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value === null) {
            return '';
        }

        return is_scalar($value) ? (string) $value : '';
    }

    protected function resolveDefaultLocale(Collection $languages): string
    {
        $localeCodes = $languages
            ->pluck('code')
            ->map(fn($code) => (string) $code)
            ->filter()
            ->values();

        return $localeCodes->contains(app()->getLocale())
            ? app()->getLocale()
            : ($localeCodes->first() ?? app()->getLocale());
    }
}
