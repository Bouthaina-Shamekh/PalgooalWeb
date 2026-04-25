<?php

use App\Models\Sections\SectionDefinition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('section_definitions')
            || ! Schema::hasTable('section_templates')
            || ! Schema::hasTable('section_definition_template')
        ) {
            return;
        }

        DB::transaction(function (): void {
            $timestamp = now();

            $definitionId = DB::table('section_definitions')
                ->where('section_key', 'hero_campaign')
                ->value('id');

            $definitionPayload = [
                'label' => 'Hero Campaign',
                'description' => 'Dynamic hero campaign section with CTA, media, feature checklist, and trust items.',
                'category' => 'hero',
                'editor_mode' => SectionDefinition::EDITOR_MODE_DYNAMIC,
                'custom_editor_key' => null,
                'is_active' => true,
                'is_visible' => true,
                'updated_at' => $timestamp,
            ];

            if ($definitionId) {
                DB::table('section_definitions')
                    ->where('id', $definitionId)
                    ->update($definitionPayload);
            } else {
                $definitionId = DB::table('section_definitions')->insertGetId(array_merge($definitionPayload, [
                    'section_key' => 'hero_campaign',
                    'settings' => json_encode([]),
                    'schema' => json_encode([]),
                    'sort_order' => 0,
                    'created_at' => $timestamp,
                ]));
            }

            $templateId = DB::table('section_templates')
                ->where('template_key', 'hero_campaign')
                ->value('id');

            $templatePayload = [
                'label' => 'Hero Campaign',
                'description' => 'Frontend renderer for the dynamic hero campaign section.',
                'category' => 'hero',
                'is_active' => true,
                'is_visible' => true,
                'updated_at' => $timestamp,
            ];

            if ($templateId) {
                DB::table('section_templates')
                    ->where('id', $templateId)
                    ->update($templatePayload);
            } else {
                $templateId = DB::table('section_templates')->insertGetId(array_merge($templatePayload, [
                    'template_key' => 'hero_campaign',
                    'settings' => json_encode([]),
                    'schema' => json_encode([]),
                    'sort_order' => 0,
                    'created_at' => $timestamp,
                ]));
            }

            DB::table('section_definition_template')
                ->where('section_definition_id', $definitionId)
                ->delete();

            DB::table('section_definition_template')->insert([
                'section_definition_id' => $definitionId,
                'section_template_id' => $templateId,
                'sort_order' => 0,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        });
    }

    public function down(): void
    {
        // Intentionally no rollback. This migration only aligns metadata for
        // the new categorized renderer convention and does not remove content.
    }
};
