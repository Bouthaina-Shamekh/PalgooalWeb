<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class GeneralSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('general_settings')->insert([
            'site_title' => 'شركة بال قول',
            'site_discretion' => 'بال قول صديقك بالعالم الرقمي',
            'logo' => 'storage/images/palgoal.png',
            'dark_logo' => 'storage/images/palgoal.png',
            'sticky_logo' => 'storage/images/palgoal.png',
            'dark_sticky_logo' => 'storage/images/palgoal.png',
            'admin_logo' => 'storage/images/palgoal.png',
            'admin_dark_logo' => 'storage/images/palgoal.png',
            'favicon' => 'storage/images/palgoal.png',
            'default_language' => 2, 
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
