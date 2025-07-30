<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(10)->create();

        User::create([
            'name' => 'Administrator',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('admin'),
            'super_admin' => 1,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);


        $this->call(GeneralSettingsSeeder::class);
        $this->call(LanguageSeeder::class);
        $this->call(ServiceSeeder::class);
        $this->call(PageHomeSeeder::class);
        $this->call(ClientSeeder::class);
        $this->call(CategoryTemplateSeeder::class);
        $this->call(TemplateSeeder::class);
    }
}
