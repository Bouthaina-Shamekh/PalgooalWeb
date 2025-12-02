<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageTranslation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * Each row represents a localized version of a page:
     * - slug: SEO-friendly URL per locale
     * - title: localized page title
     * - content: optional raw content (for simple pages without builder)
     * - meta_*: SEO meta tags per locale
     */
    protected $fillable = [
        'page_id',
        'locale',
        'slug',
        'title',
        'content',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_image',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'meta_keywords' => 'array',
    ];

    /**
     * Relationship: parent page.
     */
    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Mutator: clean up the slug value by trimming spaces.
     *
     * This prevents accidentally storing slugs with leading/trailing spaces.
     */
    public function setSlugAttribute($value): void
    {
        $this->attributes['slug'] = $value !== null ? trim($value) : null;
    }
}
