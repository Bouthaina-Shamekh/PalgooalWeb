@php
    use App\Models\Service;
    use App\Models\Testimonial;
    use App\Services\TemplateService;
    use App\Models\CategoryTemplate;
    use App\Models\DomainTld;
    use App\Models\DomainTldPrice;
    use Illuminate\Support\Str;
    use App\Support\SeoMeta;
    use App\Support\Blocks\SectionRenderer;
@endphp

@php
    /**
     * Prepare SEO metadata for the current page.
     *
     * We:
     * - Resolve the page translation for the current locale.
     * - Build title, description, keywords.
     * - Generate an OpenGraph image URL.
     * - Build basic Schema.org metadata (WebSite/WebPage).
     * - Pass everything to the <x-template.layouts.index-layouts> layout.
     */

    // Get the current translation for this page
    $pageTranslation = $page->translation();
    $fallbackTitle = t('frontend.page_default_title', 'Untitled Page');

    // Page <title>
    $pageTitle = $pageTranslation?->meta_title
        ?: $pageTranslation?->title
        ?: $fallbackTitle;

    // Raw content for fallback description if needed
    $rawPageContent = is_string($pageTranslation?->content) ? $pageTranslation->content : '';

    // Meta description
    $pageDescription = $pageTranslation?->meta_description
        ?: Str::limit(strip_tags($rawPageContent), 160);

    if ($pageDescription === '') {
        $pageDescription = (string) config('seo.default_description', '');
    }

    // Meta keywords: from translation or fallback from config
    $defaultKeywords = config('seo.default_keywords', []);
    $fallbackKeywords = is_array($defaultKeywords)
        ? $defaultKeywords
        : array_filter(array_map('trim', explode(',', (string) $defaultKeywords)));

    $keywordsFromTranslation = $pageTranslation?->meta_keywords;

    if (is_string($keywordsFromTranslation)) {
        $keywordsFromTranslation = array_filter(
            array_map('trim', explode(',', $keywordsFromTranslation))
        );
    } elseif (! is_array($keywordsFromTranslation)) {
        $keywordsFromTranslation = [];
    }

    $pageKeywords = ! empty($keywordsFromTranslation)
        ? $keywordsFromTranslation
        : $fallbackKeywords;

    // OpenGraph image
    $pageOgImage = $pageTranslation?->og_image ?: 'assets/images/services.jpg';

    if ($pageOgImage && ! Str::startsWith($pageOgImage, ['http://', 'https://'])) {
        $pageOgImage = asset($pageOgImage);
    }

    // Schema.org metadata
    $schemaType = $page->is_home ? 'WebSite' : 'WebPage';

    $pageSchema = [
        '@context'     => 'https://schema.org',
        '@type'        => $schemaType,
        'name'         => $pageTitle,
        'url'          => url()->current(),
        'description'  => $pageDescription,
        'inLanguage'   => app()->getLocale(),
    ];

    $publishedAt = $page->published_at?->toIso8601String()
        ?? $page->created_at?->toIso8601String();

    if ($publishedAt) {
        $pageSchema['datePublished'] = $publishedAt;
    }

    $updatedAt = $page->updated_at?->toIso8601String();

    if ($updatedAt) {
        $pageSchema['dateModified'] = $updatedAt;
    }

    // Build final SEO overrides object
    $seoOverrides = SeoMeta::make([
        'title'       => $pageTitle,
        'description' => $pageDescription,
        'keywords'    => $pageKeywords,
        'image'       => $pageOgImage,
        'canonical'   => url()->current(),
        'type'        => $page->is_home ? 'website' : 'article',
        'schema'      => [$pageSchema],
    ]);
@endphp

<x-template.layouts.index-layouts
    :title="$pageTitle"
    :description="$pageDescription"
    :keywords="$pageKeywords"
    :ogImage="$pageOgImage"
    :seo="$seoOverrides"
>
    {{-- إذا لم توجد أي سكشنات، ن fallback إلى محتوى الصفحة الخام --}}
    @if ($page->sections->isEmpty())
        <div class="container mx-auto py-10">
            <h1 class="text-3xl font-bold mb-6">
                {{ $pageTitle }}
            </h1>
            <div class="prose max-w-4xl">
                {!! $pageTranslation?->content ?? '<p>لا يوجد محتوى.</p>' !!}
            </div>
        </div>
    @endif

    @php
        $sectionComponents = [
            'hero' => 'hero',
            'features' => 'features',
            'features-2' => 'features-2',
            'features-3' => 'features-3',
            'cta' => 'cta',
            'services' => 'services',
            'templates' => 'templates',
            'works' => 'works',
            'home-works' => 'home-works',
            'testimonials' => 'testimonials',
            'blog' => 'blog',
            'banner' => 'banner',
            'search-domain' => 'search-domain',
            'templates-pages' => 'templates-pages',
            'hosting-plans' => 'hosting-plans',
            'faq' => 'faq',
        ];
    @endphp

    @foreach ($page->sections as $section)
        @php
            // IMPORTANT: we now use `type` instead of `key`
            $key = $section->type;
            $component = $sectionComponents[$key] ?? null;

            if (! $component) {
                continue;
            }

            if ($key === 'hero') {
                // ✅ سكشن hero عبر SectionRenderer + BlockRegistry
                $data = SectionRenderer::render($section);
            } else {
                // بقية السكشنات بمنطق الترجمة القديم
                $translation = $section->translation();
                $content = $translation?->content ?? [];
                $title = $translation?->title ?? '';

                // ✅ جهّز بيانات كل سيكشن
                $data = match ($key) {
                    'features' => [
                        'title' => $title,
                        'subtitle' => $content['subtitle'] ?? '',
                        'features' => is_array($content['features'] ?? null)
                            ? $content['features']
                            : array_filter(array_map('trim', explode("\n", $content['features'] ?? ''))),
                    ],
                    'features-2' => (function () use ($content, $title) {
                        $features = collect($content['features'] ?? [])
                            ->filter(function ($item) {
                                if (! is_array($item)) {
                                    return false;
                                }

                                $title = trim((string) ($item['title'] ?? ''));
                                $description = trim((string) ($item['description'] ?? ''));
                                $icon = trim((string) ($item['icon'] ?? ''));

                                return $title !== '' || $description !== '' || $icon !== '';
                            })
                            ->map(function ($item) {
                                return [
                                    'icon' => $item['icon'] ?? '',
                                    'title' => $item['title'] ?? '',
                                    'description' => $item['description'] ?? '',
                                ];
                            })
                            ->values()
                            ->all();

                        $backgroundPresets = [
                            'white',
                            'gray',
                            'stone',
                            'slate-light',
                            'slate-dark',
                            'zinc-dark',
                            'black',
                            'sky',
                            'blue',
                            'indigo',
                            'violet',
                            'purple',
                            'amber',
                            'orange',
                            'rose',
                            'rose-deep',
                            'emerald',
                            'emerald-deep',
                            'teal',
                        ];

                        $backgroundVariant = $content['background_variant'] ?? null;
                        if (! in_array($backgroundVariant, $backgroundPresets, true)) {
                            $legacy = $content['background_color'] ?? null;
                            $backgroundVariant = match (is_string($legacy) ? strtolower(trim($legacy)) : null) {
                                '#ffffff', '#fff'          => 'white',
                                '#f9fafb', '#f8fafc'       => 'gray',
                                '#faf5f0', '#f5e9df'       => 'stone',
                                '#e2e8f0', '#cbd5e1'       => 'slate-light',
                                '#0f172a', '#111827'       => 'slate-dark',
                                '#18181b'                  => 'zinc-dark',
                                '#020617'                  => 'black',
                                '#eff6ff', '#e0f2fe'       => 'sky',
                                '#dbeafe', '#bfdbfe'       => 'blue',
                                '#4f46e5', '#312e81'       => 'indigo',
                                '#7c3aed', '#5b21b6'       => 'violet',
                                '#9333ea', '#6b21a8'       => 'purple',
                                '#fef3c7', '#fde68a'       => 'amber',
                                '#f97316', '#ea580c'       => 'orange',
                                '#ffe4e6', '#fecdd3'       => 'rose',
                                '#e11d48', '#be123c'       => 'rose-deep',
                                '#ecfdf5', '#d1fae5'       => 'emerald',
                                '#059669', '#047857'       => 'emerald-deep',
                                '#14b8a6', '#0f766e'       => 'teal',
                                default                    => 'white',
                            };
                        }

                        return [
                            'title' => $title,
                            'subtitle' => $content['subtitle'] ?? '',
                            'button_text' => $content['button_text'] ?? '',
                            'button_url' => $content['button_url'] ?? '',
                            'background_variant' => $backgroundVariant,
                            'features' => $features,
                        ];
                    })(),
                    'features-3' => (function () use ($content, $title) {
                        $features = collect($content['features'] ?? [])
                            ->filter(function ($item) {
                                if (! is_array($item)) {
                                    return false;
                                }

                                $title = trim((string) ($item['title'] ?? ''));
                                $description = trim((string) ($item['description'] ?? ''));
                                $icon = trim((string) ($item['icon'] ?? ''));

                                return $title !== '' || $description !== '' || $icon !== '';
                            })
                            ->map(function ($item) {
                                return [
                                    'icon' => $item['icon'] ?? '',
                                    'title' => $item['title'] ?? '',
                                    'description' => $item['description'] ?? '',
                                ];
                            })
                            ->values()
                            ->all();

                        return [
                            'title' => $title,
                            'subtitle' => $content['subtitle'] ?? '',
                            'features' => $features,
                        ];
                    })(),
                    'cta' => (function () use ($content, $title) {
                        return [
                            'title'               => $title,
                            'subtitle'            => $content['subtitle'] ?? '',
                            'badge'               => $content['badge'] ?? '',
                            'primary_button_text' => $content['primary_button_text'] ?? ($content['button_text'] ?? ''),
                            'primary_button_url'  => $content['primary_button_url'] ?? ($content['button_url'] ?? ''),
                        ];
                    })(),
                    'faq' => (function () use ($content, $title) {
                        $items = collect($content['items'] ?? $content['faq'] ?? [])
                            ->map(function ($item) {
                                if (! is_array($item)) {
                                    $question = trim((string) $item);
                                    return $question === '' ? null : ['question' => $question, 'answer' => ''];
                                }

                                $question = trim((string) ($item['question'] ?? ''));
                                $answer = trim((string) ($item['answer'] ?? ''));

                                return ($question === '' && $answer === '') ? null : [
                                    'question' => $question,
                                    'answer'   => $answer,
                                ];
                            })
                            ->filter()
                            ->values()
                            ->all();

                        return [
                            'title'    => $title,
                            'subtitle' => $content['subtitle'] ?? '',
                            'items'    => $items,
                        ];
                    })(),
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
                    'works', 'banner' => [
                        'title' => $title,
                        'subtitle' => $content['subtitle'] ?? '',
                    ],
                    'testimonials' => [
                        'title' => $title,
                        'subtitle' => $content['subtitle'] ?? '',
                        'testimonials' => Testimonial::approved()->with('translations')->orderBy('order')->get(),
                    ],
                    'hosting-plans' => (function () use ($content, $title) {
                        // default
                        $cat = null;

                        $query = \App\Models\Plan::where('is_active', true)
                            ->with(['translations', 'category.translations'])
                            ->orderBy('id', 'asc');

                        // فلترة حسب plan_category_id إن وجدت
                        if (! empty($content['plan_category_id'])) {
                            $query->where('plan_category_id', (int) $content['plan_category_id']);

                            // حاول جلب التصنيف لتمريره للواجهة (إن وجد)
                            $cat = \App\Models\PlanCategory::with('translations')->find((int) $content['plan_category_id']);
                        }
                        // أو فلترة حسب slug ضمن ترجمة الحالية
                        elseif (! empty($content['plan_category_slug'])) {
                            $slug = (string) $content['plan_category_slug'];

                            // البحث ضمن الترجمات للـ locale الحالي
                            $cat = \App\Models\PlanCategory::whereHas('translations', function ($q) use ($slug) {
                                $q->where('slug', $slug)->where('locale', app()->getLocale());
                            })
                                ->with('translations')
                                ->first();

                            // إذا لم نجد ترجمة بالـ locale الحالي، جرب البحث عبر جميع الترجمات
                            if (! $cat) {
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
                        // TLDs الموجودة في الكتالوج
                        $defaultTlds = DomainTld::where('in_catalog', true)
                            ->orderBy('tld')
                            ->pluck('tld')
                            ->map(fn ($t) => strtolower(ltrim($t, '.')))
                            ->values()
                            ->all();

                        // أسعار fallback: نستخدم sale إن وجدت، وإلا cost لسنة واحدة Register
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
                            'subtitle' => '',
                            'default_tlds' => $defaultTlds,
                            'fallback_prices' => $fallbackPrices,
                            'currency' => 'USD',
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

                                $cat->translated_name = $t?->name ?? 'غير معروف';
                                $cat->translated_slug = $t?->slug ?? ($cat->slug ?? 'uncategorized');

                                return $cat;
                            }),
                    ],

                    default => [],
                };
            }
        @endphp

        {{-- ✅ رندرة خاصة لكل سيكشن حسب نوعه والـ props المطلوبة --}}
        @if ($component === 'templates-pages')
            <x-dynamic-component
                :component="'template.sections.' . $component"
                :templates="$data['templates']"
                :categories="$data['categories']"
                :max_price="$data['max_price']"
                :sort_by="$data['sort_by']"
                :show_filter_sidebar="$data['show_filter_sidebar']"
                :selectedCategory="$data['selectedCategory']"
            />
        @elseif ($component === 'search-domain')
            {{-- هنا نمرّر الـ props المتوقعة في search-domain.blade --}}
            <x-dynamic-component
                :component="'template.sections.' . $component"
                :default-tlds="$data['default_tlds'] ?? []"
                :fallback-prices="$data['fallback_prices'] ?? []"
                :currency="$data['currency'] ?? 'USD'"
            />
        @elseif ($component === 'hosting-plans')
            <x-dynamic-component
                :component="'template.sections.' . $component"
                :plans="$data['plans'] ?? collect()"
                :title="$data['title'] ?? ''"
                :subtitle="$data['subtitle'] ?? ''"
                :category="$data['category'] ?? null"
            />
        @else
            {{-- باقي السيكشنات تستقبل $data بشكل كامل + templates إن وجدت --}}
            <x-dynamic-component
                :component="'template.sections.' . $component"
                :data="$data"
                :templates="$data['templates'] ?? collect()"
            />
        @endif
    @endforeach
</x-template.layouts.index-layouts>
