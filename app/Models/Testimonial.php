<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $table = 'feedbacks';

    // ✅ استخدم image_id بدلاً من image
    protected $fillable = [
        'image_id',
        'star',
        'order',
        'is_approved',
    ];

    protected $casts = [
        'image_id'    => 'integer',
        'star'        => 'integer',
        'order'       => 'integer',
        'is_approved' => 'boolean',
    ];

    // الترجمات
    public function translations()
    {
        return $this->hasMany(TestimonialTranslation::class, 'feedback_id', 'id');
    }

    public function translation($locale = null)
    {
        $locale = $locale ?? app()->getLocale();

        // لو حاب تتجنّب N+1 مستقبلاً ممكن تستخدم where مباشرة:
        // return $this->translations()->where('locale', $locale)->first();

        return $this->translations->where('locale', $locale)->first();
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    // ✅ علاقة الصورة (Media)
    public function image()
    {
        return $this->belongsTo(\App\Models\Media::class, 'image_id');
    }

    // ✅ accessor جاهز للاستخدام في الـ Blade: $testimonial->image_url
    public function getImageUrlAttribute(): ?string
    {
        return $this->image?->url;
    }
}
