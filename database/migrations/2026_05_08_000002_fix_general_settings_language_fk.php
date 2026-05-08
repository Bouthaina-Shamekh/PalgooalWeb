<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Change general_settings.default_language FK from cascadeOnDelete to nullOnDelete.
     *
     * BUG: The original migration used cascadeOnDelete() on a nullable FK that
     * points to the singleton general_settings row. This meant deleting any
     * language set as the site default would cascade-delete the entire
     * general_settings row — wiping logos, header/footer config, and all
     * site-wide settings permanently.
     *
     * Fix: replace with nullOnDelete() so deleting a language simply clears
     * the default_language reference without touching the settings row.
     */
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            // Drop the existing cascadeOnDelete FK
            $table->dropForeign(['default_language']);

            // Re-add with nullOnDelete
            $table->foreign('default_language')
                ->references('id')
                ->on('languages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropForeign(['default_language']);

            // Restore original (unsafe) cascadeOnDelete behavior
            $table->foreign('default_language')
                ->references('id')
                ->on('languages')
                ->cascadeOnDelete();
        });
    }
};
