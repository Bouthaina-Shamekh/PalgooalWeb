<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 + 2 — Section Visibility Scope
 *
 * Adds `visibility_scope` VARCHAR(32) DEFAULT 'both' to section_definitions.
 * Existing rows receive DEFAULT 'both' automatically (zero data migration needed).
 *
 * Phase 2 is applied at the end of up(): marks `pricing_plans_dynamic` as
 * 'admin_only' so it no longer appears in the client-facing section picker.
 *
 * Allowed values (enforced in SectionDefinition constants):
 *   'both'        — visible in both Admin Builder and Client Builder
 *   'admin_only'  — Admin Builder only
 *   'client_only' — Client Builder only
 *   'hidden'      — not visible in any builder (draft / internal)
 *
 * @see docs/SECTION_VISIBILITY_SCOPE_ARCHITECTURE.md
 * @see docs/SECTION_VISIBILITY_SCOPE_REPORT.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('section_definitions', function (Blueprint $table): void {
            $table->string('visibility_scope', 32)
                ->default('both')
                ->after('is_visible')
                ->comment('Admin/client builder visibility: both|admin_only|client_only|hidden');

            $table->index('visibility_scope', 'section_definitions_visibility_scope_idx');
        });

        // ── Phase 2 — Mark system-only sections ──────────────────────────────
        // pricing_plans_dynamic queries App\Models\Plan directly and must never
        // appear in a client-facing builder. Set it to 'admin_only' now, while
        // all other existing rows stay at the DEFAULT value 'both'.
        DB::table('section_definitions')
            ->where('section_key', 'pricing_plans_dynamic')
            ->update(['visibility_scope' => 'admin_only']);
    }

    public function down(): void
    {
        Schema::table('section_definitions', function (Blueprint $table): void {
            $table->dropIndex('section_definitions_visibility_scope_idx');
            $table->dropColumn('visibility_scope');
        });
    }
};
