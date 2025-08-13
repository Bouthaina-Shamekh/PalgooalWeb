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
        Schema::create('template_reviews', function (Blueprint $table) {
            $table->id();

            // القالب
            $table->foreignId('template_id')
                  ->constrained('templates')
                  ->cascadeOnDelete();

            // مستخدم نظام (اختياري)
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // عميلك (اختياري)
            $table->foreignId('client_id')
                  ->nullable()
                  ->constrained('clients')
                  ->nullOnDelete();

            // بيانات ضيف إذا لم يكن user/client
            $table->string('author_name')->nullable();
            $table->string('author_email')->nullable();

            // التقييم والتعليق
            $table->unsignedTinyInteger('rating'); // 1..5
            $table->text('comment');

            // حالة الموافقة
            $table->boolean('approved')->default(false);

            $table->timestamps();

            // فهارس مفيدة للاستعلامات
            $table->index(['template_id', 'approved', 'created_at']);
            $table->index(['template_id', 'rating']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_reviews');
    }
};
