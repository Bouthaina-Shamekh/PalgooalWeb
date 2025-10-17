@props([
    'type' => 'info',
    'dismissible' => false,
])

@php
    $variants = [
        'success' => 'bg-green-100 border border-green-200 text-green-800',
        'error' => 'bg-red-100 border border-red-200 text-red-800',
        'warning' => 'bg-amber-100 border border-amber-200 text-amber-800',
        'info' => 'bg-blue-100 border border-blue-200 text-blue-800',
    ];
    $classes = $variants[$type] ?? $variants['info'];
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg px-4 py-3 text-sm {$classes}"]) }}>
    <div class="flex items-start gap-2">
        <div class="flex-1">
            {{ $slot }}
        </div>
        @if ($dismissible)
            <button type="button" class="text-current/60 hover:text-current font-semibold" data-alert-dismiss>
                &times;
            </button>
        @endif
    </div>
</div>
