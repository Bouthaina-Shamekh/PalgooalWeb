<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a unique index on languages.code.
     *
     * BUG: The original migration omitted a unique constraint on the `code`
     * column (e.g. 'en', 'ar'). The `code` value is used as the locale key
     * throughout the application — in locale resolution, policy slug mapping,
     * menu translation normalization, and general settings. Duplicate codes
     * would cause silent, unpredictable failures.
     *
     * PREREQUISITE: If the database already contains duplicate codes, this
     * migration will fail. Deduplicate the languages table before running.
     * In a fresh or clean installation, this runs without issues.
     */
    public function up(): void
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->unique('code', 'languages_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->dropUnique('languages_code_unique');
        });
    }
};
