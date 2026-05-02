@php
    $pageTitle = $page->translations->firstWhere('locale', app()->getLocale())?->title
        ?? $page->translations->first()?->title
        ?? $page->slug
        ?? 'Tenant Site';
    $siteNavigationPages = $subscription->canonicalPages()
        ->with('translations')
        ->where('context', 'tenant')
        ->where('is_active', true)
        ->orderByDesc('is_home')
        ->orderBy('id')
        ->get();
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
    @php $tenantThemeCssUrl = \App\Services\Tenancy\TenantThemeCssGenerator::publicUrlFor($subscription); @endphp
    @if ($tenantThemeCssUrl)
        <link rel="stylesheet" href="{{ $tenantThemeCssUrl }}">
    @endif
</head>
<body class="m-0 bg-white text-purple-brand overflow-x-hidden font-Cairo">
    <main>
        @include('tenant.partials.render-sections', [
            'page' => $headerPage ?? null,
            'sections' => $headerPage?->sections ?? collect(),
            'subscription' => $subscription,
            'siteNavigationPages' => $siteNavigationPages,
        ])

        @include('tenant.partials.render-sections', [
            'page' => $page,
            'sections' => $page->sections,
            'subscription' => $subscription,
            'siteNavigationPages' => $siteNavigationPages,
        ])

        @include('tenant.partials.render-sections', [
            'page' => $footerPage ?? null,
            'sections' => $footerPage?->sections ?? collect(),
            'subscription' => $subscription,
            'siteNavigationPages' => $siteNavigationPages,
        ])
    </main>

    @include('front.layouts.partials.end')
