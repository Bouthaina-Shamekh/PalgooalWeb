<?php

namespace App\Models\Sections;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Field definition belonging to a developer-managed section blueprint.
 *
 * Field definitions are locale-agnostic. The field scope declares whether a
 * future content value is shared or translatable across enabled locales.
 */
class SectionDefinitionField extends Model
{
    use HasFactory;

    public const FIELD_TYPE_TEXT = 'text';
    public const FIELD_TYPE_TEXTAREA = 'textarea';
    public const FIELD_TYPE_RICHTEXT = 'richtext';
    public const FIELD_TYPE_URL = 'url';
    public const FIELD_TYPE_MEDIA = 'media';
    public const FIELD_TYPE_NUMBER = 'number';
    public const FIELD_TYPE_BOOLEAN = 'boolean';
    public const FIELD_TYPE_SELECT = 'select';

    /**
     * Repeater field type (Phase 5A — schema foundation only).
     *
     * A repeater field holds an ordered list of structured items. Each item
     * conforms to an item_schema stored in the field's `schema` column under
     * the key "item_schema". Sub-field types are limited to the V1 allowlist
     * returned by repeaterSubFieldTypes(). Nested repeaters are not supported.
     *
     * Editor rendering and save/load pipeline implemented in Phase 5C
     * (dynamic-editor/fields/repeater.blade.php + repeater-item.blade.php).
     */
    public const FIELD_TYPE_REPEATER = 'repeater';

    public const FIELD_SCOPE_SHARED = 'shared';
    public const FIELD_SCOPE_TRANSLATABLE = 'translatable';

    protected $fillable = [
        'section_definition_id',
        'field_key',
        'label',
        'group_name',
        'help_text',
        'field_type',
        'field_scope',
        'default_value',
        'options',
        'settings',
        'schema',
        'is_required',
        'validation_rules',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'default_value' => 'array',
        'options' => 'array',
        'settings' => 'array',
        'schema' => 'array',
        'is_required' => 'boolean',
        'validation_rules' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * All field types recognized by the definition builder and its validation layer.
     *
     * Both form requests (Store/UpdateSectionDefinitionFieldRequest) validate
     * against this list via Rule::in(), so adding a type here is the single
     * change needed to unlock it in the admin create/edit forms.
     *
     * @return array<int, string>
     */
    public static function supportedFieldTypes(): array
    {
        return [
            self::FIELD_TYPE_TEXT,
            self::FIELD_TYPE_TEXTAREA,
            self::FIELD_TYPE_RICHTEXT,
            self::FIELD_TYPE_URL,
            self::FIELD_TYPE_MEDIA,
            self::FIELD_TYPE_NUMBER,
            self::FIELD_TYPE_BOOLEAN,
            self::FIELD_TYPE_SELECT,
            self::FIELD_TYPE_REPEATER,
        ];
    }

    /**
     * V1 allowlist of sub-field types permitted inside a repeater item_schema.
     *
     * Intentionally excludes:
     * - repeater  — nested repeaters are not supported in V1
     * - richtext  — complex editor dependencies not suitable for inline item fields
     * - number    — not yet needed by any planned V1 repeater use-case
     *
     * @return array<int, string>
     */
    public static function repeaterSubFieldTypes(): array
    {
        return [
            self::FIELD_TYPE_TEXT,
            self::FIELD_TYPE_TEXTAREA,
            self::FIELD_TYPE_URL,
            self::FIELD_TYPE_MEDIA,
            self::FIELD_TYPE_BOOLEAN,
            self::FIELD_TYPE_SELECT,
        ];
    }

    /**
     * Whether this field definition represents a repeater.
     */
    public function isRepeater(): bool
    {
        return $this->field_type === self::FIELD_TYPE_REPEATER;
    }

    /**
     * Backward-compatible repeater schema accessor used by Phase 5D normalizers.
     *
     * Returns the same normalized contract as repeaterItemSchema() so callers
     * from Phase 5A/5B/5C and newer save-time normalization all see the same
     * sanitized shape.
     *
     * @return array<int, array{key: string, label: string, type: string, required: bool, translatable: bool, options?: string}>
     */
    public function getItemSchema(): array
    {
        return $this->repeaterItemSchema();
    }

    /**
     * Return the normalized item schema for this repeater field.
     *
     * Reads the `item_schema` key from the `schema` JSON column and returns
     * a cleaned, validated array of sub-field descriptors. Each entry in the
     * returned array is guaranteed to have the following shape:
     *
     *   [
     *     'key'          => string   // non-empty; snake_case identifier
     *     'label'        => string   // display label (falls back to key)
     *     'type'         => string   // one of repeaterSubFieldTypes()
     *     'required'     => bool
     *     'translatable' => bool
     *     'options'      => string|null // select sub-fields only
     *   ]
     *
     * Malformed entries (missing key, unrecognized type, wrong shape) are
     * silently dropped. This ensures stored data can never break the system
     * even if item_schema was written directly or by a seeder.
     *
     * Returns [] when:
     * - the field is not a repeater
     * - the schema column is null or empty
     * - item_schema is missing or not an array
     *
     * @return array<int, array{key: string, label: string, type: string, required: bool, translatable: bool, options?: string}>
     */
    public function repeaterItemSchema(): array
    {
        if (! $this->isRepeater()) {
            return [];
        }

        $raw = is_array($this->schema['item_schema'] ?? null)
            ? $this->schema['item_schema']
            : [];

        $allowedSubTypes = self::repeaterSubFieldTypes();

        $items = Collection::make($raw)
            ->map(function (mixed $item) use ($allowedSubTypes): ?array {
                if (! is_array($item)) {
                    return null;
                }

                $key  = trim((string) ($item['key'] ?? ''));
                $type = trim((string) ($item['type'] ?? ''));

                // Both key and type are required and type must be in the V1 allowlist.
                if ($key === '' || ! in_array($type, $allowedSubTypes, true)) {
                    return null;
                }

                $normalized = [
                    'key'          => $key,
                    'label'        => trim((string) ($item['label'] ?? $key)) ?: $key,
                    'type'         => $type,
                    'required'     => (bool) ($item['required'] ?? false),
                    'translatable' => (bool) ($item['translatable'] ?? true),
                ];

                if ($type === self::FIELD_TYPE_SELECT) {
                    $options = trim((string) ($item['options'] ?? ''));

                    if ($options !== '') {
                        $normalized['options'] = $options;
                    }
                }

                return $normalized;
            })
            ->filter()
            ->values()
            ->all();

        $keys = Collection::make($items)
            ->pluck('key')
            ->filter(fn (mixed $key): bool => is_string($key) && $key !== '')
            ->values();

        if (
            $keys->contains('icon_source') &&
            $keys->contains('icon_media') &&
            ! $keys->contains('icon')
        ) {
            $iconMediaIndex = Collection::make($items)->search(
                fn (array $item): bool => ($item['key'] ?? null) === 'icon_media',
            );
            $iconField = [
                'key' => 'icon',
                'label' => 'icon',
                'type' => self::FIELD_TYPE_TEXT,
                'required' => false,
                'translatable' => (bool) (Collection::make($items)->firstWhere('key', 'icon_source')['translatable'] ?? true),
            ];

            if (is_int($iconMediaIndex)) {
                array_splice($items, $iconMediaIndex, 0, [$iconField]);
            } else {
                $items[] = $iconField;
            }
        }

        return $items;
    }

    /**
     * Parent blueprint definition for this field.
     */
    public function sectionDefinition(): BelongsTo
    {
        return $this->belongsTo(SectionDefinition::class);
    }

    /**
     * Whether the field stores locale-specific values by definition.
     */
    public function isTranslatable(): bool
    {
        return $this->field_scope === self::FIELD_SCOPE_TRANSLATABLE;
    }
}
