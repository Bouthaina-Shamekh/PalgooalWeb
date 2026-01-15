<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // بما أن locale موجود بالفعل في جدولك حسب SHOW CREATE TABLE
        // سنضيفه فقط إذا كان غير موجود (لتجنب أي خطأ)
        Schema::table('page_builder_structures', function (Blueprint $table) {
            if (!Schema::hasColumn('page_builder_structures', 'locale')) {
                $table->string('locale', 10)
                    ->default(config('app.fallback_locale'))
                    ->after('page_id');
            }
        });

        // 1) Drop FK أولاً
        Schema::table('page_builder_structures', function (Blueprint $table) {
            $table->dropForeign('page_builder_structures_page_id_foreign');
        });

        // 2) Drop unique(page_id)
        Schema::table('page_builder_structures', function (Blueprint $table) {
            $table->dropUnique('page_builder_structures_page_id_unique');
        });

        // 3) Re-add FK
        Schema::table('page_builder_structures', function (Blueprint $table) {
            $table->foreign('page_id')
                ->references('id')
                ->on('pages')
                ->cascadeOnDelete();
        });

        // 4) Add composite unique(page_id, locale)
        Schema::table('page_builder_structures', function (Blueprint $table) {
            $table->unique(['page_id', 'locale'], 'pbs_page_locale_unique');
        });
    }

    public function down(): void
    {
        // drop composite unique
        Schema::table('page_builder_structures', function (Blueprint $table) {
            $table->dropUnique('pbs_page_locale_unique');
        });

        // drop FK
        Schema::table('page_builder_structures', function (Blueprint $table) {
            $table->dropForeign('page_builder_structures_page_id_foreign');
        });

        // restore unique(page_id)
        Schema::table('page_builder_structures', function (Blueprint $table) {
            $table->unique('page_id', 'page_builder_structures_page_id_unique');
        });

        // restore FK
        Schema::table('page_builder_structures', function (Blueprint $table) {
            $table->foreign('page_id')
                ->references('id')
                ->on('pages')
                ->cascadeOnDelete();
        });

        // (اختياري) لا تحذف locale في down لأن جدولك صار يعتمد عليه الآن
        // إذا بدك تحذفه فعلًا، تأكد ما في كود يعتمد عليه.
    }
};
