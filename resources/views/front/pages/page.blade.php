{{-- resources/views/front/pages/page.blade.php --}}
@php
    use App\Models\Service;
    use App\Models\Testimonial;
    use App\Models\DomainTld;
    use App\Models\DomainTldPrice;
    use App\Models\Media;
    use Illuminate\Support\Str;
    use App\Support\SeoMeta;
    use App\Support\Sections\SectionRenderer;

    /**
     * ------------------------------------------------------------------
     * SEO & OpenGraph preparation for current page
     * ------------------------------------------------------------------
     */

    // Resolve current translation for active locale
    $pageTranslation = $page->translation();
    $fallbackTitle = t('frontend.page_default_title', 'Untitled Page');

    // <title> tag
    $pageTitle = $pageTranslation?->meta_title ?: $pageTranslation?->title ?: $fallbackTitle;

    // Raw content for meta description fallback
    $rawPageContent = is_string($pageTranslation?->content) ? $pageTranslation->content : '';

    // Meta description: prefer explicit meta_description, fallback to trimmed content
    $pageDescription = $pageTranslation?->meta_description ?: Str::limit(strip_tags($rawPageContent), 160);

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
        $keywordsFromTranslation = array_filter(array_map('trim', explode(',', $keywordsFromTranslation)));
    } elseif (!is_array($keywordsFromTranslation)) {
        $keywordsFromTranslation = [];
    }

    $pageKeywords = !empty($keywordsFromTranslation) ? $keywordsFromTranslation : $fallbackKeywords;

    /**
     * ------------------------------------------------------------------
     * Resolve OpenGraph image
     * ------------------------------------------------------------------
     * - If value is numeric: treat as Media ID from our Media Library
     * - If value is a URL (http/https/relative): use / wrap via asset()
     * - Fallback to default image
     * ------------------------------------------------------------------
     */
    $rawOgImage = $pageTranslation?->og_image;
    $pageOgImage = null;

    if (is_numeric($rawOgImage)) {
        // Case A: og_image stores Media ID
        $media = Media::find((int) $rawOgImage);
        if ($media) {
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
    if (!$pageOgImage) {
        $defaultOg = config('seo.default_image', 'assets/images/services.jpg');
        $pageOgImage = Str::startsWith($defaultOg, ['http://', 'https://', '//']) ? $defaultOg : asset($defaultOg);
    }

    /**
     * ------------------------------------------------------------------
     * Schema.org metadata
     * ------------------------------------------------------------------
     */
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

    /**
     * ------------------------------------------------------------------
     * Build SEO overrides object (SeoMeta)
     * ------------------------------------------------------------------
     */
    $seoOverrides = SeoMeta::make([
        'title' => $pageTitle,
        'description' => $pageDescription,
        'keywords' => $pageKeywords,
        'image' => $pageOgImage,
        'canonical' => url()->current(),
        'type' => $page->is_home ? 'website' : 'article',
        'schema' => [$pageSchema],
    ]);

    // Expose vars to layout
    $title = $pageTitle;
    $description = $pageDescription;
    $keywords = $pageKeywords;
    $ogImage = $pageOgImage;
    $seo = $seoOverrides;

    /**
     * ------------------------------------------------------------------
     * Section → Blade component mapping
     * ------------------------------------------------------------------
     * Used for:
     *  - builderSections (GrapesJS → normalizedSections())
     *  - legacy $page->sections from admin module
     * ------------------------------------------------------------------
     */
    $sectionComponents = [
        'hero' => 'hero',
        'hero_default' => 'hero_default',
        'hero-template' => null, // inline
        'support-hero' => null, // inline
        'text' => null, // inline
        'image' => null, // inline
        'button' => null, // inline
        'section' => null, // inline
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

    /**
     * ------------------------------------------------------------------
     * Builder Data (GrapesJS)
     * ------------------------------------------------------------------
     * - $publishedHtml / $publishedCss: snapshot from "Publish" action
     * - $builderSections: normalized sections array from project JSON
     * ------------------------------------------------------------------
     */
    $builder = \App\Models\PageBuilderStructure::query()
        ->where('page_id', $page->id)
        ->whereIn('locale', [app()->getLocale(), config('app.fallback_locale', 'ar')])
        ->orderByRaw('FIELD(locale, ?, ?)', [app()->getLocale(), config('app.fallback_locale', 'ar')])
        ->first();

    $publishedHtml = $builder?->published_html;
    $publishedCss = $builder?->published_css_path;

    $builderSections = $builder?->normalizedSections() ?? [];
@endphp

@extends('front.layouts.app')

@section('content')

@php
    /**
     * ------------------------------------------------------------
     * STEP 1: Prepare dynamic sections from Builder (DB driven)
     * ------------------------------------------------------------
     */
    $dynamicSections = collect($builderSections)
        ->filter(fn ($s) => isset($s['type'], $s['data']))
        ->mapWithKeys(function ($s) {
            $type = $s['type'];
            $data = is_array($s['data']) ? $s['data'] : [];

            // Resolve DB-backed data (services, templates, etc.)
            $data = \App\Support\Sections\SectionQueryResolver::resolve($type, $data);

            return [$type => $data];
        });
@endphp

{{-- =========================================================
     CASE 1: Published Builder HTML (PRIMARY – like Elementor)
========================================================== --}}
@if ($publishedHtml)

    @push('styles')
        @if ($publishedCss)
            <link rel="stylesheet" href="{{ asset($publishedCss) }}">
        @endif
    @endpush

    @php
        $html = $publishedHtml;

        /**
         * ------------------------------------------------------------
         * STEP 2: Inject dynamic sections into snapshot HTML
         * Placeholders format:
         *   <div data-pg-dynamic="services"></div>
         * ------------------------------------------------------------
         */
        foreach ($dynamicSections as $type => $data) {
            $pattern = '/<[^>]*data-pg-dynamic=["\']'.preg_quote($type, '/').'["\'][^>]*>(.*?)<\/[^>]+>/s';

            if (preg_match($pattern, $html)) {
                $replacement = view(
                    'components.template.sections.' . $type,
                    ['data' => $data]
                )->render();

                $html = preg_replace($pattern, $replacement, $html, 1);
            }
        }
    @endphp

    <div class="pg-builder-page">
        {!! $html !!}
    </div>


{{-- =========================================================
     CASE 2: Builder Sections ONLY (no snapshot yet)
========================================================== --}}
@elseif (!empty($builderSections))

    @foreach ($builderSections as $builderSection)
        @php
            $type = $builderSection['type'] ?? null;
            $component = $sectionComponents[$type] ?? null;
            $data = $dynamicSections[$type] ?? [];
        @endphp

        @if ($component)
            <x-dynamic-component
                :component="'template.sections.' . $component"
                :data="$data"
            />
        @endif
    @endforeach


{{-- =========================================================
     CASE 3: Legacy Admin Sections
========================================================== --}}
@elseif ($page->sections->isNotEmpty())

    @foreach ($page->sections as $section)
        @php
            $key = $section->type;
            $component = $sectionComponents[$key] ?? null;
        @endphp

        @if ($key === 'hero')
            {!! \App\Support\Sections\SectionRenderer::render($section) !!}
        @elseif ($component)
            <x-dynamic-component
                :component="'template.sections.' . $component"
                :data="[]"
            />
        @endif
    @endforeach


{{-- =========================================================
     CASE 4: WYSIWYG fallback
========================================================== --}}
@else

    <section class="bg-slate-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-extrabold mb-6">
                {{ $pageTitle }}
            </h1>

            <article class="prose max-w-none">
                {!! $pageTranslation?->content ?: '<p>لا يوجد محتوى</p>' !!}
            </article>
        </div>
    </section>

@endif
@endsection

