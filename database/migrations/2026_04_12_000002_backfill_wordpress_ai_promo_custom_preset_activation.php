<?php

use App\Models\Sections\SectionDefinition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Normalize existing wordpress_ai_promo definitions so they activate
     * through the formal custom preset path.
     *
     * Idempotent — re-running keeps the same values.
     */
    public function up(): void
    {
        if (! Schema::hasTable('section_definitions')) {
            return;
        }

        DB::table('section_definitions')
            ->where('section_key', 'wordpress_ai_promo')
            ->update([
                'editor_mode'       => SectionDefinition::EDITOR_MODE_DYNAMIC,
                'custom_editor_key' => null,
                'updated_at'        => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('section_definitions')) {
            return;
        }

        DB::table('section_definitions')
            ->where('section_key', 'wordpress_ai_promo')
            ->where('editor_mode', SectionDefinition::EDITOR_MODE_DYNAMIC)
            ->update([
                'editor_mode'       => SectionDefinition::EDITOR_MODE_DYNAMIC,
                'custom_editor_key' => null,
                'updated_at'        => now(),
            ]);
    }
};
