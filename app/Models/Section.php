<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = ['page_id', 'key', 'order'];

    // جميع الترجمات المرتبطة بالسكشن
    public function translations()
    {
        return $this->hasMany(SectionTranslation::class);
    }

    // جلب ترجمة بلغة محددة أو اللغة الحالية، مع fallback لأول ترجمة
    public function translation($locale = null)
    {
        $locale = $locale ?? app()->getLocale();

        return $this->translations->where('locale', $locale)->first()
            ?? $this->translations->first(); // fallback
    }

    // ارتباط السكشن بالصفحة
    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}
