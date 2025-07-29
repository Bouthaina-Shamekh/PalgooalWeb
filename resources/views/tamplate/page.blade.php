@php
    use App\Models\Service;
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
                'templates', 'works', 'testimonials', 'banner' => [
                    'title' => $title,
                    'subtitle' => $content['subtitle'] ?? '',
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
                default => [],
            };
        @endphp

        @if ($component && !empty($data))
            <x-dynamic-component :component="'template.sections.' . $component" :data="$data" />

        @endif
    @endforeach

</x-template.layouts.index-layouts>
