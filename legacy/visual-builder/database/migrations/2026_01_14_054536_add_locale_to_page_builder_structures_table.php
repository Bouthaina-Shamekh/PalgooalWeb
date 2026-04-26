<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('page_builder_structures', function (Blueprint $table) {
            if (! Schema::hasColumn('page_builder_structures', 'locale')) {
                $table->string('locale', 10)
                    ->default(config('app.fallback_locale'))
                    ->after('page_id');
            }
        });

        // SQLite does not support dropping foreign keys by constraint name during alter table.
        if ($isSqlite) {
            return;
        }

        Schema::table('page_builder_structures', function (Blueprint $table) {
            $table->dropForeign('page_builder_structures_page_id_foreign');
        });

        Schema::table('page_builder_structures', function (Blueprint $table) {
            $table->dropUnique('page_builder_structures_page_id_unique');
        });

        Schema::table('page_builder_structures', function (Blueprint $table) {
            $table->foreign('page_id')
                ->references('id')
                ->on('pages')
                ->cascadeOnDelete();
        });

        Schema::table('page_builder_structures', function (Blueprint $table) {
            $table->unique(['page_id', 'locale'], 'pbs_page_locale_unique');
        });
    }

    public function down(): void
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        if ($isSqlite) {
            return;
        }

        Schema::table('page_builder_structures', function (Blueprint $table) {
            $table->dropUnique('pbs_page_locale_unique');
        });

        Schema::table('page_builder_structures', function (Blueprint $table) {
            $table->dropForeign('page_builder_structures_page_id_foreign');
        });

        Schema::table('page_builder_structures', function (Blueprint $table) {
            $table->unique('page_id', 'page_builder_structures_page_id_unique');
        });

        Schema::table('page_builder_structures', function (Blueprint $table) {
            $table->foreign('page_id')
                ->references('id')
                ->on('pages')
                ->cascadeOnDelete();
        });
    }
};
