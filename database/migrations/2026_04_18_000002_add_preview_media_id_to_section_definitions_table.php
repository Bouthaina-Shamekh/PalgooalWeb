<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a first-class preview media reference for admin/library display.
     */
    public function up(): void
    {
        Schema::table('section_definitions', function (Blueprint $table) {
            if (! Schema::hasColumn('section_definitions', 'preview_media_id')) {
                $table->foreignId('preview_media_id')
                    ->nullable()
                    ->after('custom_editor_key')
                    ->constrained('media')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Remove the preview media reference.
     */
    public function down(): void
    {
        Schema::table('section_definitions', function (Blueprint $table) {
            if (Schema::hasColumn('section_definitions', 'preview_media_id')) {
                $table->dropConstrainedForeignId('preview_media_id');
            }
        });
    }
};
