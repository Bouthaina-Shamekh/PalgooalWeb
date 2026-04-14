<?php

namespace App\Support\Sections;

use App\Models\Media;
use Illuminate\Support\Collection;

/**
 * Frontend-safe media ID resolution for section template views.
 *
 * Accepts stored media IDs and returns public URLs.
 * Returns null (not an empty array) when a record is missing, so
 * Blade templates can use simple truthiness checks.
 *
 * This class is intentionally separate from SectionMediaPreviewBuilder,
 * which is admin/editor-only and returns arrays.
 */
class SectionFrontendMediaResolver
{
    /**
     * Resolve a single stored media ID to a public URL.
     *
     * Returns null when the value is not a valid ID or the record is missing.
     * Safe to call with null, empty string, or non-numeric values.
     *
     * Usage (scalar background image):
     *   $bgUrl = SectionFrontendMediaResolver::resolve($data['background_image'] ?? null);
     */
    public static function resolve(mixed $id): ?string
    {
        if (! is_numeric($id) || (int) $id <= 0) {
            return null;
        }

        $media = Media::find((int) $id);

        return ($media && $media->url) ? (string) $media->url : null;
    }

    /**
     * Eagerly resolve a list of media IDs to a [id => url|null] map.
     *
     * Deduplicates IDs before querying. Filters out non-numeric values silently.
     * Use this before loops to avoid N+1 queries on repeater icon_media fields.
     *
     * Usage (repeater items with icon_media):
     *   $ids = collect($items)->pluck('icon_media');
     *   $resolved = SectionFrontendMediaResolver::resolveMany($ids);
     *   // then: $url = $resolved[$item['icon_media']] ?? null;
     *
     * @param  iterable<mixed>  $ids
     * @return array<int, string|null>  keyed by integer media ID
     */
    public static function resolveMany(iterable $ids): array
    {
        $uniqueIds = Collection::make($ids)
            ->filter(fn($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($uniqueIds->isEmpty()) {
            return [];
        }

        $mediaRecords = Media::whereIn('id', $uniqueIds->all())
            ->get(['id', 'url'])
            ->keyBy('id');

        $resolved = [];

        foreach ($uniqueIds as $id) {
            $record = $mediaRecords->get($id);
            $resolved[$id] = ($record && $record->url) ? (string) $record->url : null;
        }

        return $resolved;
    }
}
