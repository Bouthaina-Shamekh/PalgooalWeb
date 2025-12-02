<?php

namespace App\Support\Blocks;

use App\Models\Section;

/**
 * SectionRenderer:
 * Takes a Section model and uses BlockRegistry to produce $data
 */
class SectionRenderer
{
    public static function render(Section $section): array
    {
        $callback = BlockRegistry::resolve($section->type);

        if (! $callback) {
            return [];
        }

        $translation = $section->translation();
        $content = $translation?->content ?? [];
        $title = $translation?->title ?? '';

        return $callback($title, $content, $section);
    }
}
