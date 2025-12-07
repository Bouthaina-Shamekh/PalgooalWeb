<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

class PageBuilderStructure extends Model
{
    protected $fillable = [
        'page_id',
        'structure',
    ];

    protected $casts = [
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
     * Convert the stored GrapesJS project structure into a
     * simple list of sections consumable by Blade components.
     *
     * @return array<int, array{type:string,data:array}>
     */
    public function normalizedSections(): array
    {
        $components = Arr::get($this->structure ?? [], 'pages.0.frames.0.component.components', []);

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
        return collect(Arr::get($component, 'components', []))
            ->first(function ($child) use ($field) {
                return Arr::get($child, 'attributes.data-field') === $field;
            });
    }
}
