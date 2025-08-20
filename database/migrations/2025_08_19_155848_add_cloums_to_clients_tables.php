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
        Schema::table('clients', function (Blueprint $table) {
            // إضافة الحقول الناقصة من الملف
            $table->enum('status', ['active', 'inactive'])
                  ->default('active')
                  ->after('can_login');

            $table->string('country', 2)->nullable()->after('zip_code');
            $table->string('city')->nullable()->after('country');
            $table->text('address')->nullable()->after('city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['status', 'country', 'city', 'address']);
        });
    }
};
