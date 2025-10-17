@props([
    'name' => 'User',
    'size' => 48,
])

@php
    $displayName = trim($name ?? '');
    $segments = preg_split('/\s+/u', $displayName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $initials = collect($segments)->map(fn ($segment) => mb_strtoupper(mb_substr($segment, 0, 1)))->take(2)->implode('');
    if ($initials === '') {
        $initials = 'U';
    }

    $hash = md5($displayName !== '' ? $displayName : 'user');
    $background = substr($hash, 0, 6);
    $textColor = 'FFFFFF';
    $fontSize = 42;

    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
    <rect width="100" height="100" rx="50" fill="#$background"></rect>
    <text x="50" y="58" font-size="$fontSize" fill="#$textColor" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-weight="600">$initials</text>
</svg>
SVG;

    $avatar = 'data:image/svg+xml;base64,' . base64_encode($svg);
@endphp

<img src="{{ $avatar }}"
    alt="{{ $displayName !== '' ? $displayName : __('User avatar') }}"
    width="{{ (int) $size }}"
    height="{{ (int) $size }}"
    {{ $attributes->merge(['class' => 'rounded-full object-cover']) }} />
