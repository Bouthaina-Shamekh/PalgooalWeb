<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Template;

class TemplateSeeder extends Seeder
{
    public function run()
    {
        Template::create([
            'slug' => 'template-1',  // مثال على slug
            'price' => 120,
            'image' => 'template-1.jpg',
            'rating' => 4.5,
            'category_template_id' => 1,  // ربط بالقسم الذي أنشأته
        ]);
    }
}

