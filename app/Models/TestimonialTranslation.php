<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestimonialTranslation extends Model
{
    // ADR-006: table renamed feedback_translations → testimonial_translations; $table override removed.
    // ADR-006: feedback_id → testimonial_id, feedback → text.
    protected $fillable = ['testimonial_id', 'locale', 'text', 'name', 'major'];
}
