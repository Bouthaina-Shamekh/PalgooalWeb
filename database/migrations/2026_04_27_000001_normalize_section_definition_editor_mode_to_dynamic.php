<?php

use App\Models\Sections\SectionDefinition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Normalize any stray legacy custom_preset definitions to the current
     * dynamic-only editor contract.
     *
     * This is intentionally a no-op for databases that already contain only
     * dynamic definitions.
     */
    public function up(): void
    {
        if (! Schema::hasTable('section_definitions')) {
            return;
        }

        DB::table('section_definitions')
            ->where('editor_mode', 'custom_preset')
            ->update([
                'editor_mode' => SectionDefinition::EDITOR_MODE_DYNAMIC,
                'custom_editor_key' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Intentionally irreversible. The removed custom_preset mode no longer
        // has a supported runtime/editor implementation.
    }
};
