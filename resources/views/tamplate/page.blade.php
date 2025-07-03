<x-template.layouts.index-layouts
    title="بال قول لتكنولوجيا المعلومات - مواقع الكترونية واستضافة عربية"
    description="شركة فلسطينية متخصصة في برمجة وتصميم المواقع الالكترونية..."
    keywords="خدمات حجز دومين , افضل شركة برمجيات , استضافة مواقع , ..."
    ogImage="{{ asset('assets/images/services.jpg') }}"
>
    {{-- محتوى الصفحة --}}
    <div class="container mx-auto py-10">
        <h1 class="text-3xl font-bold mb-6">
            {{ $page->translation()?->title ?? 'عنوان غير متوفر' }}
        </h1>

        <div class="prose max-w-4xl">
            {!! $page->translation()?->content ?? '<p>لا يوجد محتوى.</p>' !!}
        </div>
    </div>

    @php
        $sectionComponents = [
            'hero' => 'hero',
            'features' => 'features',
            'services' => 'services',
            'templates' => 'templates',
            'works' => 'works',
            'testimonials' => 'testimonials',
            'blog' => 'blog',
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
                    'button_text' => $content['button_text'] ?? '',
                    'button_url' => $content['button_url'] ?? '#',
                ],
                'features' => [
                    'title' => $title,
                    'subtitle' => $content['subtitle'] ?? '',
                    'features' => is_array($content['features'] ?? null)
                        ? $content['features']
                        : array_filter(array_map('trim', explode("\n", $content['features'] ?? ''))),
                ],
                'services', 'templates', 'works', 'testimonials', 'blog' => [
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
