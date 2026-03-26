<?php

namespace App\Models;

use App\Models\Tenancy\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionTranslation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * Each row represents a localized version of a section:
     * - locale  : the language code (ar, en, ...)
     * - title   : optional section title (can be duplicated inside JSON if needed)
     * - content : JSON payload that stores the actual fields for the section
     */
    protected $fillable = [
        'section_id',
        'tenant_id',
        'locale',
        'title',
        'content',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'content' => 'array', // Automatically decode/encode JSON to array
    ];

    /**
     * Relationship: parent section.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Relationship: optional canonical tenant ownership.
     *
     * This remains opt-in and does not alter default rendering for
     * marketing translations with `tenant_id = null`.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'tenant_id');
    }

    /**
     * Scope: optionally filter translations by tenant ownership.
     *
     * Passing no tenant leaves the query unchanged so legacy/global
     * marketing pages continue to resolve normally.
     */
    public function scopeTenant(Builder $query, Subscription|int|string|null $tenant = null): Builder
    {
        if ($tenant === null) {
            return $query;
        }

        $tenantId = $tenant instanceof Subscription ? $tenant->getKey() : $tenant;

        return $query->where('tenant_id', $tenantId);
    }
}
