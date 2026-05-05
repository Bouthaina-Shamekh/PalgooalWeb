<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            // P4 fix: make default_image nullable to match controller validation
            $table->string('default_image')->nullable()->change();

            // P9 fix: add soft-delete column (requires SoftDeletes trait on model)
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            $table->dropSoftDeletes();

            // Reverting nullable is intentionally skipped — existing NULL rows
            // would cause a constraint violation if we forced NOT NULL here.
        });
    }
};
