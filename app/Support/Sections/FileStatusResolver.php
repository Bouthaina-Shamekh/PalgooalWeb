<?php

namespace App\Support\Sections;

use App\Models\Sections\SectionDefinition;

/**
 * Resolves the file status AND sync status of a SectionDefinition's Blade template.
 *
 * Uses `blade_written_at` as the authoritative Sync Marker, per:
 * docs/BLADE_SOURCE_OF_TRUTH_ADR.md
 *
 * FILE STATUS values:
 *  'missing'   — no file on disk; never been published
 *  'published' — file exists AND blade_written_at is set   (system wrote it)
 *  'external'  — file exists BUT blade_written_at is null  (written outside admin)
 *  'invalid'   — category or section_key are invalid; path cannot be resolved
 *
 * SYNC STATUS values (Phase 4 — Out Of Sync Detection):
 *  'unknown'         — blade_hash is null (never been written by system)
 *  'in_sync'         — sha256(blade_source) === blade_hash AND no external mtime change
 *  'out_of_sync'     — sha256(blade_source) !== blade_hash (Monaco edited, not published)
 *  'external_change' — filemtime > blade_written_at AND blade_source unchanged (disk edited externally)
 *
 * Performance: NO file_get_contents() on disk. Uses:
 *  - hash('sha256', $blade_source) — in-memory string hash, ~0.1ms
 *  - filemtime($path)              — single stat() syscall, ~0.01ms
 *
 * @see docs/OUT_OF_SYNC_DETECTION_ARCHITECTURE.md
 */
class FileStatusResolver
{
    public function __construct(
        private SectionTemplateFileWriter $writer,
    ) {}

    /**
     * Resolve the full status descriptor for a SectionDefinition.
     *
     * @return array{
     *     status:       'missing'|'published'|'external'|'invalid',
     *     label:        string,
     *     color:        'gray'|'green'|'orange'|'red',
     *     icon:         string,
     *     view_name:    string|null,
     *     display_path: string,
     *     sync_status:  'unknown'|'in_sync'|'out_of_sync'|'external_change',
     *     sync_color:   'gray'|'green'|'yellow'|'orange',
     *     sync_icon:    string,
     * }
     */
    public function resolve(SectionDefinition $definition): array
    {
        $resolvedPath = $this->writer->resolvedPath($definition);
        $displayPath  = $this->writer->displayPath($definition);
        $viewName     = $this->conventionViewName($definition);

        // Invalid: category or key cannot produce a valid path
        if ($resolvedPath === null) {
            return array_merge($this->invalidStatus($displayPath), $this->unknownSync());
        }

        // Missing: no file on disk
        if (! file_exists($resolvedPath)) {
            return array_merge($this->missingStatus($viewName, $displayPath), $this->unknownSync());
        }

        // Compute sync status for files that exist on disk
        $syncStatus = $this->resolveSyncStatus($definition, $resolvedPath);

        // Published: file exists AND blade_written_at is set (admin panel wrote it)
        if ($definition->blade_written_at !== null) {
            return array_merge($this->publishedStatus($viewName, $displayPath), $syncStatus);
        }

        // External: file exists but blade_written_at is null (written outside admin)
        return array_merge($this->externalStatus($viewName, $displayPath), $syncStatus);
    }

    /**
     * Return just the file status string. Convenience wrapper around resolve().
     *
     * @return 'missing'|'published'|'external'|'invalid'
     */
    public function status(SectionDefinition $definition): string
    {
        return $this->resolve($definition)['status'];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // File Status Factories
    // ─────────────────────────────────────────────────────────────────────────

    private function invalidStatus(string $displayPath): array
    {
        return [
            'status'       => 'invalid',
            'label'        => 'Invalid',
            'color'        => 'red',
            'icon'         => 'ti-ban',
            'view_name'    => null,
            'display_path' => $displayPath,
        ];
    }

    private function missingStatus(?string $viewName, string $displayPath): array
    {
        return [
            'status'       => 'missing',
            'label'        => 'Missing',
            'color'        => 'gray',
            'icon'         => 'ti-circle-dashed',
            'view_name'    => $viewName,
            'display_path' => $displayPath,
        ];
    }

    private function publishedStatus(?string $viewName, string $displayPath): array
    {
        return [
            'status'       => 'published',
            'label'        => 'Published',
            'color'        => 'green',
            'icon'         => 'ti-circle-check-filled',
            'view_name'    => $viewName,
            'display_path' => $displayPath,
        ];
    }

    private function externalStatus(?string $viewName, string $displayPath): array
    {
        return [
            'status'       => 'external',
            'label'        => 'External',
            'color'        => 'orange',
            'icon'         => 'ti-alert-triangle-filled',
            'view_name'    => $viewName,
            'display_path' => $displayPath,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sync Status Resolution (Phase 4)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Compute sync status between blade_source (Monaco) and the disk file.
     *
     * Priority:
     *   1. No blade_hash in DB → 'unknown'  (never been written by system)
     *   2. sha256(blade_source) ≠ blade_hash → 'out_of_sync'  (Monaco was edited since last Write)
     *   3. filemtime > blade_written_at → 'external_change'  (disk changed after last Write)
     *   4. Everything matches → 'in_sync'
     *
     * PERFORMANCE: No file_get_contents(). Uses in-memory hash + single stat() syscall.
     */
    private function resolveSyncStatus(SectionDefinition $definition, string $resolvedPath): array
    {
        // Case 1: Never written by system — no hash fingerprint available
        if ($definition->blade_hash === null) {
            return $this->unknownSync();
        }

        // Case 2: Monaco was edited after last Write — blade_source hash differs from stored hash
        $currentBladeHash = hash('sha256', $definition->blade_source ?? '');
        if ($currentBladeHash !== $definition->blade_hash) {
            return [
                'sync_status' => 'out_of_sync',
                'sync_color'  => 'yellow',
                'sync_icon'   => 'ti-alert-circle-filled',
            ];
        }

        // Case 3: Disk file was modified after last Write (checked via mtime — no content read)
        // Only meaningful when we have a blade_written_at timestamp to compare against
        if (
            $definition->blade_written_at !== null &&
            file_exists($resolvedPath) &&
            @filemtime($resolvedPath) > $definition->blade_written_at->timestamp
        ) {
            return [
                'sync_status' => 'external_change',
                'sync_color'  => 'orange',
                'sync_icon'   => 'ti-refresh-alert',
            ];
        }

        // Case 4: All checks pass — fully in sync
        return [
            'sync_status' => 'in_sync',
            'sync_color'  => 'green',
            'sync_icon'   => 'ti-circle-check',
        ];
    }

    /** Default sync status when no fingerprint is available. */
    private function unknownSync(): array
    {
        return [
            'sync_status' => 'unknown',
            'sync_color'  => 'gray',
            'sync_icon'   => 'ti-circle-dashed',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build the convention Blade view name for this definition.
     * e.g. "front.sections.content.content_showcase"
     * Returns null when the key is empty or invalid.
     */
    private function conventionViewName(SectionDefinition $definition): ?string
    {
        $category = SectionTemplateRegistry::normalizeCategory($definition->category);
        $key      = trim((string) $definition->section_key);

        if (empty($key) || ! preg_match(SectionTemplateRegistry::TEMPLATE_KEY_REGEX, $key)) {
            return null;
        }

        return "front.sections.{$category}.{$key}";
    }
}
