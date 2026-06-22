<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneralSetting extends Model
{
    protected $table = 'general_settings';

    protected $fillable = [
        'site_title',
        'site_discretion',
        'logo',
        'logo_media_id',              // ADR-005 Wave 1
        'dark_logo',
        'dark_logo_media_id',         // ADR-005 Wave 1
        'sticky_logo',
        'sticky_logo_media_id',       // ADR-005 Wave 1
        'dark_sticky_logo',
        'dark_sticky_logo_media_id',  // ADR-005 Wave 1
        'admin_logo',
        'admin_logo_media_id',        // ADR-005 Wave 1
        'admin_dark_logo',
        'admin_dark_logo_media_id',   // ADR-005 Wave 1
        'favicon',
        'favicon_media_id',           // ADR-005 Wave 1
        'default_language',
        'active_header_variant',
        'active_footer_variant',
        'header_show_promo_bar',
        'header_is_sticky',
        'header_variant_settings',
        'footer_variant_settings',
        'footer_show_contact_banner',
        'footer_show_payment_methods',
        'contact_info',
        'social_links',
        'localized_content',
        'admin_brand_settings',   // Phase 1 — Admin Brand Theme
    ];

    protected $casts = [
        'header_show_promo_bar' => 'boolean',
        'header_is_sticky' => 'boolean',
        'header_variant_settings' => 'array',
        'footer_variant_settings' => 'array',
        'footer_show_contact_banner' => 'boolean',
        'footer_show_payment_methods' => 'boolean',
        'contact_info' => 'array',
        'social_links' => 'array',
        'localized_content' => 'array',
        'admin_brand_settings' => 'array',  // Phase 1 — Admin Brand Theme
    ];

    // ── ADR-005 Wave 1 Media Relations ─────────────────────────────────────

    public function logoMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_media_id');
    }

    public function darkLogoMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'dark_logo_media_id');
    }

    public function stickyLogoMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'sticky_logo_media_id');
    }

    public function darkStickyLogoMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'dark_sticky_logo_media_id');
    }

    public function adminLogoMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'admin_logo_media_id');
    }

    public function adminDarkLogoMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'admin_dark_logo_media_id');
    }

    public function faviconMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'favicon_media_id');
    }

    // ── ADR-005 Wave 1 Read Helpers ─────────────────────────────────────────
    // Use these when you want the best available path: FK relation first,
    // old path column as fallback.  Wave 2 will replace the old columns.

    public function resolvedLogoPath(): ?string
    {
        return $this->logoMedia?->file_path ?? $this->getRawOriginal('logo') ?? null;
    }

    public function resolvedDarkLogoPath(): ?string
    {
        return $this->darkLogoMedia?->file_path ?? $this->getRawOriginal('dark_logo') ?? null;
    }

    public function resolvedStickyLogoPath(): ?string
    {
        return $this->stickyLogoMedia?->file_path ?? $this->getRawOriginal('sticky_logo') ?? null;
    }

    public function resolvedDarkStickyLogoPath(): ?string
    {
        return $this->darkStickyLogoMedia?->file_path ?? $this->getRawOriginal('dark_sticky_logo') ?? null;
    }

    public function resolvedAdminLogoPath(): ?string
    {
        return $this->adminLogoMedia?->file_path ?? $this->getRawOriginal('admin_logo') ?? null;
    }

    public function resolvedAdminDarkLogoPath(): ?string
    {
        return $this->adminDarkLogoMedia?->file_path ?? $this->getRawOriginal('admin_dark_logo') ?? null;
    }

    public function resolvedFaviconPath(): ?string
    {
        return $this->faviconMedia?->file_path ?? $this->getRawOriginal('favicon') ?? null;
    }

    // ── Other Relations ─────────────────────────────────────────────────────

    public function language()
    {
        return $this->belongsTo(Language::class, 'default_language', 'id');
    }

    public function resolveLocalizedContent(string $field, string $default = ''): string
    {
        $localizedContent = is_array($this->localized_content ?? null)
            ? $this->localized_content
            : [];

        $value = $localizedContent[$field] ?? null;
        $fallbackValue = match ($field) {
            'site_title' => (string) ($this->getRawOriginal('site_title') ?? ''),
            'site_discretion' => (string) ($this->getRawOriginal('site_discretion') ?? ''),
            'contact_address' => is_array($this->contact_info) && !is_array($this->contact_info['address'] ?? null)
                ? (string) ($this->contact_info['address'] ?? '')
                : '',
            default => '',
        };

        return $this->resolveLocalizedValue($value, $fallbackValue !== '' ? $fallbackValue : $default);
    }

    public function getResolvedSiteTitleAttribute(): string
    {
        return $this->resolveLocalizedContent('site_title', config('app.name', 'Palgoals'));
    }

    public function getResolvedSiteDiscretionAttribute(): string
    {
        return $this->resolveLocalizedContent('site_discretion', '');
    }

    public function getResolvedContactInfoAttribute(): array
    {
        $contactInfo = is_array($this->contact_info ?? null) ? $this->contact_info : [];
        $contactInfo['address'] = $this->resolveLocalizedContent('contact_address', (string) ($contactInfo['address'] ?? ''));

        return $contactInfo;
    }

    protected function resolveLocalizedValue($value, string $default = ''): string
    {
        $currentLocale = strtolower((string) app()->getLocale());
        $fallbackLocale = strtolower((string) config('app.fallback_locale', 'en'));
        static $defaultLanguageCodeCache = [];

        $defaultLanguageCacheKey = (int) ($this->default_language ?? 0);
        if ($this->relationLoaded('language')) {
            $defaultLanguageCode = strtolower((string) ($this->language?->code ?? ''));
        } else {
            if (!array_key_exists($defaultLanguageCacheKey, $defaultLanguageCodeCache)) {
                $defaultLanguageCodeCache[$defaultLanguageCacheKey] = strtolower((string) (
                    $defaultLanguageCacheKey > 0
                        ? optional(Language::query()->find($defaultLanguageCacheKey))->code
                        : ''
                ));
            }

            $defaultLanguageCode = $defaultLanguageCodeCache[$defaultLanguageCacheKey];
        }

        if (is_array($value)) {
            $normalizedValues = [];
            foreach ($value as $langKey => $langValue) {
                $normalizedValues[strtolower((string) $langKey)] = $this->normalizeScalarLocalizedValue($langValue);
            }

            $localizedValue = $this->normalizeScalarLocalizedValue(
                $normalizedValues[$currentLocale]
                ?? ($defaultLanguageCode !== '' ? ($normalizedValues[$defaultLanguageCode] ?? null) : null)
                ?? $normalizedValues[$fallbackLocale]
                ?? ''
            );

            if ($localizedValue !== '') {
                return $localizedValue;
            }

            foreach ($normalizedValues as $candidate) {
                if ($candidate !== '') {
                    return $candidate;
                }
            }
        }

        $scalar = $this->normalizeScalarLocalizedValue($value);
        if ($scalar !== '') {
            return $scalar;
        }

        return $this->normalizeScalarLocalizedValue($default);
    }

    protected function normalizeScalarLocalizedValue($value): string
    {
        if (is_array($value)) {
            $normalized = '';

            array_walk_recursive($value, static function ($item) use (&$normalized): void {
                if ($normalized !== '') {
                    return;
                }

                if (is_scalar($item) || $item instanceof \Stringable) {
                    $candidate = trim((string) $item);
                    if ($candidate !== '') {
                        $normalized = $candidate;
                    }
                }
            });

            return $normalized;
        }

        if (is_scalar($value) || $value instanceof \Stringable) {
            return trim((string) $value);
        }

        return '';
    }
}
