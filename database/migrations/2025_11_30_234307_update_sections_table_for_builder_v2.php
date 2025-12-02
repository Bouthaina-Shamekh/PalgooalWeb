<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Upgrade the `sections` table to support Page Builder V2:
     * - Rename `key` to `type`
     * - Add `variant` column
     * - Add `is_active` column
     */
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {

            // Rename `key` → `type` for clearer naming
            if (Schema::hasColumn('sections', 'key')) {
                $table->renameColumn('key', 'type');
            }

            // Optional design variant for the same section type
            $table->string('variant')
                ->nullable()
                ->after('type');

            // Visibility toggle without deleting the section
            $table->boolean('is_active')
                ->default(true)
                ->after('order');
        });
    }

    /**
     * Revert the changes.
     */
    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {

            if (Schema::hasColumn('sections', 'type')) {
                $table->renameColumn('type', 'key');
            }

            $table->dropColumn(['variant', 'is_active']);
        });
    }
};
