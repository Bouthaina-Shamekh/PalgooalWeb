<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            if (Schema::hasTable('sections')) {
                $sectionIds = DB::table('sections')
                    ->where('type', 'hero_default')
                    ->pluck('id');

                if ($sectionIds->isNotEmpty() && Schema::hasTable('section_translations')) {
                    DB::table('section_translations')
                        ->whereIn('section_id', $sectionIds->all())
                        ->delete();
                }

                DB::table('sections')
                    ->where('type', 'hero_default')
                    ->delete();
            }

            if (Schema::hasTable('section_definitions')) {
                $definitionIds = DB::table('section_definitions')
                    ->where('section_key', 'hero_default')
                    ->pluck('id');

                if ($definitionIds->isNotEmpty()) {
                    if (Schema::hasTable('section_definition_template')) {
                        DB::table('section_definition_template')
                            ->whereIn('section_definition_id', $definitionIds->all())
                            ->delete();
                    }

                    if (Schema::hasTable('section_definition_fields')) {
                        DB::table('section_definition_fields')
                            ->whereIn('section_definition_id', $definitionIds->all())
                            ->delete();
                    }

                    DB::table('section_definitions')
                        ->whereIn('id', $definitionIds->all())
                        ->delete();
                }
            }

            if (Schema::hasTable('section_templates')) {
                $templateIds = DB::table('section_templates')
                    ->where('template_key', 'hero_default')
                    ->pluck('id');

                if ($templateIds->isNotEmpty()) {
                    if (Schema::hasTable('section_definition_template')) {
                        DB::table('section_definition_template')
                            ->whereIn('section_template_id', $templateIds->all())
                            ->delete();
                    }

                    DB::table('section_templates')
                        ->whereIn('id', $templateIds->all())
                        ->delete();
                }
            }
        });
    }

    public function down(): void
    {
        // Intentionally irreversible. The hero_default section type and its
        // renderer have been removed from the application.
    }
};
