<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageBuilderStructure extends Model
{
    protected $table = 'page_builder_structures';

    protected $fillable = [
        'page_id',
        'locale', // ✅ NEW
        'project',
        'structure',          // legacy support
        'html',
        'css',
        'published_html',
        'published_css_path',
        'published_at',
    ];

    protected $casts = [
        'project'      => 'array',
        'structure'    => 'array',
        'published_at' => 'datetime',
    ];

    /**
     * Relation: each builder structure belongs to a Page.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    // ✅ Helpful query scope
    public function scopeForLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    /**
     * ------------------------------------------------------------------
     * Get current project array
     * ------------------------------------------------------------------
     * We support both `project` (new field) and `structure` (legacy).
     * GrapesJS getProjectData() is expected to be stored in `project`.
     */
    public function getCurrentProject(): ?array
    {
        if (is_array($this->project) && ! empty($this->project)) return $this->project;
        if (is_array($this->structure) && ! empty($this->structure)) return $this->structure;
        return null;
    }

    /**
     * ------------------------------------------------------------------
     * Normalized sections for frontend rendering
     * ------------------------------------------------------------------
     * This method:
     *  - reads the GrapesJS project
     *  - scans all components
     *  - extracts components that are marked as "page sections"
     *    via attributes: data-pg-section="hero|features|..."
     *  - maps each section into:
     *
     *    [
     *      'type' => 'features',
     *      'data' => [ ... ]  // structure that matches Blade component
     *    ]
     *
     * These are later consumed in front/pages/page.blade.php
     * by the $builderSections loop.
     */
    public function normalizedSections(): array
    {
        $project = $this->getCurrentProject();

        if (! is_array($project)) {
            return [];
        }

        $pages = $project['pages'] ?? [];

        if (! is_array($pages) || empty($pages)) {
            return [];
        }

        $sections = [];

        foreach ($pages as $page) {
            $frames = $page['frames'] ?? [];

            foreach ($frames as $frame) {
                $root = $frame['component'] ?? null;

                if (! is_array($root)) {
                    continue;
                }

                $sections = array_merge($sections, $this->collectSectionsFromComponent($root));
            }
        }

        // ترتيب السكاشن حسب ترتيب ظهورها
        return array_values($sections);
    }

    /**
     * Recursively walk a GrapesJS component tree and collect
     * high-level sections marked with data-pg-section / data-pg-type.
     *
     * Expected markup from GrapesJS (example):
     *  <section data-pg-section="features"> ... </section>
     */
    protected function collectSectionsFromComponent(array $component): array
    {
        $sections = [];

        $attrs = $component['attributes'] ?? [];
        $pgType = $attrs['data-pg-section']
            ?? $attrs['data-pg-type']
            ?? null;

        if (is_string($pgType) && $pgType !== '') {
            $normalized = $this->mapComponentToSection($pgType, $component);

            if (is_array($normalized)) {
                $sections[] = $normalized;
            }
        }

        // Traverse children for nested sections
        $children = $component['components'] ?? [];

        if (is_array($children)) {
            foreach ($children as $child) {
                if (! is_array($child)) {
                    continue;
                }

                $sections = array_merge($sections, $this->collectSectionsFromComponent($child));
            }
        }

        return $sections;
    }

    /**
     * Map a section-type & GrapesJS component into our normalized
     * array structure compatible with Blade components.
     *
     * e.g.:
     *  - hero     => template.sections.hero
     *  - features => template.sections.features
     */
    protected function mapComponentToSection(string $type, array $component): ?array
    {
        $type = trim(strtolower($type));

        return match ($type) {
            'hero', 'hero_default'   => $this->mapHeroSection($component),
            'features', 'features-1' => $this->mapFeaturesSection($component),
            default                  => null,
        };
    }

    /**
     * ------------------------------------------------------------------
     * HERO mapping
     * ------------------------------------------------------------------
     * Expected that the GrapesJS section has attributes like:
     *  - data-pg-heading
     *  - data-pg-subtitle
     *  - data-pg-badge
     *  - data-pg-primary-text / data-pg-primary-url
     *  - data-pg-secondary-text / data-pg-secondary-url
     *  - data-pg-image (optional hero image)
     *
     * These attributes can be set from Traits or manually from
     * the block definition in page-builder.js.
     */
    protected function mapHeroSection(array $component): ?array
    {
        $attrs = $component['attributes'] ?? [];

        $heading   = $attrs['data-pg-heading'] ?? null;
        $subtitle  = $attrs['data-pg-subtitle'] ?? null;
        $badge     = $attrs['data-pg-badge'] ?? null;

        $primaryText = $attrs['data-pg-primary-text'] ?? null;
        $primaryUrl  = $attrs['data-pg-primary-url'] ?? null;

        $secondaryText = $attrs['data-pg-secondary-text'] ?? null;
        $secondaryUrl  = $attrs['data-pg-secondary-url'] ?? null;

        $imageUrl = $attrs['data-pg-image'] ?? null;

        // If everything is empty, skip mapping
        if (! $heading && ! $subtitle && ! $imageUrl && ! $primaryText && ! $secondaryText) {
            return null;
        }

        return [
            'type' => 'hero',
            'data' => [
                'heading'         => $heading,
                'subtitle'        => $subtitle,
                'badge'           => $badge,
                'primary_text'    => $primaryText,
                'primary_url'     => $primaryUrl,
                'secondary_text'  => $secondaryText,
                'secondary_url'   => $secondaryUrl,
                'bg'              => $imageUrl,
            ],
        ];
    }

    /**
     * ------------------------------------------------------------------
     * FEATURES mapping
     * ------------------------------------------------------------------
     * This method transforms a GrapesJS "features" section into the
     * structure expected by:
     *
     * resources/views/components/template/sections/features.blade.php
     *
     * which expects:
     *   $data['title']
     *   $data['subtitle']
     *   $data['features'] = [
     *      [
     *          'icon'        => '<svg ...>',
     *          'title'       => 'Feature title',
     *          'description' => 'Feature description',
     *      ],
     *      ...
     *   ]
     *
     * Expected builder markup:
     *  - Root section: <section data-pg-section="features" ...>
     *      attributes:
     *        data-pg-title
     *        data-pg-subtitle
     *
     *  - Each item inside: can be marked with data-pg-feature="item"
     *    and attributes:
     *        data-pg-feature-title
     *        data-pg-feature-description
     *    and first child could be icon (SVG) or an <i> element.
     */
    protected function mapFeaturesSection(array $component): ?array
    {
        $attrs = $component['attributes'] ?? [];

        $title    = $attrs['data-pg-title'] ?? null;
        $subtitle = $attrs['data-pg-subtitle'] ?? null;

        $featureItems = $this->extractFeatureItems($component);

        if (! $title && ! $subtitle && empty($featureItems)) {
            return null;
        }

        return [
            'type' => 'features',
            'data' => [
                'title'    => $title ?: __('مميزات منصتنا'),
                'subtitle' => $subtitle ?: '',
                'features' => $featureItems,
            ],
        ];
    }

    /**
     * Extract feature items from a GrapesJS component tree.
     *
     * We look for components having attribute data-pg-feature="item"
     * and then:
     *  - data-pg-feature-title        => title
     *  - data-pg-feature-description  => description
     *  - SVG / icon HTML from first child, if exists.
     */
    protected function extractFeatureItems(array $component): array
    {
        $items = [];

        $this->walkComponents($component, function (array $cmp) use (&$items) {
            $attrs = $cmp['attributes'] ?? [];

            if (($attrs['data-pg-feature'] ?? null) !== 'item') {
                return;
            }

            $title       = trim((string) ($attrs['data-pg-feature-title'] ?? ''));
            $description = trim((string) ($attrs['data-pg-feature-description'] ?? ''));

            // Try to capture SVG/icon HTML from first child
            $iconHtml = $this->extractIconHtmlFromComponent($cmp);

            if ($title === '' && $description === '' && $iconHtml === '') {
                return;
            }

            $items[] = [
                'icon'        => $iconHtml,
                'title'       => $title,
                'description' => $description,
            ];
        });

        return $items;
    }

    /**
     * Helper to walk the components tree.
     */
    protected function walkComponents(array $component, callable $callback): void
    {
        $callback($component);

        $children = $component['components'] ?? [];

        if (! is_array($children)) {
            return;
        }

        foreach ($children as $child) {
            if (! is_array($child)) {
                continue;
            }
            $this->walkComponents($child, $callback);
        }
    }

    /**
     * Try to extract SVG/icon markup from first child of the item.
     */
    protected function extractIconHtmlFromComponent(array $component): string
    {
        $children = $component['components'] ?? [];

        if (! is_array($children) || empty($children)) {
            return '';
        }

        $first = $children[0];

        // إذا GrapesJS خزن الـ icon كـ raw HTML في 'content'
        if (! empty($first['content']) && is_string($first['content'])) {
            return $first['content'];
        }

        // أو ممكن يكون له مزيد من الأطفال بداخل wrapper
        if (! empty($first['components']) && is_array($first['components'])) {
            foreach ($first['components'] as $child) {
                if (! empty($child['content']) && is_string($child['content'])) {
                    return $child['content'];
                }
            }
        }

        return '';
    }

    /**
     * Helper indicating if this builder has a published snapshot.
     */
    public function hasPublishedSnapshot(): bool
    {
        return ! empty($this->published_html);
    }
}
