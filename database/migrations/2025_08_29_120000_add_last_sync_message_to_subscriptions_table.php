<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'last_sync_message')) {
                $table->text('last_sync_message')->nullable()->after('updated_at');
            }
        });
    }

    public function down()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'last_sync_message')) {
                $table->dropColumn('last_sync_message');
            }
        });
    }
};
