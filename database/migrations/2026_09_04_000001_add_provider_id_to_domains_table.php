<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TLD-3D — Hybrid Provider Identity.
     *
     * يضيف domains.provider_id كـ FK اختياري (nullable) إلى domain_providers.id — Source of
     * Truth الجديد لهوية المزوّد عند توفره. registrar يبقى كما هو دون تغيير (نص مُطبَّع للعرض/
     * التوافق فقط، وليس مصدر الحقيقة بعد الآن). العمود nullable عمداً: مسارات الإضافة اليدوية
     * (admin/client quick-add) يمكنها الاستمرار بإنشاء نطاقات خارجية/غير مُدارة بـ
     * provider_id = null (راجع تدقيق TLD-3D). نفس نمط FK/on-delete المستخدم بالفعل في
     * domain_provisioning_attempts.provider_id (restrictOnDelete): يمنع حذف DomainProvider
     * طالما لا تزال نطاقات ترتبط به، دون فرض قيد NOT NULL أو Backfill.
     */
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->foreignId('provider_id')
                ->nullable()
                ->after('registrar')
                ->constrained('domain_providers')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropForeign(['provider_id']);
            $table->dropColumn('provider_id');
        });
    }
};
