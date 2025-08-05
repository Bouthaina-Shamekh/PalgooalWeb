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

if (!empty($filters['max_price']) && $filters['max_price'] > 0) {
    $query->where('price', '<=', $filters['max_price']);
}

        if (!empty($filters['category_id']) && $filters['category_id'] !== 'all') {
            $query->where('category_template_id', $filters['category_id']);
        }

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

        return $query->paginate(9)->through(function ($template) use ($locale) {
            $translation = $template->translations->firstWhere('locale', $locale)
                ?? $template->translations->firstWhere('locale', 'ar')
                ?? $template->translations->first();

            $categoryTranslation = $template->categoryTemplate->translations->firstWhere('locale', $locale)
                ?? $template->categoryTemplate->translations->firstWhere('locale', 'ar')
                ?? $template->categoryTemplate->translations->first();

            $usedLocale = $translation?->locale;

            return (object) [
                'id' => $template->id,
                'image' => $template->image,
                'price' => $template->price,
                'discount_price' => $template->discount_price,
                'name' => $translation?->name ?? '',
                'slug' => $translation?->slug ?? '',
                'description' => $translation?->description ?? '',
                'details' => $translation?->details ?? [],
                'category' => $categoryTranslation?->name ?? '',
                'fallbackNotice' => $usedLocale !== $locale,
            ];
        });
    }
}
