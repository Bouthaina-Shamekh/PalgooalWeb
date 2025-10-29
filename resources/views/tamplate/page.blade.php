@php
    use App\Models\Service;
    use App\Models\Testimonial;
    use App\Services\TemplateService;
    use App\Models\CategoryTemplate;
    use App\Models\DomainTld;
    use App\Models\DomainTldPrice;
    use Illuminate\Support\Str;
    use App\Support\SeoMeta;
@endphp
@php
    $pageTranslation = $page->translation();
    $fallbackTitle = t('frontend.page_default_title', 'Untitled Page');
    $pageTitle = $pageTranslation?->meta_title ?: $pageTranslation?->title ?? $fallbackTitle;
    $rawPageContent = is_string($pageTranslation?->content) ? $pageTranslation->content : '';
    $pageDescription = $pageTranslation?->meta_description ?: Str::limit(strip_tags($rawPageContent), 160);
    if ($pageDescription === '') {
        $pageDescription = (string) config('seo.default_description', '');
    }
    $defaultKeywords = config('seo.default_keywords', []);
    $fallbackKeywords = is_array($defaultKeywords)
        ? $defaultKeywords
        : array_filter(array_map('trim', explode(',', (string) $defaultKeywords)));
    $keywordsFromTranslation = $pageTranslation?->meta_keywords;
    if (is_string($keywordsFromTranslation)) {
        $keywordsFromTranslation = array_filter(array_map('trim', explode(',', $keywordsFromTranslation)));
    } elseif (!is_array($keywordsFromTranslation)) {
        $keywordsFromTranslation = [];
    }
    $pageKeywords = !empty($keywordsFromTranslation) ? $keywordsFromTranslation : $fallbackKeywords;
    $pageOgImage = $pageTranslation?->og_image ?: 'assets/images/services.jpg';
    if ($pageOgImage && !Str::startsWith($pageOgImage, ['http://', 'https://'])) {
        $pageOgImage = asset($pageOgImage);
    }
    $schemaType = $page->is_home ? 'WebSite' : 'WebPage';
    $pageSchema = [
        '@context' => 'https://schema.org',
        '@type' => $schemaType,
        'name' => $pageTitle,
        'url' => url()->current(),
        'description' => $pageDescription,
        'inLanguage' => app()->getLocale(),
    ];

    $publishedAt = $page->published_at?->toIso8601String() ?? $page->created_at?->toIso8601String();
    if ($publishedAt) {
        $pageSchema['datePublished'] = $publishedAt;
    }
    $updatedAt = $page->updated_at?->toIso8601String();
    if ($updatedAt) {
        $pageSchema['dateModified'] = $updatedAt;
    }
    $seoOverrides = SeoMeta::make([
        'title' => $pageTitle,
        'description' => $pageDescription,
        'keywords' => $pageKeywords,
        'image' => $pageOgImage,
        'canonical' => url()->current(),
        'type' => $page->is_home ? 'website' : 'article',
        'schema' => [$pageSchema],
    ]);
@endphp
<x-template.layouts.index-layouts :title="$pageTitle" :description="$pageDescription" :keywords="$pageKeywords" :ogImage="$pageOgImage"
    :seo="$seoOverrides">
    {{-- ظ…ط­طھظˆظ‰ ط§ظ„طµظپط­ط© --}}
    @if ($page->sections->isEmpty())
        <div class="container mx-auto py-10">
            <h1 class="text-3xl font-bold mb-6">
            </h1>
            <div class="prose max-w-4xl">
                {!! $page->translation()?->content ?? '<p>ظ„ط§ ظٹظˆط¬ط¯ ظ…ط­طھظˆظ‰.</p>' !!}
            </div>
        </div>
    @endif
    @php
        $pageTranslation = $page->translation();
        $fallbackTitle = t('frontend.page_default_title', 'Untitled Page');
        $pageTitle = $pageTranslation?->meta_title ?: $pageTranslation?->title ?? $fallbackTitle;
        $rawPageContent = is_string($pageTranslation?->content) ? $pageTranslation->content : '';
        $pageDescription = $pageTranslation?->meta_description ?: Str::limit(strip_tags($rawPageContent), 160);
        if ($pageDescription === '') {
            $pageDescription = (string) config('seo.default_description', '');
        }
        $defaultKeywords = config('seo.default_keywords', []);
        $fallbackKeywords = is_array($defaultKeywords)
            ? $defaultKeywords
            : array_filter(array_map('trim', explode(',', (string) $defaultKeywords)));
        $keywordsFromTranslation = $pageTranslation?->meta_keywords;
        if (is_string($keywordsFromTranslation)) {
            $keywordsFromTranslation = array_filter(array_map('trim', explode(',', $keywordsFromTranslation)));
        } elseif (!is_array($keywordsFromTranslation)) {
            $keywordsFromTranslation = [];
        }
        $pageKeywords = !empty($keywordsFromTranslation) ? $keywordsFromTranslation : $fallbackKeywords;
        $pageOgImage = $pageTranslation?->og_image ?: 'assets/images/services.jpg';
        if ($pageOgImage && !Str::startsWith($pageOgImage, ['http://', 'https://'])) {
            $pageOgImage = asset($pageOgImage);
        }
        $schemaType = $page->is_home ? 'WebSite' : 'WebPage';
        $pageSchema = [
            '@context' => 'https://schema.org',
            '@type' => $schemaType,
            'name' => $pageTitle,
            'url' => url()->current(),
            'description' => $pageDescription,
            'inLanguage' => app()->getLocale(),
        ];
        $publishedAt = $page->published_at?->toIso8601String() ?? $page->created_at?->toIso8601String();
        if ($publishedAt) {
            $pageSchema['datePublished'] = $publishedAt;
        }
        $updatedAt = $page->updated_at?->toIso8601String();
        if ($updatedAt) {
            $pageSchema['dateModified'] = $updatedAt;
        }
        $seoOverrides = SeoMeta::make([
            'title' => $pageTitle,
            'description' => $pageDescription,
            'keywords' => $pageKeywords,
            'image' => $pageOgImage,
            'canonical' => url()->current(),
            'type' => $page->is_home ? 'website' : 'article',
            'schema' => [$pageSchema],
        ]);
    @endphp
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
            $key = $section->key;
            $component = $sectionComponents[$key] ?? null;
            if (!$component) {
                continue;
            }
            $translation = $section->translation();
            $content = $translation?->content ?? [];
            $title = $translation?->title ?? '';

            // âœ… ط¬ظ‡ظ‘ط² ط¨ظٹط§ظ†ط§طھ ظƒظ„ ط³ظٹظƒط´ظ†
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
                'features-2' => (function () use ($content, $title) {
                    $features = collect($content['features'] ?? [])
                        ->filter(function ($item) {
                            if (!is_array($item)) {
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
                    if (!in_array($backgroundVariant, $backgroundPresets, true)) {
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
                            if (!is_array($item)) {
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
                            if (!is_array($item)) {
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
                    'testimonials' => Testimonial::with('translations')->orderBy('order')->get(),
                ],
                'hosting-plans' => (function () use ($content, $title) {
                    // default
                    $cat = null;

                    $query = \App\Models\Plan::where('is_active', true)
                        ->with(['translations', 'category.translations'])
                        ->orderBy('id', 'asc');

                    // ظپظ„طھط±ط© ط­ط³ط¨ plan_category_id ط¥ظ† ظˆظڈط¬ط¯
                    if (!empty($content['plan_category_id'])) {
                        $query->where('plan_category_id', (int) $content['plan_category_id']);

                        // ط­ط§ظˆظ„ ط¬ظ„ط¨ ط§ظ„طھطµظ†ظٹظپ ظ„طھظ…ط±ظٹط±ظ‡ ظ„ظ„ظˆط§ط¬ظ‡ط© (ط¥ظ† ظˆط¬ط¯)
                        $cat = \App\Models\PlanCategory::with('translations')->find((int) $content['plan_category_id']);
                    }
                    // ط£ظˆ ظپظ„طھط±ط© ط­ط³ط¨ slug ط¶ظ…ظ† طھط±ط¬ظ…ط© ط§ظ„ظ€ locale ط§ظ„ط­ط§ظ„ظٹط©
                    elseif (!empty($content['plan_category_slug'])) {
                        $slug = (string) $content['plan_category_slug'];

                        // ط§ظ„ط¨ط­ط« ط¶ظ…ظ† ط§ظ„طھط±ط¬ظ…ط§طھ ظ„ظ„ظ€ locale ط§ظ„ط­ط§ظ„ظٹ
                        $cat = \App\Models\PlanCategory::whereHas('translations', function ($q) use ($slug) {
                            $q->where('slug', $slug)->where('locale', app()->getLocale());
                        })
                            ->with('translations')
                            ->first();

                        // ط¥ط°ط§ ظ„ظ… ظ†ط¬ط¯ طھط±ط¬ظ…ط© ط¨ط§ظ„ظ€ locale ط§ظ„ط­ط§ظ„ظٹطŒ ط¬ط±ط¨ ط§ظ„ط¨ط­ط« ط¹ط¨ط± ط¬ظ…ظٹط¹ ط§ظ„طھط±ط¬ظ…ط§طھ
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
                            // ط®ظٹط§ط±: ط§ط±ط¬ط¹ ظ„ط§ ط´ظٹط، ط¨ط¯ظ„ ط¬ظ…ظٹط¹ ط§ظ„ط®ط·ط·
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
                // âœ… ظ‡ظ†ط§ ظ†ظ…ط±ظ‘ط± ط¨ظٹط§ظ†ط§طھ search-domain (ط§ظ„ظƒطھط§ظ„ظˆط¬ + ط£ط³ط¹ط§ط± fallback)
                'search-domain' => (function () {
                    // TLDs ط§ظ„ظ…ط¹ط±ظˆط¶ط© ظپظٹ ط§ظ„ظƒطھط§ظ„ظˆط¬
                    $defaultTlds = DomainTld::where('in_catalog', true)
                        ->orderBy('tld')
                        ->pluck('tld')
                        ->map(fn($t) => strtolower(ltrim($t, '.')))
                        ->values()
                        ->all();

                    // ط£ط³ط¹ط§ط± fallback: ظ†ط³طھط®ط¯ظ… sale ط¥ظ† ظˆط¬ط¯طھطŒ ظˆط¥ظ„ط§ cost ظ„ظ€ Register ط³ظ†ط© ظˆط§ط­ط¯ط©
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
                        'subtitle' => '', // ظ…ظ…ظƒظ† طھط³طھط®ط¯ظ… ظ…ظ† طھط±ط¬ظ…ط© ط§ظ„ط³ظٹظƒط´ظ† ظ„ظˆ ظ„ط²ظ…
                        'default_tlds' => $defaultTlds,
                        'fallback_prices' => $fallbackPrices,
                        'currency' => 'USD', // ط¹ط¯ظ‘ظ„ظ‡ط§ ط­ط³ط¨ ط¥ط¹ط¯ط§ط¯ط§طھظƒ ط¥ظ† ظ„ط²ظ…
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

                            $cat->translated_name = $t?->name ?? 'ط؛ظٹط± ظ…ط¹ط±ظپ';

                            $cat->translated_slug = $t?->slug ?? ($cat->slug ?? 'uncategorized');

                            return $cat;
                        }),
                ],

                default => [],
            };

        @endphp
        {{-- âœ… ط±ظ†ط¯ط±ط© ط®ط§طµط© ظ„ظƒظ„ ط³ظٹظƒط´ظ† ظٹط­طھط§ط¬ props ظ…ط®طµطµط© --}}
        @if ($component === 'templates-pages')
            <x-dynamic-component :component="'template.sections.' . $component" :templates="$data['templates']" :categories="$data['categories']" :max_price="$data['max_price']"
                :sort_by="$data['sort_by']" :show_filter_sidebar="$data['show_filter_sidebar']" :selectedCategory="$data['selectedCategory']" />
        @elseif ($component === 'search-domain')
            {{-- ظ‡ظ†ط§ ظ†ظ…ط±ظ‘ط± ط§ظ„ظ€ props ط§ظ„ظ…طھظˆظ‚ط¹ط© ظپظٹ search-domain.blade --}}
            <x-dynamic-component :component="'template.sections.' . $component" :default-tlds="$data['default_tlds'] ?? []" :fallback-prices="$data['fallback_prices'] ?? []" :currency="$data['currency'] ?? 'USD'" />
        @elseif ($component === 'hosting-plans')
            <x-dynamic-component :component="'template.sections.' . $component" :plans="$data['plans'] ?? collect()" :title="$data['title'] ?? ''" :subtitle="$data['subtitle'] ?? ''"
                :category="$data['category'] ?? null" />
        @else
            {{-- ط¨ط§ظ‚ظٹ ط§ظ„ط³ظٹظƒط´ظ†ط§طھ طھط³طھظ‚ط¨ظ„ $data ط¨ط§ظ„ط´ظƒظ„ ط§ظ„ظ‚ط¯ظٹظ… --}}
            <x-dynamic-component :component="'template.sections.' . $component" :data="$data" :templates="$data['templates'] ?? collect()" />
        @endif
    @endforeach
</x-template.layouts.index-layouts>
