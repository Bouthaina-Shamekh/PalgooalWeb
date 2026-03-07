<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    protected $table = 'general_settings';

    protected $fillable = [
        'site_title',
        'site_discretion',
        'logo',
        'dark_logo',
        'sticky_logo',
        'dark_sticky_logo',
        'admin_logo',
        'admin_dark_logo',
        'favicon',
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
    ];

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
                $normalizedValues[strtolower((string) $langKey)] = trim((string) $langValue);
            }

            $localizedValue = trim((string) (
                $normalizedValues[$currentLocale]
                ?? ($defaultLanguageCode !== '' ? ($normalizedValues[$defaultLanguageCode] ?? null) : null)
                ?? $normalizedValues[$fallbackLocale]
                ?? ''
            ));

            if ($localizedValue !== '') {
                return $localizedValue;
            }

            foreach ($normalizedValues as $candidate) {
                if ($candidate !== '') {
                    return $candidate;
                }
            }
        }

        $scalar = trim((string) $value);
        if ($scalar !== '') {
            return $scalar;
        }

        return trim($default);
    }
}
