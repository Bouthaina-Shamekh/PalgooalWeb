<?php

namespace App\Models\Sections;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     * Normalized field types supported by the MVP definition builder.
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
        ];
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
