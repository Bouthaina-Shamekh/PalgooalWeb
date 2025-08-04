<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CategoryTemplate;
use App\Models\CategoryTemplateTranslation;

class CategoryTemplateSeeder extends Seeder
{
    public function run()
    {
        // إنشاء فئة القوالب
        $category = CategoryTemplate::create([
            'slug' => 'flowers',  // مثال على slug
        ]);

        // إضافة الترجمات
        CategoryTemplateTranslation::create([
            'category_template_id' => $category->id,
            'locale' => 'ar',
            'name' => 'زهور',
            'description' => 'الفئة الخاصة بالزهور',
        ]);

        CategoryTemplateTranslation::create([
            'category_template_id' => $category->id,
            'locale' => 'en',
            'name' => 'Flowers',
            'description' => 'The category for flowers',
        ]);
    }
}
