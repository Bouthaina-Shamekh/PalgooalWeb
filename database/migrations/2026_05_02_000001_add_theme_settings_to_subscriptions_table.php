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
        Schema::table('subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('subscriptions', 'theme_settings')) {
                $table->json('theme_settings')
                    ->nullable()
                    ->after('settings')
                    ->comment('Tenant brand/theme token overrides (colors, typography, shape, buttons)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'theme_settings')) {
                $table->dropColumn('theme_settings');
            }
        });
    }
};
