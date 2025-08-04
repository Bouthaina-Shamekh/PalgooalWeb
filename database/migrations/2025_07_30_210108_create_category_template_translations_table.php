<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('category_template_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_template_id')->constrained('category_templates')->onDelete('cascade');  // ربط الفئة بالقوالب
            $table->string('locale');  // اللغة (مثال: 'ar' أو 'en')
            $table->string('slug')->unique();
            $table->string('name');  // اسم الفئة
            $table->text('description')->nullable();  // وصف الفئة
            $table->timestamps();
            $table->unique(['category_template_id', 'locale'], 'category_template_locale_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_template_translations');
    }
};
