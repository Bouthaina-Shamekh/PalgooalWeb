<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageTranslation extends Model
{
    use HasFactory;

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

    protected $casts = [
        'meta_keywords' => 'array',
    ];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function setSlugAttribute($value): void
    {
        $this->attributes['slug'] = $value !== null ? trim($value) : null;
    }
}
