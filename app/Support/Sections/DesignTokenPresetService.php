<?php

namespace App\Support\Sections;

use App\Models\Sections\SectionDefinition;
use App\Models\Sections\SectionDefinitionField;
use Illuminate\Support\Facades\DB;

/**
 * DesignTokenPresetService — one-click design control presets for Section Definitions.
 *
 * Each preset maps a logical name (e.g. 'colors') to a list of token keys
 * from DesignTokenRegistry. The service is the single place that decides
 * which tokens belong to which preset — no token definitions are repeated here.
 *
 * ALL field attributes (field_type, field_scope, default_value, options, etc.)
 * are read from DesignTokenRegistry::get($key). Nothing is hardcoded here.
 *
 * PRESETS
 * -------
 * colors          → background_token, text_token
 * spacing         → section_spacing
 * container       → container_width
 * image_layout    → image_position
 * all_design      → all 5 tokens (deduplication handled automatically)
 *
 * DUPLICATE PROTECTION
 * --------------------
 * missingFields() compares the requested token keys against the definition's
 * existing field_key values and returns only those not yet present.
 * This ensures no duplicate SectionDefinitionField rows are ever created.
 */
class DesignTokenPresetService
{
    /**
     * All available presets.
     *
     * Shape: preset_key => [
     *   'label'  => string,   // human-readable name
     *   'icon'   => string,   // Tabler icon class (ti-*)
     *   'color'  => string,   // colour slug for the UI button
     *   'tokens' => string[], // ordered list of DesignTokenRegistry keys
     * ]
     *
     * IMPORTANT: tokens[] MUST only contain keys registered in DesignTokenRegistry.
     * Adding an unknown key here will cause buildFields() to skip it silently
     * (DesignTokenRegistry::get() returns null for unknown keys).
     */
    private const ALL_PRESETS = [
        'colors' => [
            'label'  => 'الألوان',
            'icon'   => 'ti-palette',
            'color'  => 'violet',
            'tokens' => ['background_token', 'text_token'],
        ],
        'spacing' => [
            'label'  => 'المسافات',
            'icon'   => 'ti-arrow-autofit-height',
            'color'  => 'cyan',
            'tokens' => ['section_spacing'],
        ],
        'container' => [
            'label'  => 'عرض المحتوى',
            'icon'   => 'ti-arrows-horizontal',
            'color'  => 'indigo',
            'tokens' => ['container_width'],
        ],
        'image_layout' => [
            'label'  => 'موضع الصورة',
            'icon'   => 'ti-layout-sidebar-right',
            'color'  => 'emerald',
            'tokens' => ['image_position'],
        ],
        'all_design' => [
            'label'  => 'كل إعدادات التصميم',
            'icon'   => 'ti-sparkles',
            'color'  => 'amber',
            'tokens' => [
                'background_token',
                'text_token',
                'section_spacing',
                'container_width',
                'image_position',
            ],
        ],
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Preset discovery
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Return all preset keys.
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::ALL_PRESETS);
    }

    /**
     * Return the full preset definition for a given key, or null if not found.
     *
     * @return array<string, mixed>|null
     */
    public static function get(string $presetKey): ?array
    {
        return self::ALL_PRESETS[$presetKey] ?? null;
    }

    /**
     * Return all preset definitions.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return self::ALL_PRESETS;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Token keys for a preset
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Return the ordered token keys for a given preset.
     * Returns [] if the preset is not found.
     *
     * @return list<string>
     */
    public static function presetKeys(string $presetKey): array
    {
        return self::ALL_PRESETS[$presetKey]['tokens'] ?? [];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Field building
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build SectionDefinitionField attribute arrays for the given token keys.
     *
     * Every attribute is sourced from DesignTokenRegistry — nothing is hardcoded here.
     * sort_order values are sequential starting from $startSortOrder.
     *
     * @param  list<string>                $tokenKeys   ordered list of token field_keys
     * @param  int                         $startSortOrder  first sort_order to assign
     * @return list<array<string, mixed>>  ready-to-create attribute arrays
     */
    public static function buildFields(array $tokenKeys, int $startSortOrder = 0): array
    {
        $fields     = [];
        $sortOrder  = $startSortOrder;

        foreach ($tokenKeys as $tokenKey) {
            $token = DesignTokenRegistry::get($tokenKey);

            if ($token === null) {
                // Skip keys not registered in DesignTokenRegistry (safe fallback)
                continue;
            }

            // Build options array — format matches SectionDefinitionField expectations:
            // stored as JSON array of {value, label} objects
            $options = !empty($token['options']) ? $token['options'] : null;

            $fields[] = [
                'field_key'     => $token['key'],
                'label'         => $token['key'],     // raw key as label; admin can rename
                'field_type'    => $token['field_type'],
                'field_scope'   => $token['field_scope'],
                'default_value' => $token['default'] ?? null,
                'group_name'    => $token['group_name'] ?? 'design',
                'options'       => $options,
                'is_required'   => false,
                'is_active'     => true,
                'sort_order'    => $sortOrder++,
            ];
        }

        return $fields;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Duplicate protection
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Filter a list of token keys to only those NOT already present in
     * the given SectionDefinition's fields.
     *
     * Use this before buildFields() to avoid creating duplicate field rows.
     *
     * @param  SectionDefinition  $definition
     * @param  list<string>       $tokenKeys
     * @return list<string>       keys not yet present as SectionDefinitionField rows
     */
    public static function missingFields(SectionDefinition $definition, array $tokenKeys): array
    {
        $existingKeys = $definition->fields()
            ->pluck('field_key')
            ->flip(); // field_key => index for O(1) lookup

        return array_values(array_filter(
            $tokenKeys,
            fn (string $k) => ! $existingKeys->has($k)
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Apply
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Apply a design token preset to a SectionDefinition.
     *
     * Steps:
     *   1. Resolve the preset's token keys.
     *   2. Filter to only those not already present (duplicate protection).
     *   3. Build the attribute arrays (sourced from Registry).
     *   4. Insert inside a DB transaction.
     *
     * Returns the number of fields actually created (0 = all already existed).
     */
    public static function apply(string $presetKey, SectionDefinition $definition): int
    {
        $preset = self::get($presetKey);

        if ($preset === null) {
            return 0;
        }

        $allTokenKeys     = $preset['tokens'];
        $missingTokenKeys = self::missingFields($definition, $allTokenKeys);

        if (empty($missingTokenKeys)) {
            return 0;
        }

        $nextSortOrder  = ((int) $definition->fields()->max('sort_order')) + 1;
        $fieldAttributes = self::buildFields($missingTokenKeys, $nextSortOrder);
        $created         = 0;

        DB::transaction(function () use ($definition, $fieldAttributes, &$created) {
            foreach ($fieldAttributes as $attributes) {
                $definition->fields()->create($attributes);
                $created++;
            }
        });

        return $created;
    }
}
