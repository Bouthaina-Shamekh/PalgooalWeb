<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the allowed template mappings for each section definition.
     *
     * This pivot links a section blueprint to one or more registered template
     * keys without storing any raw render implementation in the database.
     */
    public function up(): void
    {
        Schema::create('section_definition_template', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_definition_id')
                ->constrained('section_definitions')
                ->cascadeOnDelete();
            $table->foreignId('section_template_id')
                ->constrained('section_templates')
                ->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0)->comment('Optional ordering for available templates per definition.');
            $table->timestamps();

            $table->unique(
                ['section_definition_id', 'section_template_id'],
                'section_definition_template_unique_pair',
            );
            $table->index(['section_definition_id', 'sort_order'], 'section_definition_template_order_idx');
        });
    }

    /**
     * Drop the section definition/template pivot.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_definition_template');
    }
};
