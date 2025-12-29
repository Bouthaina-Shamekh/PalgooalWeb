<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_builder_structures', function (Blueprint $table) {
            // HTML النهائي المنشور (snapshot)
            $table->longText('published_html')->nullable()->after('css');

            // مسار ملف الـ CSS الخارجي (إن وجد)
            $table->string('published_css_path')->nullable()->after('published_html');

            // متى تم النشر (زر Publish)
            $table->timestamp('published_at')->nullable()->after('published_css_path');
        });
    }

    public function down(): void
    {
        Schema::table('page_builder_structures', function (Blueprint $table) {
            $table->dropColumn(['published_html', 'published_css_path', 'published_at']);
        });
    }
};
