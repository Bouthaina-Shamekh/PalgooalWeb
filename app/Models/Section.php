<?php

namespace App\Models;

use App\Models\Tenancy\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Canonical structured content block for marketing pages.
 *
 * Architectural note:
 * - `Section` belongs to the primary authored content system used by
 *   `Page`, the tenant runtime, and the admin sections workspace.
 * - `tenant_id` is intentionally nullable and opt-in so existing
 *   marketing sections continue to render unchanged.
 */
class Section extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * - page_id   : the page this section belongs to
     * - type      : logical block key used by the Page Builder (hero_default, features_grid, etc.)
     * - variant   : optional design variation for the same type (default, v2, minimal, etc.)
     * - order     : display order of the section within the page
     * - is_active : toggle to show/hide the section without deleting it
     */
    protected $fillable = [
        'page_id',
        'tenant_id',
        'type',
        'variant',
        'style',
        'order',
        'is_active',
    ];

    /**
     * Attribute type casting.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'style'     => 'array',
    ];

    /**
     * Relationship: all translations for this section (per locale).
     */
    public function translations()
    {
        return $this->hasMany(SectionTranslation::class);
    }

    /**
     * Helper: get a single translation for the current (or given) locale.
     *
     * - First, try to find the requested locale.
     * - If not found, fallback to the first available translation.
     */
    public function translation(?string $locale = null): ?SectionTranslation
    {
        $locale = $locale ?? app()->getLocale();

        // If translations are already eager loaded, use the in-memory collection.
        if ($this->relationLoaded('translations')) {
            return $this->translations->firstWhere('locale', $locale)
                ?? $this->translations->first();
        }

        // Otherwise, query the database directly.
        return $this->translations()
            ->where('locale', $locale)
            ->first()
            ?? $this->translations()->first();
    }

    /**
     * Relationship: the page this section belongs to.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Relationship: optional canonical tenant ownership.
     *
     * No global scope is applied, so sections with `tenant_id = null`
     * remain part of the default marketing content path.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'tenant_id');
    }

    /**
     * Optional relationship: associated image for this section.
     *
     * NOTE: This assumes there is an `image_id` column on the `sections` table
     * and a `Media` model that stores uploaded media files.
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_id');
    }

    /**
     * Scope: optionally filter sections by tenant ownership.
     *
     * Passing no tenant leaves the query unchanged, which keeps existing
     * rendering and admin behavior stable until tenant-aware queries are added.
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
