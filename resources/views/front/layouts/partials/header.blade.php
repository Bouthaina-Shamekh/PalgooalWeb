@php
    use App\Models\Header;

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

    $headerLocation = (string) ($availableHeaders[$headerVariant]['menu_location'] ?? 'header_primary');

    $headerQuery = Header::with(['items.translations', 'items.page.translations'])
        ->where('is_active', true);

    $header = (clone $headerQuery)
        ->where('location_key', $headerLocation)
        ->first();

    if (! $header) {
        $header = (clone $headerQuery)
            ->where('location_key', 'like', 'header_%')
            ->orderBy('id')
            ->first();
    }

    if (! $header) {
        $header = (clone $headerQuery)->orderBy('id')->first();
    }

    $headerVariantSettings = is_array($settings?->header_variant_settings ?? null)
        ? ($settings->header_variant_settings[$headerVariant] ?? [])
        : [];

    if (!is_array($headerVariantSettings)) {
        $headerVariantSettings = [];
    }
@endphp

@include($headerView, ['header' => $header, 'headerVariantSettings' => $headerVariantSettings])
