<?php

namespace App\Support\Sections;

use App\Models\Section;
use Illuminate\Support\Collection;

class SectionEditorLocaleViewDataFactory
{
    public function __construct(protected SectionMediaPreviewBuilder $mediaPreviewBuilder) {}

    public function make(Section $section, iterable $languages, array $editorState): array
    {
        return Collection::make($languages)
            ->mapWithKeys(function ($language) use ($section, $editorState) {
                $locale = (string) $language->code;

                return [
                    $locale => $this->buildLocaleViewData($section, $locale, $editorState),
                ];
            })
            ->all();
    }

    protected function buildLocaleViewData(Section $section, string $locale, array $editorState): array
    {
        $translation = $section->translations->firstWhere('locale', $locale);
        $content = is_array($translation?->content) ? $translation->content : [];
        $typeFlags = is_array($editorState['typeFlags'] ?? null) ? $editorState['typeFlags'] : [];
        $localeScalarValues = is_array($editorState['localeScalarValues'][$locale] ?? null)
            ? $editorState['localeScalarValues'][$locale]
            : [];

        return [
            'featuresTextarea' => $this->resolveFeaturesTextarea($locale, $content),
            'outputsTextarea' => $this->resolveOutputsTextarea($locale, $content),
            'servicesTextarea' => $this->resolveServicesTextarea($locale, $content),
            'faqItemsTextarea' => $this->resolveFaqItemsTextarea($locale, $content),
            'reviewItems' => $this->normalizeReviewItems(
                old("translations.$locale.content.items", $content['items'] ?? []),
            ),
            'campaignIllustrationPreviewUrls' => ($typeFlags['isHeroCampaign'] ?? false) || ($typeFlags['isProgrammingShowcase'] ?? false)
                ? $this->mediaPreviewBuilder->build($localeScalarValues['campaignIllustrationValue'] ?? null)
                : [],
            'headerLogoPreviewUrls' => ($typeFlags['isSiteHeader'] ?? false)
                ? $this->mediaPreviewBuilder->build($localeScalarValues['headerLogoValue'] ?? null)
                : [],
            'mobileAppImageOnePreviewUrls' => ($typeFlags['isMobileAppShowcase'] ?? false)
                || ($typeFlags['isDesignShowcase'] ?? false)
                || ($typeFlags['isDigitalMarketingShowcase'] ?? false)
                ? $this->mediaPreviewBuilder->build($localeScalarValues['mobileAppImageOneValue'] ?? null)
                : [],
            'mobileAppImageTwoPreviewUrls' => ($typeFlags['isMobileAppShowcase'] ?? false)
                || ($typeFlags['isDesignShowcase'] ?? false)
                || ($typeFlags['isDigitalMarketingShowcase'] ?? false)
                ? $this->mediaPreviewBuilder->build($localeScalarValues['mobileAppImageTwoValue'] ?? null)
                : [],
            'mobileAppImageThreePreviewUrls' => ($typeFlags['isMobileAppShowcase'] ?? false)
                || ($typeFlags['isDesignShowcase'] ?? false)
                ? $this->mediaPreviewBuilder->build($localeScalarValues['mobileAppImageThreeValue'] ?? null)
                : [],
            'designImageFourPreviewUrls' => ($typeFlags['isDesignShowcase'] ?? false)
                ? $this->mediaPreviewBuilder->build($localeScalarValues['designImageFourValue'] ?? null)
                : [],
            'designImageFivePreviewUrls' => ($typeFlags['isDesignShowcase'] ?? false)
                ? $this->mediaPreviewBuilder->build($localeScalarValues['designImageFiveValue'] ?? null)
                : [],
            'designImageSixPreviewUrls' => ($typeFlags['isDesignShowcase'] ?? false)
                ? $this->mediaPreviewBuilder->build($localeScalarValues['designImageSixValue'] ?? null)
                : [],
            'techStackLogoPreviewUrls' => ($typeFlags['isTechStackShowcase'] ?? false)
                ? $this->mediaPreviewBuilder->buildMany(
                    $this->normalizeMediaValues($localeScalarValues['techStackLogosValue'] ?? []),
                )
                : [],
        ];
    }

    protected function resolveFeaturesTextarea(string $locale, array $content): string
    {
        $value = old("translations.$locale.content.features_textarea");

        if ($value !== null) {
            return $this->stringValue($value);
        }

        return $this->implodeTextLines($content['features'] ?? []);
    }

    protected function resolveOutputsTextarea(string $locale, array $content): string
    {
        $value = old("translations.$locale.content.outputs_textarea");

        if ($value !== null) {
            return $this->stringValue($value);
        }

        return $this->implodeTextLines($content['outputs'] ?? []);
    }

    protected function resolveServicesTextarea(string $locale, array $content): string
    {
        $value = old("translations.$locale.content.services_textarea");

        if ($value !== null) {
            return $this->stringValue($value);
        }

        return $this->implodeTextLines($content['services'] ?? []);
    }

    protected function resolveFaqItemsTextarea(string $locale, array $content): string
    {
        $value = old("translations.$locale.content.faq_textarea");

        if ($value !== null) {
            return $this->stringValue($value);
        }

        return Collection::make(
            is_array($content['items'] ?? null)
                ? $content['items']
                : (is_array($content['faq'] ?? null) ? $content['faq'] : []),
        )
            ->map(function ($item) {
                if (! is_array($item)) {
                    return trim((string) $item);
                }

                $question = trim((string) ($item['question'] ?? ''));
                $answer = trim((string) ($item['answer'] ?? ''));

                return trim($question . ($answer !== '' ? ' || ' . $answer : ''));
            })
            ->filter()
            ->implode("\n");
    }

    protected function normalizeReviewItems(mixed $items): array
    {
        return Collection::make(is_array($items) ? $items : [])
            ->map(function ($item) {
                if (! is_array($item)) {
                    return null;
                }

                $name = trim((string) ($item['name'] ?? ''));
                $role = trim((string) ($item['role'] ?? ''));
                $text = trim((string) ($item['text'] ?? ''));
                $rating = max(1, min(5, (int) ($item['rating'] ?? 5)));

                if ($name === '' && $role === '' && $text === '') {
                    return null;
                }

                return [
                    'name' => $name,
                    'role' => $role,
                    'text' => $text,
                    'rating' => $rating,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function implodeTextLines(mixed $items): string
    {
        if (! is_array($items) || $items === []) {
            return '';
        }

        return Collection::make($items)
            ->map(function ($item) {
                if (is_array($item)) {
                    return trim((string) ($item['text'] ?? ($item['title'] ?? ($item['label'] ?? ''))));
                }

                return is_scalar($item) ? trim((string) $item) : '';
            })
            ->filter()
            ->implode("\n");
    }

    protected function normalizeMediaValues(mixed $values): array
    {
        if (is_string($values)) {
            return array_values(array_filter(array_map('trim', explode(',', $values))));
        }

        return is_array($values) ? $values : [];
    }

    protected function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
