@php
    $missingTemplate = is_array($missingTemplate ?? null) ? $missingTemplate : [];
    $title = trim((string) ($missingTemplate['title'] ?? __('Section renderer not found')));
    $message = trim((string) ($missingTemplate['message'] ?? __('The requested section renderer could not be resolved.')));
    $details = collect(is_array($missingTemplate['details'] ?? null) ? $missingTemplate['details'] : [])
        ->map(static fn ($detail) => trim((string) $detail))
        ->filter()
        ->values();
    $attemptedViews = collect(is_array($missingTemplate['attempted_views'] ?? null) ? $missingTemplate['attempted_views'] : [])
        ->map(static fn ($view) => trim((string) $view))
        ->filter()
        ->values();
@endphp

<section class="px-4 py-10 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-4xl rounded-3xl border border-rose-200 bg-rose-50 px-6 py-8 text-rose-900">
        <h2 class="text-xl font-semibold">{{ $title }}</h2>
        <p class="mt-2 text-sm leading-6">{{ $message }}</p>

        @if ($details->isNotEmpty())
            <div class="mt-4 space-y-2 text-sm text-rose-900">
                @foreach ($details as $detail)
                    <p>{{ $detail }}</p>
                @endforeach
            </div>
        @endif

        <div class="mt-6 space-y-2 text-xs text-rose-800">
            @if (! empty($missingTemplate['template_key']))
                <p><strong>{{ __('Template Key') }}:</strong> <code>{{ $missingTemplate['template_key'] }}</code></p>
            @endif

            @if (! empty($missingTemplate['category']))
                <p><strong>{{ __('Category') }}:</strong> <code>{{ $missingTemplate['category'] }}</code></p>
            @endif

            @if (! empty($missingTemplate['section_key']))
                <p><strong>{{ __('Section Key') }}:</strong> <code>{{ $missingTemplate['section_key'] }}</code></p>
            @endif

            @if (! empty($missingTemplate['resolved_section_type']))
                <p><strong>{{ __('Resolved Section Type') }}:</strong> <code>{{ $missingTemplate['resolved_section_type'] }}</code></p>
            @endif

            @if (! empty($missingTemplate['resolution_source']))
                <p><strong>{{ __('Resolution Source') }}:</strong> <code>{{ $missingTemplate['resolution_source'] }}</code></p>
            @endif

            @if ($attemptedViews->isNotEmpty())
                <p><strong>{{ __('Attempted Views') }}:</strong> {{ $attemptedViews->implode(', ') }}</p>
            @endif
        </div>
    </div>
</section>
