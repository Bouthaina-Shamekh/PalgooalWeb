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
            || ! Schema::hasTable('section_definition_fields')
            || ! Schema::hasTable('section_templates')
            || ! Schema::hasTable('section_definition_template')
        ) {
            return;
        }

        DB::transaction(function (): void {
            $timestamp = now();

            $definitionId = DB::table('section_definitions')
                ->where('section_key', 'hosting_hero')
                ->value('id');

            if ($definitionId) {
                DB::table('section_definitions')
                    ->where('id', $definitionId)
                    ->update([
                        'label' => 'Hosting Hero',
                        'description' => 'Clean dynamic hosting hero with checklist items and one CTA card.',
                        'category' => 'hero',
                        'editor_mode' => SectionDefinition::EDITOR_MODE_DYNAMIC,
                        'custom_editor_key' => null,
                        'is_active' => true,
                        'is_visible' => true,
                        'updated_at' => $timestamp,
                    ]);
            } else {
                $definitionId = DB::table('section_definitions')->insertGetId([
                    'section_key' => 'hosting_hero',
                    'label' => 'Hosting Hero',
                    'description' => 'Clean dynamic hosting hero with checklist items and one CTA card.',
                    'category' => 'hero',
                    'editor_mode' => SectionDefinition::EDITOR_MODE_DYNAMIC,
                    'custom_editor_key' => null,
                    'settings' => json_encode([]),
                    'schema' => json_encode([]),
                    'is_active' => true,
                    'is_visible' => true,
                    'sort_order' => 0,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }

            $templateId = DB::table('section_templates')
                ->where('template_key', 'hosting_hero')
                ->value('id');

            if ($templateId) {
                DB::table('section_templates')
                    ->where('id', $templateId)
                    ->update([
                        'label' => 'Hosting Hero',
                        'description' => 'Frontend renderer for the clean dynamic hosting hero section.',
                        'category' => 'hero',
                        'is_active' => true,
                        'is_visible' => true,
                        'updated_at' => $timestamp,
                    ]);
            } else {
                $templateId = DB::table('section_templates')->insertGetId([
                    'template_key' => 'hosting_hero',
                    'label' => 'Hosting Hero',
                    'description' => 'Frontend renderer for the clean dynamic hosting hero section.',
                    'category' => 'hero',
                    'settings' => json_encode([]),
                    'schema' => json_encode([]),
                    'is_active' => true,
                    'is_visible' => true,
                    'sort_order' => 0,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
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

            DB::table('section_definition_fields')
                ->where('section_definition_id', $definitionId)
                ->delete();

            DB::table('section_definition_fields')->insert([
                [
                    'section_definition_id' => $definitionId,
                    'field_key' => 'title',
                    'label' => 'Title',
                    'group_name' => 'content',
                    'help_text' => 'Main headline for the hosting hero section.',
                    'field_type' => 'text',
                    'field_scope' => 'translatable',
                    'default_value' => json_encode([
                        'ar' => 'الاستضافة',
                        'en' => 'Hosting',
                        'fr' => 'Hosting',
                    ], JSON_UNESCAPED_UNICODE),
                    'options' => null,
                    'settings' => json_encode([]),
                    'schema' => null,
                    'is_required' => false,
                    'validation_rules' => json_encode([]),
                    'is_active' => true,
                    'sort_order' => 1,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
                [
                    'section_definition_id' => $definitionId,
                    'field_key' => 'subtitle',
                    'label' => 'Subtitle',
                    'group_name' => 'content',
                    'help_text' => 'Supporting copy shown below the main title.',
                    'field_type' => 'textarea',
                    'field_scope' => 'translatable',
                    'default_value' => json_encode([
                        'ar' => 'حلول استضافة موثوقة وسريعة لموقعك مع إعداد مرن وتجربة استخدام احترافية.',
                        'en' => 'Reliable and fast hosting solutions for your website with flexible setup and a professional user experience.',
                        'fr' => 'Reliable and fast hosting solutions for your website with flexible setup and a professional user experience.',
                    ], JSON_UNESCAPED_UNICODE),
                    'options' => null,
                    'settings' => json_encode(['rows' => 4]),
                    'schema' => null,
                    'is_required' => false,
                    'validation_rules' => json_encode([]),
                    'is_active' => true,
                    'sort_order' => 2,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
                [
                    'section_definition_id' => $definitionId,
                    'field_key' => 'features',
                    'label' => 'Features',
                    'group_name' => 'content',
                    'help_text' => 'Optional checklist items displayed beside the hero copy.',
                    'field_type' => 'repeater',
                    'field_scope' => 'translatable',
                    'default_value' => null,
                    'options' => null,
                    'settings' => json_encode([]),
                    'schema' => json_encode([
                        'item_schema' => [
                            [
                                'key' => 'text',
                                'label' => 'Feature Text',
                                'type' => 'text',
                                'required' => false,
                                'translatable' => true,
                            ],
                        ],
                    ], JSON_UNESCAPED_UNICODE),
                    'is_required' => false,
                    'validation_rules' => json_encode([]),
                    'is_active' => true,
                    'sort_order' => 3,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
                [
                    'section_definition_id' => $definitionId,
                    'field_key' => 'cta_title',
                    'label' => 'CTA Title',
                    'group_name' => 'cta',
                    'help_text' => 'Short title shown inside the right-side CTA card.',
                    'field_type' => 'text',
                    'field_scope' => 'translatable',
                    'default_value' => json_encode([
                        'ar' => 'لا تتردد',
                        'en' => "Don't Hesitate",
                        'fr' => "Don't Hesitate",
                    ], JSON_UNESCAPED_UNICODE),
                    'options' => null,
                    'settings' => json_encode([]),
                    'schema' => null,
                    'is_required' => false,
                    'validation_rules' => json_encode([]),
                    'is_active' => true,
                    'sort_order' => 4,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
                [
                    'section_definition_id' => $definitionId,
                    'field_key' => 'cta_button.label',
                    'label' => 'CTA Button Label',
                    'group_name' => 'cta',
                    'help_text' => 'Button label shown inside the CTA card.',
                    'field_type' => 'text',
                    'field_scope' => 'translatable',
                    'default_value' => json_encode([
                        'ar' => 'اطلب الآن',
                        'en' => 'Order Now',
                        'fr' => 'Order Now',
                    ], JSON_UNESCAPED_UNICODE),
                    'options' => null,
                    'settings' => json_encode([]),
                    'schema' => null,
                    'is_required' => false,
                    'validation_rules' => json_encode([]),
                    'is_active' => true,
                    'sort_order' => 5,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
                [
                    'section_definition_id' => $definitionId,
                    'field_key' => 'cta_button.url',
                    'label' => 'CTA Button URL',
                    'group_name' => 'cta',
                    'help_text' => 'Destination URL for the CTA card button.',
                    'field_type' => 'url',
                    'field_scope' => 'shared',
                    'default_value' => json_encode([
                        'value' => '#',
                    ], JSON_UNESCAPED_UNICODE),
                    'options' => null,
                    'settings' => json_encode([]),
                    'schema' => null,
                    'is_required' => false,
                    'validation_rules' => json_encode([]),
                    'is_active' => true,
                    'sort_order' => 6,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
                [
                    'section_definition_id' => $definitionId,
                    'field_key' => 'cta_button.new_tab',
                    'label' => 'CTA Button Opens In New Tab',
                    'group_name' => 'cta',
                    'help_text' => 'Open the CTA link in a new tab.',
                    'field_type' => 'boolean',
                    'field_scope' => 'shared',
                    'default_value' => json_encode([
                        'value' => false,
                    ], JSON_UNESCAPED_UNICODE),
                    'options' => null,
                    'settings' => json_encode([]),
                    'schema' => null,
                    'is_required' => false,
                    'validation_rules' => json_encode([]),
                    'is_active' => true,
                    'sort_order' => 7,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
                [
                    'section_definition_id' => $definitionId,
                    'field_key' => 'background_image',
                    'label' => 'Background Image',
                    'group_name' => 'background',
                    'help_text' => 'Shared hero background image.',
                    'field_type' => 'media',
                    'field_scope' => 'shared',
                    'default_value' => null,
                    'options' => null,
                    'settings' => json_encode([]),
                    'schema' => null,
                    'is_required' => false,
                    'validation_rules' => json_encode([]),
                    'is_active' => true,
                    'sort_order' => 8,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
            ]);
        });
    }

    public function down(): void
    {
        // This reset intentionally has no automatic rollback because the
        // previous hosting_hero field contract is deprecated and should not be
        // restored implicitly.
    }
};
