<?php

namespace App\Support\Sections;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\View;

/**
 * Code-side registry for developer-managed section templates.
 *
 * Database records should only store a stable template_key. This registry maps
 * that key to the actual Blade view and optional metadata, keeping rendering
 * logic in code instead of in the database.
 *
 * This registry is intentionally separate from SectionRegistry:
 * - SectionRegistry maps section types to current render configuration.
 * - SectionTemplateRegistry maps developer-system template keys to Blade
 *   template metadata for future definition-driven rendering flows.
 */
class SectionTemplateRegistry
{
    public const TEMPLATE_KEY_REGEX = '/^[a-z0-9_-]+$/';
    public const CATEGORY_REGEX = '/^[a-z0-9_-]+$/';
    public const DEFAULT_CATEGORY = 'uncategorized';

    /**
     * Runtime registrations layered on top of config-defined templates.
     *
     * This keeps the registry easy to extend later from modules or providers
     * without forcing a rewrite of the core API.
     *
     * @var array<string, array<string, mixed>>
     */
    protected static array $templates = [];

    /**
     * Register or override a template entry at runtime.
     */
    public static function register(string $templateKey, array $config): void
    {
        static::$templates[$templateKey] = $config;
    }

    /**
     * Return all registered templates with normalized metadata.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        $configuredTemplates = config('sections.template_registry.templates', []);
        $configuredTemplates = is_array($configuredTemplates) ? $configuredTemplates : [];

        $templates = array_replace($configuredTemplates, static::$templates);
        $normalizedTemplates = [];

        foreach ($templates as $templateKey => $config) {
            if (! is_array($config)) {
                continue;
            }

            $normalizedTemplates[$templateKey] = static::normalize($templateKey, $config);
        }

        return $normalizedTemplates;
    }

    /**
     * Return a single template entry when it exists.
     */
    public static function get(string $templateKey): ?array
    {
        $templates = static::all();

        return $templates[$templateKey] ?? null;
    }

    /**
     * Determine whether a template key is registered.
     */
    public static function exists(?string $templateKey): bool
    {
        if (! is_string($templateKey) || trim($templateKey) === '') {
            return false;
        }

        return static::get($templateKey) !== null;
    }

    /**
     * Determine whether the given template key is safe for convention-based
     * renderer resolution.
     */
    public static function isValidTemplateKey(?string $templateKey): bool
    {
        if (! is_string($templateKey)) {
            return false;
        }

        $templateKey = trim($templateKey);

        return $templateKey !== ''
            && preg_match(static::TEMPLATE_KEY_REGEX, $templateKey) === 1;
    }

    /**
     * Describe a template key for admin/runtime usage without exposing raw
     * view paths in the database.
     *
     * Registered keys keep their explicit code-side metadata. Unregistered but
     * valid keys resolve to the convention-based Blade candidate.
     *
     * @return array<string, mixed>|null
     */
    public static function describe(?string $templateKey, ?string $category = null): ?array
    {
        if (! static::isValidTemplateKey($templateKey)) {
            return null;
        }

        $templateKey = trim((string) $templateKey);
        $normalizedCategory = static::normalizeCategory($category);
        $registeredTemplate = static::get($templateKey);

        if (is_array($registeredTemplate)) {
            $view = trim((string) ($registeredTemplate['view'] ?? ''));

            return array_merge($registeredTemplate, [
                'view' => $view,
                'resolution_source' => 'registry',
                'view_exists' => $view !== '' && View::exists($view),
            ]);
        }

        $conventionView = static::conventionView($templateKey, $normalizedCategory);

        return [
            'template_key' => $templateKey,
            'label' => Str::headline(str_replace(['_', '-'], ' ', $templateKey)),
            'view' => $conventionView ?? '',
            'category' => $normalizedCategory,
            'meta' => [],
            'resolution_source' => 'convention',
            'view_exists' => $conventionView !== null && View::exists($conventionView),
        ];
    }

    /**
     * Resolve the Blade view for a template key without silently falling back
     * to the internal missing-template view.
     *
     * @return array{
     *     template_key: string|null,
     *     found: bool,
     *     view: string|null,
     *     source: string,
     *     descriptor: array<string, mixed>|null,
     *     attempted_views: array<int, string>
     * }
     */
    public static function resolve(?string $templateKey, ?string $category = null): array
    {
        $templateKey = is_string($templateKey) ? trim($templateKey) : null;
        $descriptor = static::describe($templateKey, $category);
        $candidateView = is_array($descriptor) ? trim((string) ($descriptor['view'] ?? '')) : '';
        $resolutionSource = is_array($descriptor)
            ? (string) ($descriptor['resolution_source'] ?? 'convention')
            : 'invalid';
        $attemptedViews = [];

        if ($candidateView !== '') {
            $attemptedViews[] = $candidateView;
        }

        $resolvedView = null;

        if ($candidateView !== '' && View::exists($candidateView)) {
            $resolvedView = $candidateView;
        }

        return [
            'template_key' => $templateKey,
            'found' => $resolvedView !== null,
            'view' => $resolvedView,
            'source' => $resolutionSource,
            'descriptor' => $descriptor,
            'attempted_views' => array_values(array_unique($attemptedViews)),
        ];
    }

    /**
     * Resolve the convention-based Blade view for a valid template key.
     */
    public static function conventionView(?string $templateKey, ?string $category = null): ?string
    {
        if (! static::isValidTemplateKey($templateKey)) {
            return null;
        }

        return 'front.sections.' . static::normalizeCategory($category) . '.' . trim((string) $templateKey);
    }

    public static function normalizeCategory(?string $category): string
    {
        $category = strtolower(trim((string) $category));

        return $category !== '' && preg_match(static::CATEGORY_REGEX, $category) === 1
            ? $category
            : static::DEFAULT_CATEGORY;
    }

    /**
     * Return the safe fallback Blade view for missing templates.
     */
    public static function fallbackView(): string
    {
        $fallbackView = config('sections.template_registry.fallback_view', 'front.sections._missing-template');

        if (is_string($fallbackView) && $fallbackView !== '' && View::exists($fallbackView)) {
            return $fallbackView;
        }

        return 'front.sections._missing-template';
    }

    /**
     * Normalize a raw template entry into a stable metadata contract.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected static function normalize(string $templateKey, array $config): array
    {
        $fallbackLabel = Str::headline(str_replace(['_', '-'], ' ', $templateKey));
        $view = trim((string) ($config['view'] ?? ''));

        return [
            'template_key' => $templateKey,
            'label' => __((string) ($config['label'] ?? $fallbackLabel)),
            'view' => $view,
            'category' => isset($config['category']) ? (string) $config['category'] : null,
            'meta' => is_array($config['meta'] ?? null) ? $config['meta'] : [],
        ];
    }
}
