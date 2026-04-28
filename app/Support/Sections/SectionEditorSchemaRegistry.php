<?php

namespace App\Support\Sections;

use Illuminate\Support\Str;

class SectionEditorSchemaRegistry
{
    public function for(string $type): array
    {
        return match ($type) {
            // Shell editor legacy compatibility only.
            'site_footer' => $this->siteFooterSchema(),
            default => $this->fallbackSchema($type),
        };
    }

    protected function siteFooterSchema(): array
    {
        return [
            'type' => 'site_footer',
            'label' => 'Site Footer',
            'groups' => [
                [
                    'name' => 'branding',
                    'label' => 'Branding',
                ],
                [
                    'name' => 'links',
                    'label' => 'Footer Links',
                ],
                [
                    'name' => 'social',
                    'label' => 'Social Links',
                ],
            ],
            'fields' => [
                [
                    'name' => 'title',
                    'type' => 'text',
                    'label' => 'Site Name',
                    'group' => 'branding',
                    'localized' => true,
                    'required' => false,
                ],
                [
                    'name' => 'copyright',
                    'type' => 'textarea',
                    'label' => 'Copyright',
                    'group' => 'branding',
                    'localized' => true,
                    'required' => false,
                ],
                [
                    'name' => 'footer_links',
                    'type' => 'repeater',
                    'label' => 'Footer Links',
                    'group' => 'links',
                    'localized' => true,
                    'required' => false,
                    'ui' => [
                        'itemLabel' => 'Link',
                    ],
                    'default' => [],
                ],
                [
                    'name' => 'social_links.facebook',
                    'type' => 'url',
                    'label' => 'Facebook URL',
                    'group' => 'social',
                    'localized' => true,
                    'required' => false,
                ],
                [
                    'name' => 'social_links.instagram',
                    'type' => 'url',
                    'label' => 'Instagram URL',
                    'group' => 'social',
                    'localized' => true,
                    'required' => false,
                ],
                [
                    'name' => 'social_links.x',
                    'type' => 'url',
                    'label' => 'X URL',
                    'group' => 'social',
                    'localized' => true,
                    'required' => false,
                ],
                [
                    'name' => 'social_links.github',
                    'type' => 'url',
                    'label' => 'GitHub URL',
                    'group' => 'social',
                    'localized' => true,
                    'required' => false,
                ],
                [
                    'name' => 'social_links.youtube',
                    'type' => 'url',
                    'label' => 'YouTube URL',
                    'group' => 'social',
                    'localized' => true,
                    'required' => false,
                ],
            ],
        ];
    }

    protected function fallbackSchema(string $type): array
    {
        return [
            'type' => $type,
            'label' => Str::headline(str_replace(['_', '-'], ' ', $type)),
            'groups' => [],
            'fields' => [],
        ];
    }
}
