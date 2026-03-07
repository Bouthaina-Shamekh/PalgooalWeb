@php
    use App\Models\Header;

    $availableFooters = config('front_layouts.footers', []);
    $defaultVariant = config('front_layouts.defaults.footer', 'default');
    $footerVariant = $settings?->active_footer_variant ?: $defaultVariant;

    if (! array_key_exists($footerVariant, $availableFooters)) {
        $footerVariant = $defaultVariant;
    }

    $footerView = "front.layouts.footers.{$footerVariant}";
    if (! view()->exists($footerView)) {
        $footerView = 'front.layouts.footers.default';
    }

    $footerLocation = (string) ($availableFooters[$footerVariant]['menu_location'] ?? 'footer_primary');

    $footerMenuQuery = Header::with(['items.translations', 'items.page.translations'])
        ->where('is_active', true);

    $footerMenu = (clone $footerMenuQuery)
        ->where('location_key', $footerLocation)
        ->first();

    if (! $footerMenu) {
        $footerMenu = (clone $footerMenuQuery)
            ->where('location_key', 'like', 'footer_%')
            ->orderBy('id')
            ->first();
    }
@endphp

@include($footerView, ['footerMenu' => $footerMenu])
