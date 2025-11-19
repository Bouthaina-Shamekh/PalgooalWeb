<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPageTranslation extends Model
{
    protected $fillable = [
        'subscription_page_id',
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

    public function page(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPage::class, 'subscription_page_id');
    }
}
