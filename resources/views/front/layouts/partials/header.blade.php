@php
    $availableHeaders = config('front_layouts.headers', []);
    $defaultVariant = config('front_layouts.defaults.header', 'default');
    $headerVariant = $settings?->active_header_variant ?: $defaultVariant;

    if (! array_key_exists($headerVariant, $availableHeaders)) {
        $headerVariant = $defaultVariant;
    }

    $headerView = "front.layouts.headers.{$headerVariant}";
    if (! view()->exists($headerView)) {
        $headerView = 'front.layouts.headers.default';
    }

    $header = \App\Models\Header::with(['items.translations', 'items.page.translations'])->first();
@endphp

@include($headerView, ['header' => $header])
