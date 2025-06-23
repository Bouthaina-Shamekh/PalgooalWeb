<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('languages')->insert([
            [
                'name' => 'English',
                'native' => 'الإنجليزية',
                'code' => 'en',
                'flag' => 'flags/en.png',
                'is_rtl' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Arabic',
                'native' => 'العربية',
                'code' => 'ar',
                'flag' => 'flags/ar.png',
                'is_rtl' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
