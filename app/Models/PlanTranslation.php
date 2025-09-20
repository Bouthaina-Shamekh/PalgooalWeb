<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanTranslation extends Model
{
    protected $fillable = [
        'plan_id',
        'locale',
        'title',
        'description',
        'features',
    ];

    protected $casts = [
        'features' => 'array',
    ];

    /**
     * الخطة المرتبطة بالترجمة
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Optional: إرجاع عنوان مع fallback
     */
    public function getTitleAttribute($value): string
    {
        return $value ?? $this->plan->name;
    }

    /**
     * Optional: إرجاع وصف مع fallback
     */
    public function getDescriptionAttribute($value): string
    {
        return $value ?? '';
    }
}
