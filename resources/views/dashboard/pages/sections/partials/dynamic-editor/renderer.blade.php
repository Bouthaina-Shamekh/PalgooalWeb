@php
    $dynamicLocaleEditor = $dynamicEditor['locales'][$code] ?? null;
    $dynamicGroups = is_array($dynamicLocaleEditor['groups'] ?? null) ? $dynamicLocaleEditor['groups'] : [];
@endphp

<div class="{{ $contentGridClass }}">
    <input type="hidden" name="translations[{{ $code }}][title]" value="{{ $sectionTitleValue }}">

    @forelse ($dynamicGroups as $dynamicGroup)
        <div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/60 p-5">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">
                        {{ $dynamicGroup['label'] }}
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ __('Definition-driven fields rendered from the developer section schema.') }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                @foreach ($dynamicGroup['fields'] as $field)
                    @include($field['partial'], ['field' => $field])

                    @foreach ($field['replicaInputs'] ?? [] as $replicaInput)
                        <input type="hidden" name="{{ $replicaInput['name'] }}" value="{{ $replicaInput['value'] }}">
                    @endforeach
                @endforeach
            </div>
        </div>
    @empty
        <div
            class="lg:col-span-2 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">
            {{ __('No dynamic fields are registered for this locale yet.') }}
        </div>
    @endforelse
</div>
