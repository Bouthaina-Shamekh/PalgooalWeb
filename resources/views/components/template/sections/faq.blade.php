@php
    $title = trim((string) ($data['title'] ?? '')) ?: __('Frequently Asked Questions');
    $subtitle = trim((string) ($data['subtitle'] ?? ''));

    $items = collect($data['items'] ?? $data['faq'] ?? [])
        ->map(function ($item) {
            if (!is_array($item)) {
                $question = trim((string) $item);
                return $question === '' ? null : ['question' => $question, 'answer' => ''];
            }

            $question = trim((string) ($item['question'] ?? ''));
            $answer = trim((string) ($item['answer'] ?? ''));

            return ($question === '' && $answer === '') ? null : [
                'question' => $question,
                'answer'   => $answer,
            ];
        })
        ->filter()
        ->values();
@endphp

@once
    <style>
        .faq-accordion summary::-webkit-details-marker {
            display: none;
        }

        .faq-accordion details[open] .faq-toggle-icon {
            transform: rotate(180deg);
        }
    </style>
@endonce

<section class="py-20 px-4 sm:px-8 lg:px-24 bg-white dark:bg-gray-950" dir="auto" aria-labelledby="faq-heading">
    <div class="max-w-3xl mx-auto text-center mb-12">
        <h2 id="faq-heading" class="text-3xl md:text-4xl font-extrabold text-primary mb-4">
            {{ $title }}
        </h2>

        @if ($subtitle !== '')
            <p class="text-base text-gray-600 dark:text-gray-300 leading-relaxed">
                {{ $subtitle }}
            </p>
        @endif
    </div>

    @if ($items->isEmpty())
        <p class="text-center text-sm text-gray-500 dark:text-gray-400 max-w-2xl mx-auto">
            {{ __('FAQ entries will appear here once configured in the dashboard.') }}
        </p>
    @else
        <div class="faq-accordion max-w-3xl mx-auto space-y-4">
            @foreach ($items as $index => $item)
                <details class="border border-gray-200 rounded-lg overflow-hidden shadow-sm bg-white dark:bg-gray-900 transition"
                         @if ($loop->first) open @endif>
                    <summary class="cursor-pointer px-6 py-4  flex justify-between items-center text-primary font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-gray-900">
                        <span class="flex-1">{{ $item['question'] }}</span>
                        <svg class="faq-toggle-icon w-5 h-5 transform transition-transform duration-200 text-secondary"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>

                    @if ($item['answer'] !== '')
                        <div class="px-6 pb-4 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                            {!! nl2br(e($item['answer'])) !!}
                        </div>
                    @endif
                </details>
            @endforeach
        </div>
    @endif
</section>
