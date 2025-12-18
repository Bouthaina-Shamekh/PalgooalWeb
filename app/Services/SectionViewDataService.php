<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Section;

class SectionViewDataService
{
    /**
     * رجّع بيانات الـ Hero (hero_default) كـ $data للـ Blade component
     */
    public function heroDefault(Page $page, ?string $locale = null): array
    {
        $locale = $locale ?: app()->getLocale();

        $section = Section::with(['translations' => function ($q) use ($locale) {
            $q->where('locale', $locale);
        }])
            ->where('page_id', $page->id)
            ->where('type', 'hero_default')
            ->first();

        $content = $section?->translations->first()?->content ?? [];

        $title        = $content['title'] ?? 'عنوان غير متوفر';
        $subtitle     = $content['subtitle'] ?? '';
        $primaryLabel = data_get($content, 'primary_button.label') ?? __('ابدأ الآن');
        $primaryUrl   = data_get($content, 'primary_button.url')   ?? '#';
        $secondaryLabel = data_get($content, 'secondary_button.label') ?? __('استعرض القوالب');
        $secondaryUrl   = data_get($content, 'secondary_button.url')   ?? '#';

        // ✅ نرجّع المفاتيح بطريقة تناسب الـ hero.blade.php الحالي
        return [
            'title'    => $title,
            'subtitle' => $subtitle,

            // نفس الأسماء اللي حاطهم في الـ hero.blade.php
            'button_url-1'  => $primaryUrl,
            'button_text-1' => $primaryLabel,
            'button_url-2'  => $secondaryUrl,
            'button_text-2' => $secondaryLabel,

            // ونعطي كمان المفاتيح الاحتياطية لو استخدمناها لاحقًا
            'primary_button_url'   => $primaryUrl,
            'primary_button_text'  => $primaryLabel,
            'secondary_button_url' => $secondaryUrl,
            'secondary_button_text' => $secondaryLabel,
        ];
    }
}
