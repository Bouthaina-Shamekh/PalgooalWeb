<?php

namespace App\Models\Sections;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Developer-managed section template registry entry.
 *
 * This model intentionally uses the "section_templates" table to avoid
 * colliding with the existing customer-facing App\Models\Template and the
 * production "templates" catalog already used elsewhere in the CMS.
 */
class Template extends Model
{
    use HasFactory;

    protected $table = 'section_templates';

    protected $fillable = [
        'template_key',
        'label',
        'description',
        'category',
        'settings',
        'schema',
        'is_active',
        'is_visible',
        'sort_order',
    ];

    protected $casts = [
        'settings' => 'array',
        'schema' => 'array',
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Section blueprint definitions that can use this registered template key.
     */
    public function sectionDefinitions(): BelongsToMany
    {
        return $this->belongsToMany(
            SectionDefinition::class,
            'section_definition_template',
            'section_template_id',
            'section_definition_id',
        )
            ->withTimestamps()
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }
}
