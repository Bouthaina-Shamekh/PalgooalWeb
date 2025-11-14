<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = ['image', 'star', 'order', 'is_approved'];
    protected $table = 'feedbacks';

    public function translations()
    {
        return $this->hasMany(TestimonialTranslation::class, 'feedback_id', 'id');
    }

    public function translation($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return $this->translations->where('locale', $locale)->first();
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }
}
