<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds optional tenant ownership columns to the canonical Page + Section
     * content tables without changing current rendering behavior.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            if (! Schema::hasColumn('pages', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')
                    ->nullable()
                    ->after('subscription_id');

                $table->index('tenant_id', 'pages_tenant_id_index');
            }
        });

        Schema::table('sections', function (Blueprint $table) {
            if (! Schema::hasColumn('sections', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')
                    ->nullable()
                    ->after('page_id');

                $table->index('tenant_id', 'sections_tenant_id_index');
            }
        });

        Schema::table('section_translations', function (Blueprint $table) {
            if (! Schema::hasColumn('section_translations', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')
                    ->nullable()
                    ->after('section_id');

                $table->index('tenant_id', 'section_translations_tenant_id_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('section_translations', function (Blueprint $table) {
            if (Schema::hasColumn('section_translations', 'tenant_id')) {
                $table->dropIndex('section_translations_tenant_id_index');
                $table->dropColumn('tenant_id');
            }
        });

        Schema::table('sections', function (Blueprint $table) {
            if (Schema::hasColumn('sections', 'tenant_id')) {
                $table->dropIndex('sections_tenant_id_index');
                $table->dropColumn('tenant_id');
            }
        });

        Schema::table('pages', function (Blueprint $table) {
            if (Schema::hasColumn('pages', 'tenant_id')) {
                $table->dropIndex('pages_tenant_id_index');
                $table->dropColumn('tenant_id');
            }
        });
    }
};
