<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('page_builder_structures')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        if (Schema::hasColumn('page_builder_structures', 'project')) {
            DB::statement('ALTER TABLE `page_builder_structures` MODIFY `project` LONGTEXT NULL');
        }

        if (Schema::hasColumn('page_builder_structures', 'structure')) {
            DB::statement('ALTER TABLE `page_builder_structures` MODIFY `structure` LONGTEXT NULL');
        }
    }

    public function down(): void
    {
        // Intentionally left as a no-op.
        // Legacy GrapesJS payloads may fail MariaDB JSON_VALID checks,
        // so converting these columns back to JSON is not guaranteed safe.
    }
};
