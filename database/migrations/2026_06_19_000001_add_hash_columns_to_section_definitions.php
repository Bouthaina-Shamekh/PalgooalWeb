<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 — Out Of Sync Detection
 *
 * Adds content-fingerprint columns to section_definitions:
 *
 *  blade_hash — sha256(blade_source) captured at last successful Write
 *  disk_hash  — sha256(disk file content) captured at last successful Write
 *
 * Since Write copies blade_source → disk verbatim, both hashes are identical
 * immediately after a Write. They diverge only when:
 *   1. blade_source is edited in Monaco (blade_hash becomes stale)
 *   2. Disk file is modified externally (disk_hash becomes stale)
 *
 * sync_status is NOT stored — it is computed dynamically by FileStatusResolver
 * using sha256(blade_source) comparison + filemtime() — no file_get_contents().
 *
 * @see docs/OUT_OF_SYNC_DETECTION_ARCHITECTURE.md
 * @see app/Support/Sections/FileStatusResolver.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('section_definitions', function (Blueprint $table) {
            // sha256 of blade_source at the moment of last successful Write.
            // Used to detect "Out of Sync" (Monaco edited but not published).
            $table->string('blade_hash', 64)->nullable()->after('blade_written_at');

            // sha256 of the disk file at the moment of last successful Write.
            // Equal to blade_hash immediately after Write; diverges on external edits.
            // Reserved for future Phase 5 (full content comparison).
            $table->string('disk_hash', 64)->nullable()->after('blade_hash');
        });
    }

    public function down(): void
    {
        Schema::table('section_definitions', function (Blueprint $table) {
            $table->dropColumn(['blade_hash', 'disk_hash']);
        });
    }
};
