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
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();  // الـ slug فريد لكل قالب
            $table->decimal('price', 10, 2);  // السعر
            $table->string('image');  // صورة القالب
            $table->float('rating')->default(0);  // التقييم
            $table->foreignId('category_template_id')->constrained('category_templates')->onDelete('cascade');  // ربط القوالب بفئات القوالب
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
