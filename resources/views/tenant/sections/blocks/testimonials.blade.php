{{--
Expected content JSON:
{
  "eyebrow": "Testimonials",
  "description": "Optional intro text",
  "items": [
    { "name": "Amina", "role": "Founder", "text": "Short quote", "rating": 5 }
  ]
}
--}}
@php
    $eyebrow = trim((string) data_get($content, 'eyebrow', __('Testimonials')));
    $title = trim((string) (data_get($content, 'title') ?? $translation->title ?? __('Proof that your message is landing')));
    $description = trim((string) data_get($content, 'description', data_get($content, 'subtitle', '')));
    $items = collect(data_get($content, 'items', []))
        ->filter(fn ($item) => is_array($item))
        ->map(function (array $item) {
            return [
                'name' => trim((string) ($item['name'] ?? '')),
                'role' => trim((string) ($item['role'] ?? '')),
                'text' => trim((string) ($item['text'] ?? '')),
                'rating' => max(0, min(5, (int) ($item['rating'] ?? 5))),
            ];
        })
        ->filter(fn (array $item) => $item['name'] !== '' || $item['text'] !== '')
        ->values();
@endphp

<section id="{{ e((string) data_get($content, 'id', 'testimonials')) }}" class="bg-slate-50 py-16 sm:py-20">
    <div class="mx-auto max-w-7xl px-6 sm:px-8 lg:px-12">
        <div class="max-w-3xl space-y-4 text-start">
            <span class="inline-flex w-fit items-center rounded-full border border-[#240B36]/10 bg-white px-4 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-[#240B36]/70">
                {{ $eyebrow }}
            </span>

            <h2 class="text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
                {{ $title }}
            </h2>

            @if ($description !== '')
                <p class="max-w-2xl text-base leading-8 text-slate-600">
                    {{ $description }}
                </p>
            @endif
        </div>

        @if ($items->isEmpty())
            <div class="mt-10 rounded-[1.75rem] border border-dashed border-slate-200 bg-white px-6 py-8 text-sm text-slate-500">
                {{ __('No testimonials have been added yet.') }}
            </div>
        @else
            <div class="mt-10 grid gap-5 lg:grid-cols-3">
                @foreach ($items as $item)
                    <article class="flex h-full flex-col rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center gap-1 text-amber-400 rtl:flex-row-reverse">
                            @for ($i = 0; $i < $item['rating']; $i++)
                                <span aria-hidden="true">&#9733;</span>
                            @endfor
                        </div>

                        <p class="mt-5 flex-1 text-sm leading-7 text-slate-700">
                            {{ $item['text'] !== '' ? '"' . $item['text'] . '"' : __('"Add a short quote here."') }}
                        </p>

                        <div class="mt-6 border-t border-slate-100 pt-4 text-start">
                            <p class="text-sm font-semibold text-slate-950">
                                {{ $item['name'] !== '' ? $item['name'] : __('Customer') }}
                            </p>

                            @if ($item['role'] !== '')
                                <p class="mt-1 text-xs uppercase tracking-[0.22em] text-slate-400">
                                    {{ $item['role'] }}
                                </p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>
