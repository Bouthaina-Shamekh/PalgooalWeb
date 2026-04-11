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
     * Resolve the Blade view for a template key.
     *
     * If the key is missing or the configured view no longer exists, the
     * registry falls back to a safe internal view.
     */
    public static function resolveView(?string $templateKey): string
    {
        $template = is_string($templateKey) ? static::get($templateKey) : null;
        $view = is_array($template) ? ($template['view'] ?? null) : null;

        if (is_string($view) && $view !== '' && View::exists($view)) {
            return $view;
        }

        return static::fallbackView();
    }

    /**
     * Return the safe fallback Blade view for missing templates.
     */
    public static function fallbackView(): string
    {
        $fallbackView = config('sections.template_registry.fallback_view', 'components.template.sections._missing-template');

        if (is_string($fallbackView) && $fallbackView !== '' && View::exists($fallbackView)) {
            return $fallbackView;
        }

        return 'components.template.sections._missing-template';
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

        return [
            'template_key' => $templateKey,
            'label' => __((string) ($config['label'] ?? $fallbackLabel)),
            'view' => (string) ($config['view'] ?? static::fallbackView()),
            'category' => isset($config['category']) ? (string) $config['category'] : null,
            'meta' => is_array($config['meta'] ?? null) ? $config['meta'] : [],
        ];
    }
}
