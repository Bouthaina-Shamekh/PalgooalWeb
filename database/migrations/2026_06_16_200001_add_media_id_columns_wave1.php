<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-005 Wave 1 — Add FK columns for media references.
 *
 * Adds nullable media_id FK columns alongside existing path columns.
 * The old path columns are intentionally NOT dropped here — they will be
 * removed after a stability window (Wave 1 Phase 5, separate migration).
 *
 * Columns added:
 *   clients              → avatar_media_id
 *   portfolios           → default_image_media_id
 *   general_settings     → logo_media_id, dark_logo_media_id,
 *                          sticky_logo_media_id, dark_sticky_logo_media_id,
 *                          admin_logo_media_id, admin_dark_logo_media_id,
 *                          favicon_media_id
 *
 * Excluded (services.icon): static theme assets — intentional Pattern B
 * exception, documented in ADR_005_PHASE05_WAVE1_BACKFILL_AUDIT.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── clients.avatar_media_id ─────────────────────────────────────────
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedBigInteger('avatar_media_id')
                  ->nullable()
                  ->after('avatar');

            $table->foreign('avatar_media_id')
                  ->references('id')
                  ->on('media')
                  ->nullOnDelete();
        });

        // ── portfolios.default_image_media_id ───────────────────────────────
        Schema::table('portfolios', function (Blueprint $table) {
            $table->unsignedBigInteger('default_image_media_id')
                  ->nullable()
                  ->after('default_image');

            $table->foreign('default_image_media_id')
                  ->references('id')
                  ->on('media')
                  ->nullOnDelete();
        });

        // ── general_settings × 7 logo columns ──────────────────────────────
        Schema::table('general_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('logo_media_id')
                  ->nullable()
                  ->after('logo');

            $table->foreign('logo_media_id')
                  ->references('id')
                  ->on('media')
                  ->nullOnDelete();

            $table->unsignedBigInteger('dark_logo_media_id')
                  ->nullable()
                  ->after('dark_logo');

            $table->foreign('dark_logo_media_id')
                  ->references('id')
                  ->on('media')
                  ->nullOnDelete();

            $table->unsignedBigInteger('sticky_logo_media_id')
                  ->nullable()
                  ->after('sticky_logo');

            $table->foreign('sticky_logo_media_id')
                  ->references('id')
                  ->on('media')
                  ->nullOnDelete();

            $table->unsignedBigInteger('dark_sticky_logo_media_id')
                  ->nullable()
                  ->after('dark_sticky_logo');

            $table->foreign('dark_sticky_logo_media_id')
                  ->references('id')
                  ->on('media')
                  ->nullOnDelete();

            $table->unsignedBigInteger('admin_logo_media_id')
                  ->nullable()
                  ->after('admin_logo');

            $table->foreign('admin_logo_media_id')
                  ->references('id')
                  ->on('media')
                  ->nullOnDelete();

            $table->unsignedBigInteger('admin_dark_logo_media_id')
                  ->nullable()
                  ->after('admin_dark_logo');

            $table->foreign('admin_dark_logo_media_id')
                  ->references('id')
                  ->on('media')
                  ->nullOnDelete();

            $table->unsignedBigInteger('favicon_media_id')
                  ->nullable()
                  ->after('favicon');

            $table->foreign('favicon_media_id')
                  ->references('id')
                  ->on('media')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropForeign(['favicon_media_id']);
            $table->dropForeign(['admin_dark_logo_media_id']);
            $table->dropForeign(['admin_logo_media_id']);
            $table->dropForeign(['dark_sticky_logo_media_id']);
            $table->dropForeign(['sticky_logo_media_id']);
            $table->dropForeign(['dark_logo_media_id']);
            $table->dropForeign(['logo_media_id']);
            $table->dropColumn([
                'favicon_media_id',
                'admin_dark_logo_media_id',
                'admin_logo_media_id',
                'dark_sticky_logo_media_id',
                'sticky_logo_media_id',
                'dark_logo_media_id',
                'logo_media_id',
            ]);
        });

        Schema::table('portfolios', function (Blueprint $table) {
            $table->dropForeign(['default_image_media_id']);
            $table->dropColumn('default_image_media_id');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['avatar_media_id']);
            $table->dropColumn('avatar_media_id');
        });
    }
};
