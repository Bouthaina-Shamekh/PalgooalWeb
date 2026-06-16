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

    // ── ADR-005 Wave 3 Gallery Helper ────────────────────────────────────────

    /**
     * Return fully-resolved URLs for the gallery images.
     *
     * Handles both storage formats:
     *   • New (Wave 3): JSON array of integer Media IDs  → [7, 12, 15]
     *   • Old (pre-Wave 3): JSON array of path strings   → ["media/...", ...]
     *
     * Always returns an array of fully-qualified URLs (asset('storage/...')).
     * Returns an empty array when no gallery images are set.
     */
    public function resolvedGalleryImages(): array
    {
        $raw = $this->images; // cast as 'array' by Eloquent
        if (empty($raw) || ! is_array($raw)) {
            return [];
        }

        $first = reset($raw);

        // New format: array of integer Media IDs
        if (is_int($first) || (is_string($first) && ctype_digit((string) $first))) {
            $ids          = array_map('intval', $raw);
            $mediaRecords = Media::whereIn('id', $ids)->get()->keyBy('id');
            $urls         = [];
            foreach ($ids as $id) {
                $media = $mediaRecords->get($id);
                if ($media && ! empty($media->file_path)) {
                    $urls[] = asset('storage/' . ltrim((string) $media->file_path, '/'));
                }
            }
            return $urls;
        }

        // Old format: array of path strings
        $urls = [];
        foreach ($raw as $path) {
            if (! empty($path) && is_string($path)) {
                $urls[] = asset('storage/' . ltrim($path, '/'));
            }
        }
        return $urls;
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

