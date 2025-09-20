<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plan_categories', function (Blueprint $table) {
            $table->id();
            // slug moved into translations (per-locale)
            // top-level slug removed; translations will hold locale-specific slugs
            $table->boolean('is_active')->default(true);
            $table->integer('position')->default(0); // ترتيب العرض
            $table->timestamps();
            $table->softDeletes(); // يمكن استخدامه مستقبلًا لاسترجاع التصنيفات
        });

        Schema::create('plan_category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_category_id')->constrained('plan_categories')->cascadeOnDelete();
            $table->string('locale', 8); // ar, en, ...
            $table->string('title');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();

            // يضمن ترجمة واحدة لكل فئة/لغة
            $table->unique(['plan_category_id', 'locale']);

            // يضمن أن نفس الـ slug لا يتكرر داخل نفس الـ locale
            // ملاحظة: يسمح ذلك بعدة NULLs في بعض قواعد البيانات — إن أردت منع NULL اجعل الحقل non-nullable
            $table->unique(['locale', 'slug']);

            // index مفيد لعمليات البحث بالـ slug + locale
            $table->index(['locale', 'slug']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_category_translations');
        Schema::dropIfExists('plan_categories');
    }
};
