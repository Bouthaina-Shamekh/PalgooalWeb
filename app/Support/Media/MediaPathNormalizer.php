<?php

namespace App\Support\Media;

use App\Models\Media;

/**
 * Unified media path normalizer for ADR-005 Wave 1.
 *
 * Replaces two divergent private implementations in HomeController and
 * AppearanceController with a single, statically callable authority.
 *
 * Responsibilities:
 *  - Trim whitespace
 *  - Remove leading slashes
 *  - Strip the `storage/` prefix that some write-paths prepend
 *  - Return null for empty strings or external URLs (not media paths)
 *  - Resolve numeric IDs → media.file_path
 *  - Resolve path strings → media.id (for backfill and dual-write)
 *
 * Out of scope (Wave 1):
 *  - services.icon (static theme assets — intentional Pattern B exception)
 *  - portfolios.images JSON array  (Wave 3)
 *  - header_variant_settings JSON  (Wave 3)
 */
class MediaPathNormalizer
{
    /**
     * Normalize a raw stored value to a clean `file_path` string suitable
     * for looking up a record in `media.file_path`.
     *
     * Returns null when:
     * - Value is null or empty after trimming
     * - Value is an external URL (http/https/protocol-relative) — these
     *   are never stored as file_path in the media table
     *
     * @param  mixed  $value
     */
    public static function normalize($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        // External URLs cannot be file_path values in the media table.
        if (
            str_starts_with($normalized, 'http://')  ||
            str_starts_with($normalized, 'https://') ||
            str_starts_with($normalized, '//')
        ) {
            return null;
        }

        // Remove leading slash (some paths are stored with one)
        $normalized = ltrim($normalized, '/');

        // Strip storage/ prefix added by some write-paths
        if (str_starts_with($normalized, 'storage/')) {
            $normalized = substr($normalized, strlen('storage/'));
        }

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * Resolve a raw stored value to a `media.id` integer.
     *
     * Resolution order:
     *  1. Numeric string → treated as an existing media.id (direct lookup)
     *  2. Path string    → normalize, then look up by file_path
     *  3. External URL   → null (cannot be in the media table)
     *  4. Empty / null   → null
     *
     * Returns null (orphan) when no media record can be found.
     *
     * @param  mixed  $value
     */
    public static function resolveToMediaId($value): ?int
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        // Numeric → treat as direct media.id
        if (ctype_digit($raw)) {
            $media = Media::find((int) $raw);
            return $media?->id;
        }

        // External URLs cannot be media records
        if (
            str_starts_with($raw, 'http://')  ||
            str_starts_with($raw, 'https://') ||
            str_starts_with($raw, '//')
        ) {
            return null;
        }

        // Path → normalize then query by file_path
        $path = static::normalize($raw);
        if ($path === null) {
            return null;
        }

        $media = Media::where('file_path', $path)->first();
        return $media?->id;
    }

    /**
     * Convenience: resolve multiple values to an array of media IDs.
     * Null entries (orphans) are included as null at the same index.
     *
     * @param  array<mixed>  $values
     * @return array<int|null>
     */
    public static function resolveMany(array $values): array
    {
        return array_map(
            fn ($v) => static::resolveToMediaId($v),
            $values
        );
    }
}
