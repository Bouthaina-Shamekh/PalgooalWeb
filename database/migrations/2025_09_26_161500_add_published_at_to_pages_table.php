<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pages') || Schema::hasColumn('pages', 'published_at')) {
            return;
        }

        Schema::table('pages', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->after('is_home');
        });

        DB::table('pages')
            ->where('is_active', true)
            ->whereNull('published_at')
            ->update([
                'published_at' => DB::raw('created_at'),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('pages') || !Schema::hasColumn('pages', 'published_at')) {
            return;
        }

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('published_at');
        });
    }
};
