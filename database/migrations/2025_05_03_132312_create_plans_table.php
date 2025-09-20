<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique(); // basic / pro...
            $table->unsignedInteger('monthly_price_cents')->nullable();
            $table->unsignedInteger('annual_price_cents')->nullable();
            $table->boolean('is_active')->default(true);

            // الأعمدة بدون constrained() مباشرة
            $table->unsignedBigInteger('plan_category_id')->nullable()->index();
            $table->unsignedBigInteger('server_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->index('is_active');
            $table->index(['monthly_price_cents', 'annual_price_cents']);
        });

        // إضافة قيود FK بأسماء صريحة لتجنب التعارض
        Schema::table('plans', function (Blueprint $table) {
            $table->foreign('plan_category_id', 'fk_plans_plan_category')
                ->references('id')->on('plan_categories')
                ->nullOnDelete();

            $table->foreign('server_id', 'fk_plans_server')
                ->references('id')->on('servers')
                ->nullOnDelete();

            $table->foreign('created_by', 'fk_plans_created_by')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->foreign('updated_by', 'fk_plans_updated_by')
                ->references('id')->on('users')
                ->nullOnDelete();
        });

        // جدول الترجمات للخطط
        Schema::create('plan_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');
            $table->string('locale', 8);
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->unique(['plan_id', 'locale']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            try {
                $table->dropForeign('fk_plans_plan_category');
            } catch (\Throwable) {
            }
            try {
                $table->dropForeign('fk_plans_server');
            } catch (\Throwable) {
            }
            try {
                $table->dropForeign('fk_plans_created_by');
            } catch (\Throwable) {
            }
            try {
                $table->dropForeign('fk_plans_updated_by');
            } catch (\Throwable) {
            }
        });

        Schema::dropIfExists('plan_translations');
        Schema::dropIfExists('plans');
    }
};
