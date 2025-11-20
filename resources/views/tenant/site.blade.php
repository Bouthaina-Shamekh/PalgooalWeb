<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->translations->firstWhere('locale', app()->getLocale())?->title ?? $page->slug ?? 'Tenant Site' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.4/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-white text-gray-900">
    <div class="min-h-screen">
        <header class="border-b bg-gradient-to-r from-blue-50 to-blue-100/60">
            <div class="max-w-5xl mx-auto px-6 py-6">
                <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">{{ $tenantSubscription->domain_name }}</p>
                <h1 class="text-3xl font-bold text-blue-900">
                    {{ $page->translations->firstWhere('locale', app()->getLocale())?->title ?? $page->slug ?? 'صفحة' }}
                </h1>
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-6 py-10 space-y-8">
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
    </div>
</body>
</html>
