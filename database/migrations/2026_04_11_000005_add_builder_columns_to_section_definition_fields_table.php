<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add first-class builder metadata for admin field definition management.
     *
     * These columns support grouping and normalized validation metadata without
     * changing how runtime rendering remains code-driven.
     */
    public function up(): void
    {
        Schema::table('section_definition_fields', function (Blueprint $table) {
            $table->string('group_name')
                ->nullable()
                ->after('label')
                ->comment('Optional dashboard grouping label for related fields.');
            $table->json('validation_rules')
                ->nullable()
                ->after('is_required')
                ->comment('Normalized validation rules for future editor/content validation.');

            $table->index(['section_definition_id', 'group_name', 'sort_order'], 'section_definition_fields_group_idx');
        });
    }

    /**
     * Remove admin field builder metadata columns.
     */
    public function down(): void
    {
        Schema::table('section_definition_fields', function (Blueprint $table) {
            $table->dropIndex('section_definition_fields_group_idx');
            $table->dropColumn(['group_name', 'validation_rules']);
        });
    }
};
