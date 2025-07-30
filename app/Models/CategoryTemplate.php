<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryTemplate extends Model
{
    use HasFactory;

     /**
     * العلاقة مع الترجمات
     */
    public function translations()
    {
        return $this->hasMany(CategoryTemplateTranslation::class);
    }

    /**
     * الحصول على الترجمة بناءً على اللغة
     */
    public function getTranslation($locale = 'en')
    {
        return $this->translations->where('locale', $locale)->first();
    }
}
