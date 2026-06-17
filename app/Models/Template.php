<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'price_cents',
        'discount_price_cents',
        'discount_ends_at',
        'rating',
        'category_template_id',
        'plan_id',
        'image',
        'image_media_id',
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
        'price_cents'          => 'integer',
        'discount_price_cents' => 'integer',
        'rating'               => 'float',
        'discount_ends_at'     => 'datetime',
    ];

    // ──────────────────────────────────────────────────────────────────────────
    // ── ADR-003 Phase 3 — Price helpers (cents-only, legacy columns dropped) ──

    /**
     * Return the base price in integer cents.
     */
    public function resolvedPriceCents(): int
    {
        return (int) ($this->getRawOriginal('price_cents') ?? 0);
    }

    /**
     * Return the discount price in integer cents, or null when no discount exists.
     */
    public function resolvedDiscountPriceCents(): ?int
    {
        $rawCents = $this->getRawOriginal('discount_price_cents');
        return $rawCents !== null ? (int) $rawCents : null;
    }

    /**
     * Return the base price as a float (dollars).
     */
    public function resolvedPrice(): float
    {
        return $this->resolvedPriceCents() / 100;
    }

    /**
     * Return the discount price as a float (dollars), or null when no discount exists.
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
