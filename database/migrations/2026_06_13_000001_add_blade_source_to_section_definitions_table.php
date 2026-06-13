<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('section_definitions', function (Blueprint $table) {
            $table->longText('blade_source')->nullable()->after('settings');
            $table->timestamp('blade_written_at')->nullable()->after('blade_source');
        });
    }

    public function down(): void
    {
        Schema::table('section_definitions', function (Blueprint $table) {
            $table->dropColumn(['blade_source', 'blade_written_at']);
        });
    }
};
