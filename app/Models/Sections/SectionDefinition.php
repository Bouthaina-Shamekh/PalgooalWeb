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

    // ── Visibility Scope — allowed values ────────────────────────────────────
    // Controls which builder picker surfaces this definition to the user.
    // DEFAULT in DB is 'both' so existing rows require no backfill.

    /** Visible in both Admin Builder and Client Builder (default). */
    public const SCOPE_BOTH = 'both';

    /** Visible in Admin Builder only — e.g. pricing_plans_dynamic. */
    public const SCOPE_ADMIN_ONLY = 'admin_only';

    /** Visible in Client Builder only — reserved for future client-specific sections. */
    public const SCOPE_CLIENT_ONLY = 'client_only';

    /** Hidden from all builder pickers — draft / internal / deprecated. */
    public const SCOPE_HIDDEN = 'hidden';

    // ─────────────────────────────────────────────────────────────────────────

    protected $fillable = [
        'section_key',
        'label',
        'description',
        'category',
        'editor_mode',
        'preview_media_id',
        'settings',
        'schema',
        'blade_source',
        'blade_written_at',
        'blade_hash',
        'disk_hash',
        'is_active',
        'is_visible',
        'visibility_scope',
        'sort_order',
    ];

    protected $casts = [
        'preview_media_id' => 'integer',
        'settings' => 'array',
        'schema' => 'array',
        'blade_written_at' => 'datetime',
        'blade_hash'       => 'string',
        'disk_hash'        => 'string',
        'is_active'        => 'boolean',
        'is_visible'       => 'boolean',
        'visibility_scope' => 'string',
        'sort_order'       => 'integer',
    ];

    // ── Scope Helpers ────────────────────────────────────────────────────────

    /**
     * Scope values that should appear in the Admin Page Builder picker.
     *
     * @return string[]
     */
    public static function adminVisibleScopes(): array
    {
        return [self::SCOPE_BOTH, self::SCOPE_ADMIN_ONLY];
    }

    /**
     * Scope values that should appear in the Client Site Builder picker.
     *
     * @return string[]
     */
    public static function clientVisibleScopes(): array
    {
        return [self::SCOPE_BOTH, self::SCOPE_CLIENT_ONLY];
    }

    /**
     * Whether this definition should appear in the Admin Builder picker.
     */
    public function isVisibleForAdmin(): bool
    {
        return in_array($this->visibility_scope ?? self::SCOPE_BOTH, self::adminVisibleScopes(), true);
    }

    /**
     * Whether this definition should appear in the Client Builder picker.
     */
    public function isVisibleForClient(): bool
    {
        return in_array($this->visibility_scope ?? self::SCOPE_BOTH, self::clientVisibleScopes(), true);
    }

    // ─────────────────────────────────────────────────────────────────────────

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
