<?php

namespace App\Support\Sections;

use App\Models\Section;
use App\Services\Tenancy\TenantSiteShellService;
use Illuminate\Support\Collection;

/**
 * Shell editor legacy compatibility only.
 *
 * This service isolates the remaining type-based admin/editor behavior used by
 * the client site header and site footer editors. Normal page sections should
 * stay on the definition-driven path and must not depend on this class.
 */
class ShellSectionEditorSupport
{
    public function __construct(
        protected SectionEditorTypeCapabilities $typeCapabilities,
        protected SectionEditorSchemaRegistry $schemaRegistry,
        protected SectionMediaPreviewBuilder $mediaPreviewBuilder,
    ) {}

    public function availableSectionTypes(string $workspaceShell): array
    {
        if ($workspaceShell === TenantSiteShellService::SHELL_FOOTER) {
            return [
                'site_footer' => [
                    'type' => 'site_footer',
                    'label' => 'Site Footer',
                    'description' => 'Global tenant footer block.',
                    'category' => 'other',
                    'preview' => null,
                    'library_hidden' => true,
                ],
                'site_footer_simple_social' => [
                    'type' => 'site_footer',
                    'variant' => 'simple_social',
                    'label' => 'Footer: Social + Copyright',
                    'description' => 'A compact footer with social icons and one copyright line.',
                    'category' => 'other',
                    'preview' => null,
                ],
                'site_footer_links_social' => [
                    'type' => 'site_footer',
                    'variant' => 'links_social',
                    'label' => 'Footer: Links + Social',
                    'description' => 'A larger footer with navigation links, social icons, and copyright.',
                    'category' => 'other',
                    'preview' => null,
                ],
            ];
        }

        return [
            'site_header' => [
                'type' => 'site_header',
                'label' => 'Site Header',
                'description' => 'Global tenant header with automatic page links and one optional call-to-action button.',
                'category' => 'other',
                'preview' => null,
            ],
        ];
    }

    public function buildEditorState(Section $section, iterable $languages, array $sectionTypes = []): array
    {
        $selectedType = $this->normalizeSelectedType(old('type', $section->type), $section->type);
        $typeCapabilities = $this->typeCapabilities->for($selectedType);

        $editorState = [
            'selectedType' => $selectedType,
            'defaultLocale' => $this->resolveDefaultLocale($languages),
            'usesInternalLabel' => (bool) ($typeCapabilities['usesInternalLabel'] ?? false),
            'flags' => $typeCapabilities['flags'],
            'editorSchema' => $this->schemaRegistry->for($selectedType),
            'usesDynamicEditor' => false,
            'dynamicEditor' => null,
            'hostingPricingAvailableCategories' => collect(),
            'localeScalarValues' => $this->buildLocaleScalarValues($section, $languages),
        ];

        $editorState['localeHeaderLogoPreviewUrls'] = Collection::make($languages)
            ->mapWithKeys(function ($language) use ($editorState) {
                $code = (string) $language->code;

                return [
                    $code => (($editorState['selectedType'] ?? null) === 'site_header'
                        ? $this->mediaPreviewBuilder->build($editorState['localeScalarValues'][$code]['headerLogoValue'] ?? null)
                        : []),
                ];
            })
            ->all();

        return $editorState;
    }

    public function normalizeTranslations(string $type, array $translations): array
    {
        foreach ($translations as $key => $translationData) {
            $content = is_array($translationData['content'] ?? null) ? $translationData['content'] : [];
            $translations[$key]['content'] = $this->normalizeContent($type, $content);
        }

        return $translations;
    }

    public function normalizeContent(string $type, array $content): array
    {
        return match ($type) {
            'site_header' => $this->normalizeSiteHeaderContent($content),
            'site_footer' => $this->normalizeSiteFooterContent($content),
            default => $content,
        };
    }

    public function defaultContent(string $type): array
    {
        return match ($type) {
            'site_header' => [
                'title' => 'My Website',
                'logo' => null,
                'primary_button' => [
                    'label' => 'Contact us',
                    'url' => '#contact',
                    'new_tab' => false,
                ],
            ],
            'site_footer' => [
                'title' => 'My Website',
                'footer_links' => [
                    ['label' => 'About', 'url' => '#'],
                    ['label' => 'Blog', 'url' => '#'],
                    ['label' => 'Jobs', 'url' => '#'],
                    ['label' => 'Press', 'url' => '#'],
                    ['label' => 'Accessibility', 'url' => '#'],
                    ['label' => 'Partners', 'url' => '#'],
                ],
                'social_links' => [
                    'facebook' => '',
                    'instagram' => '',
                    'x' => '',
                    'github' => '',
                    'youtube' => '',
                ],
                'copyright' => sprintf('© %s My Website. All rights reserved.', now()->year),
            ],
            default => [],
        };
    }

    public function defaultStyle(string $type): array
    {
        return [];
    }

    protected function buildLocaleScalarValues(Section $section, iterable $languages): array
    {
        return Collection::make($languages)
            ->mapWithKeys(function ($language) use ($section) {
                $code = (string) $language->code;
                $translation = $section->translations->firstWhere('locale', $code);
                $content = is_array($translation?->content) ? $translation->content : [];
                $primaryButton = is_array($content['primary_button'] ?? null) ? $content['primary_button'] : [];

                return [
                    $code => [
                        'sectionTitleValue' => $this->stringValue($this->oldTranslationValue($code, 'title', $translation?->title ?? '')),
                        'heroTitleValue' => $this->stringValue($this->oldContentValue($code, 'title', $content['title'] ?? '')),
                        'primaryButtonLabelValue' => $this->stringValue(
                            $this->oldNestedContentValue($code, 'primary_button.label', $primaryButton['label'] ?? ''),
                        ),
                        'primaryButtonUrlValue' => $this->stringValue(
                            $this->oldNestedContentValue($code, 'primary_button.url', $primaryButton['url'] ?? ''),
                        ),
                        'primaryButtonVisibleValue' => $this->oldBooleanContentValue(
                            $code,
                            'primary_button.visible',
                            $primaryButton['visible'] ?? false,
                        ),
                        'primaryButtonNewTabValue' => $this->oldBooleanContentValue(
                            $code,
                            'primary_button.new_tab',
                            $primaryButton['new_tab'] ?? false,
                        ),
                        'footerCopyrightValue' => $this->stringValue($this->oldContentValue($code, 'copyright', $content['copyright'] ?? '')),
                        'footerLinkItems' => $this->resolveFooterLinkItems($code, $content),
                        'footerFacebookUrlValue' => $this->stringValue(
                            $this->oldNestedContentValue($code, 'social_links.facebook', data_get($content, 'social_links.facebook', '')),
                        ),
                        'footerInstagramUrlValue' => $this->stringValue(
                            $this->oldNestedContentValue($code, 'social_links.instagram', data_get($content, 'social_links.instagram', '')),
                        ),
                        'footerXUrlValue' => $this->stringValue(
                            $this->oldNestedContentValue($code, 'social_links.x', data_get($content, 'social_links.x', '')),
                        ),
                        'footerGithubUrlValue' => $this->stringValue(
                            $this->oldNestedContentValue($code, 'social_links.github', data_get($content, 'social_links.github', '')),
                        ),
                        'footerYoutubeUrlValue' => $this->stringValue(
                            $this->oldNestedContentValue($code, 'social_links.youtube', data_get($content, 'social_links.youtube', '')),
                        ),
                        'headerLogoValue' => $this->oldContentValue($code, 'logo', $content['logo'] ?? null),
                    ],
                ];
            })
            ->all();
    }

    protected function normalizeSiteHeaderContent(array $content): array
    {
        $primaryButton = is_array($content['primary_button'] ?? null) ? $content['primary_button'] : [];

        return [
            'title' => trim((string) ($content['title'] ?? __('My Website'))),
            'logo' => $this->sanitizeMediaReference($content['logo'] ?? null),
            'primary_button' => [
                'label' => trim((string) ($content['primary_button_label'] ?? ($primaryButton['label'] ?? ''))),
                'url' => trim((string) ($content['primary_button_url'] ?? ($primaryButton['url'] ?? ''))),
                'new_tab' => filter_var(
                    $content['primary_button_new_tab'] ?? ($primaryButton['new_tab'] ?? false),
                    FILTER_VALIDATE_BOOLEAN,
                ),
            ],
        ];
    }

    protected function normalizeSiteFooterContent(array $content): array
    {
        $socialLinks = is_array($content['social_links'] ?? null) ? $content['social_links'] : [];
        $footerLinksRaw = $content['footer_links_textarea'] ?? ($content['footer_links'] ?? []);

        $footerLinks = is_array($footerLinksRaw)
            ? collect($footerLinksRaw)
            ->map(function ($item): ?array {
                if (! is_array($item)) {
                    return null;
                }

                $label = trim((string) ($item['label'] ?? ''));
                $url = trim((string) ($item['url'] ?? '#'));

                if ($label === '') {
                    return null;
                }

                return [
                    'label' => $label,
                    'url' => $url !== '' ? $url : '#',
                ];
            })
            ->filter()
            ->values()
            ->all()
            : collect(preg_split("/\r\n|\r|\n/", (string) $footerLinksRaw))
            ->map(fn($item) => trim((string) $item))
            ->filter()
            ->map(function (string $item): ?array {
                $parts = preg_split('/\s*\|\|\s*|\s*\|\s*/', $item, 2);
                $label = trim((string) ($parts[0] ?? ''));
                $url = trim((string) ($parts[1] ?? '#'));

                if ($label === '') {
                    return null;
                }

                return [
                    'label' => $label,
                    'url' => $url !== '' ? $url : '#',
                ];
            })
            ->filter()
            ->values()
            ->all();

        return [
            'title' => trim((string) ($content['title'] ?? __('My Website'))),
            'copyright' => trim((string) ($content['copyright'] ?? '')),
            'footer_links' => $footerLinks,
            'social_links' => [
                'facebook' => trim((string) ($socialLinks['facebook'] ?? ($content['facebook_url'] ?? ''))),
                'instagram' => trim((string) ($socialLinks['instagram'] ?? ($content['instagram_url'] ?? ''))),
                'x' => trim((string) ($socialLinks['x'] ?? ($content['x_url'] ?? ''))),
                'github' => trim((string) ($socialLinks['github'] ?? ($content['github_url'] ?? ''))),
                'youtube' => trim((string) ($socialLinks['youtube'] ?? ($content['youtube_url'] ?? ''))),
            ],
        ];
    }

    protected function resolveFooterLinkItems(string $code, array $content): array
    {
        $footerLinks = $this->oldContentValue($code, 'footer_links');

        return collect(
            is_array($footerLinks)
                ? $footerLinks
                : (is_array($content['footer_links'] ?? null) ? $content['footer_links'] : []),
        )
            ->map(function ($item): ?array {
                if (! is_array($item)) {
                    return null;
                }

                $label = trim((string) ($item['label'] ?? ''));
                $url = trim((string) ($item['url'] ?? ''));

                if ($label === '' && $url === '') {
                    return null;
                }

                return [
                    'label' => $label,
                    'url' => $url,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function normalizeSelectedType(mixed $selectedType, string $fallbackType): string
    {
        if (! is_string($selectedType) || trim($selectedType) === '') {
            return $fallbackType;
        }

        return trim($selectedType);
    }

    protected function resolveDefaultLocale(iterable $languages): string
    {
        $localeCodes = Collection::make($languages)
            ->pluck('code')
            ->filter()
            ->values();

        return $localeCodes->contains(app()->getLocale())
            ? app()->getLocale()
            : ($localeCodes->first() ?? app()->getLocale());
    }

    protected function oldTranslationValue(string $code, string $key, mixed $default = ''): mixed
    {
        return old("translations.$code.$key", $default);
    }

    protected function oldContentValue(string $code, string $key, mixed $default = ''): mixed
    {
        return $this->oldNestedContentValue($code, $key, $default);
    }

    protected function oldNestedContentValue(string $code, string $dotKey, mixed $default = ''): mixed
    {
        return old("translations.$code.content.$dotKey", $default);
    }

    protected function oldBooleanContentValue(string $code, string $dotKey, mixed $default = false): bool
    {
        return filter_var(
            $this->oldNestedContentValue($code, $dotKey, $default),
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    protected function sanitizeMediaReference(mixed $value): int|string|null
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    protected function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
