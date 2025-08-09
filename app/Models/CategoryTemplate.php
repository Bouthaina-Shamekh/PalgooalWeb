<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryTemplate extends Model
{
    use HasFactory;

    /**
     * علاقة جميع الترجمات
     */
    public function translations()
    {
        return $this->hasMany(CategoryTemplateTranslation::class);
    }

    /**
     * علاقة الترجمة الحالية حسب اللغة
     */
    public function translation()
    {
        return $this->hasOne(CategoryTemplateTranslation::class)->where('locale', app()->getLocale());
    }

    /**
     * دالة عامة للحصول على الترجمة بلغة محددة (أو اللغة الحالية)
     */
    public function getTranslation($locale = null)
    {
        $locale = $locale ?? app()->getLocale();

        // تأكد أن العلاقة محمّلة مسبقًا لتجنب استعلامات إضافية
        if ($this->relationLoaded('translations')) {
            return $this->translations->where('locale', $locale)->first();
        }

        // fallback: تحميل مباشر من قاعدة البيانات
        return $this->translations()->where('locale', $locale)->first();
    }

    /**
     * Accessor: الاسم المترجم حسب اللغة الحالية
     */
    public function getTranslatedNameAttribute()
    {
        return $this->getTranslation()?->name ?? '';
    }

    /**
     * Accessor: الرابط (slug) المترجم حسب اللغة الحالية
     */
    public function getTranslatedSlugAttribute()
{
    $t = $this->translations->firstWhere('locale', app()->getLocale())
        ?? $this->translations->firstWhere('locale', 'ar');
    return $t?->slug;
}
}