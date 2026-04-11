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
    </div>

    <input
        id="{{ $field['id'] }}"
        type="url"
        name="{{ $field['name'] }}"
        value="{{ is_scalar($field['value']) ? (string) $field['value'] : '' }}"
        @if ($field['placeholder']) placeholder="{{ $field['placeholder'] }}" @endif
        @if ($field['isRequired']) required @endif
        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
    >

    @if (filled($field['helpText']))
        <p class="mt-2 text-xs text-slate-500">{{ $field['helpText'] }}</p>
    @endif
</div>
