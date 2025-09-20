<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

// use Illuminate\Database\Eloquent\SoftDeletes; // فك التعليق إذا أضفت softDeletes() في المايجريشن

class PlanCategory extends Model
{
    // use SoftDeletes; // فك التعليق إذا أضفت softDeletes() في المايجريشن

    protected $fillable = [
        'is_active',
        'position', // ضعها هنا إذا أضفت العمود لاحقًا
    ];

    protected $casts = [
        'is_active' => 'bool',
    ];

    /**
     * كل الترجمات المرتبطة بالفئة
     */
    public function translations(): HasMany
    {
        return $this->hasMany(PlanCategoryTranslation::class);
    }

    /**
     * جلب ترجمة بحسب الـ locale المحدد أو locale التطبيق.
     * إذا كانت الترجمات محمّلة (eager loaded) نبحث في الcollection لتجنّب استعلام إضافي.
     */
    public function translation($locale = null)
    {
        $locale = $locale ?: app()->getLocale();

        if ($this->relationLoaded('translations')) {
            return $this->translations->firstWhere('locale', $locale) ?? $this->translations->first();
        }

        // إذا لم تكن محمّلة: نفّذ استعلام واحد مع fallback لوجود أي ترجمة
        return $this->translations()->where('locale', $locale)->first() ?? $this->translations()->first();
    }

    /**
     * Return the slug for a given locale (or current app locale).
     */
    public function translatedSlug($locale = null)
    {
        $t = $this->translation($locale);
        return $t?->slug;
    }

    /**
     * Backwards-compatible accessor so $category->slug returns translated slug
     */
    public function getSlugAttribute()
    {
        return $this->translatedSlug();
    }

    /**
     * المساعد للعناوين المترجمة
     */
    public function translatedTitle($locale = null)
    {
        $t = $this->translation($locale);
        return $t?->title;
    }

    public function getTitleAttribute()
    {
        return $this->translatedTitle();
    }

    /**
     * الخطط المرتبطة بهذه الفئة.
     * تأكد أن جدول plans يحتوي على foreign key باسم plan_category_id
     */
    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class, 'plan_category_id');
    }

    /**
     * Scopes مفيدة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        // إذا أضفت عمود position استخدمه للترتيب، وإلا حافظ على الترتيب بالـ id
        if (Schema::hasColumn($this->getTable(), 'position')) {
            return $query->orderBy('position');
        }

        return $query->orderBy('id');
    }
}