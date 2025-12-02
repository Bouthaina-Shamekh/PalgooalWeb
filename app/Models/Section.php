<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'type',
        'variant',
        'order',
        'is_active',
    ];

    /**
     * Attribute type casting.
     */
    protected $casts = [
        'is_active' => 'boolean',
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
    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Optional relationship: associated image for this section.
     *
     * NOTE: This assumes there is an `image_id` column on the `sections` table
     * and a `Media` model that stores uploaded media files.
     */
    public function image()
    {
        return $this->belongsTo(Media::class, 'image_id');
    }
}
