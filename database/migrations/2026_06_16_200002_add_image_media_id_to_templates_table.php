<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-005 Wave 2 — templates.image_media_id
 *
 * Adds a nullable FK column referencing media.id alongside the existing
 * templates.image path-string column.  The old column is NOT dropped here;
 * both columns coexist during the dual-write transition period.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table): void {
            $table->unsignedBigInteger('image_media_id')->nullable()->after('image');

            $table->foreign('image_media_id')
                  ->references('id')
                  ->on('media')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table): void {
            $table->dropForeign(['image_media_id']);
            $table->dropColumn('image_media_id');
        });
    }
};
