<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'price',
        'image',
        'rating',
        'category_template_id',
        'discount_price',
        'discount_ends_at',
        'plan_id',
    ];
    public function plan()
    {
        return $this->belongsTo(\App\Models\Plan::class);
    }

    protected $casts = [
        'price' => 'float',
        'discount_price' => 'float',
        'rating' => 'float',
        'discount_ends_at' => 'datetime',
    ];

    public function categoryTemplate()
    {
        return $this->belongsTo(CategoryTemplate::class, 'category_template_id')->withDefault();
    }

    public function translations()
    {
        return $this->hasMany(TemplateTranslation::class);
    }

    public function translation($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return $this->translations->where('locale', $locale)->first();
    }

    public function reviews()
    {
        return $this->hasMany(\App\Models\TemplateReview::class);
    }

    // متوسط التقييم المعتمد فقط
    public function avgRating(): float
    {
        return (float) ($this->reviews()->approved()->avg('rating') ?? 0);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
