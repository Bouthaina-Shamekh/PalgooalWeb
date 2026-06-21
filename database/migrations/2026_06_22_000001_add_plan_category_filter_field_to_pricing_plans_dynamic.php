<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add plan_category_id filter field to the pricing_plans_dynamic SectionDefinition.
 *
 * This field allows admin page-builders to restrict which plan category is shown
 * inside the pricing_plans_dynamic section. When left blank, all active plans appear.
 *
 * Implementation notes:
 * - field_type  = 'select'  (rendered as a <select> in the dynamic editor)
 * - field_scope = 'shared'  (category choice is language-agnostic, like image_position)
 * - settings.options_source = 'plan_categories'  → DynamicSectionEditorRenderer
 *   fetches options live from DB instead of reading static JSON
 * - options is intentionally NULL — options_source supersedes it
 */
return new class extends Migration
{
    public function up(): void
    {
        // Locate the SectionDefinition for pricing_plans_dynamic.
        $def = DB::table('section_definitions')
            ->where('section_key', 'pricing_plans_dynamic')
            ->first();

        if (! $def) {
            // Section Definition not seeded yet — skip silently.
            // The field can be added manually via the admin UI.
            return;
        }

        // Avoid duplicate insertion on re-run.
        $exists = DB::table('section_definition_fields')
            ->where('section_definition_id', $def->id)
            ->where('field_key', 'plan_category_id')
            ->exists();

        if ($exists) {
            return;
        }

        // Determine the highest current sort_order so we place this field last.
        $maxOrder = DB::table('section_definition_fields')
            ->where('section_definition_id', $def->id)
            ->max('sort_order') ?? 0;

        DB::table('section_definition_fields')->insert([
            'section_definition_id' => $def->id,
            'field_key'             => 'plan_category_id',
            'label'                 => 'تصنيف الخطط',
            'group_name'            => 'design',
            'help_text'             => 'اختر تصنيفاً لتصفية الخطط المعروضة. اتركه فارغاً لعرض جميع الخطط النشطة.',
            'field_type'            => 'select',
            'field_scope'           => 'shared',
            'default_value'         => null,
            'options'               => null,    // dynamic — loaded via options_source
            'settings'              => json_encode(['options_source' => 'plan_categories']),
            'schema'                => null,
            'is_required'           => 0,
            'is_active'             => 1,
            'sort_order'            => $maxOrder + 10,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);
    }

    public function down(): void
    {
        $def = DB::table('section_definitions')
            ->where('section_key', 'pricing_plans_dynamic')
            ->first();

        if (! $def) {
            return;
        }

        DB::table('section_definition_fields')
            ->where('section_definition_id', $def->id)
            ->where('field_key', 'plan_category_id')
            ->delete();
    }
};
