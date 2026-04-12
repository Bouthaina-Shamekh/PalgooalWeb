<?php

namespace App\Support\Sections;

/**
 * Code-side registry for curated section editor presets.
 *
 * Definitions should activate custom presets through:
 * - editor_mode = custom_preset
 * - custom_editor_key = registered preset key
 *
 * A temporary legacy bridge is also supported so already-linked definitions
 * can keep using their preset editor until data is backfilled.
 */
class SectionCustomPresetRegistry
{
    /**
     * Runtime registrations layered on top of config-defined presets.
     *
     * @var array<string, array<string, mixed>>
     */
    protected static array $presets = [];

    /**
     * Runtime legacy bridges layered on top of config-defined bridges.
     *
     * @var array<string, string>
     */
    protected static array $legacyBridges = [];

    /**
     * Register or override a preset entry at runtime.
     */
    public static function register(string $presetKey, array $config): void
    {
        static::$presets[$presetKey] = $config;
    }

    /**
     * Register or override a temporary bridge from section_key to preset key.
     */
    public static function bridgeLegacySectionKey(string $sectionKey, string $presetKey): void
    {
        static::$legacyBridges[$sectionKey] = $presetKey;
    }

    /**
     * Return all registered presets with normalized metadata.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        $configuredPresets = config('sections.custom_preset_registry.presets', []);
        $configuredPresets = is_array($configuredPresets) ? $configuredPresets : [];
        $presets = array_replace($configuredPresets, static::$presets);
        $normalized = [];

        foreach ($presets as $presetKey => $config) {
            if (! is_array($config)) {
                continue;
            }

            $builder = trim((string) ($config['builder'] ?? ''));
            $view = trim((string) ($config['view'] ?? ''));

            if ($builder === '' || $view === '') {
                continue;
            }

            $normalized[$presetKey] = [
                'preset_key' => $presetKey,
                'label' => (string) ($config['label'] ?? $presetKey),
                'builder' => $builder,
                'view' => $view,
                'description' => isset($config['description']) ? (string) $config['description'] : null,
                'meta' => is_array($config['meta'] ?? null) ? $config['meta'] : [],
            ];
        }

        return $normalized;
    }

    /**
     * Return one preset entry when it exists.
     */
    public static function get(?string $presetKey): ?array
    {
        if (! is_string($presetKey) || trim($presetKey) === '') {
            return null;
        }

        $presets = static::all();

        return $presets[$presetKey] ?? null;
    }

    /**
     * Determine whether a preset key is registered.
     */
    public static function exists(?string $presetKey): bool
    {
        return static::get($presetKey) !== null;
    }

    /**
     * Resolve a temporary bridge from a legacy section key to a preset key.
     */
    public static function legacyBridgePresetKey(?string $sectionKey): ?string
    {
        if (! is_string($sectionKey) || trim($sectionKey) === '') {
            return null;
        }

        $configuredBridges = config('sections.custom_preset_registry.legacy_section_key_bridge', []);
        $configuredBridges = is_array($configuredBridges) ? $configuredBridges : [];
        $bridges = array_replace($configuredBridges, static::$legacyBridges);
        $presetKey = $bridges[$sectionKey] ?? null;

        return static::exists($presetKey) ? $presetKey : null;
    }
}
