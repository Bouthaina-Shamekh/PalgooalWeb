<?php

namespace App\Support\Sections;

use App\Models\Section;
use App\Models\SectionTranslation;

class SectionRenderer
{
    public static function render(Section $section, string $locale = null): string
    {
        $locale ??= app()->getLocale();

        $translation = $section->translations
            ->where('locale', $locale)
            ->first();

        $config = SectionRegistry::get($section->type);

        if (!$config) {
            return "<!-- Section type '{$section->type}' not registered -->";
        }

        $view = $config['view'];

        $data = $translation?->content ?? [];

        return view($view, [
            'data' => $data,
            'section' => $section,
        ])->render();
    }
}
