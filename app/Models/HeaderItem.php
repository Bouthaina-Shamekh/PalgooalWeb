<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HeaderItem extends Model
{
    protected $fillable = [
        'header_id',
        'type',
        'url',
        'children',
        'order',
    ];

    protected $casts = [
        'children' => 'array',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(Header::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(HeaderItemTranslation::class);
    }

    // لجلب الترجمة حسب اللغة الحالية
    public function getLabelAttribute(): string
    {
        $locale = app()->getLocale();

        return $this->translations->where('locale', $locale)->first()?->label
            ?? $this->translations->first()?->label
            ?? '';
    }
}
