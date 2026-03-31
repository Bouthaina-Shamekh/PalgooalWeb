{{--
Expected content JSON:
{
  "subtitle": "Section introduction",
  "items": [
    { "icon": "01", "title": "Feature title", "description": "Short description" }
  ]
}
--}}
@php
    $title = trim((string) (data_get($content, 'title') ?? $translation->title ?? __('Everything your landing page needs')));
    $subtitle = trim((string) data_get($content, 'subtitle', ''));
    $items = collect(data_get($content, 'items', data_get($content, 'features', [])))
        ->map(function ($item) {
            if (is_string($item)) {
                return [
                    'icon' => '',
                    'title' => trim($item),
                    'description' => '',
                ];
            }

            if (! is_array($item)) {
                return null;
            }

            return [
                'icon' => trim((string) ($item['icon'] ?? '')),
                'title' => trim((string) ($item['title'] ?? '')),
                'description' => trim((string) ($item['description'] ?? '')),
            ];
        })
        ->filter(fn ($item) => is_array($item) && ($item['title'] !== '' || $item['description'] !== ''))
        ->values();
@endphp

<section id="{{ e((string) data_get($content, 'id', 'features')) }}" class="bg-white py-16 sm:py-20">
    <div class="mx-auto max-w-7xl px-6 sm:px-8 lg:px-12">
        <div class="max-w-3xl space-y-4 text-start">
            <span class="inline-flex w-fit items-center rounded-full border border-[#240B36]/10 bg-[#240B36]/5 px-4 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-[#240B36]/70">
                {{ __('Features') }}
            </span>

            <h2 class="text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
                {{ $title }}
            </h2>

            @if ($subtitle !== '')
                <p class="max-w-2xl text-base leading-8 text-slate-600">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        @if ($items->isEmpty())
            <div class="mt-10 rounded-[1.75rem] border border-dashed border-slate-200 bg-slate-50 px-6 py-8 text-sm text-slate-500">
                {{ __('No features have been added yet.') }}
            </div>
        @else
            <div class="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($items as $item)
                    <article class="rounded-[1.75rem] border border-slate-200 bg-slate-50/70 p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                        <div class="flex items-center justify-between gap-4 rtl:flex-row-reverse">
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-[#240B36] text-sm font-semibold text-white">
                                {{ $item['icon'] !== '' ? $item['icon'] : str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                            </span>
                            <span class="text-xs uppercase tracking-[0.24em] text-slate-400">{{ __('Benefit') }}</span>
                        </div>

                        <div class="mt-6 space-y-3 text-start">
                            <h3 class="text-lg font-semibold text-slate-950">
                                {{ $item['title'] !== '' ? $item['title'] : __('Feature title') }}
                            </h3>
                            <p class="text-sm leading-7 text-slate-600">
                                {{ $item['description'] !== '' ? $item['description'] : __('Add a short description that explains the customer benefit.') }}
                            </p>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>
