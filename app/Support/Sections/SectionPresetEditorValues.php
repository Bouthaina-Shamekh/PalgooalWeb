<?php

namespace App\Support\Sections;

/**
 * Typed accessor for custom preset editor values in Blade views.
 *
 * Eliminates the repeated two-line locale/values setup and the
 * inconsistent null-coalescing patterns across preset editor partials.
 *
 * Usage in a custom preset Blade:
 *
 *   $pv = \App\Support\Sections\SectionPresetEditorValues::for($customPresetEditor, $code);
 *   $titleValue             = $pv->scalar('titleValue');
 *   $featureItems           = $pv->items('featureItems');
 *   $backgroundImageValue   = $pv->mediaId('backgroundImageValue');
 *   $backgroundImagePreviewUrls = $pv->items('backgroundImagePreviewUrls');
 */
class SectionPresetEditorValues
{
    protected function __construct(protected readonly array $values) {}

    /**
     * Build an accessor from the current locale slice of the editor payload.
     *
     * Safe when $code is missing from the locales map — returns an empty accessor.
     */
    public static function for(array $customPresetEditor, string $code): self
    {
        $locale = $customPresetEditor['locales'][$code] ?? null;
        $values = is_array($locale['values'] ?? null) ? $locale['values'] : [];

        return new self($values);
    }

    /**
     * Read a scalar string value.
     *
     * Returns $default when the key is absent or the stored value is not scalar.
     */
    public function scalar(string $key, string $default = ''): string
    {
        $value = $this->values[$key] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * Read an array value — covers repeater item lists and preview URL arrays.
     *
     * Returns [] when the key is absent or the stored value is not an array.
     */
    public function items(string $key): array
    {
        return is_array($this->values[$key] ?? null) ? $this->values[$key] : [];
    }

    /**
     * Read a raw media ID value.
     *
     * Returns null when absent. The stored value may be an integer, a numeric
     * string, or null — callers decide how to use it (admin preview, frontend
     * resolution, etc.).
     */
    public function mediaId(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }
}
