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
            if (! Schema::hasColumn('subscriptions', 'template_id')) {
                $table->foreignId('template_id')
                    ->nullable()
                    ->after('plan_id')
                    ->constrained('templates')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('subscriptions', 'engine')) {
                $table->string('engine')
                    ->default('laravel')
                    ->after('template_id')
                    ->comment('laravel / wordpress / other engines');
            }

            if (! Schema::hasColumn('subscriptions', 'provisioning_status')) {
                $table->string('provisioning_status')
                    ->default('pending')
                    ->after('status');
            }

            if (! Schema::hasColumn('subscriptions', 'provisioned_at')) {
                $table->timestamp('provisioned_at')
                    ->nullable()
                    ->after('provisioning_status');
            }

            if (! Schema::hasColumn('subscriptions', 'last_synced_at')) {
                $table->timestamp('last_synced_at')
                    ->nullable()
                    ->after('next_due_date');
            }

            if (! Schema::hasColumn('subscriptions', 'subdomain')) {
                $table->string('subdomain', 191)
                    ->nullable()
                    ->after('domain_option')
                    ->unique();
            }

            if (! Schema::hasColumn('subscriptions', 'domain_id')) {
                $table->foreignId('domain_id')
                    ->nullable()
                    ->after('domain_name')
                    ->constrained('domains')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('subscriptions', 'cpanel_username')) {
                $table->string('cpanel_username')
                    ->nullable()
                    ->after('username');
            }

            if (! Schema::hasColumn('subscriptions', 'cpanel_password')) {
                $table->string('cpanel_password')
                    ->nullable()
                    ->after('cpanel_username');
            }

            if (! Schema::hasColumn('subscriptions', 'cpanel_url')) {
                $table->string('cpanel_url')
                    ->nullable()
                    ->after('cpanel_password');
            }

            if (! Schema::hasColumn('subscriptions', 'settings')) {
                $table->json('settings')
                    ->nullable()
                    ->after('cpanel_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'settings')) {
                $table->dropColumn('settings');
            }

            if (Schema::hasColumn('subscriptions', 'cpanel_url')) {
                $table->dropColumn('cpanel_url');
            }

            if (Schema::hasColumn('subscriptions', 'cpanel_password')) {
                $table->dropColumn('cpanel_password');
            }

            if (Schema::hasColumn('subscriptions', 'cpanel_username')) {
                $table->dropColumn('cpanel_username');
            }

            if (Schema::hasColumn('subscriptions', 'domain_id')) {
                $table->dropConstrainedForeignId('domain_id');
            }

            if (Schema::hasColumn('subscriptions', 'subdomain')) {
                $table->dropColumn('subdomain');
            }

            if (Schema::hasColumn('subscriptions', 'last_synced_at')) {
                $table->dropColumn('last_synced_at');
            }

            if (Schema::hasColumn('subscriptions', 'provisioned_at')) {
                $table->dropColumn('provisioned_at');
            }

            if (Schema::hasColumn('subscriptions', 'provisioning_status')) {
                $table->dropColumn('provisioning_status');
            }

            if (Schema::hasColumn('subscriptions', 'engine')) {
                $table->dropColumn('engine');
            }

            if (Schema::hasColumn('subscriptions', 'template_id')) {
                $table->dropConstrainedForeignId('template_id');
            }
        });
    }
};
