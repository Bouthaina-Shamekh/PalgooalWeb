<?php

namespace App\Support\Sections;

use App\Models\Sections\SectionDefinition;

/**
 * Writes a SectionDefinition's blade_source to the correct path on disk.
 *
 * Security rules:
 *  - category and section_key must match /^[a-z0-9_-]+$/
 *  - resolved path must stay inside resource_path('views/front/sections/')
 *  - directory is created automatically if it doesn't exist
 */
class SectionTemplateFileWriter
{
    /** @var string Absolute base directory that file writes are restricted to */
    private string $baseDir;

    public function __construct()
    {
        $this->baseDir = resource_path('views/front/sections');
    }

    /**
     * Resolve the absolute disk path for a given SectionDefinition.
     * Returns null when category or key are invalid.
     */
    public function resolvedPath(SectionDefinition $definition): ?string
    {
        $category = SectionTemplateRegistry::normalizeCategory($definition->category);
        $key      = trim((string) $definition->section_key);

        if (
            $category === SectionTemplateRegistry::DEFAULT_CATEGORY && empty($definition->category)
            || ! preg_match(SectionTemplateRegistry::TEMPLATE_KEY_REGEX, $key)
        ) {
            return null;
        }

        return $this->baseDir . DIRECTORY_SEPARATOR
            . $category . DIRECTORY_SEPARATOR
            . $key . '.blade.php';
    }

    /**
     * Return the path relative to the project root for display purposes.
     * e.g. "resources/views/front/sections/hero/hero_main.blade.php"
     */
    public function displayPath(SectionDefinition $definition): string
    {
        $path = $this->resolvedPath($definition);

        if ($path === null) {
            return 'resources/views/front/sections/{category}/{key}.blade.php';
        }

        // Normalize to forward slashes then strip the project root prefix
        $normalizedPath = str_replace('\\', '/', $path);
        $normalizedBase = str_replace('\\', '/', rtrim(base_path(), '/\\')) . '/';

        return str_replace($normalizedBase, '', $normalizedPath);
    }

    /**
     * Determine the current file status for a definition.
     *
     * Returns:
     *  'missing'  — no file on disk (common before first write)
     *  'exists'   — file on disk, written via admin panel (blade_written_at set)
     *  'external' — file on disk, but blade_source is null (written outside admin)
     *  'invalid'  — category/key are invalid, cannot determine path
     */
    public function fileStatus(SectionDefinition $definition): string
    {
        $path = $this->resolvedPath($definition);

        if ($path === null) {
            return 'invalid';
        }

        $fileExists = file_exists($path);

        if (! $fileExists) {
            return 'missing';
        }

        if ($definition->blade_source === null) {
            return 'external';
        }

        return 'exists';
    }

    /**
     * Write blade_source to disk and update blade_written_at on success.
     *
     * Returns an array with:
     *  ['ok' => true,  'path' => '...']
     *  ['ok' => false, 'error' => '...']
     */
    public function write(SectionDefinition $definition): array
    {
        // Validate source content
        if (empty($definition->blade_source)) {
            return ['ok' => false, 'error' => 'blade_source is empty — nothing to write.'];
        }

        // Resolve and validate path
        $path = $this->resolvedPath($definition);

        if ($path === null) {
            return ['ok' => false, 'error' => 'Invalid category or template key — cannot resolve path.'];
        }

        // Extra safety: ensure resolved path is within the allowed base directory
        $realBase = realpath($this->baseDir);
        $realDir  = realpath(dirname($path)) ?: dirname($path);

        // Build what the real path would be once the file exists
        $normalizedPath = str_replace(['\\', '//'], ['/', '/'], $path);
        $normalizedBase = str_replace(['\\', '//'], ['/', '/'], $this->baseDir);

        if (! str_starts_with($normalizedPath, $normalizedBase)) {
            return ['ok' => false, 'error' => 'Path traversal detected — write refused.'];
        }

        // Create directory if needed
        $dir = dirname($path);
        if (! is_dir($dir)) {
            if (! mkdir($dir, 0755, true)) {
                return ['ok' => false, 'error' => "Could not create directory: {$dir}"];
            }
        }

        // Write file
        $bytesWritten = file_put_contents($path, $definition->blade_source);

        if ($bytesWritten === false) {
            return ['ok' => false, 'error' => "Failed to write file: {$path}"];
        }

        // Update timestamp
        $definition->blade_written_at = now();
        $definition->saveQuietly();

        return ['ok' => true, 'path' => $path];
    }

    /**
     * Delete the Blade file from disk for a given SectionDefinition.
     *
     * Returns:
     *  ['ok' => true,  'deleted' => true,  'path' => '...']   — file existed and was deleted
     *  ['ok' => true,  'deleted' => false, 'skipped' => '...'] — file not found or path invalid (safe to ignore)
     *  ['ok' => false, 'error' => '...']                       — file exists but could not be deleted
     */
    public function deleteFile(SectionDefinition $definition): array
    {
        $path = $this->resolvedPath($definition);

        if ($path === null) {
            return ['ok' => true, 'deleted' => false, 'skipped' => 'invalid category/key'];
        }

        if (! file_exists($path)) {
            return ['ok' => true, 'deleted' => false, 'skipped' => 'file not found on disk'];
        }

        if (unlink($path)) {
            return ['ok' => true, 'deleted' => true, 'path' => $path];
        }

        return ['ok' => false, 'error' => "Could not delete Blade file: {$path}"];
    }
}
