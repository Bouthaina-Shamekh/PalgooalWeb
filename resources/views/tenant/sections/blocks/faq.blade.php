{{--
Expected content JSON:
{
  "subtitle": "Optional intro text",
  "items": [
    { "question": "Question?", "answer": "Answer text" }
  ]
}
--}}
@php
    $title = trim((string) (data_get($content, 'title') ?? $translation->title ?? __('Questions before you launch?')));
    $subtitle = trim((string) data_get($content, 'subtitle', ''));
    $items = collect(data_get($content, 'items', data_get($content, 'faq', [])))
        ->map(function ($item) {
            if (! is_array($item)) {
                $question = trim((string) $item);

                return $question === '' ? null : [
                    'question' => $question,
                    'answer' => '',
                ];
            }

            $question = trim((string) ($item['question'] ?? ''));
            $answer = trim((string) ($item['answer'] ?? ''));

            return ($question === '' && $answer === '') ? null : [
                'question' => $question,
                'answer' => $answer,
            ];
        })
        ->filter()
        ->values();
@endphp

<section id="{{ e((string) data_get($content, 'id', 'faq')) }}" class="bg-white py-16 sm:py-20">
    <div class="mx-auto max-w-5xl px-6 sm:px-8 lg:px-12">
        <div class="max-w-3xl space-y-4 text-start">
            <span class="inline-flex w-fit items-center rounded-full border border-[#240B36]/10 bg-[#240B36]/5 px-4 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-[#240B36]/70">
                {{ __('FAQ') }}
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
                {{ __('FAQ entries will appear here once configured.') }}
            </div>
        @else
            <div class="mt-10 space-y-4">
                @foreach ($items as $item)
                    <details class="group rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-5 shadow-sm">
                        <summary class="flex cursor-pointer list-none items-start justify-between gap-4 text-start text-base font-semibold text-slate-950 rtl:flex-row-reverse">
                            <span>{{ $item['question'] }}</span>
                            <span class="mt-1 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition group-open:rotate-45 group-open:text-[#240B36]">
                                +
                            </span>
                        </summary>

                        @if ($item['answer'] !== '')
                            <div class="pt-4 text-sm leading-7 text-slate-600">
                                {!! nl2br(e($item['answer'])) !!}
                            </div>
                        @endif
                    </details>
                @endforeach
            </div>
        @endif
    </div>
</section>
