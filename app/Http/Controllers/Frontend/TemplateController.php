<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Template;

class TemplateController extends Controller
{
    public function show(string $slug)
    {
        $locale = app()->getLocale();

        $query = Template::with(['translations','categoryTemplate.translations'])
            ->whereHas('translations', fn($q) => $q->where('slug',$slug)->where('locale',$locale));

        $template = $query->first();

        // fallback للّغة العربية لو ما لقي باللّغة الحالية
        if (!$template && $locale !== 'ar') {
            $template = Template::with(['translations','categoryTemplate.translations'])
                ->whereHas('translations', fn($q)=> $q->where('slug',$slug)->where('locale','ar'))
                ->first();

            if ($template) {
                $arabicSlug = optional($template->translations->firstWhere('locale','ar'))->slug;
                if ($arabicSlug && $slug !== $arabicSlug) {
                    return redirect()->route('template.show', $arabicSlug);
                }
            }
        }

        abort_if(!$template, 404);

        $translation = $template->translations->firstWhere('locale',$locale)
            ?? $template->translations->firstWhere('locale','ar');

        // جهّز المراجعات المعتمدة + علاقاتها
        $template->load([
            'reviews' => fn($q) => $q->approved()->latest()->take(10),
            'reviews.client:id,first_name,last_name,avatar',
            'reviews.user:id,name',
        ]);

        // (اختياري) لو عندك دالة avgRating() على الموديل، ما تحتاج التالي.
        // أما لو ما عندكها وتحتاج المتوسط داخل الـ Blade، تقدر ترسله هيك:
        // $avgRating = round((float) $template->reviews()->approved()->avg('rating'), 1);

        return view('tamplate.template-show', compact('template','translation'));
        // أو: return view('tamplate.template-show', compact('template','translation','avgRating'));
    }

    public function preview(string $slug)
    {
        $locale = app()->getLocale();

        $template = Template::with('translations')
            ->whereHas('translations', fn($q)=> $q->where('slug',$slug)->where('locale',$locale))
            ->first();

        if (!$template && $locale !== 'ar') {
            $template = Template::with('translations')
                ->whereHas('translations', fn($q)=> $q->where('slug',$slug)->where('locale','ar'))
                ->first();
        }

        abort_if(!$template, 404);

        $translation = $template->translations->firstWhere('locale',$locale)
            ?? $template->translations->firstWhere('locale','ar');

        $previewUrl = $translation?->preview_url;
        abort_if(empty($previewUrl), 404);

        $previewHost = strtolower(parse_url($previewUrl, PHP_URL_HOST) ?? '');
        $appHost     = strtolower(parse_url(config('app.url'), PHP_URL_HOST) ?? request()->getHost());

        $baseDomain = 'palgoals.com';
        $sameOrigin = $previewHost === $appHost;
        $isSubdomainOfYou = str_ends_with($previewHost, '.'.$baseDomain) || $previewHost === $baseDomain;
        $embedAllowed = $sameOrigin || $isSubdomainOfYou;

        return view('tamplate.view-templat', compact('template','translation','previewUrl','embedAllowed'));
    }
}
