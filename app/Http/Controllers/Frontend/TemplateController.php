<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Database\Eloquent\Builder;

class TemplateController extends Controller
{
    /**
     * صفحة عرض تفاصيل القالب
     */
    public function show(string $slug)
    {
        $locale = app()->getLocale();

        // نحاول إيجاد القالب بالـ slug واللغة الحالية مع fallback للعربية
        $template = $this->findBySlugWithFallback($slug, $locale);

        abort_if(!$template, 404);

        // نختار الترجمة المناسبة للّغة الحالية مع fallback
        $translation = $this->resolveTranslationForLocale($template, $locale);

        // إعادة توجيه للـ slug "الرسمي" لو مختلف (يفيد الـ SEO + توحيد الروابط)
        if ($translation && $translation->slug && $translation->slug !== $slug) {
            return redirect()->route('template.show', $translation->slug);
        }

        // تحميل المراجعات والعلاقات المرتبطة بها (عميل / يوزر)
        $template->load([
            'reviews' => fn($q) => $q->approved()->latest()->take(10),
            'reviews.client:id,first_name,last_name,avatar',
            'reviews.user:id,name',
        ]);

        return view('tamplate.template-show', compact('template', 'translation'));
    }

    /**
     * صفحة الـ Preview للقالب
     */
    public function preview(string $slug)
    {
        $locale = app()->getLocale();

        // نستخدم نفس منطق البحث + fallback عشان التصرّف يكون موحّد
        $template = $this->findBySlugWithFallback($slug, $locale);

        abort_if(!$template, 404);

        $translation = $this->resolveTranslationForLocale($template, $locale);

        if (!$translation || empty($translation->preview_url)) {
            abort(404);
        }

        $previewUrl  = $translation->preview_url;
        $previewHost = strtolower(parse_url($previewUrl, PHP_URL_HOST) ?? '');
        $appHost     = strtolower(parse_url(config('app.url'), PHP_URL_HOST) ?? request()->getHost());

        $baseDomain  = 'palgoals.com';
        $sameOrigin  = $previewHost === $appHost;
        $isSubdomain = str_ends_with($previewHost, '.' . $baseDomain) || $previewHost === $baseDomain;

        $embedAllowed = $sameOrigin || $isSubdomain;

        return view('tamplate.view-templat', compact('template', 'translation', 'previewUrl', 'embedAllowed'));
    }

    /**
     * يبحث عن قالب حسب الـ slug واللغة الحالية، مع fallback للعربية
     */
    private function findBySlugWithFallback(string $slug, string $locale): ?Template
    {
        // محاولة أولى: اللغة الحالية
        $template = Template::with(['translations', 'categoryTemplate.translations'])
            ->whereHas('translations', function (Builder $q) use ($slug, $locale) {
                $q->where('slug', $slug)->where('locale', $locale);
            })
            ->first();

        // fallback للعربية لو ما وجدنا شيء وبشرط أن اللغة ليست عربية أصلاً
        if (!$template && $locale !== 'ar') {
            $template = Template::with(['translations', 'categoryTemplate.translations'])
                ->whereHas('translations', function (Builder $q) use ($slug) {
                    $q->where('slug', $slug)->where('locale', 'ar');
                })
                ->first();
        }

        return $template;
    }

    /**
     * إرجاع الترجمة الأنسب للّغة الحالية مع fallback للعربية ثم أي ترجمة متوفرة
     */
    private function resolveTranslationForLocale(Template $template, string $locale)
    {
        return $template->translations->firstWhere('locale', $locale)
            ?? $template->translations->firstWhere('locale', 'ar')
            ?? $template->translations->first(); // آخر fallback لو ما في عربية
    }
}