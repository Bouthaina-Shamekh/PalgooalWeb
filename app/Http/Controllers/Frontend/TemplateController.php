<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function show(string $slug)
    {
        $locale = app()->getLocale();

        $template = Template::with(['translations','categoryTemplate.translations'])
            ->whereHas('translations', fn($q)=> $q->where('slug',$slug)->where('locale',$locale))
            ->first();

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

        return view('tamplate.template-show', compact('template','translation'));
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
