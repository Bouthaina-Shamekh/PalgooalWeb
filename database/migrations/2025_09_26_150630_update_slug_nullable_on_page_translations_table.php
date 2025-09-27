<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('page_translations') || !Schema::hasColumn('page_translations', 'slug')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE page_translations MODIFY slug VARCHAR(255) NULL');

            return;
        }

        Schema::table('page_translations', function (Blueprint $table) {
            $table->string('slug')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('page_translations') || !Schema::hasColumn('page_translations', 'slug')) {
            return;
        }

        $rows = DB::table('page_translations')
            ->select('id', 'locale')
            ->whereNull('slug')
            ->get();

        foreach ($rows as $row) {
            DB::table('page_translations')
                ->where('id', $row->id)
                ->update([
                    'slug' => sprintf('page-%s-%d', $row->locale, $row->id),
                ]);
        }

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE page_translations MODIFY slug VARCHAR(255) NOT NULL');

            return;
        }

        Schema::table('page_translations', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }
};
