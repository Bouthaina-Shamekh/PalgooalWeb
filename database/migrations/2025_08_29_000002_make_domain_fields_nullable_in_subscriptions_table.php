<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('domain_option')->nullable()->change();
            $table->string('domain_name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Intentionally skipped: reverting nullable(false) on columns that may
     * already contain NULL values would cause a DB constraint violation in
     * any environment where up() was previously run. The columns remain
     * nullable on rollback to preserve data integrity.
     * See: 2026_05_08_000002_fix_general_settings_language_fk.php for context.
     */
    public function down(): void
    {
        // No-op: see docblock above.
    }
};
