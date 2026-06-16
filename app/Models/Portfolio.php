<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Portfolio extends Model
{
    use SoftDeletes;

    protected $table = 'portfolios';

    protected $fillable = [
        'default_image',
        'default_image_media_id',  // ADR-005 Wave 1
        'images',
        'delivery_date',
        'order',
        'implementation_period_days',
        'slug',
        'client',
    ];

    protected $casts = [
        'images' => 'array',
        'delivery_date' => 'date',
    ];

    // ── ADR-005 Wave 1 Media Relations ─────────────────────────────────────

    /** The portfolio's featured image as a Media record (Pattern A). */
    public function defaultImageMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'default_image_media_id');
    }

    // ── ADR-005 Wave 1 Read Helper ───────────────────────────────────────────

    /** Best available path: FK relation first, old path column as fallback. */
    public function resolvedDefaultImagePath(): ?string
    {
        return $this->defaultImageMedia?->file_path ?? $this->getRawOriginal('default_image') ?? null;
    }

    // ── Other Relations ─────────────────────────────────────────────────────

    public function translations()
    {
        return $this->hasMany(PortfolioTranslation::class);
    }

    public function translation($locale = null)
    {
        $locale = $locale ?: app()->getLocale();
        return $this->translations->firstWhere('locale', $locale);
    }
}

