<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PlanCategoryTranslation extends Model
{
    protected $fillable = [
        'plan_category_id',
        'locale',
        'slug',
        'title',
        'description',
    ];

    /**
     * إذا أردت تحويل الحقول تلقائيًا أو cast معين أضف هنا.
     * مثال: protected $casts = ['meta' => 'array'];
     */

    /**
     * توليد slug تلقائيًا من العنوان عند الحفظ إن لم يكن موجوداً.
     */
    protected static function booted()
    {
        static::saving(function (self $model) {
            // إذا كان slug فاضيًا واعطينا title — انشئ slug من الـ title
            if (empty($model->slug) && !empty($model->title)) {
                $model->slug = Str::slug($model->title);
            }
            // دائمًا قم بتطهير الـ slug إن كانت موجودة (مثلاً لو أرسل المستخدم قيمة يدوية)
            if (!empty($model->slug)) {
                $model->slug = Str::slug($model->slug);
            }
        });
    }

    /**
     * علاقة العودة للفئة الرئيسية
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PlanCategory::class, 'plan_category_id');
    }

    /**
     * Mutator بديل (محافظ) لو أردت استخدامه بدلاً من booted
     */
    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = $value ? Str::slug($value) : null;
    }
}