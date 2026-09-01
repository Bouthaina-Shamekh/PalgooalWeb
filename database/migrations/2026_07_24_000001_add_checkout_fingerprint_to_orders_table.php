<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ADR — Idempotency Phase 1 (Domain Checkout only)
     *
     * عمود بصمة حتمية لمحاولة Checkout الواحدة (جلسة الضيف أو هوية العميل + محتوى السلة
     * المطبَّع). يُستخدم فقط داخل CheckoutController::process() لمنع إنشاء Order/Invoice/
     * InvoiceItems مكررة عند: النقر المزدوج، إعادة إرسال POST، Refresh بعد الإرسال، تبويبين
     * بنفس السلة، أو إعادة محاولة بعد timeout مع نجاح الطلب الأول فعليًا.
     *
     * - Nullable: الطلبات غير المرتبطة بمسار سلة الدومينات (template/plan checkout) لا تحسب
     *   بصمة، فتبقى NULL لها — والفهرس الفريد في MySQL/MariaDB يسمح بعدد غير محدود من قيم NULL.
     * - Unique: قاعدة البيانات هي الحكم النهائي عند تصادم متزامن (race) بين طلبين لنفس البصمة.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('checkout_fingerprint', 64)->nullable()->after('type');
            $table->unique('checkout_fingerprint', 'orders_checkout_fingerprint_unique');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_checkout_fingerprint_unique');
            $table->dropColumn('checkout_fingerprint');
        });
    }
};
