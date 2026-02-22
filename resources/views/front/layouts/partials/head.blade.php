{{-- resources/views/front/layouts/partials/head.blade.php --}}
@php
    use App\Support\SeoMeta;

    /**
     * Normalize incoming SEO payload
     * - $seo ممكن يكون SeoMeta أو array أو null
     * - $settings يُفترض جاي من View Composer (site_title, favicon, ...)
     */
    if ($seo instanceof SeoMeta) {
        $seoMeta = $seo;
    } elseif (is_array($seo ?? null)) {
        // لو جاينا SEO كـ array من صفحة معينة
        $seoMeta = SeoMeta::make($seo);
    } else {
        // ديفولت عام للموقع
        $defaultTitle = $settings->site_title
            ?? config('seo.default_title')
            ?? config('app.name', 'Palgoals');

        $seoMeta = SeoMeta::make([
            'title'       => $defaultTitle,
            'description' => config('seo.default_description'),
            'keywords'    => config('seo.default_keywords', []),
            'image'       => asset(config('seo.default_image', 'assets/images/default-og.jpg')),
            'canonical'   => url()->current(),
            'type'        => 'website',
        ]);
    }
@endphp

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ current_dir() }}">

<head>
    {{-- SEO + OG + Twitter + Schema (كلها تتولّد من SeoMeta) --}}
    <x-seo.meta :meta="$seoMeta" />

    {{-- CSRF --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Favicons --}}
    <link
        rel="icon"
        href="{{ $settings?->favicon ? asset('storage/' . $settings->favicon) : asset('assets/images/favicon.ico') }}"
        type="image/x-icon"
    >

    {{-- مهم: لا نضيف <title> ثاني هنا حتى لا يتكرر العنوان --}}
    {{-- <title> سيتم توليده داخل x-seo.meta من SeoMeta --}}

    {{-- Fonts and Styles --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&family=Cairo:wght@200..1000&display=swap"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="{{ mix('assets/tamplate/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/dashboard/fonts/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    @stack('meta')
    @stack('styles')
</head>

<body x-data="languageSwitcher()" class="font-Cairo scroll-smooth">
