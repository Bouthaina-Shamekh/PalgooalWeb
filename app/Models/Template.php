<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'price',
        'image',
        'rating',
        'category_template_id',
    ];

    /**
     * العلاقة مع فئة القوالب
     */
    public function categoryTemplate()
    {
        return $this->belongsTo(CategoryTemplate::class, 'category_template_id');
    }

    /**
     * العلاقة مع الترجمات
     */
    public function translations()
    {
        return $this->hasMany(TemplateTranslation::class);
    }

    /**
     * للحصول على الترجمة بناءً على اللغة
     */
    public function getTranslation($locale = 'en')
    {
        return $this->translations->where('locale', $locale)->first();
    }
}
