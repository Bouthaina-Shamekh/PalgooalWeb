@php
    $sections = collect($sections ?? [])
        ->filter(fn ($sectionItem) => $sectionItem instanceof \App\Models\Section && $sectionItem->is_active)
        ->sortBy('order')
        ->values();
@endphp

@foreach ($sections as $section)
    @php
        $sectionTrans = $section->translations->firstWhere('locale', app()->getLocale())
            ?? $section->translations->first();
        $content = is_array($sectionTrans?->content ?? null) ? $sectionTrans->content : [];
        $partial = 'tenant.sections.' . \Illuminate\Support\Str::slug($section->type ?? 'generic', '_');
    @endphp

    @includeFirst([
        $partial,
        'tenant.sections.generic',
    ], [
        'section' => $section,
        'translation' => $sectionTrans,
        'content' => $content,
        'subscription' => $subscription ?? null,
        'page' => $page ?? null,
        'siteNavigationPages' => $siteNavigationPages ?? collect(),
    ])
@endforeach
