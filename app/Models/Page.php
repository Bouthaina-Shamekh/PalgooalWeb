<?php

namespace App\Models;

use App\Models\Tenancy\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Canonical content aggregate for marketing pages.
 *
 * Architectural note:
 * - `Page` + `Section` is the primary authored content model for the
 *   marketing site, tenant runtime, and the admin sections workspace.
 * - `tenant_id` is an opt-in ownership field for future canonical tenancy
 *   support. It is nullable so existing marketing pages continue to work
 *   without any query or rendering changes.
 */
class Page extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * We include the new Page Builder context fields:
     * - context: defines where the page is used (marketing / tenant / ...)
     * - subscription_id: optional legacy linkage retained for backward-safe data access
     * - tenant_id: optional tenant ownership for the canonical Page + Section system
     */
    protected $fillable = [
        'context',
        'subscription_id',
        'tenant_id',
        'builder_mode',
        'is_active',
        'is_home',
        'published_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active'    => 'boolean',
        'is_home'      => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Relationship: all translations for this page (per locale).
     */
    public function translations()
    {
        return $this->hasMany(PageTranslation::class);
    }

    /**
     * Relationship: all sections belonging to this page.
     *
     * Each section represents a structured marketing content block.
     *
     * This relation is part of the canonical authored content model.
     * It should remain the source of truth when documenting or cleaning
     * up the marketing rendering system.
     */
    public function sections()
    {
        // We keep the default ordering by `order` so sections are always sorted.
        return $this->hasMany(Section::class)
            ->orderBy('order');
    }

    /**
     * Relationship: the subscription this page belongs to (for tenant/client pages).
     *
     * For marketing pages, `subscription_id` will be null.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Relationship: optional canonical tenant ownership.
     *
     * This deliberately points at the existing subscription tenancy record.
     * No global scope is applied, so `tenant_id = null` marketing pages
     * continue to behave exactly as they do today.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'tenant_id');
    }

    /**
     * Helper to get a single translation for the current (or given) locale.
     *
     * - First, try to find the requested locale.
     * - If not found, fallback to the first available translation.
     */
    public function translation(?string $locale = null): ?PageTranslation
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
     * Scope: filter pages by slug in a specific locale.
     *
     * This is mostly used for routing/SEO in the marketing site.
     */
    public function scopeWhereSlug(Builder $query, string $slug, ?string $locale = null): Builder
    {
        $locale = $locale ?? app()->getLocale();

        return $query->whereHas('translations', function (Builder $query) use ($slug, $locale) {
            $query->where('locale', $locale)
                ->where('slug', $slug);
        });
    }

    /**
     * Scope: only marketing pages (Palgoals main website).
     */
    public function scopeMarketing(Builder $query): Builder
    {
        return $query->where('context', 'marketing');
    }

    /**
     * Scope: filter pages by tenant ownership when a tenant is provided.
     *
     * Backward compatibility:
     * - Calling `tenant()` with no argument preserves the older
     *   `context = tenant` behavior.
     * - Calling `tenant($subscriptionOrId)` uses the new nullable
     *   `tenant_id` column without applying any global scope.
     */
    public function scopeTenant(Builder $query, Subscription|int|string|null $tenant = null): Builder
    {
        if ($tenant === null) {
            return $query->where('context', 'tenant');
        }

        $tenantId = $tenant instanceof Subscription ? $tenant->getKey() : $tenant;

        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: only active pages.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Accessor: convenient shortcut to get the current translation slug.
     *
     * Allows usage like: $page->slug
     */
    public function getSlugAttribute(): ?string
    {
        return $this->translation()?->slug;
    }

    /**
     * Accessor: convenient shortcut to get the translated title.
     *
     * Allows usage like: $page->title
     */
    public function getTitleAttribute(): ?string
    {
        return $this->translation()?->title;
    }
}
