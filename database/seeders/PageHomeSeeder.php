<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\PageTranslation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PageHomeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // أنشئ الصفحة الرئيسية
        $page = Page::create([
            'slug' => 'home',
            'is_active' => true,
            'is_home' => true,
        ]);

        // اللغة العربية
        PageTranslation::create([
            'page_id' => $page->id,
            'locale' => 'ar',
            'title' => 'الصفحة الرئيسية',
            'content' => '',
        ]);

        // اللغة الإنجليزية (اختياري)
        PageTranslation::create([
            'page_id' => $page->id,
            'locale' => 'en',
            'title' => 'Home Page',
            'content' => '',
        ]);
    }
}
