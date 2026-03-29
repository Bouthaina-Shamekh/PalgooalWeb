{{--
Expected content JSON:
{
  "badge": "Optional short badge",
  "subtitle": "Supporting text",
  "primary_button_text": "Start now",
  "primary_button_url": "#",
  "primary_button_new_tab": false
}
--}}
@php
    $title = trim((string) (data_get($content, 'title') ?? $translation->title ?? __('Make your first impression count')));
    $badge = trim((string) data_get($content, 'badge', data_get($content, 'eyebrow', '')));
    $subtitle = trim((string) data_get($content, 'subtitle', ''));
    $buttonText = trim((string) data_get($content, 'primary_button_text', data_get($content, 'primary_button.label', data_get($content, 'button_label', __('Start now')))));
    $buttonUrl = trim((string) data_get($content, 'primary_button_url', data_get($content, 'primary_button.url', data_get($content, 'button_url', '#'))));
    $buttonNewTab = (bool) data_get($content, 'primary_button_new_tab', data_get($content, 'primary_button.new_tab', false));
@endphp

<section id="{{ e((string) data_get($content, 'id', 'cta')) }}" class="bg-slate-950 py-16 sm:py-20">
    <div class="mx-auto max-w-6xl px-6 sm:px-8 lg:px-12">
        <div class="overflow-hidden rounded-[2rem] border border-white/10 bg-gradient-to-br from-[#240B36] via-[#321149] to-slate-950 px-6 py-10 text-white shadow-2xl sm:px-10 sm:py-12">
            <div class="flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-3xl space-y-4 text-start">
                    @if ($badge !== '')
                        <span class="inline-flex w-fit items-center rounded-full border border-white/10 bg-white/10 px-4 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-white/80">
                            {{ $badge }}
                        </span>
                    @endif

                    <h2 class="text-3xl font-semibold tracking-tight text-white sm:text-4xl">
                        {{ $title }}
                    </h2>

                    @if ($subtitle !== '')
                        <p class="max-w-2xl text-base leading-8 text-white/75">
                            {{ $subtitle }}
                        </p>
                    @endif
                </div>

                @if ($buttonText !== '')
                    <div class="shrink-0">
                        <a href="{{ $buttonUrl !== '' ? $buttonUrl : '#' }}"
                            @if ($buttonNewTab) target="_blank" rel="noopener" @endif
                            class="inline-flex items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-[#240B36] shadow-sm transition hover:bg-white/90">
                            {{ $buttonText }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
