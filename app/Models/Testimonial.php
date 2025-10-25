<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = ['image', 'star', 'order'];
    protected $table = 'feedbacks';

    public function translations()
    {
        return $this->hasMany(TestimonialTranslation::class);
    }

    public function translation($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return $this->translations->where('locale', $locale)->first();
    }
}
