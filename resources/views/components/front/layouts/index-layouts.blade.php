@include('components.template.layouts.index-layouts', [
    'title' => $title ?? (config('seo.default_title') ?? config('app.name', 'Palgoals')),
    'description' => $description ?? config('seo.default_description'),
    'keywords' => $keywords ?? config('seo.default_keywords', []),
    'ogImage' => $ogImage ?? asset(config('seo.default_image', 'assets/images/default-og.jpg')),
    'seo' => $seo ?? null,
    'slot' => $slot,
])
