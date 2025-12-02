<?php

namespace App\Support\Blocks;

class HeroBlock
{
    public static function register(): void
    {
        BlockRegistry::register('hero', function ($title, $content, $section) {
            return [
                'title' => $title,
                'subtitle' => $content['subtitle'] ?? '',
                'button_text-1' => $content['button_text-1'] ?? '',
                'button_url-1' => $content['button_url-1'] ?? '',
                'button_text-2' => $content['button_text-2'] ?? '',
                'button_url-2' => $content['button_url-2'] ?? '',
            ];
        });
    }
}
