<?php

namespace App\Services;

use App\Models\Template;

class TemplateService
{
    public static function getFrontendTemplates($filters = [])
    {
        $locale = app()->getLocale();

        $query = Template::with([
            'translations',
            'categoryTemplate.translations'
        ]);

        // تصفية حسب السعر
        if (!empty($filters['max_price']) && $filters['max_price'] > 0) {
            $query->where('price', '<=', $filters['max_price']);
        }

        // تصفية حسب التصنيف
        if (!empty($filters['category_id']) && $filters['category_id'] !== 'all') {
            $query->where('category_template_id', $filters['category_id']);
        }

        // ترتيب النتائج
        switch ($filters['sort_by'] ?? 'default') {
            case 'high':
                $query->orderByDesc('price');
                break;
            case 'low':
                $query->orderBy('price');
                break;
            default:
                $query->latest();
        }

        // تمرير الترجمة الصحيحة داخل الـ through
        return $query->paginate(9)->through(function ($template) use ($locale) {
            $translations = collect($template->translations);
            $translation = $translations->firstWhere('locale', $locale)
            ?? $translations->firstWhere('locale', 'ar')
            ?? $translations->first();
            $categoryTranslations = collect($template->categoryTemplate->translations ?? []);
            $categoryTranslation = $categoryTranslations->firstWhere('locale', $locale)
            ?? $categoryTranslations->firstWhere('locale', 'ar')
            ?? $categoryTranslations->first();
            $usedLocale = $translation?->locale ?? $locale;
            return (object) [
                'id' => $template->id,
                'image' => $template->image,
                'price' => $template->price,
                'discount_price' => $template->discount_price,
                'name' => $translation?->name ?? 'بدون ترجمة',
                'slug' => $translation?->slug ?? '',
                'description' => $translation?->description ?? '',
                'details' => $translation?->details ?? '',
                'category' => $categoryTranslation?->name ?? '',
                'fallbackNotice' => $usedLocale !== $locale,
            ];
        });
    }
}