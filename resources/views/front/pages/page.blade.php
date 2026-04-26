{{-- resources/views/front/pages/page.blade.php --}}
@php
    use App\Models\Media;
    use App\Models\Template;
    use Illuminate\Support\Str;
    use App\Support\SeoMeta;

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

@endphp

@extends('front.layouts.app')

@section('content')

    @php
        $legacySections = $page->sections->where('is_active', true)->values();

        $shouldRenderLegacySections = $legacySections->isNotEmpty();

        $legacyTemplates = collect();

        if (
            $shouldRenderLegacySections &&
            $legacySections->contains(fn($section) => $section->type === 'templates_showcase')
        ) {
            $legacyTemplates = Template::query()
                ->with(['translations', 'categoryTemplate.translation', 'categoryTemplate.translations'])
                ->latest('id')
                ->limit(8)
                ->get();
        }
    @endphp

    {{-- =========================================================
     CASE 1: Admin Sections
========================================================== --}}
    @if ($shouldRenderLegacySections)
        @foreach ($legacySections as $section)
            @include('front.pages.partials.definition-section', [
                'section' => $section,
            ])
        @endforeach


        {{-- =========================================================
     CASE 2: WYSIWYG fallback
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
