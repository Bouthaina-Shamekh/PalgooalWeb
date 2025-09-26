@props([
  'title' => '��?�?�? ��?�?�? - ��?�?�?�? ��?�?�?�?�? ��?�?�?�?�?�?�?',
  'description' => '��?�?�? ��?�?�?�?�? ��?�?�?�?�?�? ��?�?�?�?�?�?�? ��?�?�?�?�?�? ��?�? ��?�?�?�?�? ��?�?�? ��?�?�?.',
  'keywords' => '��?�?�?�?�?�?�?, ��?�?�?�?�?, ��?���?�?�? ��?�?�?�?�?, WordPress, ��?�?�? ��?�?�?',
  'ogImage' => asset('assets/images/default-og.jpg'),
  'seo' => null,
])

@php
    use App\Support\SeoMeta;

    $baseSeo = SeoMeta::make([
        'title' => $title,
        'description' => $description,
        'keywords' => $keywords,
        'image' => $ogImage,
    ]);

    $seoPayload = isset($seo)
        ? $baseSeo->with($seo instanceof SeoMeta ? $seo->toArray() : (array) $seo)
        : $baseSeo;

    $defaultSchema = trim(view('tamplate.layouts.schema')->render());
    if ($defaultSchema !== '') {
        $existingSchema = $seoPayload->toArray()['schema'] ?? [];
        $existingSchema[] = $defaultSchema;
        $seoPayload = $seoPayload->with(['schema' => $existingSchema]);
    }
@endphp

@include('tamplate.layouts.head', ['seo' => $seoPayload])
@include('tamplate.layouts.header')
{{ $slot }}

<!-- [ Footer ] start -->
@include('tamplate.layouts.footer')
<!-- [ Footer ] end -->
<!-- [ Customizer ] start -->
@include('tamplate.layouts.end')
<!-- [ Customizer ] end -->
