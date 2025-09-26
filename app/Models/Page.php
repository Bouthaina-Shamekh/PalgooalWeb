<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_active',
        'is_home',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_home' => 'boolean',
    ];

    public function translations()
    {
        return $this->hasMany(PageTranslation::class);
    }

    public function translation($locale = null)
    {
        $locale = $locale ?? app()->getLocale();

        if ($this->relationLoaded('translations')) {
            return $this->translations->firstWhere('locale', $locale)
                ?? $this->translations->first();
        }

        return $this->translations()->where('locale', $locale)->first()
            ?? $this->translations()->first();
    }

    public function sections()
    {
        return $this->hasMany(Section::class)->orderBy('order');
    }

    public function scopeWhereSlug(Builder $query, string $slug, ?string $locale = null): Builder
    {
        $locale = $locale ?? app()->getLocale();

        return $query->whereHas('translations', function (Builder $query) use ($slug, $locale) {
            $query->where('locale', $locale)->where('slug', $slug);
        });
    }

    public function getSlugAttribute(): ?string
    {
        return $this->translation()?->slug;
    }
}
