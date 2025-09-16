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
        if (Schema::hasColumn('domains', 'template_id')) {
            Schema::table('domains', function (Blueprint $table) {
                // Drop FK constraint if it exists, then drop the column
                try {
                    $table->dropForeign(['template_id']);
                } catch (\Throwable $e) {
                    // Ignore if foreign key does not exist or already dropped
                }
                $table->dropColumn('template_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('domains', 'template_id')) {
            Schema::table('domains', function (Blueprint $table) {
                $table->unsignedBigInteger('template_id')->nullable()->after('status');
                $table->foreign('template_id')->references('id')->on('templates')->onDelete('cascade');
            });
        }
    }
};

