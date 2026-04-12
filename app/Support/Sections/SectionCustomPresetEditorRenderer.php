<?php

namespace App\Support\Sections;

use App\Models\Section;
use App\Models\Sections\SectionDefinition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Render curated admin editors for definition-driven sections that need
 * bespoke UI while keeping the same saved content contract.
 *
 * This is intentionally editor-only. Frontend rendering continues to use the
 * existing definition/template runtime, while the admin can opt into a more
 * tailored editing surface for selected definitions.
 */
class SectionCustomPresetEditorRenderer
{
    public function __construct(
        protected SectionEditorRepeaterFactory $repeaterFactory,
        protected SectionMediaPreviewBuilder $mediaPreviewBuilder,
    ) {}

    /**
     * Build a custom preset payload when the current section explicitly maps
     * to a supported preset editor.
     *
     * @return array<string, mixed>|null
     */
    public function buildForSection(Section $section, iterable $languages): ?array
    {
        $definition = $this->resolvePresetDefinition($section);

        if (! $definition) {
            return null;
        }

        $presetMeta = $this->resolvePresetMeta($definition);

        if (! is_array($presetMeta)) {
            return null;
        }

        $builder = $presetMeta['builder'] ?? null;

        if (! is_string($builder) || ! method_exists($this, $builder)) {
            return null;
        }

        return $this->{$builder}($section, $definition, $languages, $presetMeta);
    }

    protected function resolvePresetDefinition(Section $section): ?SectionDefinition
    {
        if (
            ! Schema::hasTable('section_definitions')
            || ! Schema::hasColumn('sections', 'section_definition_id')
            || ! $section->section_definition_id
        ) {
            return null;
        }

        $section->loadMissing(['translations', 'sectionDefinition']);
        $definition = $section->sectionDefinition;

        if (! $definition instanceof SectionDefinition || ! $definition->is_active) {
            return null;
        }

        return $definition;
    }

    /**
     * Resolve the preset metadata that should drive the admin UI.
     *
     * Formal activation rule:
     * - editor_mode = custom_preset
     * - custom_editor_key = registered preset key
     *
     * Temporary compatibility:
     * - already-linked hosting_hero definitions may still activate through
     *   the configured legacy bridge until their records are backfilled.
     */
    protected function resolvePresetMeta(SectionDefinition $definition): ?array
    {
        $customEditorKey = trim((string) ($definition->custom_editor_key ?? ''));

        if (
            $definition->editor_mode === SectionDefinition::EDITOR_MODE_CUSTOM_PRESET
            && $customEditorKey !== ''
        ) {
            return SectionCustomPresetRegistry::get($customEditorKey);
        }

        $bridgePresetKey = SectionCustomPresetRegistry::legacyBridgePresetKey($definition->section_key);

        return SectionCustomPresetRegistry::get($bridgePresetKey);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildHostingHeroPreset(
        Section $section,
        SectionDefinition $definition,
        iterable $languages,
        array $presetMeta,
    ): array {
        $languagesCollection = Collection::make($languages)->values();
        $defaultLocale = $this->resolveDefaultLocale($languagesCollection);
        $featureItemsByLocale = $this->repeaterFactory->buildLocaleCampaignFeatureItems($section, $languagesCollection);

        return [
            'enabled' => true,
            'presetKey' => (string) ($presetMeta['preset_key'] ?? 'hosting_hero'),
            'view' => (string) ($presetMeta['view'] ?? 'dashboard.pages.sections.partials.custom-presets.hosting-hero'),
            'activationSource' => $definition->editor_mode === SectionDefinition::EDITOR_MODE_CUSTOM_PRESET
                ? 'custom_editor_key'
                : 'legacy_section_key_bridge',
            'defaultLocale' => $defaultLocale,
            'definition' => [
                'id' => $definition->id,
                'key' => $definition->section_key,
                'label' => $definition->label,
                'description' => $definition->description,
            ],
            'locales' => $languagesCollection
                ->mapWithKeys(function ($language) use ($section, $featureItemsByLocale) {
                    $locale = (string) $language->code;
                    $translation = $section->translations->firstWhere('locale', $locale);
                    $content = is_array($translation?->content) ? $translation->content : [];
                    $backgroundImageValue = $this->oldContentValue(
                        $locale,
                        'background_image',
                        $content['background_image'] ?? null,
                    );

                    return [
                        $locale => [
                            'code' => $locale,
                            'label' => (string) ($language->name ?? strtoupper($locale)),
                            'values' => [
                                'breadcrumbHomeLabelValue' => $this->stringValue(
                                    $this->oldContentValue(
                                        $locale,
                                        'breadcrumb_home_label',
                                        $content['breadcrumb_home_label'] ?? __('Home'),
                                    ),
                                ),
                                'breadcrumbHomeUrlValue' => $this->stringValue(
                                    $this->oldContentValue(
                                        $locale,
                                        'breadcrumb_home_url',
                                        $content['breadcrumb_home_url'] ?? 'index.html',
                                    ),
                                ),
                                'breadcrumbCurrentLabelValue' => $this->stringValue(
                                    $this->oldContentValue(
                                        $locale,
                                        'breadcrumb_current_label',
                                        $content['breadcrumb_current_label'] ?? __('Hosting'),
                                    ),
                                ),
                                'titleValue' => $this->stringValue(
                                    $this->oldContentValue($locale, 'title', $content['title'] ?? ''),
                                ),
                                'subtitleValue' => $this->stringValue(
                                    $this->oldContentValue($locale, 'subtitle', $content['subtitle'] ?? ''),
                                ),
                                'cardTitleValue' => $this->stringValue(
                                    $this->oldContentValue($locale, 'card_title', $content['card_title'] ?? ''),
                                ),
                                'cardButtonLabelValue' => $this->stringValue(
                                    $this->oldContentValue(
                                        $locale,
                                        'card_button_label',
                                        $content['card_button_label'] ?? '',
                                    ),
                                ),
                                'cardButtonUrlValue' => $this->stringValue(
                                    $this->oldContentValue(
                                        $locale,
                                        'card_button_url',
                                        $content['card_button_url'] ?? '',
                                    ),
                                ),
                                'backgroundImageValue' => $backgroundImageValue,
                                'backgroundImagePreviewUrls' => $this->mediaPreviewBuilder->build($backgroundImageValue),
                                'featureItems' => $featureItemsByLocale[$locale] ?? [],
                            ],
                        ],
                    ];
                })
                ->all(),
        ];
    }

    protected function oldContentValue(string $locale, string $key, mixed $default = ''): mixed
    {
        return old("translations.$locale.content.$key", $default);
    }

    protected function resolveDefaultLocale(Collection $languages): string
    {
        $localeCodes = $languages
            ->pluck('code')
            ->map(fn ($code) => (string) $code)
            ->filter()
            ->values();

        return $localeCodes->contains(app()->getLocale())
            ? app()->getLocale()
            : ($localeCodes->first() ?? app()->getLocale());
    }

    protected function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
