<?php

namespace App\Models\Sections;

use App\Models\Media;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Developer-managed section blueprint definition.
 *
 * This model belongs to the definition layer only. It does not represent a
 * page-level section instance and it does not replace the existing Section /
 * SectionTranslation content tables.
 */
class SectionDefinition extends Model
{
    use HasFactory;

    public const EDITOR_MODE_DYNAMIC = 'dynamic';
    public const EDITOR_MODE_CUSTOM_PRESET = 'custom_preset';

    protected $fillable = [
        'section_key',
        'label',
        'description',
        'category',
        'editor_mode',
        'custom_editor_key',
        'preview_media_id',
        'settings',
        'schema',
        'is_active',
        'is_visible',
        'sort_order',
    ];

    protected $casts = [
        'preview_media_id' => 'integer',
        'settings' => 'array',
        'schema' => 'array',
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Field definitions attached to this section blueprint.
     */
    public function fields(): HasMany
    {
        return $this->hasMany(SectionDefinitionField::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Canonical section instances currently linked to this definition.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    /**
     * Registered frontend template keys allowed for this definition.
     */
    public function templates(): BelongsToMany
    {
        return $this->belongsToMany(
            Template::class,
            'section_definition_template',
            'section_definition_id',
            'section_template_id',
        )
            ->withTimestamps()
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    /**
     * Optional media thumbnail used for admin/library preview cards.
     */
    public function previewMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'preview_media_id');
    }

    /**
     * Primary template currently selected for this definition.
     *
     * The current admin CRUD stores one effective template selection through
     * the ordered pivot, so frontend rendering can safely treat the first
     * ordered template as the active template_key.
     */
    public function primaryTemplate(): ?Template
    {
        if ($this->relationLoaded('templates')) {
            return $this->templates->first();
        }

        return $this->templates()
            ->where('is_active', true)
            ->orderByPivot('sort_order')
            ->orderBy('id')
            ->first();
    }

    /**
     * Primary template key used by the definition-driven render path.
     */
    public function primaryTemplateKey(): ?string
    {
        return $this->primaryTemplate()?->template_key;
    }
}
