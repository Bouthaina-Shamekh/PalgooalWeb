<?php

use App\Models\Sections\SectionDefinition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Normalize existing hosting_hero definitions so they activate through the
     * formal custom preset path instead of the temporary legacy bridge.
     *
     * This migration is intentionally idempotent. Re-running it keeps the same
     * normalized values and preserves all other definition metadata.
     */
    public function up(): void
    {
        if (! Schema::hasTable('section_definitions')) {
            return;
        }

        DB::table('section_definitions')
            ->where('section_key', 'hosting_hero')
            ->update([
                'editor_mode' => SectionDefinition::EDITOR_MODE_DYNAMIC,
                'custom_editor_key' => null,
                'updated_at' => now(),
            ]);
    }

    /**
     * Roll back only the normalization values introduced by this migration.
     *
     * We intentionally avoid touching rows that no longer match the exact
     * normalized pair, so custom follow-up edits are preserved.
     */
    public function down(): void
    {
        if (! Schema::hasTable('section_definitions')) {
            return;
        }

        DB::table('section_definitions')
            ->where('section_key', 'hosting_hero')
            ->where('editor_mode', SectionDefinition::EDITOR_MODE_DYNAMIC)
            ->update([
                'editor_mode' => SectionDefinition::EDITOR_MODE_DYNAMIC,
                'custom_editor_key' => null,
                'updated_at' => now(),
            ]);
    }
};
