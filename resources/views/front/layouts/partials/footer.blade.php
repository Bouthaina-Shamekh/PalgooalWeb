@php
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
@endphp

@include($footerView)
