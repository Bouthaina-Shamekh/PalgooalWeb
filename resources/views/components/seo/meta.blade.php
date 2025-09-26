@props([
    'meta' => null,
])

@php
    use App\Support\SeoMeta;
    use Illuminate\Support\Str;

    $metaInstance = $meta instanceof SeoMeta
        ? $meta
        : (is_null($meta) ? SeoMeta::defaults() : SeoMeta::fromArray((array) $meta));

    $data = $metaInstance->toArray();
    $title = $data['title'] ?? config('app.name');
    $description = $data['description'] ?? null;
    $keywords = $metaInstance->keywordsString();
    $canonical = $data['canonical'] ?? null;
    $robots = $data['robots'] ?? (config('seo.default_robots') ?? null);
    $type = $data['type'] ?? (config('seo.default_type') ?? 'website');
    $locale = $data['locale'] ?? app()->getLocale();
    $siteName = $data['site_name'] ?? (config('seo.site_name') ?? config('app.name'));
    $twitterCard = $data['twitter_card'] ?? config('seo.twitter.card', 'summary_large_image');
    $twitterHandle = $data['twitter_handle'] ?? config('seo.twitter.handle');
    $alternates = $data['alternates'] ?? [];
    $extraMeta = $data['extra_meta'] ?? [];
    $extraLinks = $data['extra_links'] ?? [];
    $schemaEntries = $data['schema'] ?? [];

    $currentUrl = url()->current();
    if ($canonical === true || $canonical === null) {
        $canonical = $currentUrl;
    }

    $image = $data['image'] ?? null;
    if ($image) {
        $image = Str::startsWith($image, ['http://', 'https://']) ? $image : asset($image);
    }

    $ogLocale = null;
    if ($locale) {
        $parts = explode('-', $locale);
        $language = strtolower($parts[0] ?? '');
        $region = strtoupper($parts[1] ?? ($language ? $language : ''));
        $ogLocale = trim($language . '_' . $region, '_');
    }
@endphp

<title>{{ $title }}</title>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
@if ($description)
    <meta name="description" content="{{ $description }}" />
@endif
@if ($keywords)
    <meta name="keywords" content="{{ $keywords }}" />
@endif
@if ($robots)
    <meta name="robots" content="{{ $robots }}" />
@endif
@if ($canonical)
    <link rel="canonical" href="{{ $canonical }}" />
@endif
@if ($locale)
    <meta name="language" content="{{ $locale }}" />
@endif

{{-- Open Graph --}}
<meta property="og:title" content="{{ $title }}" />
@if ($description)
    <meta property="og:description" content="{{ $description }}" />
@endif
<meta property="og:type" content="{{ $type }}" />
<meta property="og:url" content="{{ $canonical }}" />
@if ($siteName)
    <meta property="og:site_name" content="{{ $siteName }}" />
@endif
@if ($ogLocale)
    <meta property="og:locale" content="{{ $ogLocale }}" />
@endif
@if ($image)
    <meta property="og:image" content="{{ $image }}" />
@endif

{{-- Twitter --}}
@if ($twitterCard)
    <meta name="twitter:card" content="{{ $twitterCard }}" />
@endif
@if ($twitterHandle)
    <meta name="twitter:site" content="{{ $twitterHandle }}" />
    <meta name="twitter:creator" content="{{ $twitterHandle }}" />
@endif
<meta name="twitter:title" content="{{ $title }}" />
@if ($description)
    <meta name="twitter:description" content="{{ $description }}" />
@endif
@if ($image)
    <meta name="twitter:image" content="{{ $image }}" />
@endif
@if ($canonical)
    <meta name="twitter:url" content="{{ $canonical }}" />
@endif

{{-- Hreflang alternates --}}
@foreach ($alternates as $alternate)
    @php($hrefLang = $alternate['locale'] ?? null)
    @php($href = $alternate['url'] ?? null)
    @if ($hrefLang && $href)
        <link rel="alternate" hreflang="{{ $hrefLang }}" href="{{ $href }}" />
    @endif
@endforeach

{{-- Extra meta/link entries --}}
@foreach ($extraMeta as $entry)
    @php($name = $entry['name'] ?? null)
    @php($property = $entry['property'] ?? null)
    @php($content = $entry['content'] ?? null)
    @if ($content !== null)
        @if ($property)
            <meta property="{{ $property }}" content="{{ $content }}" />
        @elseif ($name)
            <meta name="{{ $name }}" content="{{ $content }}" />
        @endif
    @endif
@endforeach

@foreach ($extraLinks as $entry)
    @php($rel = $entry['rel'] ?? null)
    @php($href = $entry['href'] ?? null)
    @php($typeAttr = $entry['type'] ?? null)
    @if ($rel && $href)
        <link rel="{{ $rel }}" href="{{ $href }}" @if ($typeAttr) type="{{ $typeAttr }}" @endif />
    @endif
@endforeach

{{-- Structured data --}}
@foreach ($schemaEntries as $schema)
    @php
        $json = is_string($schema) ? $schema : json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    @endphp
    @if ($json)
        <script type="application/ld+json">{!! $json !!}</script>
    @endif
@endforeach
