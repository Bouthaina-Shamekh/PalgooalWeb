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
        Schema::table('domains', function (Blueprint $table) {
            if (!Schema::hasColumn('domains', 'nameservers')) {
                $table->json('nameservers')->nullable()->after('status');
            }

            if (!Schema::hasColumn('domains', 'dns_last_note')) {
                $table->text('dns_last_note')->nullable()->after('nameservers');
            }

            if (!Schema::hasColumn('domains', 'dns_last_synced_at')) {
                $table->timestamp('dns_last_synced_at')->nullable()->after('dns_last_note');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            if (Schema::hasColumn('domains', 'dns_last_synced_at')) {
                $table->dropColumn('dns_last_synced_at');
            }

            if (Schema::hasColumn('domains', 'dns_last_note')) {
                $table->dropColumn('dns_last_note');
            }

            if (Schema::hasColumn('domains', 'nameservers')) {
                $table->dropColumn('nameservers');
            }
        });
    }
};
