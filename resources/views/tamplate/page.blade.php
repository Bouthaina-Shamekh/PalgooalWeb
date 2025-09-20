{{-- resources/views/tamplate/page.blade.php --}}
@php
    use App\Models\Service;
    use App\Services\TemplateService;
    use App\Models\CategoryTemplate;

    // ✅ أضف هذول السطرين لاستخدام جداول TLD
    use App\Models\DomainTld;
    use App\Models\DomainTldPrice;
@endphp

<x-template.layouts.index-layouts title="{{ $page->translation()?->title ?? 'عنوان غير متوفر' }}"
    description="شركة فلسطينية متخصصة في برمجة وتصميم المواقع الالكترونية..."
    keywords="خدمات حجز دومين , افضل شركة برمجيات , استضافة مواقع , ..."
    ogImage="{{ asset('assets/images/services.jpg') }}">
    {{-- محتوى الصفحة --}}
    @if ($page->sections->isEmpty())
        <div class="container mx-auto py-10">
            <h1 class="text-3xl font-bold mb-6">
                {{ $page->translation()?->title ?? 'عنوان غير متوفر' }}
            </h1>

            <div class="prose max-w-4xl">
                {!! $page->translation()?->content ?? '<p>لا يوجد محتوى.</p>' !!}
            </div>
        </div>
    @endif

    @php
        $sectionComponents = [
            'hero' => 'hero',
            'features' => 'features',
            'services' => 'services',
            'templates' => 'templates',
            'works' => 'works',
            'home-works' => 'home-works',
            'testimonials' => 'testimonials',
            'blog' => 'blog',
            'banner' => 'banner',
            'search-domain' => 'search-domain', // ← مهم
            'templates-pages' => 'templates-pages',
            'hosting-plans' => 'hosting-plans',
        ];
    @endphp

    @foreach ($page->sections as $section)
        @php
            $key = $section->key;
            $component = $sectionComponents[$key] ?? null;
            $translation = $section->translation();
            $content = $translation?->content ?? [];
            $title = $translation?->title ?? '';

            // ✅ جهّز بيانات كل سيكشن
            $data = match ($key) {
                'hero' => [
                    'title' => $title,
                    'subtitle' => $content['subtitle'] ?? '',
                    'button_text-1' => $content['button_text-1'] ?? '',
                    'button_url-1' => $content['button_url-1'] ?? '',
                    'button_text-2' => $content['button_text-2'] ?? '',
                    'button_url-2' => $content['button_url-2'] ?? '',
                ],
                'features' => [
                    'title' => $title,
                    'subtitle' => $content['subtitle'] ?? '',
                    'features' => is_array($content['features'] ?? null)
                        ? $content['features']
                        : array_filter(array_map('trim', explode("\n", $content['features'] ?? ''))),
                ],
                'services' => [
                    'title' => $title,
                    'subtitle' => $content['subtitle'] ?? '',
                    'services' => Service::with('translations')->orderBy('order')->get(),
                ],
                'home-works' => [
                    'title' => $title,
                    'subtitle' => $content['subtitle'] ?? '',
                    'button_text-1' => $content['button_text-1'] ?? '',
                    'button_url-1' => $content['button_url-1'] ?? '',
                ],
                'works', 'testimonials', 'banner' => [
                    'title' => $title,
                    'subtitle' => $content['subtitle'] ?? '',
                ],
                'hosting-plans' => (function () use ($content) {
                    // default
                    $cat = null;

                    $query = \App\Models\Plan::where('is_active', true)
                        ->with(['translations', 'category.translations'])
                        ->orderBy('id', 'asc');

                    // فلترة حسب plan_category_id إن وُجد
                    if (!empty($content['plan_category_id'])) {
                        $query->where('plan_category_id', (int) $content['plan_category_id']);

                        // حاول جلب التصنيف لتمريره للواجهة (إن وجد)
                        $cat = \App\Models\PlanCategory::with('translations')->find((int) $content['plan_category_id']);
                    }
                    // أو فلترة حسب slug ضمن ترجمة الـ locale الحالية
                    elseif (!empty($content['plan_category_slug'])) {
                        $slug = (string) $content['plan_category_slug'];

                        // البحث ضمن الترجمات للـ locale الحالي
                        $cat = \App\Models\PlanCategory::whereHas('translations', function ($q) use ($slug) {
                            $q->where('slug', $slug)->where('locale', app()->getLocale());
                        })
                            ->with('translations')
                            ->first();

                        // إذا لم نجد ترجمة بالـ locale الحالي، جرب البحث عبر جميع الترجمات
                        if (!$cat) {
                            $cat = \App\Models\PlanCategory::whereHas('translations', function ($q) use ($slug) {
                                $q->where('slug', $slug);
                            })
                                ->with('translations')
                                ->first();
                        }

                        if ($cat) {
                            $query->where('plan_category_id', $cat->id);
                        } else {
                            // خيار: ارجع لا شيء بدل جميع الخطط
                            $query->whereRaw('0 = 1');
                        }
                    }

                    $plans = $query->get();

                    return [
                        'title' => $title ?? '',
                        'subtitle' => $content['subtitle'] ?? '',
                        'plans' => $plans,
                        'category' => $cat,
                    ];
                })(),

                'templates' => [
                    'title' => $title,
                    'subtitle' => $content['subtitle'] ?? '',
                    'templates' => \App\Models\Template::with('translations')->latest()->take(8)->get(),
                ],
                'blog' => [
                    'title' => $title,
                    'subtitle' => $content['subtitle'] ?? '',
                    'button_text-1' => $content['button_text-1'] ?? '',
                    'button_url-1' => $content['button_url-1'] ?? '',
                ],
                // ✅ هنا نمرّر بيانات search-domain (الكتالوج + أسعار fallback)
                'search-domain' => (function () {
                    // TLDs المعروضة في الكتالوج
                    $defaultTlds = DomainTld::where('in_catalog', true)
                        ->orderBy('tld')
                        ->pluck('tld')
                        ->map(fn($t) => strtolower(ltrim($t, '.')))
                        ->values()
                        ->all();

                    // أسعار fallback: نستخدم sale إن وجدت، وإلا cost لـ Register سنة واحدة
                    $fallbackPrices = DomainTldPrice::with('tld')
                        ->whereIn('domain_tld_id', DomainTld::where('in_catalog', true)->pluck('id'))
                        ->where('action', 'register')
                        ->where('years', 1)
                        ->get()
                        ->mapWithKeys(function ($p) {
                            $tld = strtolower($p->tld->tld ?? '');
                            if ($tld === '') {
                                return [];
                            }
                            $price = $p->sale ?? $p->cost;
                            return $price !== null ? [$tld => (float) $price] : [];
                        })
                        ->toArray();

                    return [
                        'title' => $GLOBALS['title'] ?? '',
                        'subtitle' => '', // ممكن تستخدم من ترجمة السيكشن لو لزم
                        'default_tlds' => $defaultTlds,
                        'fallback_prices' => $fallbackPrices,
                        'currency' => 'USD', // عدّلها حسب إعداداتك إن لزم
                    ];
                })(),

                'templates-pages' => [
                    'max_price' => $content['max_price'] ?? 500,
                    'sort_by' => request('sort', $content['sort_by'] ?? 'default'),
                    'show_filter_sidebar' => $content['show_filter_sidebar'] ?? true,
                    'selectedCategory' => $content['selectedCategory'] ?? 'all',
                    'templates' => \App\Models\Template::with(['translations', 'categoryTemplate.translations'])
                        ->latest()
                        ->take(60)
                        ->get(),
                    'categories' => \App\Models\CategoryTemplate::with([
                        'translations' => function ($q) {
                            $q->where('locale', app()->getLocale())->orWhere('locale', 'ar');
                        },
                    ])
                        ->get()
                        ->map(function ($cat) {
                            $t =
                                $cat->translations->firstWhere('locale', app()->getLocale()) ??
                                $cat->translations->firstWhere('locale', 'ar');
                            $cat->translated_name = $t?->name ?? 'غير معرف';
                            $cat->translated_slug = $t?->slug ?? ($cat->slug ?? 'uncategorized');
                            return $cat;
                        }),
                ],
                default => [],
            };
        @endphp

        {{-- ✅ رندرة خاصة لكل سيكشن يحتاج props مخصصة --}}
        @if ($component === 'templates-pages')
            <x-dynamic-component :component="'template.sections.' . $component" :templates="$data['templates']" :categories="$data['categories']" :max_price="$data['max_price']"
                :sort_by="$data['sort_by']" :show_filter_sidebar="$data['show_filter_sidebar']" :selectedCategory="$data['selectedCategory']" />
        @elseif ($component === 'search-domain')
            {{-- هنا نمرّر الـ props المتوقعة في search-domain.blade --}}
            <x-dynamic-component :component="'template.sections.' . $component" :default-tlds="$data['default_tlds'] ?? []" :fallback-prices="$data['fallback_prices'] ?? []" :currency="$data['currency'] ?? 'USD'" />
        @elseif ($component === 'hosting-plans')
            <x-dynamic-component :component="'template.sections.' . $component" :plans="$data['plans'] ?? collect()" :title="$data['title'] ?? ''" :subtitle="$data['subtitle'] ?? ''"
                :category="$data['category'] ?? null" />
        @else
            {{-- باقي السيكشنات تستقبل $data بالشكل القديم --}}
            <x-dynamic-component :component="'template.sections.' . $component" :data="$data" :templates="$data['templates'] ?? collect()" />
        @endif
    @endforeach
</x-template.layouts.index-layouts>