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

    $defaultSchema = trim(view('front.layouts.partials.schema')->render());
    if ($defaultSchema !== '') {
        $existingSchema = $seoPayload->toArray()['schema'] ?? [];
        $existingSchema[] = $defaultSchema;
        $seoPayload = $seoPayload->with(['schema' => $existingSchema]);
    }
@endphp

@include('front.layouts.partials.head', ['seo' => $seoPayload])
@include('front.layouts.partials.header')
{{ $slot }}

<!-- [ Footer ] start -->
@include('front.layouts.partials.footer')
<!-- [ Footer ] end -->
<!-- [ Customizer ] start -->
@include('front.layouts.partials.end')
<!-- [ Customizer ] end -->
