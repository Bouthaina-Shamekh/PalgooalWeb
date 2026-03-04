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
        Schema::table('general_settings', function (Blueprint $table) {
            $table->boolean('header_show_promo_bar')->default(true)->after('active_footer_variant');
            $table->boolean('header_is_sticky')->default(true)->after('header_show_promo_bar');
            $table->boolean('footer_show_contact_banner')->default(true)->after('header_is_sticky');
            $table->boolean('footer_show_payment_methods')->default(true)->after('footer_show_contact_banner');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn([
                'header_show_promo_bar',
                'header_is_sticky',
                'footer_show_contact_banner',
                'footer_show_payment_methods',
            ]);
        });
    }
};
