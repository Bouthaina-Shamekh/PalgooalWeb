<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_translations', function (Blueprint $table) {
            if (!Schema::hasColumn('plan_translations', 'featured_label')) {
                $table->string('featured_label', 120)->nullable()->after('description');
            }
        });

        if (Schema::hasColumn('plan_translations', 'featured_label')) {
            $translations = DB::table('plan_translations')
                ->select('id', 'plan_id')
                ->get();

            foreach ($translations as $translation) {
                $label = DB::table('plans')
                    ->where('id', $translation->plan_id)
                    ->value('featured_label');

                if ($label !== null) {
                    DB::table('plan_translations')
                        ->where('id', $translation->id)
                        ->update(['featured_label' => $label]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('plan_translations', function (Blueprint $table) {
            if (Schema::hasColumn('plan_translations', 'featured_label')) {
                $table->dropColumn('featured_label');
            }
        });
    }
};
