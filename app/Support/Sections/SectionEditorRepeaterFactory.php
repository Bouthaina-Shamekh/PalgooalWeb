<?php

namespace App\Support\Sections;

use App\Models\Section;
use Illuminate\Support\Collection;

class SectionEditorRepeaterFactory
{
    public function buildLocaleCampaignFeatureItems(Section $section, iterable $languages): array
    {
        return Collection::make($languages)
            ->mapWithKeys(function ($language) use ($section) {
                $code = $language->code;
                $translation = $section->translations->firstWhere('locale', $code);
                $content = is_array($translation?->content) ? $translation->content : [];
                $oldCampaignFeatures = old("translations.$code.content.features");
                $campaignFeaturesSource = is_array($oldCampaignFeatures)
                    ? $oldCampaignFeatures
                    : (is_array($content['features'] ?? null)
                        ? $content['features']
                        : []);

                return [
                    $code => collect($campaignFeaturesSource)
                        ->map(function ($item) {
                            if (is_array($item)) {
                                $text = trim(
                                    (string) ($item['text'] ?? ($item['title'] ?? ($item['label'] ?? ''))),
                                );
                                $icon = trim((string) ($item['icon'] ?? ''));
                                $iconSource = trim((string) ($item['icon_source'] ?? 'class'));
                                $iconSvg = trim((string) ($item['icon_svg'] ?? ''));
                                $iconMedia = is_scalar($item['icon_media'] ?? null)
                                    ? (string) $item['icon_media']
                                    : '';
                            } elseif (is_scalar($item)) {
                                $text = trim((string) $item);
                                $icon = '';
                                $iconSource = 'class';
                                $iconSvg = '';
                                $iconMedia = '';
                            } else {
                                return null;
                            }

                            if ($text === '') {
                                return null;
                            }

                            return [
                                'text' => $text,
                                'icon' => $icon,
                                'icon_source' => in_array($iconSource, ['class', 'svg', 'media'], true)
                                    ? $iconSource
                                    : 'class',
                                'icon_svg' => $iconSvg,
                                'icon_media' => $iconMedia,
                            ];
                        })
                        ->filter()
                        ->values()
                        ->all(),
                ];
            })
            ->all();
    }

    public function buildLocaleProtectionItems(Section $section, iterable $languages): array
    {
        return Collection::make($languages)
            ->mapWithKeys(function ($language) use ($section) {
                $code = $language->code;
                $translation = $section->translations->firstWhere('locale', $code);
                $content = is_array($translation?->content) ? $translation->content : [];
                $oldItems = old("translations.$code.content.items");
                $itemsSource = is_array($oldItems)
                    ? $oldItems
                    : (is_array($content['items'] ?? null)
                        ? $content['items']
                        : []);

                return [
                    $code => collect($itemsSource)
                        ->map(function ($item) {
                            if (! is_array($item)) {
                                return null;
                            }

                            $title       = trim((string) ($item['title'] ?? ''));
                            $description = trim((string) ($item['description'] ?? ''));
                            $icon        = trim((string) ($item['icon'] ?? ''));
                            $iconSource  = trim((string) ($item['icon_source'] ?? 'class'));
                            $iconMedia   = is_scalar($item['icon_media'] ?? null)
                                ? (string) $item['icon_media']
                                : '';

                            if ($title === '' && $description === '') {
                                return null;
                            }

                            return [
                                'title'       => $title,
                                'description' => $description,
                                'icon'        => $icon,
                                'icon_source' => in_array($iconSource, ['class', 'media'], true)
                                    ? $iconSource
                                    : 'class',
                                'icon_media'  => $iconMedia,
                            ];
                        })
                        ->filter()
                        ->values()
                        ->all(),
                ];
            })
            ->all();
    }

    public function buildLocaleOutputItems(Section $section, iterable $languages): array
    {
        return Collection::make($languages)
            ->mapWithKeys(function ($language) use ($section) {
                $code = $language->code;
                $translation = $section->translations->firstWhere('locale', $code);
                $content = is_array($translation?->content) ? $translation->content : [];
                $oldOutputs = old("translations.$code.content.outputs");
                $outputsSource = is_array($oldOutputs)
                    ? $oldOutputs
                    : (! empty($content['outputs']) && is_array($content['outputs'])
                        ? $content['outputs']
                        : []);

                $outputsTextarea = old("translations.$code.content.outputs_textarea");

                if ($outputsTextarea === null) {
                    if (! empty($content['outputs']) && is_array($content['outputs'])) {
                        $outputsTextarea = collect($content['outputs'])
                            ->map(function ($item) {
                                if (is_array($item)) {
                                    return trim((string) ($item['text'] ?? ($item['title'] ?? '')));
                                }

                                return is_scalar($item) ? trim((string) $item) : '';
                            })
                            ->filter()
                            ->implode("\n");
                    } else {
                        $outputsTextarea = '';
                    }
                }

                if (empty($outputsSource) && filled($outputsTextarea)) {
                    $outputsSource = preg_split("/\r\n|\r|\n/", (string) $outputsTextarea);
                }

                return [
                    $code => collect($outputsSource)
                        ->map(function ($item) {
                            if (is_array($item)) {
                                $text = trim(
                                    (string) ($item['text'] ?? ($item['title'] ?? ($item['label'] ?? ''))),
                                );
                                $icon = trim((string) ($item['icon'] ?? ''));
                                $iconSource = trim((string) ($item['icon_source'] ?? 'class'));
                                $iconMedia = is_scalar($item['icon_media'] ?? null)
                                    ? (string) $item['icon_media']
                                    : '';
                            } elseif (is_scalar($item)) {
                                $text = trim((string) $item);
                                $icon = '';
                                $iconSource = 'class';
                                $iconMedia = '';
                            } else {
                                return null;
                            }

                            if ($text === '') {
                                return null;
                            }

                            return [
                                'text' => $text,
                                'icon' => $icon,
                                'icon_source' => in_array($iconSource, ['class', 'media'], true)
                                    ? $iconSource
                                    : 'class',
                                'icon_media' => $iconMedia,
                            ];
                        })
                        ->filter()
                        ->values()
                        ->all(),
                ];
            })
            ->all();
    }

    public function buildLocaleServiceItems(Section $section, iterable $languages): array
    {
        return Collection::make($languages)
            ->mapWithKeys(function ($language) use ($section) {
                $code = $language->code;
                $translation = $section->translations->firstWhere('locale', $code);
                $content = is_array($translation?->content) ? $translation->content : [];
                $oldServices = old("translations.$code.content.services");
                $servicesSource = is_array($oldServices)
                    ? $oldServices
                    : (! empty($content['services']) && is_array($content['services'])
                        ? $content['services']
                        : []);

                $servicesTextarea = old("translations.$code.content.services_textarea");

                if ($servicesTextarea === null) {
                    if (! empty($content['services']) && is_array($content['services'])) {
                        $servicesTextarea = collect($content['services'])
                            ->map(function ($item) {
                                if (is_array($item)) {
                                    return trim(
                                        (string) ($item['text'] ?? ($item['title'] ?? ($item['label'] ?? ''))),
                                    );
                                }

                                return is_scalar($item) ? trim((string) $item) : '';
                            })
                            ->filter()
                            ->implode("\n");
                    } else {
                        $servicesTextarea = '';
                    }
                }

                if (empty($servicesSource) && filled($servicesTextarea)) {
                    $servicesSource = preg_split("/\r\n|\r|\n/", (string) $servicesTextarea);
                }

                return [
                    $code => collect($servicesSource)
                        ->map(function ($item) {
                            if (is_array($item)) {
                                $text = trim(
                                    (string) ($item['text'] ?? ($item['title'] ?? ($item['label'] ?? ''))),
                                );
                                $icon = trim((string) ($item['icon'] ?? ''));
                                $iconSource = trim((string) ($item['icon_source'] ?? 'class'));
                                $iconMedia = is_scalar($item['icon_media'] ?? null)
                                    ? (string) $item['icon_media']
                                    : '';
                            } elseif (is_scalar($item)) {
                                $text = trim((string) $item);
                                $icon = '';
                                $iconSource = 'class';
                                $iconMedia = '';
                            } else {
                                return null;
                            }

                            if ($text === '') {
                                return null;
                            }

                            return [
                                'text' => $text,
                                'icon' => $icon,
                                'icon_source' => in_array($iconSource, ['class', 'media'], true)
                                    ? $iconSource
                                    : 'class',
                                'icon_media' => $iconMedia,
                            ];
                        })
                        ->filter()
                        ->values()
                        ->all(),
                ];
            })
            ->all();
    }

    public function buildLocalePricingCategoryItems(Section $section, iterable $languages): array
    {
        return Collection::make($languages)
            ->mapWithKeys(function ($language) use ($section) {
                $code = $language->code;
                $translation = $section->translations->firstWhere('locale', $code);
                $content = is_array($translation?->content) ? $translation->content : [];
                $oldPricingCategories = old("translations.$code.content.categories");
                $pricingCategoriesSource = is_array($oldPricingCategories)
                    ? $oldPricingCategories
                    : (is_array($content['categories'] ?? null)
                        ? $content['categories']
                        : []);

                return [
                    $code => collect($pricingCategoriesSource)
                        ->map(function ($item) {
                            if (! is_array($item)) {
                                return null;
                            }

                            $label = trim((string) ($item['label'] ?? ($item['title'] ?? '')));
                            $key = trim((string) ($item['key'] ?? ($item['slug'] ?? '')));

                            if ($label === '' && $key === '') {
                                return null;
                            }

                            return [
                                'label' => $label,
                                'key' => $key,
                            ];
                        })
                        ->filter()
                        ->values()
                        ->all(),
                ];
            })
            ->all();
    }

    public function buildLocalePricingPlanItems(Section $section, iterable $languages): array
    {
        return Collection::make($languages)
            ->mapWithKeys(function ($language) use ($section) {
                $code = $language->code;
                $translation = $section->translations->firstWhere('locale', $code);
                $content = is_array($translation?->content) ? $translation->content : [];
                $oldPricingPlans = old("translations.$code.content.plans");
                $pricingPlansSource = is_array($oldPricingPlans)
                    ? $oldPricingPlans
                    : (is_array($content['plans'] ?? null)
                        ? $content['plans']
                        : []);

                return [
                    $code => collect($pricingPlansSource)
                        ->map(function ($item) {
                            if (! is_array($item)) {
                                return null;
                            }

                            $title = trim((string) ($item['title'] ?? ($item['name'] ?? '')));
                            $category = trim((string) ($item['category'] ?? ($item['category_key'] ?? '')));
                            $button = is_array($item['button'] ?? null) ? $item['button'] : [];
                            $features = $item['features'] ?? ($item['features_textarea'] ?? []);

                            if (is_array($features)) {
                                $featuresTextareaValue = collect($features)
                                    ->map(function ($feature) {
                                        if (is_array($feature)) {
                                            return trim(
                                                (string) ($feature['text'] ??
                                                    ($feature['title'] ?? ($feature['label'] ?? ''))),
                                            );
                                        }

                                        return is_scalar($feature) ? trim((string) $feature) : '';
                                    })
                                    ->filter()
                                    ->implode("\n");
                            } else {
                                $featuresTextareaValue = trim((string) $features);
                            }

                            if ($title === '' && $category === '' && $featuresTextareaValue === '') {
                                return null;
                            }

                            return [
                                'title' => $title,
                                'category' => $category,
                                'features_textarea' => $featuresTextareaValue,
                                'button_label' => trim((string) ($item['button_label'] ?? ($button['label'] ?? ''))),
                                'button_url' => trim((string) ($item['button_url'] ?? ($button['url'] ?? ''))),
                                'button_new_tab' => filter_var(
                                    $item['button_new_tab'] ?? ($button['new_tab'] ?? false),
                                    FILTER_VALIDATE_BOOLEAN,
                                ),
                            ];
                        })
                        ->filter()
                        ->values()
                        ->all(),
                ];
            })
            ->all();
    }

    public function buildLocaleBuildStepItems(Section $section, iterable $languages): array
    {
        return Collection::make($languages)
            ->mapWithKeys(function ($language) use ($section) {
                $code = $language->code;
                $translation = $section->translations->firstWhere('locale', $code);
                $content = is_array($translation?->content) ? $translation->content : [];
                $oldBuildSteps = old("translations.$code.content.steps");
                $buildStepsSource = is_array($oldBuildSteps)
                    ? $oldBuildSteps
                    : (is_array($content['steps'] ?? null)
                        ? $content['steps']
                        : []);

                return [
                    $code => collect($buildStepsSource)
                        ->map(function ($item) {
                            if (! is_array($item)) {
                                return null;
                            }

                            $title = trim((string) ($item['title'] ?? ($item['label'] ?? '')));
                            if ($title === '') {
                                return null;
                            }

                            return [
                                'title' => $title,
                                'icon' => trim((string) ($item['icon'] ?? '')),
                                'icon_source' => in_array(
                                    $item['icon_source'] ?? 'class',
                                    ['class', 'svg', 'media'],
                                    true,
                                )
                                    ? (string) ($item['icon_source'] ?? 'class')
                                    : 'class',
                                'icon_svg' => trim((string) ($item['icon_svg'] ?? '')),
                                'icon_media' => is_scalar($item['icon_media'] ?? null)
                                    ? (string) $item['icon_media']
                                    : '',
                                'is_accent' => filter_var($item['is_accent'] ?? false, FILTER_VALIDATE_BOOLEAN),
                            ];
                        })
                        ->filter()
                        ->values()
                        ->all(),
                ];
            })
            ->all();
    }
}
