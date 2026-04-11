<?php

namespace App\Support\Sections;

use Illuminate\Support\Str;

class SectionEditorSchemaRegistry
{
    public function for(string $type): array
    {
        return match ($type) {
            'hero_campaign' => $this->heroCampaignSchema(),
            'programming_showcase' => $this->programmingShowcaseSchema(),
            'site_footer' => $this->siteFooterSchema(),
            default => $this->fallbackSchema($type),
        };
    }

    protected function heroCampaignSchema(): array
    {
        return [
            'type' => 'hero_campaign',
            'label' => 'Hero - Campaign',
            'groups' => [
                [
                    'name' => 'content',
                    'label' => 'Content',
                ],
                [
                    'name' => 'cta',
                    'label' => 'CTA',
                ],
                [
                    'name' => 'features',
                    'label' => 'Features',
                ],
                [
                    'name' => 'media',
                    'label' => 'Media',
                ],
            ],
            'fields' => [
                [
                    'name' => 'title',
                    'type' => 'text',
                    'label' => 'Title',
                    'group' => 'content',
                    'localized' => true,
                    'required' => false,
                ],
                [
                    'name' => 'description',
                    'type' => 'textarea',
                    'label' => 'Description',
                    'group' => 'content',
                    'localized' => true,
                    'required' => false,
                ],
                [
                    'name' => 'features_heading',
                    'type' => 'text',
                    'label' => 'Features Heading',
                    'group' => 'features',
                    'localized' => true,
                    'required' => false,
                ],
                [
                    'name' => 'features',
                    'type' => 'repeater',
                    'label' => 'Features',
                    'group' => 'features',
                    'localized' => true,
                    'required' => false,
                    'ui' => [
                        'itemLabel' => 'Feature',
                        'supportsIconClass' => true,
                        'supportsIconSvg' => true,
                        'supportsIconMedia' => true,
                    ],
                    'default' => [],
                ],
                [
                    'name' => 'primary_button.label',
                    'type' => 'text',
                    'label' => 'CTA Button Label',
                    'group' => 'cta',
                    'localized' => true,
                    'required' => false,
                ],
                [
                    'name' => 'primary_button.url',
                    'type' => 'text',
                    'label' => 'CTA Button URL',
                    'group' => 'cta',
                    'localized' => true,
                    'required' => false,
                ],
                [
                    'name' => 'trust_items',
                    'type' => 'textarea',
                    'label' => 'Trust Items',
                    'group' => 'cta',
                    'localized' => true,
                    'required' => false,
                    'ui' => [
                        'rows' => 3,
                        'placeholder' => 'One line per item shown below the CTA button',
                    ],
                ],
                [
                    'name' => 'media_url',
                    'type' => 'media',
                    'label' => 'Illustration',
                    'group' => 'media',
                    'localized' => true,
                    'required' => false,
                    'ui' => [
                        'preview' => true,
                    ],
                ],
            ],
        ];
    }

    protected function hostingHeroSchema(): array
    {
        return [
            'type' => 'hosting_hero',
            'label' => 'hosting_hero',
            'groups' => [
                [
                    'name' => 'content',
                    'label' => 'Content',
                ],
                [
                    'name' => 'cta',
                    'label' => 'CTA',
                ],
                [
                    'name' => 'features',
                    'label' => 'Features',
                ],
                [
                    'name' => 'media',
                    'label' => 'Media',
                ],
            ],
            'fields' => [
                [
                    'name' => 'title',
                    'type' => 'text',
                    'label' => 'Title',
                    'group' => 'content',
                    'localized' => true,
                    'required' => false,
                ],
                [
                    'name' => 'description',
                    'type' => 'textarea',
                    'label' => 'Description',
                    'group' => 'content',
                    'localized' => true,
                    'required' => false,
                ],
                [
                    'name' => 'features_heading',
                    'type' => 'text',
                    'label' => 'Features Heading',
                    'group' => 'features',
                    'localized' => true,
                    'required' => false,
                ],
                [
                    'name' => 'features',
                    'type' => 'repeater',
                    'label' => 'Features',
                    'group' => 'features',
                    'localized' => true,
                    'required' => false,
                    'ui' => [
                        'itemLabel' => 'Feature',
                        'supportsIconClass' => true,
                        'supportsIconSvg' => true,
                        'supportsIconMedia' => true,
                    ],
                    'default' => [],
                ],
                [
                    'name' => 'primary_button.label',
                    'type' => 'text',
                    'label' => 'CTA Button Label',
                    'group' => 'cta',
                    'localized' => true,
                    'required' => false,
                ],
                [
                    'name' => 'primary_button.url',
                    'type' => 'text',
                    'label' => 'CTA Button URL',
                    'group' => 'cta',
                    'localized' => true,
                    'required' => false,
                ],
                [
                    'name' => 'trust_items',
                    'type' => 'textarea',
                    'label' => 'Trust Items',
                    'group' => 'cta',
                    'localized' => true,
                    'required' => false,
                    'ui' => [
                        'rows' => 3,
                        'placeholder' => 'One line per item shown below the CTA button',
                    ],
                ],
                [
                    'name' => 'media_url',
                    'type' => 'media',
                    'label' => 'Illustration',
                    'group' => 'media',
                    'localized' => true,
                    'required' => false,
                    'ui' => [
                        'preview' => true,
                    ],
                ],
            ],
        ];
    }

    protected function programmingShowcaseSchema(): array
    {
        return [
            'type' => 'programming_showcase',
            'label' => 'Programming Showcase',
            'groups' => [
                [
                    'name' => 'branding',
                    'label' => 'Branding',
                ],
                [
                    'name' => 'content',
                    'label' => 'Content',
                ],
                [
                    'name' => 'outputs',
                    'label' => 'Outputs',
                ],
                [
                    'name' => 'cta',
                    'label' => 'CTA',
                ],
                [
                    'name' => 'media',
                    'label' => 'Media',
                ],
            ],
            'fields' => [
                [
                    'name' => 'brand_prefix',
                    'type' => 'text',
                    'label' => 'Brand Prefix',
                    'group' => 'branding',
                    'localized' => true,
                    'required' => false,
                ],
                [
                    'name' => 'brand_suffix',
                    'type' => 'text',
                    'label' => 'Brand Suffix',
                    'group' => 'branding',
                    'localized' => true,
                    'required' => false,
                ],
                [
                    'name' => 'title',
                    'type' => 'text',
                    'label' => 'Title',
                    'group' => 'content',
                    'localized' => true,
                    'required' => false,
                ],
                [
                    'name' => 'description',
                    'type' => 'textarea',
                    'label' => 'Description',
                    'group' => 'content',
                    'localized' => true,
                    'required' => false,
                ],
                [
                    'name' => 'outputs_heading',
                    'type' => 'text',
                    'label' => 'Outputs Heading',
                    'group' => 'outputs',
                    'localized' => true,
                    'required' => false,
                ],
                [
                    'name' => 'outputs',
                    'type' => 'repeater',
                    'label' => 'Outputs',
                    'group' => 'outputs',
                    'localized' => true,
                    'required' => false,
                    'ui' => [
                        'itemLabel' => 'Output',
                        'supportsIconClass' => true,
                        'supportsIconMedia' => true,
                    ],
                    'default' => [],
                ],
                [
                    'name' => 'primary_button.label',
                    'type' => 'text',
                    'label' => 'CTA Button Label',
                    'group' => 'cta',
                    'localized' => true,
                    'required' => false,
                ],
                [
                    'name' => 'primary_button.url',
                    'type' => 'text',
                    'label' => 'CTA Button URL',
                    'group' => 'cta',
                    'localized' => true,
                    'required' => false,
                ],
                [
                    'name' => 'primary_button.new_tab',
                    'type' => 'boolean',
                    'label' => 'Open CTA In New Tab',
                    'group' => 'cta',
                    'localized' => true,
                    'required' => false,
                    'default' => false,
                ],
                [
                    'name' => 'media_url',
                    'type' => 'media',
                    'label' => 'Featured Image',
                    'group' => 'media',
                    'localized' => true,
                    'required' => false,
                    'ui' => [
                        'preview' => true,
                    ],
                ],
            ],
        ];
    }

    protected function siteFooterSchema(): array
    {
        return [
            'type' => 'site_footer',
            'label' => 'Site Footer',
            'groups' => [
                [
                    'name' => 'settings',
                    'label' => 'Settings',
                ],
                [
                    'name' => 'content',
                    'label' => 'Content',
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
                    'name' => 'variant',
                    'type' => 'select',
                    'label' => 'Footer Layout',
                    'group' => 'settings',
                    'localized' => false,
                    'required' => false,
                    'default' => 'simple_social',
                    'ui' => [
                        'options' => [
                            ['value' => 'simple_social', 'label' => 'Social icons + copyright'],
                            ['value' => 'links_social', 'label' => 'Links + social icons + copyright'],
                        ],
                    ],
                ],
                [
                    'name' => 'copyright',
                    'type' => 'text',
                    'label' => 'Copyright Line',
                    'group' => 'content',
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
                        'fields' => [
                            ['name' => 'label', 'type' => 'text', 'label' => 'Label'],
                            ['name' => 'url', 'type' => 'text', 'label' => 'URL'],
                        ],
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
