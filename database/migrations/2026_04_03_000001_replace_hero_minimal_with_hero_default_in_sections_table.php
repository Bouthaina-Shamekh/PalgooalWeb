<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('sections')
            ->where('type', 'hero_minimal')
            ->update([
                'type' => 'hero_default',
            ]);
    }

    public function down(): void
    {
        // Irreversible data migration: after consolidating types, a rollback
        // would risk converting genuine hero_default sections to hero_minimal.
    }
};
