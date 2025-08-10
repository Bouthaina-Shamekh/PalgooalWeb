@php
    use App\Models\Service;
    use App\Services\TemplateService;
    use App\Models\CategoryTemplate;
@endphp

<x-template.layouts.index-layouts
    title="{{ $page->translation()?->title ?? 'عنوان غير متوفر' }}"
    description="شركة فلسطينية متخصصة في برمجة وتصميم المواقع الالكترونية..."
    keywords="خدمات حجز دومين , افضل شركة برمجيات , استضافة مواقع , ..."
    ogImage="{{ asset('assets/images/services.jpg') }}"
>

    {{-- محتوى الصفحة --}}
    @if ($page->sections->isEmpty())
        <div class="container mx-auto py-10">
            <h1 class="text-3xl font-bold mb-6">
                {{ $page->translation()?->title ?? 'عنوان غير متوفر' }}
            </h1>

            <div class="prose max-w-4xl">
                {!! $page->translation()?->content ?? '<p>لا يوجد محتوى.</p>' !!}
            </div>
        </div>
    @endif

    @php
        $sectionComponents = [
            'hero' => 'hero',
            'features' => 'features',
            'services' => 'services',
            'templates' => 'templates',
            'works' => 'works',
            'home-works' => 'home-works',
            'testimonials' => 'testimonials',
            'blog' => 'blog',
            'banner' => 'banner',
            'Search-Domain' => 'search-domain',
            'templates-pages' => 'templates-pages',
        ];
    @endphp

    @foreach ($page->sections as $section)
        @php
            $key = $section->key;
            $component = $sectionComponents[$key] ?? null;
            $translation = $section->translation();
            $content = $translation?->content ?? [];
            $title = $translation?->title ?? '';

            $data = match ($key) {
                'hero' => [
                    'title' => $title,
                    'subtitle' => $content['subtitle'] ?? '',
                    'button_text-1' => $content['button_text-1'] ?? '',
                    'button_url-1' => $content['button_url-1'] ?? '',
                    'button_text-2' => $content['button_text-2'] ?? '',
                    'button_url-2' => $content['button_url-2'] ?? '',
                ],
                'features' => [
                    'title' => $title,
                    'subtitle' => $content['subtitle'] ?? '',
                    'features' => is_array($content['features'] ?? null)
                        ? $content['features']
                        : array_filter(array_map('trim', explode("\n", $content['features'] ?? ''))),
                ],
                'services' => [
                    'title' => $title,
                    'subtitle' => $content['subtitle'] ?? '',
                    'services' => Service::with('translations')->orderBy('order')->get(),
                ],
                'home-works' => [
                    'title' => $title,
                    'subtitle' => $content['subtitle'] ?? '',
                    'button_text-1' => $content['button_text-1'] ?? '',
                    'button_url-1' => $content['button_url-1'] ?? '',
                ],
                'works', 'testimonials', 'banner' => [
                    'title' => $title,
                    'subtitle' => $content['subtitle'] ?? '',
                ],
                'templates' => [
                    'title' => $title,
                    'subtitle' => $content['subtitle'] ?? '',
                    'templates' => \App\Models\Template::with('translations')->latest()->take(8)->get(),
                ],
                'blog' => [
                    'title' => $title,
                    'subtitle' => $content['subtitle'] ?? '',
                    'button_text-1' => $content['button_text-1'] ?? '',
                    'button_url-1' => $content['button_url-1'] ?? '',
                ],
                'Search-Domain' => [
                    'title' => $title,
                    'subtitle' => $content['subtitle'] ?? '',
                ],
                'templates-pages' => [
                    'max_price' => $content['max_price'] ?? 500,
                    'sort_by' => request('sort', $content['sort_by'] ?? 'default'),
                    // 'sort_by' => $content['sort_by'] ?? 'default',
                    'show_filter_sidebar' => $content['show_filter_sidebar'] ?? true,
                    'selectedCategory' => $content['selectedCategory'] ?? 'all',
                    'templates' => \App\Models\Template::with(['translations','categoryTemplate.translations'])->latest()->take(60)->get(),
                    'categories' => \App\Models\CategoryTemplate::with(['translations' => function ($q) {
                        $q->where('locale', app()->getLocale())->orWhere('locale','ar');
                    }])->get()->map(function ($cat) {
                        $t = $cat->translations->firstWhere('locale', app()->getLocale()) ?? $cat->translations->firstWhere('locale','ar');
                        $cat->translated_name = $t?->name ?? 'غير معرف';
                        $cat->translated_slug = $t?->slug ?? ($cat->slug ?? 'uncategorized');
                        return $cat;
                    }),
                ],
                default => [],
            };
        @endphp

        @if ($component === 'templates-pages')
            <x-dynamic-component :component="'template.sections.' . $component"
                :templates="$data['templates']"
                :categories="$data['categories']"
                :max_price="$data['max_price']"
                :sort_by="$data['sort_by']"
                :show_filter_sidebar="$data['show_filter_sidebar']"
                :selectedCategory="$data['selectedCategory']"
            />
        @else
            <x-dynamic-component :component="'template.sections.' . $component" :data="$data" :templates="$data['templates'] ?? collect()" />
        @endif
    @endforeach
</x-template.layouts.index-layouts>
