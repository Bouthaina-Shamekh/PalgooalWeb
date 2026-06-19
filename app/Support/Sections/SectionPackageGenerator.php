<?php

namespace App\Support\Sections;

use App\Models\Sections\SectionDefinition;
use App\Models\Sections\SectionDefinitionField;
use App\Models\Sections\Template as SectionTemplate;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * SectionPackageGenerator — Phase 7 Orchestration Service
 *
 * Turns a SectionTemplateLibrary key into a fully-wired Section Package
 * (Definition + Fields + Generated Blade + Written File) in a single call.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * SERVICE RESPONSIBILITIES
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *  This class is an ORCHESTRATOR ONLY. It delegates every step to an
 *  existing service and adds NO business logic of its own:
 *
 *  SectionTemplateLibrary  → template definition + resolved fields
 *  ComponentLibrary        → (called implicitly via resolveTemplateFields)
 *  BladeGenerator          → scaffold generation
 *  SectionTemplateFileWriter → path resolution + disk write
 *  FileStatusResolver      → final status descriptor
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WORKFLOW (10 steps)
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *  Step 1  — Validate template_key exists in SectionTemplateLibrary
 *  Step 2  — Guard: section_key must not already exist in DB
 *  Step 3  — Guard: resolvedPath() must return a valid value
 *  Step 4  — DB Transaction: Create SectionDefinition + Fields
 *  Step 5  — BladeGenerator::generate() → scaffold content
 *  Step 6  — Fallback to blade_stub if generate() returns empty
 *  Step 7  — Persist blade_source via saveQuietly() (no events)
 *  Step 8  — SectionTemplateFileWriter::write() → disk
 *  Step 9  — FileStatusResolver::resolve() → final status descriptor
 *  Step 10 — Return Result DTO
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * STATUS VALUES
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *  'ready'           — Definition + Fields + Blade file all created
 *  'definition_only' — Definition + Fields created; Blade file NOT written
 *                      (file already existed, or write failed)
 *  'failed'          — DB transaction failed; nothing was created
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * RESULT DTO (array)
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *  definition_id    int|null
 *  section_key      string
 *  view_name        string|null
 *  blade_path       string       — display path (relative to project root)
 *  fields_count     int
 *  components_count int
 *  component_names  string[]
 *  status           'ready'|'definition_only'|'failed'
 *  warnings         string[]
 *  errors           string[]
 *
 * @see docs/SECTION_PACKAGE_GENERATOR_ARCHITECTURE.md
 */
class SectionPackageGenerator
{
    public function __construct(
        private readonly SectionTemplateFileWriter $writer,
        private readonly FileStatusResolver        $resolver,
    ) {}

    /**
     * Generate a full Section Package from a library template key.
     *
     * @param  string  $templateKey  Key from SectionTemplateLibrary::keys()
     * @return array                 Result DTO
     */
    public function generate(string $templateKey): array
    {
        $warnings = [];
        $errors   = [];

        // ── Step 1: Validate template_key ────────────────────────────────
        $template = SectionTemplateLibrary::get($templateKey);

        if (! is_array($template)) {
            return $this->failedResult(
                section_key: $templateKey,
                errors: ["Template key '{$templateKey}' not found in SectionTemplateLibrary."],
            );
        }

        $defConfig  = $template['definition'];
        $sectionKey = (string) ($defConfig['section_key'] ?? '');

        // ── Step 2: Guard — section_key uniqueness ───────────────────────
        if (SectionDefinition::where('section_key', $sectionKey)->exists()) {
            return $this->failedResult(
                section_key: $sectionKey,
                errors: ["Section key '{$sectionKey}' already exists in the database."],
            );
        }

        // ── Step 3: Guard — path must be resolvable ──────────────────────
        // We need a temporary definition instance to call resolvedPath().
        // We construct an unsaved instance to validate path without touching DB.
        $tempDef = new SectionDefinition([
            'section_key' => $sectionKey,
            'category'    => $defConfig['category'] ?? null,
        ]);

        $resolvedPath = $this->writer->resolvedPath($tempDef);
        $displayPath  = $this->writer->displayPath($tempDef);

        if ($resolvedPath === null) {
            return $this->failedResult(
                section_key: $sectionKey,
                blade_path: $displayPath,
                errors: ["Invalid category or section_key — cannot resolve blade path."],
            );
        }

        // ── Step 3b: Phase 1 — No Force Overwrite ────────────────────────
        // If a Blade file already exists on disk, return definition_only without writing.
        $fileAlreadyExists = file_exists($resolvedPath);

        // ── Step 4: DB Transaction — Create Definition + Fields ──────────
        /** @var SectionDefinition|null $definition */
        $definition  = null;
        $fieldsCount = 0;

        try {
            DB::transaction(function () use (
                $defConfig, $template, $templateKey, &$definition, &$fieldsCount
            ): void {
                // 4a. Create SectionDefinition (blade_source = null for now; set after generate)
                $definition = SectionDefinition::create([
                    'section_key'  => $defConfig['section_key'],
                    'label'        => $defConfig['label'],
                    'description'  => $defConfig['description'] ?? null,
                    'category'     => $defConfig['category'] ?? null,
                    'editor_mode'  => SectionDefinition::EDITOR_MODE_DYNAMIC,
                    'blade_source' => null,
                    'is_active'    => (bool) ($defConfig['is_active'] ?? true),
                    'is_visible'   => (bool) ($defConfig['is_visible'] ?? true),
                    'sort_order'   => (int) ($defConfig['sort_order'] ?? 0),
                ]);

                // 4b. Resolve fields via SectionTemplateLibrary (handles v1/v2)
                $resolvedFields = SectionTemplateLibrary::resolveTemplateFields($templateKey);

                foreach ($resolvedFields as $fieldDef) {
                    $fieldKey = (string) ($fieldDef['field_key'] ?? '');
                    if ($fieldKey === '') {
                        continue;
                    }

                    // Normalize options: array of {value,label} pairs → pipe-delimited string
                    $options = null;
                    if (! empty($fieldDef['options'])) {
                        $rawOptions = $fieldDef['options'];
                        if (is_array($rawOptions)) {
                            $options = implode('|', array_map(
                                fn($o) => ($o['value'] ?? '') . '|' . ($o['label'] ?? ''),
                                $rawOptions,
                            ));
                        } else {
                            $options = (string) $rawOptions;
                        }
                    }

                    $definition->fields()->create([
                        'field_key'   => $fieldKey,
                        'label'       => $fieldDef['label'] ?? $fieldKey,
                        'field_type'  => $fieldDef['field_type'] ?? SectionDefinitionField::FIELD_TYPE_TEXT,
                        'field_scope' => $fieldDef['field_scope'] ?? SectionDefinitionField::FIELD_SCOPE_TRANSLATABLE,
                        'is_required' => (bool) ($fieldDef['is_required'] ?? false),
                        'is_active'   => true,
                        'sort_order'  => (int) ($fieldDef['sort_order'] ?? 0),
                        'schema'      => $fieldDef['schema'] ?? null,
                        'options'     => $options,
                    ]);

                    $fieldsCount++;
                }

                // 4c. Create (or find) the SectionTemplate registry entry.
                //
                // template_key = section_key by convention for library-generated
                // definitions. This is the value returned by primaryTemplateKey()
                // and consumed by SectionDefinitionRuntimeResolver::hasPrimaryTemplate()
                // to gate frontend rendering. Without this record the section will
                // never render, even if the Blade file exists on disk.
                $sectionTemplate = SectionTemplate::firstOrCreate(
                    ['template_key' => $definition->section_key],
                    [
                        'label'      => $definition->label,
                        'category'   => $definition->category,
                        'is_active'  => true,
                        'is_visible' => true,
                        'sort_order' => 0,
                    ],
                );

                // 4d. Attach to definition via pivot (sort_order = 0 = primary).
                // sync() replaces any existing pivot entries — safe on first create.
                $definition->templates()->sync([
                    $sectionTemplate->id => ['sort_order' => 0],
                ]);
            });
        } catch (Throwable $e) {
            report($e);

            return $this->failedResult(
                section_key: $sectionKey,
                blade_path: $displayPath,
                errors: ['Database transaction failed: ' . $e->getMessage()],
            );
        }

        if (! $definition instanceof SectionDefinition) {
            return $this->failedResult(
                section_key: $sectionKey,
                blade_path: $displayPath,
                errors: ['Definition was not created — transaction may have silently failed.'],
            );
        }

        // ── Step 5: Generate scaffold from field definitions ─────────────
        // Reload fields from DB so BladeGenerator can read them via the relation.
        $definition->load('fields');
        $generator     = new BladeGenerator();
        $generatedBlade = $generator->generate($definition);
        $stats          = $generator->stats($definition);

        // ── Step 6: Fallback to blade_stub ───────────────────────────────
        if (trim($generatedBlade) === '' && ! empty($template['blade_stub'])) {
            $generatedBlade = $template['blade_stub'];
            $warnings[]     = 'BladeGenerator returned empty scaffold — using blade_stub as fallback.';
        }

        // ── Step 7: Persist blade_source ─────────────────────────────────
        $definition->blade_source = $generatedBlade ?: null;
        $definition->saveQuietly();

        // ── Handle case: file already exists (Phase 1 — no overwrite) ────
        if ($fileAlreadyExists) {
            $warnings[] = "Blade file already exists at '{$displayPath}' — skipping write (Phase 1 no-overwrite policy).";

            return $this->buildResult(
                definition:       $definition,
                displayPath:      $displayPath,
                fieldsCount:      $fieldsCount,
                stats:            $stats,
                status:           'definition_only',
                warnings:         $warnings,
                errors:           $errors,
            );
        }

        // ── Step 8: Write to disk ─────────────────────────────────────────
        if (! $definition->blade_source) {
            $warnings[] = 'blade_source is empty — skipping disk write.';

            return $this->buildResult(
                definition:       $definition,
                displayPath:      $displayPath,
                fieldsCount:      $fieldsCount,
                stats:            $stats,
                status:           'definition_only',
                warnings:         $warnings,
                errors:           $errors,
            );
        }

        $writeResult = $this->writer->write($definition);

        if (! $writeResult['ok']) {
            $errors[] = 'Blade file write failed: ' . ($writeResult['error'] ?? 'unknown error');

            return $this->buildResult(
                definition:       $definition,
                displayPath:      $displayPath,
                fieldsCount:      $fieldsCount,
                stats:            $stats,
                status:           'definition_only',
                warnings:         $warnings,
                errors:           $errors,
            );
        }

        // ── Step 9: Resolve final file status ────────────────────────────
        // Reload after write() updated blade_written_at on the model.
        $definition->refresh();
        $fileStatus = $this->resolver->resolve($definition);

        // ── Step 10: Return ready Result ─────────────────────────────────
        return $this->buildResult(
            definition:       $definition,
            displayPath:      $displayPath,
            fieldsCount:      $fieldsCount,
            stats:            $stats,
            status:           'ready',
            warnings:         $warnings,
            errors:           $errors,
            viewName:         $fileStatus['view_name'],
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private Result Builders
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build a complete Result DTO from a successfully created definition.
     */
    private function buildResult(
        SectionDefinition $definition,
        string $displayPath,
        int $fieldsCount,
        array $stats,
        string $status,
        array $warnings = [],
        array $errors   = [],
        ?string $viewName = null,
    ): array {
        return [
            'definition_id'   => $definition->id,
            'section_key'     => $definition->section_key,
            'view_name'       => $viewName ?? $this->conventionViewName($definition),
            'blade_path'      => $displayPath,
            'fields_count'    => $fieldsCount,
            'components_count' => (int) ($stats['components'] ?? 0),
            'component_names'  => (array) ($stats['component_names'] ?? []),
            'status'           => $status,
            'warnings'         => $warnings,
            'errors'           => $errors,
        ];
    }

    /**
     * Build a Result DTO for early-exit failure (before Definition was created).
     */
    private function failedResult(
        string $section_key,
        string $blade_path = '',
        array $errors = [],
    ): array {
        return [
            'definition_id'    => null,
            'section_key'      => $section_key,
            'view_name'        => null,
            'blade_path'       => $blade_path,
            'fields_count'     => 0,
            'components_count' => 0,
            'component_names'  => [],
            'status'           => 'failed',
            'warnings'         => [],
            'errors'           => $errors,
        ];
    }

    /**
     * Derive the convention-based Blade view name for a definition.
     * Mirrors the logic in FileStatusResolver::conventionViewName().
     */
    private function conventionViewName(SectionDefinition $definition): ?string
    {
        $category = SectionTemplateRegistry::normalizeCategory($definition->category);
        $key      = trim((string) $definition->section_key);

        if ($key === '') {
            return null;
        }

        return "front.sections.{$category}.{$key}";
    }
}
