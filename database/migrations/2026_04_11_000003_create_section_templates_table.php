<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the template registry for developer-managed section blueprints.
     *
     * A dedicated table is used instead of the existing production "templates"
     * catalog, because that catalog already stores customer-facing website
     * templates. These records only store metadata and a template key used by
     * code-driven rendering later.
     */
    public function up(): void
    {
        Schema::create('section_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_key')->unique()->comment('Stable registered template key used by code-driven rendering.');
            $table->string('label')->comment('Admin-facing template label.');
            $table->text('description')->nullable()->comment('Optional internal description.');
            $table->string('category')->nullable()->comment('Optional grouping for admin filtering.');
            $table->json('settings')->nullable()->comment('Template metadata only. No Blade, PHP, or render logic.');
            $table->json('schema')->nullable()->comment('Optional normalized schema metadata for future tooling.');
            $table->boolean('is_active')->default(true)->comment('Inactive templates remain stored for compatibility.');
            $table->boolean('is_visible')->default(true)->comment('Controls visibility in admin selectors.');
            $table->unsignedInteger('sort_order')->default(0)->comment('Display order for admin/developer tooling.');
            $table->timestamps();

            $table->index(['category', 'sort_order'], 'section_templates_category_order_idx');
            $table->index(['is_active', 'is_visible', 'sort_order'], 'section_templates_visibility_idx');
        });
    }

    /**
     * Drop the developer section template registry.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_templates');
    }
};
