<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_sections', function (Blueprint $table) {
            if (! Schema::hasColumn('subscription_sections', 'type')) {
                $table->string('type', 64)->default('generic')->after('key');
            }

            if (! Schema::hasColumn('subscription_sections', 'variant')) {
                $table->string('variant', 64)->nullable()->after('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscription_sections', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_sections', 'variant')) {
                $table->dropColumn('variant');
            }

            if (Schema::hasColumn('subscription_sections', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
