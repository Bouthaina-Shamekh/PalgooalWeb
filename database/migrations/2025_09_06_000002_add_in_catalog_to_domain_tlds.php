<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('domain_tlds', function (Blueprint $table) {
            if (!Schema::hasColumn('domain_tlds', 'in_catalog')) {
                $table->boolean('in_catalog')->default(false)->index();
            }
        });
    }
    public function down(): void
    {
        Schema::table('domain_tlds', function (Blueprint $table) {
            if (Schema::hasColumn('domain_tlds', 'in_catalog')) {
                $table->dropColumn('in_catalog');
            }
        });
    }
};
