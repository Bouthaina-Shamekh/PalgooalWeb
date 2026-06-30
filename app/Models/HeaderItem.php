<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HeaderItem extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'header_id',
        'type',
        'page_id',
        'children',
        'order',
    ];

    protected $casts = [
        'children' => 'array',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(Header::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(HeaderItemTranslation::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    // لجلب الترجمة حسب اللغة الحالية
    public function getLabelAttribute(): string
    {
        $locale = app()->getLocale();

        if ($this->relationLoaded('translations')) {
            return $this->translations->where('locale', $locale)->first()?->label
                ?? $this->translations->first()?->label
                ?? '';
        }

        // fallback إذا لم تكن العلاقة محملة
        $translation = $this->translations()->where('locale', $locale)->first()
            ?? $this->translations()->first();
        return $translation?->label ?? '';
    }

    // لجلب الرابط حسب اللغة الحالية
    public function getUrlAttribute(): string
    {
        // إذا كان مربوط بصفحة، أحضر رابط الصفحة
        if ($this->page_id && $this->relationLoaded('page') && $this->page) {
            $pageTranslation = $this->resolvePageTranslation($this->page);
            $slug = trim((string) ($pageTranslation?->slug ?? ''));

            return $slug !== '' ? '/' . ltrim($slug, '/') : '#';
        }

        // أو أحضر الرابط من الترجمات
        $locale = app()->getLocale();

        if ($this->relationLoaded('translations')) {
            return $this->translations->where('locale', $locale)->first()?->url
                ?? $this->translations->first()?->url
                ?? '';
        }

        // fallback إذا لم تكن العلاقة محملة
        $translation = $this->translations()->where('locale', $locale)->first()
            ?? $this->translations()->first();
        return $translation?->url ?? '';
    }

    // دالة للحصول على ترجمة بلغة محددة
    public function translation($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return $this->translations->where('locale', $locale)->first()
            ?? $this->translations->first();
    }

    // accessor للعناصر الفرعية مع معالجة الصفحات المربوطة
    public function getProcessedChildrenAttribute()
    {
        if (!$this->children || $this->type !== 'dropdown') {
            return [];
        }

        $processedChildren = [];
        foreach ($this->children as $child) {
            $childData = $child;

            // إذا كان العنصر الفرعي مربوط بصفحة، جلب بيانات الصفحة
            if (($child['type'] ?? 'link') === 'page' && !empty($child['page_id'])) {
                $page = Page::with('translations')->find($child['page_id']);
                if ($page) {
                    $pageTranslation = $this->resolvePageTranslation($page);
                    $slug = trim((string) ($pageTranslation?->slug ?? ''));
                    if ($slug !== '') {
                        $title = trim((string) ($pageTranslation?->title ?? ''));
                        $childData['current_label'] = $title !== '' ? $title : '-';
                        $childData['current_url'] = '/' . ltrim($slug, '/');
                    }
                }
            } else {
                // للروابط العادية، جلب الترجمة الحالية
                $locale = strtolower((string) app()->getLocale());
                $fallbackLocale = strtolower((string) config('app.fallback_locale', 'en'));

                $labels = [];
                if (is_array($child['labels'] ?? null)) {
                    foreach ($child['labels'] as $langKey => $langValue) {
                        $labels[strtolower((string) $langKey)] = is_array($langValue) ? $langValue : [];
                    }
                }

                $currentLabel = trim((string) (
                    $labels[$locale]['label'] ??
                    $labels[$fallbackLocale]['label'] ??
                    $child['label'][$locale] ??
                    $child['label'][$fallbackLocale] ??
                    ''
                ));

                if ($currentLabel === '') {
                    foreach ($labels as $candidate) {
                        $candidateLabel = trim((string) ($candidate['label'] ?? ''));
                        if ($candidateLabel !== '') {
                            $currentLabel = $candidateLabel;
                            break;
                        }
                    }
                }

                $currentUrl = trim((string) (
                    $labels[$locale]['url'] ??
                    $labels[$fallbackLocale]['url'] ??
                    $child['url'] ??
                    ''
                ));

                if ($currentUrl === '') {
                    foreach ($labels as $candidate) {
                        $candidateUrl = trim((string) ($candidate['url'] ?? ''));
                        if ($candidateUrl !== '') {
                            $currentUrl = $candidateUrl;
                            break;
                        }
                    }
                }

                $childData['current_label'] = $currentLabel !== '' ? $currentLabel : '-';
                $childData['current_url'] = $currentUrl !== '' ? $currentUrl : '#';
            }

            $processedChildren[] = $childData;
        }

        return $processedChildren;
    }

    protected function resolvePageTranslation(Page $page, ?string $locale = null): ?PageTranslation
    {
        $locale = strtolower((string) ($locale ?? app()->getLocale()));
        $fallbackLocale = strtolower((string) config('app.fallback_locale', 'en'));

        if ($page->relationLoaded('translations')) {
            return $this->firstUsablePageTranslation($page->translations, $locale)
                ?? $this->firstUsablePageTranslation($page->translations, $fallbackLocale)
                ?? $page->translations->first(fn ($translation) => trim((string) ($translation->slug ?? '')) !== '')
                ?? $page->translations->first();
        }

        return $page->translations()
            ->where('locale', $locale)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->first()
            ?? $page->translations()
                ->where('locale', $fallbackLocale)
                ->whereNotNull('slug')
                ->where('slug', '!=', '')
                ->first()
            ?? $page->translations()
                ->whereNotNull('slug')
                ->where('slug', '!=', '')
                ->first()
            ?? $page->translations()->first();
    }

    protected function firstUsablePageTranslation($translations, string $locale): ?PageTranslation
    {
        return $translations->first(
            fn ($translation) => strtolower((string) ($translation->locale ?? '')) === $locale
                && trim((string) ($translation->slug ?? '')) !== ''
        );
    }
}
