<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create developer-managed section definitions.
     *
     * These records describe reusable section blueprints for admin/developer
     * tooling only. They do not store page-level content instances and they do
     * not store renderable Blade or PHP logic.
     */
    public function up(): void
    {
        Schema::create('section_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('section_key')->unique()->comment('Stable developer-facing section identifier.');
            $table->string('label')->comment('Admin-facing definition label.');
            $table->text('description')->nullable()->comment('Optional internal description for maintainers.');
            $table->string('category')->nullable()->comment('Optional grouping for admin lists and filtering.');
            $table->string('editor_mode')->default('dynamic')->comment('Dynamic definition editor mode.');
            $table->string('custom_editor_key')->nullable()->comment('Deprecated legacy metadata; kept nullable for compatibility.');
            $table->json('settings')->nullable()->comment('Editor/runtime metadata only. No render logic.');
            $table->json('schema')->nullable()->comment('Optional normalized schema metadata for future tooling.');
            $table->boolean('is_active')->default(true)->comment('Inactive definitions stay stored but should not be offered.');
            $table->boolean('is_visible')->default(true)->comment('Controls visibility in admin selection UIs.');
            $table->unsignedInteger('sort_order')->default(0)->comment('Display order for developer/admin tooling.');
            $table->timestamps();

            $table->index('custom_editor_key');
            $table->index(['category', 'sort_order'], 'section_definitions_category_order_idx');
            $table->index(['is_active', 'is_visible', 'sort_order'], 'section_definitions_visibility_idx');
        });
    }

    /**
     * Drop the developer section definition registry.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_definitions');
    }
};
