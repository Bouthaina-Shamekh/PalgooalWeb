@php
    use App\Support\SeoMeta;

    $baseTitle       = $title
        ?? (config('seo.default_title') ?? config('app.name', 'Palgoals'));

    $baseDescription = $description
        ?? config('seo.default_description');

    $baseKeywords    = $keywords
        ?? config('seo.default_keywords', []);

    $baseOgImage     = $ogImage
        ?? asset(config('seo.default_image', 'assets/images/default-og.jpg'));

    $baseSeo = SeoMeta::make([
        'title'       => $baseTitle,
        'description' => $baseDescription,
        'keywords'    => $baseKeywords,
        'image'       => $baseOgImage,
    ]);

    $seoPayload = isset($seo)
        ? $baseSeo->with($seo instanceof SeoMeta ? $seo->toArray() : (array) $seo)
        : $baseSeo;

    $defaultSchema = trim(view('front.layouts.partials.schema')->render());
    if ($defaultSchema !== '') {
        $existingSchema = $seoPayload->toArray()['schema'] ?? [];
        $existingSchema[] = $defaultSchema;
        $seoPayload = $seoPayload->with(['schema' => $existingSchema]);
    }
@endphp

{{-- Head (SEO + OG + Schema) --}}
@include('front.layouts.partials.head', [
    'seo' => $seoPayload ?? null,   {{-- مهم: نستخدم seoPayload اللي مرّ من الكومبوننت --}}
])

{{-- Header / Navigation --}}
@include('front.layouts.partials.header')

<div class="pc-container">
    <div class="pc-content">
        {{-- محتوى الكومبوننت (صفحة الهيرو، السكشنات، إلخ) --}}
        {{ $slot ?? '' }}

        {{-- في حال استخدمنا نفس layout مع @extends --}}
        @yield('content')
    </div>
</div>

{{-- Footer --}}
@include('front.layouts.partials.footer')

{{-- Scripts & closing tags --}}
@include('front.layouts.partials.end')
