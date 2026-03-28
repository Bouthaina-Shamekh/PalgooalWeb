@php
    $pageTitle = $page->translations->firstWhere('locale', app()->getLocale())?->title
        ?? $page->translations->first()?->title
        ?? $page->slug
        ?? 'Tenant Site';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&family=Cairo:wght@200..1000&display=swap"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="{{ mix('assets/tamplate/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/dashboard/fonts/tabler-icons.min.css') }}">
</head>
<body class="m-0 bg-white text-purple-brand overflow-x-hidden font-Cairo">
    <main>
        @foreach ($page->sections as $section)
            @php
                $sectionTrans = $section->translations->firstWhere('locale', app()->getLocale())
                    ?? $section->translations->first();
                $content = $sectionTrans?->content ?? [];
                $partial = 'tenant.sections.' . \Illuminate\Support\Str::slug($section->type ?? 'generic', '_');
            @endphp
            @includeFirst([
                $partial,
                'tenant.sections.generic'
            ], [
                'section' => $section,
                'translation' => $sectionTrans,
                'content' => $content,
            ])
        @endforeach
    </main>

    @include('front.layouts.partials.end')
