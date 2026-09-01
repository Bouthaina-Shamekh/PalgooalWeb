<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('payment_session_status', 20)
                ->default('idle')
                ->index()
                ->after('payment_attempt_id');

            $table->unsignedBigInteger('payment_session_attempt_id')
                ->nullable()
                ->after('payment_session_status');

            $table->foreign('payment_session_attempt_id')
                ->references('id')
                ->on('payment_attempts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['payment_session_attempt_id']);
            $table->dropIndex(['payment_session_status']);
            $table->dropColumn(['payment_session_attempt_id', 'payment_session_status']);
        });
    }
};
