@php
    $items = collect(data_get($content, 'items', []))
        ->filter(fn ($item) => is_array($item))
        ->values();

    $eyebrow = data_get($content, 'eyebrow', __('Signature Menu'));
    $description = data_get($content, 'description');
    $note = data_get($content, 'note');
    $variant = (string) ($section->variant ?? '');

    $gridClass = match ($variant) {
        'two-column' => 'grid gap-4 md:grid-cols-2',
        'three-column' => 'grid gap-4 md:grid-cols-3',
        default => 'space-y-4',
    };
@endphp

<section class="rounded-3xl border border-orange-100 bg-white p-8 shadow-sm">
    <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div class="max-w-2xl">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-orange-500">
                {{ $eyebrow }}
            </p>
            <h2 class="mt-3 text-3xl font-extrabold text-gray-900">
                {{ $translation->title ?? __('Chef Selection') }}
            </h2>
            @if (!empty($description))
                <p class="mt-3 text-sm leading-7 text-gray-600">
                    {{ $description }}
                </p>
            @endif
        </div>

        @if (!empty($note))
            <div class="rounded-2xl bg-orange-50 px-4 py-3 text-sm font-medium text-orange-700">
                {{ $note }}
            </div>
        @endif
    </div>

    @if ($items->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-5 py-6 text-sm text-gray-500">
            {{ __('No menu items have been added yet.') }}
        </div>
    @else
        <div class="{{ $gridClass }}">
            @foreach ($items as $item)
                @php
                    $name = data_get($item, 'name', __('Menu Item'));
                    $itemDescription = data_get($item, 'description');
                    $price = data_get($item, 'price');
                @endphp

                <article class="rounded-2xl border border-gray-100 bg-gradient-to-br from-white to-orange-50/40 p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <h3 class="text-lg font-bold text-gray-900">
                                {{ $name }}
                            </h3>

                            @if (!empty($itemDescription))
                                <p class="mt-2 text-sm leading-6 text-gray-600">
                                    {{ $itemDescription }}
                                </p>
                            @endif
                        </div>

                        @if (!empty($price))
                            <span class="shrink-0 rounded-full bg-orange-500 px-3 py-1 text-sm font-bold text-white shadow-sm">
                                {{ $price }}
                            </span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>
