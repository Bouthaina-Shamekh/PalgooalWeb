@php
    $checked = filter_var($field['value'], FILTER_VALIDATE_BOOLEAN);
@endphp

<div class="{{ $field['wrapperClass'] }}">
    <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
        <input type="hidden" name="{{ $field['name'] }}" value="0">
        <input
            id="{{ $field['id'] }}"
            type="checkbox"
            name="{{ $field['name'] }}"
            value="1"
            class="rounded border-slate-300"
            @checked($checked)
        >
        <span>
            <span class="block">{{ $field['label'] }}</span>
            @if (! $field['isTranslatable'])
                <span class="block text-xs font-normal text-slate-500">{{ __('Shared across locales') }}</span>
            @elseif (filled($field['helpText']))
                <span class="block text-xs font-normal text-slate-500">{{ $field['helpText'] }}</span>
            @endif
        </span>
    </label>

    @if ($field['isTranslatable'] && filled($field['helpText']))
        <p class="mt-2 text-xs text-slate-500">{{ $field['helpText'] }}</p>
    @endif
</div>
