<?php

namespace App\Services;

use App\Models\Template;

class TemplateService
{
    public static function getFrontendTemplates($filters = [])
    {
        $locale = app()->getLocale();

        $query = Template::with(['translations', 'categoryTemplate.translation']);

        // فلترة حسب السعر
        if (!empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        // فلترة حسب التصنيف
        if (!empty($filters['category_id']) && $filters['category_id'] !== 'all') {
            $query->where('category_template_id', $filters['category_id']);
        }

        // الترتيب
        if (!empty($filters['sort_by'])) {
            switch ($filters['sort_by']) {
                case 'high':
                    $query->orderByDesc('price');
                    break;
                case 'low':
                    $query->orderBy('price');
                    break;
                default:
                    $query->latest();
            }
        } else {
            $query->latest();
        }

        return $query->paginate(9)->through(function ($template) use ($locale) {
            $translation = $template->translations->where('locale', $locale)->first();
            return (object)[
                'id' => $template->id,
                'image' => $template->image,
                'price' => $template->price,
                'discount_price' => $template->discount_price,
                'name' => $translation?->name,
                'slug' => $translation?->slug,
                'description' => $translation?->description,
                'details' => $translation?->details,
                'category' => $template->categoryTemplate->translation?->name,
            ];
        });
    }
}
