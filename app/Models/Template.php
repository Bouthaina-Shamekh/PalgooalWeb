<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'price',
        'price_cents',
        'image',
        'image_media_id',
        'rating',
        'category_template_id',
        'discount_price',
        'discount_price_cents',
        'discount_ends_at',
        'plan_id',
    ];
    public function plan(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Plan::class);
    }

    // ADR-005 Wave 2 — media FK relation
    public function imageMedia(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Media::class, 'image_media_id');
    }

    /**
     * Prefer the FK-linked Media path; fall back to the raw image path string.
     * Returns null when no image is set at all.
     */
    public function resolvedImagePath(): ?string
    {
        return $this->imageMedia?->file_path ?? $this->getRawOriginal('image') ?? null;
    }

    protected $casts = [
        'price'                  => 'float',
        'price_cents'            => 'integer',
        'discount_price'         => 'float',
        'discount_price_cents'   => 'integer',
        'rating'                 => 'float',
        'discount_ends_at'       => 'datetime',
    ];

    // ──────────────────────────────────────────────────────────────────────────
    // ADR-003 Phase 1 — Price helpers
    //
    // Prefer the new integer-cents columns; fall back to the legacy decimal
    // columns during the dual-write / backfill period.
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Return the base price in integer cents.
     * Falls back to (int) round(price * 100) when price_cents is not yet populated.
     */
    public function resolvedPriceCents(): int
    {
        $raw = $this->getRawOriginal('price_cents');
        if ($raw !== null) {
            return (int) $raw;
        }

        return (int) round((float) ($this->getRawOriginal('price') ?? 0) * 100);
    }

    /**
     * Return the discount price in integer cents, or null when no discount exists.
     * Falls back to (int) round(discount_price * 100) during the transition period.
     */
    public function resolvedDiscountPriceCents(): ?int
    {
        $rawCents = $this->getRawOriginal('discount_price_cents');
        if ($rawCents !== null) {
            return (int) $rawCents;
        }

        $rawDecimal = $this->getRawOriginal('discount_price');
        if ($rawDecimal === null || (float) $rawDecimal <= 0) {
            return null;
        }

        return (int) round((float) $rawDecimal * 100);
    }

    /**
     * Return the base price as a float (for display / legacy decimal writes).
     */
    public function resolvedPrice(): float
    {
        return $this->resolvedPriceCents() / 100;
    }

    /**
     * Return the discount price as a float, or null when no discount exists.
     */
    public function resolvedDiscountPrice(): ?float
    {
        $cents = $this->resolvedDiscountPriceCents();
        return $cents !== null ? $cents / 100 : null;
    }

    public function categoryTemplate()
    {
        return $this->belongsTo(CategoryTemplate::class, 'category_template_id')->withDefault();
    }

    public function translations()
    {
        return $this->hasMany(TemplateTranslation::class);
    }

    public function translation($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return $this->translations->where('locale', $locale)->first();
    }

    public function reviews()
    {
        return $this->hasMany(\App\Models\TemplateReview::class);
    }

    // متوسط التقييم المعتمد فقط
    public function avgRating(): float
    {
        return (float) ($this->reviews()->approved()->avg('rating') ?? 0);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
