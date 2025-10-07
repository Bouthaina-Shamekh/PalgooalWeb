<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'monthly_price_cents',
        'annual_price_cents',
        'server_id',
        'server_package',
        'plan_category_id',
        'is_active',
        'is_featured',
        'featured_label',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'is_featured' => 'bool',
        'monthly_price_cents' => 'integer',
        'annual_price_cents' => 'integer',
    ];

    /**
     * Subscriptions related to this plan
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * All translations for the plan
     */
    public function translations(): HasMany
    {
        return $this->hasMany(PlanTranslation::class, 'plan_id');
    }

    /**
     * Return the translation for a given locale (or current app locale).
     * Uses eager-loaded collection when available to avoid N+1.
     */
    public function translation(?string $locale = null)
    {
        $locale = $locale ?: app()->getLocale();

        if ($this->relationLoaded('translations')) {
            return $this->translations->firstWhere('locale', $locale) ?? $this->translations->first();
        }

        return $this->translations()->where('locale', $locale)->first() ?? $this->translations()->first();
    }

    /**
     * The category (plan_category) this plan belongs to
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PlanCategory::class, 'plan_category_id');
    }

    /**
     * Server relation
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    /**
     * Title accessor - returns translated title if available, otherwise top-level name or slug
     */
    public function getTitleAttribute(): string
    {
        return $this->translation()?->title
            ?? $this->attributes['name'] ?? $this->attributes['slug'] ?? '';
    }

    /**
     * Return monthly price as float (dollars)
     */
    public function getMonthlyPriceAttribute(): ?float
    {
        if (!isset($this->attributes['monthly_price_cents']) || $this->attributes['monthly_price_cents'] === null) {
            return null;
        }
        return $this->attributes['monthly_price_cents'] / 100;
    }

    /**
     * Return annual price as float (dollars)
     */
    public function getAnnualPriceAttribute(): ?float
    {
        if (!isset($this->attributes['annual_price_cents']) || $this->attributes['annual_price_cents'] === null) {
            return null;
        }
        return $this->attributes['annual_price_cents'] / 100;
    }

    /**
     * Formatted price strings for display (e.g. "$9.99")
     */
    public function getMonthlyPriceFormattedAttribute(): ?string
    {
        return $this->monthly_price !== null ? '$' . number_format($this->monthly_price, 2) : null;
    }

    public function getAnnualPriceFormattedAttribute(): ?string
    {
        return $this->annual_price !== null ? '$' . number_format($this->annual_price, 2) : null;
    }

    /**
     * Backwards-compatible accessor so $plan->slug returns stored slug
     */
    public function getSlugAttribute(): ?string
    {
        return $this->attributes['slug'] ?? null;
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        // if you add position later, switch to orderBy('position')
        return $query->orderBy('id', 'desc');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function getFeaturedLabelAttribute(): ?string
    {
        $label = $this->attributes['featured_label'] ?? null;
        if ($this->is_featured && !$label) {
            return __('Most Popular');
        }
        return $label;
    }
}
