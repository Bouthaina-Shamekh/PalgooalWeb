<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            if (!Schema::hasColumn('feedbacks', 'is_approved')) {
                $table->boolean('is_approved')->default(true)->after('order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            if (Schema::hasColumn('feedbacks', 'is_approved')) {
                $table->dropColumn('is_approved');
            }
        });
    }
};

