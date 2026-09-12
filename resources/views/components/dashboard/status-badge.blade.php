@props([
    'status' => '',
    'label' => null,
])

@php
    $normalized = strtolower((string) $status);
    $variants = [
        'paid' => 'bg-green-100 text-green-800 ring-green-200',
        'unpaid' => 'bg-amber-100 text-amber-800 ring-amber-200',
        'draft' => 'bg-gray-100 text-gray-700 ring-gray-200',
        'cancelled' => 'bg-red-100 text-red-700 ring-red-200',
    ];
    $classes = $variants[$normalized] ?? 'bg-gray-100 text-gray-700 ring-gray-200';
    // Optional caller-supplied label lets pages that already have a safe,
    // codebase-consistent Arabic status label pass it in directly instead of
    // relying on __() -- this app ships no lang/ files, so __() would just
    // echo the raw status string back. Falls back to previous behavior when
    // no label is given, so any existing usage is unaffected.
    $text = $label ?? __($status);
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold ring-1 ring-inset {$classes}"]) }}>
    {{ $text }}
</span>