<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_home')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['subscription_id', 'slug']);
        });

        Schema::create('subscription_page_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscription_page_id');
            $table->string('locale', 8);
            $table->string('slug');
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            $table->string('og_image')->nullable();
            $table->timestamps();

            $table->unique(['subscription_page_id', 'locale'], 'sub_page_trans_locale_unique');
            $table->index(['locale', 'slug']);
            $table->foreign('subscription_page_id', 'sub_page_trans_page_fk')
                ->references('id')->on('subscription_pages')
                ->cascadeOnDelete();
        });

        Schema::create('subscription_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscription_page_id');
            $table->string('key')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('subscription_page_id', 'sub_section_page_fk')
                ->references('id')->on('subscription_pages')
                ->cascadeOnDelete();
        });

        Schema::create('subscription_section_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscription_section_id');
            $table->string('locale', 8);
            $table->string('title')->nullable();
            $table->json('content')->nullable();
            $table->timestamps();

            $table->unique(['subscription_section_id', 'locale'], 'sub_section_trans_locale_unique');
            $table->foreign('subscription_section_id', 'sub_section_trans_section_fk')
                ->references('id')->on('subscription_sections')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_section_translations');
        Schema::dropIfExists('subscription_sections');
        Schema::dropIfExists('subscription_page_translations');
        Schema::dropIfExists('subscription_pages');
    }
};
