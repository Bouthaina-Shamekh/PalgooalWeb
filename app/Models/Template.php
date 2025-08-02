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
    ];

    protected $casts = [
        'price' => 'float',
        'discount_price' => 'float', // <-- تحسين: أضف السعر المخفض أيضًا
        'rating' => 'float',
        'discount_ends_at' => 'datetime', // <-- تحسين مهم: تعامل معه كتاريخ
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

    public function translation()
    {
        return $this->hasOne(TemplateTranslation::class)->where('locale', app()->getLocale());
    }
}
