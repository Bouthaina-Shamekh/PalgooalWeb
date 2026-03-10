<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('domains', 'auto_renew')) {
            Schema::table('domains', function (Blueprint $table) {
                $table->boolean('auto_renew')->default(false)->after('renewal_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('domains', 'auto_renew')) {
            Schema::table('domains', function (Blueprint $table) {
                $table->dropColumn('auto_renew');
            });
        }
    }
};
