<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (! Schema::hasColumn('plans', 'plan_type')) {
                $table->string('plan_type', 32)
                    ->default('multi_tenant')
                    ->after('slug')
                    ->comment('multi_tenant أو hosting');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'plan_type')) {
                $table->dropColumn('plan_type');
            }
        });
    }
};
