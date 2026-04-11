<div class="{{ $field['wrapperClass'] }}">
    <div class="flex items-center gap-2">
        <label for="{{ $field['id'] }}" class="block text-sm font-medium text-slate-700">
            {{ $field['label'] }}
        </label>
        @if (! $field['isTranslatable'])
            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                {{ __('Shared') }}
            </span>
        @endif
        @if ($field['isRichText'])
            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700">
                {{ __('Rich Text') }}
            </span>
        @endif
    </div>

    <textarea
        id="{{ $field['id'] }}"
        name="{{ $field['name'] }}"
        rows="{{ $field['rows'] }}"
        @if ($field['placeholder']) placeholder="{{ $field['placeholder'] }}" @endif
        @if ($field['isRequired']) required @endif
        @if ($field['isRichText']) data-dynamic-richtext="pending" @endif
        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
    >{{ is_scalar($field['value']) ? (string) $field['value'] : '' }}</textarea>

    @if (filled($field['helpText']))
        <p class="mt-2 text-xs text-slate-500">{{ $field['helpText'] }}</p>
    @elseif ($field['isRichText'])
        <p class="mt-2 text-xs text-slate-500">
            {{ __('Rich text fields currently use the shared textarea control until a dedicated editor integration is added.') }}
        </p>
    @endif
</div>
