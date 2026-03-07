@php
    $menuItems = collect();

    if (!empty($footerMenu?->items)) {
        foreach ($footerMenu->items as $item) {
            if (in_array($item->type, ['link', 'page'], true)) {
                $menuItems->push([
                    'label' => (string) $item->label,
                    'url' => (string) $item->url,
                ]);
            }

            if ($item->type === 'dropdown') {
                foreach ($item->processedChildren as $child) {
                    $menuItems->push([
                        'label' => (string) ($child['current_label'] ?? ''),
                        'url' => (string) ($child['current_url'] ?? '#'),
                    ]);
                }
            }
        }
    }

    $menuItems = $menuItems
        ->filter(fn (array $link) => trim((string) ($link['label'] ?? '')) !== '')
        ->values();
@endphp

@if ($menuItems->isEmpty())
    <li><a href="{{ route('frontend.home') }}" class="{{ $linkClass ?? '' }}">{{ t('frontend.Home', 'Home') }}</a></li>
    <li><a href="{{ route('domains.page') }}" class="{{ $linkClass ?? '' }}">{{ t('frontend.Domain', 'Domains') }}</a></li>
    <li><a href="{{ route('cart') }}" class="{{ $linkClass ?? '' }}">{{ t('frontend.Cart', 'Cart') }}</a></li>
    <li><a href="{{ route('testimonials.submit') }}" class="{{ $linkClass ?? '' }}">{{ t('frontend.Contact_Us', 'Contact Us') }}</a></li>
@else
    @foreach ($menuItems as $menuItem)
        <li>
            <a href="{{ $menuItem['url'] ?: '#' }}" class="{{ $linkClass ?? '' }}">
                {{ $menuItem['label'] }}
            </a>
        </li>
    @endforeach
@endif

