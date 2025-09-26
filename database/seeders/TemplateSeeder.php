<?php

namespace Database\Seeders;

use App\Models\Template;
use App\Models\TemplateTranslation;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $template = Template::create([
            'price' => 12000, // price in cents
            'image' => 'assets/tamplate/images/template-1.webp',
            'rating' => 4.5,
            'category_template_id' => 1,
            'is_active' => true,
        ]);

        TemplateTranslation::create([
            'template_id' => $template->id,
            'locale' => 'ar',
            'name' => 'قالب متجر إلكتروني',
            'slug' => 'قالب-متجر-إلكتروني',
            'description' => 'قالب عربي للمتاجر الإلكترونية مع جاهزية كاملة للهواتف. رمز السعر بالريال.
            ',
            'details' => [
                'features' => [
                    ['title' => 'متوافق مع الجوال', 'description' => 'يعمل على جميع الأجهزة.'],
                ],
                'gallery' => [],
                'tags' => ['متجر', 'تجارة إلكترونية'],
            ],
        ]);

        TemplateTranslation::create([
            'template_id' => $template->id,
            'locale' => 'en',
            'name' => 'E-commerce Template',
            'slug' => 'ecommerce-template',
            'description' => 'Responsive e-commerce template prepared for RTL and multi-language.',
            'details' => [
                'features' => [
                    ['title' => 'Responsive design', 'description' => 'Looks great on mobile and desktop.'],
                ],
                'gallery' => [],
                'tags' => ['store', 'ecommerce'],
            ],
        ]);
    }
}
