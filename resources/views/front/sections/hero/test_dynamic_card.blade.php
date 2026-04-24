@php
    $title = trim((string) ($data['title'] ?? 'Test Dynamic Card'));
@endphp

<section class="px-4 py-8">
    <div class="mx-auto max-w-3xl rounded-lg border border-slate-200 bg-white p-6">
        <h2 class="text-xl font-semibold text-slate-900">{{ $title }}</h2>
    </div>
</section>
