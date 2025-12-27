<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use App\Models\Service;

class PageBuilderStructure extends Model
{
    /**
     * Table for visual page builder structures.
     *
     * We are in a transition from older "structure" JSON column
     * to a cleaner schema:
     *  - project : full GrapesJS projectData (array)
     *  - html    : compiled HTML for frontend rendering
     *  - css     : compiled CSS for frontend rendering
     *
     * For backward compatibility:
     *  - normalizedSections() will read from project if available,
     *    otherwise falls back to "structure".
     */
    protected $table = 'page_builder_structures';

    protected $fillable = [
        'page_id',

        // New recommended fields
        'project',
        'html',
        'css',

        // Legacy field (still used until old data is migrated)
        'structure',
    ];

    protected $casts = [
        // New preferred storage for GrapesJS project data
        'project'   => 'array',

        // Legacy storage (older code may still use it)
        'structure' => 'array',
    ];

    /**
     * Relationship: owning page.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Helper to get current builder project array in a unified way.
     *
     * Prefer $this->project (new schema), but fall back to $this->structure
     * so old rows continue to work until fully migrated.
     */
    public function getCurrentProject(): array
    {
        if (is_array($this->project) && ! empty($this->project)) {
            return $this->project;
        }

        if (is_array($this->structure) && ! empty($this->structure)) {
            return $this->structure;
        }

        return [];
    }

    /**
     * Convert the stored GrapesJS project structure into a
     * simple list of sections consumable by Blade components.
     *
     * @return array<int, array{type:string,data:array}>
     */
    public function normalizedSections(): array
    {
        // ✅ استخدام project الجديد، أو structure القديم كـ fallback
        $structure = $this->getCurrentProject();

        // Lite builder format: { mode: 'lite-builder', blocks: [...] }
        if (Arr::get($structure, 'mode') === 'lite-builder' && is_array(Arr::get($structure, 'blocks'))) {
            return $this->mapLiteBlocks(Arr::get($structure, 'blocks', []));
        }

        $components = Arr::get($structure, 'pages.0.frames.0.component.components', []);

        return collect($components)
            ->map(fn(array $component) => $this->mapComponentToSection($component))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Map a single GrapesJS component to a Blade section payload.
     */
    protected function mapComponentToSection(array $component): ?array
    {
        $type = Arr::get($component, 'attributes.data-section-type')
            ?: Arr::get($component, 'type');

        return match ($type) {
            'hero' => [
                'type' => 'hero',
                'data' => [
                    'title'          => $this->extractText($component, 'title', 'Hero title'),
                    'subtitle'       => $this->extractText($component, 'subtitle', ''),
                    'button_text-1'  => $this->extractText($component, 'primary-button', 'Get Started'),
                    'button_url-1'   => $this->extractAttribute($component, 'primary-button', 'href', '#'),
                    'button_text-2'  => $this->extractText($component, 'secondary-button', 'View templates'),
                    'button_url-2'   => $this->extractAttribute($component, 'secondary-button', 'href', '#'),
                    'image'          => $this->extractAttribute($component, 'image', 'src'),
                    'alignment'      => Arr::get($component, 'attributes.data-alignment', 'left'),
                    'background'     => Arr::get($component, 'attributes.data-background'),
                ],
            ],

            'features' => [
                'type' => 'features',
                'data' => [
                    'title'    => $this->extractText($component, 'title', 'Features'),
                    'subtitle' => $this->extractText($component, 'subtitle', ''),
                    'features' => $this->extractFeatureItems($component),
                ],
            ],

            default => null,
        };
    }

    /**
     * Map simplified blocks (lite builder) to sections.
     *
     * @param array<int, array{type:string,data:array}> $blocks
     */
    protected function mapLiteBlocks(array $blocks): array
    {
        return collect($blocks)
            ->map(function ($block) {
                $type = $block['type'] ?? '';
                $data = $block['data'] ?? [];

                return match ($type) {
                    'text' => [
                        'type' => 'text',
                        'data' => [
                            'title' => Arr::get($data, 'title', ''),
                            'body'  => Arr::get($data, 'body', ''),
                            'align' => Arr::get($data, 'align', 'left'),
                        ],
                    ],
                    'image' => [
                        'type' => 'image',
                        'data' => [
                            'url'   => Arr::get($data, 'url', ''),
                            'alt'   => Arr::get($data, 'alt', ''),
                            'width' => Arr::get($data, 'width', '100%'),
                            'align' => Arr::get($data, 'align', 'center'),
                        ],
                    ],
                    'button' => [
                        'type' => 'button',
                        'data' => [
                            'text'  => Arr::get($data, 'text', ''),
                            'url'   => Arr::get($data, 'url', '#'),
                            'style' => Arr::get($data, 'style', 'primary'),
                            'align' => Arr::get($data, 'align', 'center'),
                        ],
                    ],
                    'features' => [
                        'type' => 'features',
                        'data' => [
                            'title'    => Arr::get($data, 'title', ''),
                            'subtitle' => Arr::get($data, 'subtitle', ''),
                            'features' => is_array(Arr::get($data, 'features'))
                                ? Arr::get($data, 'features')
                                : [],
                        ],
                    ],
                    'section' => [
                        'type' => 'section',
                        'data' => [
                            'title'   => Arr::get($data, 'title', ''),
                            'body'    => Arr::get($data, 'body', ''),
                            'bg'      => Arr::get($data, 'bg', '#ffffff'),
                            'padding' => Arr::get($data, 'padding', '24'),
                            'align'   => Arr::get($data, 'align', 'left'),
                        ],
                    ],
                    'hero-template' => [
                        'type' => 'hero-template',
                        'data' => [
                            'heading'       => Arr::get($data, 'heading', ''),
                            'subtitle'      => Arr::get($data, 'subtitle', ''),
                            'primary_text'  => Arr::get($data, 'primaryText', ''),
                            'primary_url'   => Arr::get($data, 'primaryUrl', '#'),
                            'secondary_text' => Arr::get($data, 'secondaryText', ''),
                            'secondary_url' => Arr::get($data, 'secondaryUrl', '#'),
                            'bg'            => Arr::get($data, 'bg', ''),
                        ],
                    ],
                    'support-hero' => [
                        'type' => 'support-hero',
                        'data' => [
                            'heading'    => Arr::get($data, 'heading', ''),
                            'body'       => Arr::get($data, 'body', ''),
                            'light_img'  => Arr::get($data, 'lightImg', ''),
                            'dark_img'   => Arr::get($data, 'darkImg', ''),
                            'color_from' => Arr::get($data, 'colorFrom', '#ff4694'),
                            'color_to'   => Arr::get($data, 'colorTo', '#776fff'),
                        ],
                    ],
                    'services' => [
                        'type' => 'services',
                        'data' => [
                            'badge'    => Arr::get($data, 'badge', ''),
                            'title'    => Arr::get($data, 'title', ''),
                            'subtitle' => Arr::get($data, 'subtitle', ''),
                            'bg'       => Arr::get($data, 'bg', ''),
                            // Always fetch current services from DB so front matches dashboard content.
                            'services' => Service::with('translations')->orderBy('order')->get(),
                        ],
                    ],
                    default => null,
                };
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Extract text content from a child component with data-field=$field.
     */
    protected function extractText(array $component, string $field, string $default = ''): string
    {
        $child = $this->findChildByField($component, $field);
        $content = is_array($child) ? ($child['content'] ?? '') : '';

        if (is_string($content) && $content !== '') {
            return trim($content);
        }

        // If the text component has nested children, prefer their content
        $nested = Arr::get($child, 'components.0.content');

        return is_string($nested) && $nested !== ''
            ? trim($nested)
            : $default;
    }

    /**
     * Extract an attribute from a child component with data-field=$field.
     */
    protected function extractAttribute(array $component, string $field, string $attribute, ?string $default = null): ?string
    {
        $child = $this->findChildByField($component, $field);
        $value = is_array($child)
            ? Arr::get($child, "attributes.{$attribute}")
            : null;

        return is_string($value) && $value !== ''
            ? $value
            : $default;
    }

    /**
     * Build features list from feature-item children.
     *
     * Each feature item can optionally define:
     * - data-icon (HTML string or icon name)
     * - item-title / item-description as data-field tags
     */
    protected function extractFeatureItems(array $component): array
    {
        return collect(Arr::get($component, 'components', []))
            ->filter(fn($child) => Arr::get($child, 'attributes.data-field') === 'feature-item')
            ->map(function ($item) {
                return [
                    'icon'        => Arr::get($item, 'attributes.data-icon') ?? '<i class="ti ti-check"></i>',
                    'title'       => $this->extractText($item, 'item-title', 'Feature title'),
                    'description' => $this->extractText($item, 'item-description', 'Feature description'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Helper to locate a child component by its data-field attribute.
     */
    protected function findChildByField(array $component, string $field): ?array
    {
        // 🔹 نبحث بشكل Recursively في كل الـ components
        $children = Arr::get($component, 'components', []);

        foreach ($children as $child) {
            if (! is_array($child)) {
                continue;
            }

            // لو هذا العنصر عنده data-field يطابق المطلوب → رجّعه
            if (Arr::get($child, 'attributes.data-field') === $field) {
                return $child;
            }

            // غير هيك، نكمّل البحث جواته
            $found = $this->findChildByField($child, $field);
            if ($found) {
                return $found;
            }
        }

        return null;
    }
}
