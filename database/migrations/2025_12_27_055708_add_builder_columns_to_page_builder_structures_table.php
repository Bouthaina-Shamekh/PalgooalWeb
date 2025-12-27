<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('page_builder_structures', function (Blueprint $table) {
            // في حال ما كان عندك "project" من قبل
            if (!Schema::hasColumn('page_builder_structures', 'project')) {
                $table->json('project')->nullable()->after('page_id');
            }

            if (!Schema::hasColumn('page_builder_structures', 'html')) {
                $table->longText('html')->nullable()->after('project');
            }

            if (!Schema::hasColumn('page_builder_structures', 'css')) {
                $table->longText('css')->nullable()->after('html');
            }
        });
    }

    public function down(): void
    {
        Schema::table('page_builder_structures', function (Blueprint $table) {
            if (Schema::hasColumn('page_builder_structures', 'project')) {
                $table->dropColumn('project');
            }
            if (Schema::hasColumn('page_builder_structures', 'html')) {
                $table->dropColumn('html');
            }
            if (Schema::hasColumn('page_builder_structures', 'css')) {
                $table->dropColumn('css');
            }
        });
    }
};
