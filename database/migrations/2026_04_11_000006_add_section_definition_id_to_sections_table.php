<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add an explicit definition link to canonical section instances.
     *
     * Existing sections remain valid because the column is nullable. New
     * definition-driven sections can now bind to a concrete blueprint record
     * instead of relying on a fragile type-to-key string convention at runtime.
     */
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            if (! Schema::hasColumn('sections', 'section_definition_id')) {
                $table->foreignId('section_definition_id')
                    ->nullable()
                    ->after('page_id')
                    ->constrained('section_definitions')
                    ->nullOnDelete();

                $table->index(
                    ['section_definition_id', 'type'],
                    'sections_definition_type_idx',
                );
            }
        });
    }

    /**
     * Remove the explicit definition link from section instances.
     */
    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            if (Schema::hasColumn('sections', 'section_definition_id')) {
                $table->dropIndex('sections_definition_type_idx');
                $table->dropConstrainedForeignId('section_definition_id');
            }
        });
    }
};
