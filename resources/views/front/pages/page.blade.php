{{-- resources/views/front/pages/page.blade.php --}}
@php
    use App\Models\Service;
    use App\Models\Testimonial;
    use App\Models\DomainTld;
    use App\Models\DomainTldPrice;
    use App\Models\Media;
    use Illuminate\Support\Str;
    use App\Support\SeoMeta;
    use App\Support\Blocks\SectionRenderer;

    /**
     * ------------------------------------------------------------------
     * SEO & OpenGraph preparation for current page
     * ------------------------------------------------------------------
     */

    // Resolve current translation for active locale
    $pageTranslation = $page->translation();
    $fallbackTitle   = t('frontend.page_default_title', 'Untitled Page');

    // <title> tag
    $pageTitle = $pageTranslation?->meta_title
        ?: $pageTranslation?->title
        ?: $fallbackTitle;

    // Raw content for meta description fallback
    $rawPageContent = is_string($pageTranslation?->content)
        ? $pageTranslation->content
        : '';

    // Meta description: prefer explicit meta_description, fallback to trimmed content
    $pageDescription = $pageTranslation?->meta_description
        ?: Str::limit(strip_tags($rawPageContent), 160);

    if ($pageDescription === '') {
        $pageDescription = (string) config('seo.default_description', '');
    }

    // Meta keywords: use translation if present, otherwise fallback from config
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

    /**
     * ------------------------------------------------------------------
     * Resolve OpenGraph image
     * - If value is numeric: treat as Media ID from our Media Library
     * - If value is a URL (http/https/relative): use / wrap via asset()
     * - Fallback to default image
     * ------------------------------------------------------------------
     */
    $rawOgImage  = $pageTranslation?->og_image;
    $pageOgImage = null;

    if (is_numeric($rawOgImage)) {
        // Case A: og_image stores Media ID
        $media = Media::find((int) $rawOgImage);
        if ($media) {
            // Adjust "url" accessor/column according to your Media model
            $pageOgImage = $media->url ?? ($media->file_url ?? null);
        }
    } elseif (is_string($rawOgImage) && $rawOgImage !== '') {
        // Case B: og_image stores direct URL or relative path
        if (Str::startsWith($rawOgImage, ['http://', 'https://', '//'])) {
            $pageOgImage = $rawOgImage;
        } else {
            $pageOgImage = asset($rawOgImage);
        }
    }

    // Final fallback OG image
    if (! $pageOgImage) {
        $defaultOg   = config('seo.default_image', 'assets/images/services.jpg');
        $pageOgImage = Str::startsWith($defaultOg, ['http://', 'https://', '//'])
            ? $defaultOg
            : asset($defaultOg);
    }

    /**
     * ------------------------------------------------------------------
     * Schema.org metadata
     * ------------------------------------------------------------------
     */
    $schemaType = $page->is_home ? 'WebSite' : 'WebPage';

    $pageSchema = [
        '@context'    => 'https://schema.org',
        '@type'       => $schemaType,
        'name'        => $pageTitle,
        'url'         => url()->current(),
        'description' => $pageDescription,
        'inLanguage'  => app()->getLocale(),
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

    /**
     * ------------------------------------------------------------------
     * Build SEO overrides object (SeoMeta)
     * ------------------------------------------------------------------
     */
    $seoOverrides = SeoMeta::make([
        'title'       => $pageTitle,
        'description' => $pageDescription,
        'keywords'    => $pageKeywords,
        'image'       => $pageOgImage,
        'canonical'   => url()->current(),
        'type'        => $page->is_home ? 'website' : 'article',
        'schema'      => [$pageSchema],
    ]);

    /**
     * Expose SEO variables to the layout (front.layouts.app)
     * So the layout can build <head> tags using these.
     */
    $title       = $pageTitle;
    $description = $pageDescription;
    $keywords    = $pageKeywords;
    $ogImage     = $pageOgImage;
    $seo         = $seoOverrides;

    /**
     * ------------------------------------------------------------------
     * Section → Blade component mapping
     * ------------------------------------------------------------------
     * Each page section has ->type (string).
     * We map that type to a Blade component under template.sections.*:
     *
     *  hero / hero_default  → template.sections.hero
     *  features             → template.sections.features
     *  templates-pages      → template.sections.templates-pages
     *  ...etc
     * ------------------------------------------------------------------
     */
    $sectionComponents = [
        'hero'            => 'hero',
        'hero_default'    => 'hero_default',          // New Hero Default block → reuse hero component
        'features'        => 'features',
        'features-2'      => 'features-2',
        'features-3'      => 'features-3',
        'cta'             => 'cta',
        'services'        => 'services',
        'templates'       => 'templates',
        'works'           => 'works',
        'home-works'      => 'home-works',
        'testimonials'    => 'testimonials',
        'blog'            => 'blog',
        'banner'          => 'banner',
        'search-domain'   => 'search-domain',
        'templates-pages' => 'templates-pages',
        'hosting-plans'   => 'hosting-plans',
        'faq'             => 'faq',
    ];

    // Sections built via GrapesJS (stored JSON structure)
    $builderSections = $page->builderStructure?->normalizedSections() ?? [];
@endphp

@extends('front.layouts.app')

@section('content')
    {{-- -----------------------------------------------------------------
         Fallback: if the page has NO sections → show simple page layout
       ----------------------------------------------------------------- --}}
    @if ($page->sections->isEmpty() && empty($builderSections))
        <section class="bg-slate-50 dark:bg-slate-900 py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto">
                {{-- Page heading --}}
                <header class="mb-6">
                    <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                        {{ $pageTitle }}
                    </h1>
                </header>

                {{-- Rich content from WYSIWYG --}}
                <article class="prose prose-slate lg:prose-lg dark:prose-invert max-w-none">
                    {!! $pageTranslation?->content ?: '<p>' . e(__('لا يوجد محتوى لهذه الصفحة حالياً.')) . '</p>' !!}
                </article>
            </div>
        </section>
    @endif

    {{-- -----------------------------------------------------------------
         Dynamic sections rendering (Page Builder)
         - If sections exist, we render them in order.
         - Each section type generates its own $data payload.
       ----------------------------------------------------------------- --}}
    @if (!empty($builderSections))
        @foreach ($builderSections as $builderSection)
            @php
                $componentKey = $builderSection['type'] ?? null;
                $component    = $sectionComponents[$componentKey] ?? null;

                if (! $component) {
                    continue;
                }

                $data = $builderSection['data'] ?? [];
            @endphp

            <x-dynamic-component
                :component="'template.sections.' . $component"
                :data="$data"
            />
        @endforeach
    @else
    @foreach ($page->sections as $section)
        @php
            /** @var \App\Models\Section $section */

            $key       = $section->type;                       // e.g. "hero", "hero_default", "features", ...
            $component = $sectionComponents[$key] ?? null;

            // Skip unknown types gracefully
            if (! $component) {
                continue;
            }

            $translation = $section->translation();
            $content     = $translation?->content ?? [];
            $title       = $translation?->title ?? '';

            /**
             * Prepare $data for each section type
             * - This $data array will be passed to the Blade component
             * - Each component expects specific props structure
             */
            $data = ($key === 'hero')
                ? SectionRenderer::render($section) // Dedicated renderer for hero-style sections
                : match ($key) {
                    'features' => [
                        'title'    => $title,
                        'subtitle' => $content['subtitle'] ?? '',
                        'features' => is_array($content['features'] ?? null)
                            ? $content['features']
                            : array_filter(
                                array_map(
                                    'trim',
                                    explode("\n", $content['features'] ?? '')
                                )
                            ),
                    ],

                    'features-2' => (function () use ($content, $title) {
                        $features = collect($content['features'] ?? [])
                            ->filter(function ($item) {
                                if (! is_array($item)) {
                                    return false;
                                }

                                $title       = trim((string) ($item['title'] ?? ''));
                                $description = trim((string) ($item['description'] ?? ''));
                                $icon        = trim((string) ($item['icon'] ?? ''));

                                return $title !== '' || $description !== '' || $icon !== '';
                            })
                            ->map(function ($item) {
                                return [
                                    'icon'        => $item['icon'] ?? '',
                                    'title'       => $item['title'] ?? '',
                                    'description' => $item['description'] ?? '',
                                ];
                            })
                            ->values()
                            ->all();

                        // Background preset mapping (design tokens)
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
                                '#ffffff', '#fff'    => 'white',
                                '#f9fafb', '#f8fafc' => 'gray',
                                '#faf5f0', '#f5e9df' => 'stone',
                                '#e2e8f0', '#cbd5e1' => 'slate-light',
                                '#0f172a', '#111827' => 'slate-dark',
                                '#18181b'            => 'zinc-dark',
                                '#020617'            => 'black',
                                '#eff6ff', '#e0f2fe' => 'sky',
                                '#dbeafe', '#bfdbfe' => 'blue',
                                '#4f46e5', '#312e81' => 'indigo',
                                '#7c3aed', '#5b21b6' => 'violet',
                                '#9333ea', '#6b21a8' => 'purple',
                                '#fef3c7', '#fde68a' => 'amber',
                                '#f97316', '#ea580c' => 'orange',
                                '#ffe4e6', '#fecdd3' => 'rose',
                                '#e11d48', '#be123c' => 'rose-deep',
                                '#ecfdf5', '#d1fae5' => 'emerald',
                                '#059669', '#047857' => 'emerald-deep',
                                '#14b8a6', '#0f766e' => 'teal',
                                default              => 'white',
                            };
                        }

                        return [
                            'title'              => $title,
                            'subtitle'           => $content['subtitle'] ?? '',
                            'button_text'        => $content['button_text'] ?? '',
                            'button_url'         => $content['button_url'] ?? '',
                            'background_variant' => $backgroundVariant,
                            'features'           => $features,
                        ];
                    })(),

                    'features-3' => (function () use ($content, $title) {
                        $features = collect($content['features'] ?? [])
                            ->filter(function ($item) {
                                if (! is_array($item)) {
                                    return false;
                                }

                                $title       = trim((string) ($item['title'] ?? ''));
                                $description = trim((string) ($item['description'] ?? ''));
                                $icon        = trim((string) ($item['icon'] ?? ''));

                                return $title !== '' || $description !== '' || $icon !== '';
                            })
                            ->map(function ($item) {
                                return [
                                    'icon'        => $item['icon'] ?? '',
                                    'title'       => $item['title'] ?? '',
                                    'description' => $item['description'] ?? '',
                                ];
                            })
                            ->values()
                            ->all();

                        return [
                            'title'    => $title,
                            'subtitle' => $content['subtitle'] ?? '',
                            'features' => $features,
                        ];
                    })(),

                    'cta' => [
                        'title'               => $title,
                        'subtitle'            => $content['subtitle'] ?? '',
                        'badge'               => $content['badge'] ?? '',
                        'primary_button_text' => $content['primary_button_text'] ?? ($content['button_text'] ?? ''),
                        'primary_button_url'  => $content['primary_button_url'] ?? ($content['button_url'] ?? ''),
                    ],

                    'faq' => (function () use ($content, $title) {
                        $items = collect($content['items'] ?? $content['faq'] ?? [])
                            ->map(function ($item) {
                                if (! is_array($item)) {
                                    $question = trim((string) $item);
                                    return $question === ''
                                        ? null
                                        : ['question' => $question, 'answer' => ''];
                                }

                                $question = trim((string) ($item['question'] ?? ''));
                                $answer   = trim((string) ($item['answer'] ?? ''));

                                return ($question === '' && $answer === '')
                                    ? null
                                    : [
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
                        'title'    => $title,
                        'subtitle' => $content['subtitle'] ?? '',
                        'services' => Service::with('translations')
                            ->orderBy('order')
                            ->get(),
                    ],

                    'home-works' => [
                        'title'         => $title,
                        'subtitle'      => $content['subtitle'] ?? '',
                        'button_text-1' => $content['button_text-1'] ?? '',
                        'button_url-1'  => $content['button_url-1'] ?? '',
                    ],

                    'works', 'banner' => [
                        'title'    => $title,
                        'subtitle' => $content['subtitle'] ?? '',
                    ],

                    'testimonials' => [
                        'title'        => $title,
                        'subtitle'     => $content['subtitle'] ?? '',
                        'testimonials' => Testimonial::approved()
                            ->with('translations')
                            ->orderBy('order')
                            ->get(),
                    ],

                    'hosting-plans' => (function () use ($content, $title) {
                        $cat   = null;

                        $query = \App\Models\Plan::where('is_active', true)
                            ->with(['translations', 'category.translations'])
                            ->orderBy('id', 'asc');

                        // Filter by explicit plan_category_id
                        if (! empty($content['plan_category_id'])) {
                            $query->where('plan_category_id', (int) $content['plan_category_id']);

                            $cat = \App\Models\PlanCategory::with('translations')
                                ->find((int) $content['plan_category_id']);
                        }
                        // Or filter by category slug
                        elseif (! empty($content['plan_category_slug'])) {
                            $slug = (string) $content['plan_category_slug'];

                            $cat = \App\Models\PlanCategory::whereHas('translations', function ($q) use ($slug) {
                                    $q->where('slug', $slug)->where('locale', app()->getLocale());
                                })
                                ->with('translations')
                                ->first();

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
                                // If no category found, force empty result
                                $query->whereRaw('0 = 1');
                            }
                        }

                        $plans = $query->get();

                        return [
                            'title'    => $title ?? '',
                            'subtitle' => $content['subtitle'] ?? '',
                            'plans'    => $plans,
                            'category' => $cat,
                        ];
                    })(),

                    'templates' => [
                        'title'     => $title,
                        'subtitle'  => $content['subtitle'] ?? '',
                        'templates' => \App\Models\Template::with('translations')
                            ->latest()
                            ->take(8)
                            ->get(),
                    ],

                    'blog' => [
                        'title'         => $title,
                        'subtitle'      => $content['subtitle'] ?? '',
                        'button_text-1' => $content['button_text-1'] ?? '',
                        'button_url-1'  => $content['button_url-1'] ?? '',
                    ],

                    'search-domain' => (function () use ($title) {
                        // TLDs in catalog
                        $defaultTlds = DomainTld::where('in_catalog', true)
                            ->orderBy('tld')
                            ->pluck('tld')
                            ->map(fn ($t) => strtolower(ltrim($t, '.')))
                            ->values()
                            ->all();

                        // Fallback prices for 1-year register
                        $fallbackPrices = DomainTldPrice::with('tld')
                            ->whereIn(
                                'domain_tld_id',
                                DomainTld::where('in_catalog', true)->pluck('id')
                            )
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
                            'title'           => $title,
                            'subtitle'        => '',
                            'default_tlds'    => $defaultTlds,
                            'fallback_prices' => $fallbackPrices,
                            'currency'        => 'USD',
                        ];
                    })(),

                    'templates-pages' => [
                        'max_price'           => $content['max_price'] ?? 500,
                        'sort_by'             => request('sort', $content['sort_by'] ?? 'default'),
                        'show_filter_sidebar' => $content['show_filter_sidebar'] ?? true,
                        'selectedCategory'    => $content['selectedCategory'] ?? 'all',
                        'templates'           => \App\Models\Template::with([
                                'translations',
                                'categoryTemplate.translations',
                            ])
                            ->latest()
                            ->take(60)
                            ->get(),
                        'categories' => \App\Models\CategoryTemplate::with([
                                'translations' => function ($q) {
                                    $q->where('locale', app()->getLocale())
                                      ->orWhere('locale', 'ar');
                                },
                            ])
                            ->get()
                            ->map(function ($cat) {
                                $t =
                                    $cat->translations->firstWhere('locale', app()->getLocale())
                                    ?? $cat->translations->firstWhere('locale', 'ar');

                                $cat->translated_name = $t?->name ?? 'غير معروف';
                                $cat->translated_slug = $t?->slug ?? ($cat->slug ?? 'uncategorized');

                                return $cat;
                            }),
                    ],

                    default => [],
                };
        @endphp

        {{-- -----------------------------------------------------------------
             Render each section using its dedicated Blade component
             Special cases (templates-pages, search-domain, hosting-plans)
             receive explicit props; others receive a generic `$data` payload.
           ----------------------------------------------------------------- --}}
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
        @elseif ($component === 'hero_default')
            <x-dynamic-component
                :component="'template.sections.' . $component"
                :section="$section"
                :title="$title"
                :content="$content"
                :variant="$section->variant"
            />
        @else
            {{-- Default: component expects a `data` prop and optionally `templates` --}}
            <x-dynamic-component
                :component="'template.sections.' . $component"
                :data="$data"
                :templates="$data['templates'] ?? collect()"
            />
        @endif
    @endforeach
    @endif
@endsection
