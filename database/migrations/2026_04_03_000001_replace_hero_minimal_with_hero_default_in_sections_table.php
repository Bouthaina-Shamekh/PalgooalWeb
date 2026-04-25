<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('sections')
            ->where('type', 'hero_minimal')
            ->delete();
    }

    public function down(): void
    {
        // Irreversible data migration: hero_minimal and hero_default are removed.
    }
};
