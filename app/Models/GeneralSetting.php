<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    protected $table = 'general_settings';
    protected $fillable = [
        'site_title',
        'site_discretion',
        'logo',
        'dark_logo',
        'sticky_logo',
        'dark_sticky_logo',
        'admin_logo',
        'admin_dark_logo',
        'favicon',
        'default_language',
        'active_header_variant',
        'active_footer_variant',
        'header_show_promo_bar',
        'header_is_sticky',
        'header_variant_settings',
        'footer_variant_settings',
        'footer_show_contact_banner',
        'footer_show_payment_methods',
        'contact_info',    // جديد
        'social_links',    // جديد
    ];


    protected $casts = [
        'header_show_promo_bar' => 'boolean',
        'header_is_sticky' => 'boolean',
        'header_variant_settings' => 'array',
        'footer_variant_settings' => 'array',
        'footer_show_contact_banner' => 'boolean',
        'footer_show_payment_methods' => 'boolean',
        'contact_info' => 'array',
        'social_links' => 'array',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'default_language', 'id');
    }
}
