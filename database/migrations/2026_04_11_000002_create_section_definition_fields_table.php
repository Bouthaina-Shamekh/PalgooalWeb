<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create field definitions for developer-managed section blueprints.
     *
     * Field definitions are locale-agnostic. A field declares whether it is
     * shared or translatable, but it never hardcodes locale codes.
     */
    public function up(): void
    {
        Schema::create('section_definition_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_definition_id')
                ->constrained('section_definitions')
                ->cascadeOnDelete();
            $table->string('field_key')->comment('Stable field identifier within a section definition.');
            $table->string('label')->comment('Admin-facing field label.');
            $table->text('help_text')->nullable()->comment('Optional internal/admin help copy.');
            $table->string('field_type')->comment('Normalized field type such as text, textarea, select, media, repeater.');
            $table->string('field_scope')->default('translatable')->comment('shared or translatable');
            $table->json('default_value')->nullable()->comment('Default field value without locale-specific storage.');
            $table->json('options')->nullable()->comment('Option lists or select metadata.');
            $table->json('settings')->nullable()->comment('Editor behavior flags and field metadata.');
            $table->json('schema')->nullable()->comment('Optional future-proof schema details for tooling.');
            $table->boolean('is_required')->default(false)->comment('Whether the field is required by the admin UI contract.');
            $table->boolean('is_active')->default(true)->comment('Inactive fields stay stored for compatibility.');
            $table->unsignedInteger('sort_order')->default(0)->comment('Display order within the definition.');
            $table->timestamps();

            $table->unique(['section_definition_id', 'field_key'], 'section_definition_fields_unique_key');
            $table->index(['section_definition_id', 'sort_order'], 'section_definition_fields_order_idx');
            $table->index(['section_definition_id', 'is_active', 'sort_order'], 'section_definition_fields_visibility_idx');
            $table->index('field_scope');
            $table->index('field_type');
        });
    }

    /**
     * Drop developer section field definitions.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_definition_fields');
    }
};
