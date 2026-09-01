<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ADR — Provisioning Idempotency Phase 1 (Register Domain فقط).
     *
     * OrderItem يمثّل العملية التجارية نفسها (register/renew/transfer/restore)، لذا حالة
     * التزويد تُخزَّن هنا وليس على Domain (الذي يمثّل الكيان النهائي فقط). تمنع هذه الأعمدة
     * إعادة تنفيذ RegistrarProvisioningService::provisionOrderDomain() لنفس عملية الشراء
     * عند: retry، webhook مكرر، إعادة تشغيل worker، أو إجراء إداري مكرر.
     *
     * القيم المستخدمة حاليًا فقط لعملية Register: not_started (افتراضي) → in_progress →
     * completed | failed.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('provisioning_status')->default('not_started')->after('meta');
            $table->timestamp('provisioning_started_at')->nullable()->after('provisioning_status');
            $table->timestamp('provisioning_completed_at')->nullable()->after('provisioning_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'provisioning_status',
                'provisioning_started_at',
                'provisioning_completed_at',
            ]);
        });
    }
};
