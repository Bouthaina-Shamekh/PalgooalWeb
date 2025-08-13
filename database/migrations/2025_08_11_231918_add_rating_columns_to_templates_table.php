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
        Schema::table('templates', function (Blueprint $table) {

            $table->unsignedInteger('rating_count')->default(0)->after('rating'); // عندك عمود rating أصلاً؛ نخليه كما هو أو تتخلى عنه لاحقاً
            $table->decimal('rating_avg', 3, 2)->default(0)->after('rating_count');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn(['rating_count', 'rating_avg']);
        });
    }
};
