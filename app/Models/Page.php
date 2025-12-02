<?php

namespace App\Models;

use App\Models\Tenancy\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * We include the new Page Builder context fields:
     * - context: defines where the page is used (marketing / tenant / ...)
     * - subscription_id: links tenant pages to a specific subscription (future use)
     */
    protected $fillable = [
        'context',
        'subscription_id',
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
     * Each section represents a content block in the Page Builder.
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
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
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
     * Scope: only tenant/client pages (for subscriptions).
     */
    public function scopeTenant(Builder $query): Builder
    {
        return $query->where('context', 'tenant');
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
