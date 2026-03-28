<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('subscriptions', 'domain_verification_status')) {
                $table->string('domain_verification_status')
                    ->nullable()
                    ->after('domain_id')
                    ->index();
            }

            if (! Schema::hasColumn('subscriptions', 'domain_last_checked_at')) {
                $table->timestamp('domain_last_checked_at')
                    ->nullable()
                    ->after('domain_verification_status');
            }

            if (! Schema::hasColumn('subscriptions', 'domain_verified_at')) {
                $table->timestamp('domain_verified_at')
                    ->nullable()
                    ->after('domain_last_checked_at');
            }

            if (! Schema::hasColumn('subscriptions', 'domain_verification_error')) {
                $table->text('domain_verification_error')
                    ->nullable()
                    ->after('domain_verified_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'domain_verification_error')) {
                $table->dropColumn('domain_verification_error');
            }

            if (Schema::hasColumn('subscriptions', 'domain_verified_at')) {
                $table->dropColumn('domain_verified_at');
            }

            if (Schema::hasColumn('subscriptions', 'domain_last_checked_at')) {
                $table->dropColumn('domain_last_checked_at');
            }

            if (Schema::hasColumn('subscriptions', 'domain_verification_status')) {
                $table->dropIndex(['domain_verification_status']);
                $table->dropColumn('domain_verification_status');
            }
        });
    }
};
