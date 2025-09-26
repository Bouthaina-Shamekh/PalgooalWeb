<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\PageTranslation;
use Illuminate\Database\Seeder;

class PageHomeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $page = Page::create([
            'is_active' => true,
            'is_home' => true,
        ]);

        PageTranslation::create([
            'page_id' => $page->id,
            'locale' => 'ar',
            'slug' => null,
            'title' => 'الصفحة الرئيسية',
            'content' => '',
            'meta_title' => 'الصفحة الرئيسية',
            'meta_description' => 'بال قول تقدم حلول الاستضافة والتسويق الرقمي باللغة العربية.',
            'meta_keywords' => ['استضافة', 'تصميم مواقع', 'برمجة'],
            'og_image' => null,
        ]);

        PageTranslation::create([
            'page_id' => $page->id,
            'locale' => 'en',
            'slug' => null,
            'title' => 'Home Page',
            'content' => '',
            'meta_title' => 'Home',
            'meta_description' => 'Palgoals delivers hosting and digital services for businesses.',
            'meta_keywords' => ['hosting', 'web design', 'digital services'],
            'og_image' => null,
        ]);
    }
}
