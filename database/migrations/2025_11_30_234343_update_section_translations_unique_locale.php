<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a unique constraint to ensure one translation per locale per section.
     */
    public function up(): void
    {
        Schema::table('section_translations', function (Blueprint $table) {
            $table->unique(['section_id', 'locale'], 'section_locale_unique');
        });
    }

    public function down(): void
    {
        Schema::table('section_translations', function (Blueprint $table) {
            $table->dropUnique('section_locale_unique');
        });
    }
};
