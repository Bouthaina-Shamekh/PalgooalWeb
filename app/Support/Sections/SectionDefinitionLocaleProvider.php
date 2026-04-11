<?php

namespace App\Support\Sections;

use App\Models\Language;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Resolve the currently enabled locales for developer section definition tools.
 *
 * The admin field-definition layer must stay locale-agnostic. This class
 * provides one abstraction point that prefers the project's active language
 * model but falls back safely when that source is unavailable.
 */
class SectionDefinitionLocaleProvider
{
    /**
     * Return enabled locale metadata for builder forms.
     *
     * @return array<int, array{code: string, label: string}>
     */
    public function all(): array
    {
        try {
            if (Schema::hasTable('languages')) {
                $languages = Language::query()
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->get(['code', 'name']);

                if ($languages->isNotEmpty()) {
                    return $languages
                        ->map(fn (Language $language) => [
                            'code' => (string) $language->code,
                            'label' => (string) ($language->name ?: strtoupper((string) $language->code)),
                        ])
                        ->all();
                }
            }
        } catch (\Throwable) {
            // Fall back below if the language source is unavailable during an
            // early migration/test lifecycle.
        }

        return Collection::make([
            app()->getLocale(),
            config('app.locale'),
            config('app.fallback_locale'),
        ])
            ->filter()
            ->map(fn ($locale) => (string) $locale)
            ->unique()
            ->values()
            ->map(fn (string $locale) => [
                'code' => $locale,
                'label' => strtoupper($locale),
            ])
            ->all();
    }
}
