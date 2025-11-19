<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionSectionTranslation extends Model
{
    protected $fillable = [
        'subscription_section_id',
        'locale',
        'title',
        'content',
    ];

    protected $casts = [
        'content' => 'array',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(SubscriptionSection::class, 'subscription_section_id');
    }
}
