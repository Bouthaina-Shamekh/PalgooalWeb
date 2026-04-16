{{--
    Repeater item schema editor.

    Expected variables:
      $repeaterItemSchema          — array<int, {key, label, type, required, translatable}>
      $repeaterSubFieldTypeOptions — array<string, string>  (type => label)

    Phase 5B: schema authoring only.
    The dynamic section editor rendering for repeater fields is deferred to Phase 5C.
--}}
@php
    $subTypeOptions = $repeaterSubFieldTypeOptions ?? [];
    $existingRows   = $repeaterItemSchema ?? [];
@endphp

<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-slate-200 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                <th class="pb-2 pr-3">{{ __('Key') }}</th>
                <th class="pb-2 pr-3">{{ __('Label') }}</th>
                <th class="pb-2 pr-3">{{ __('Type') }}</th>
                <th class="pb-2 pr-3 text-center">{{ __('Required') }}</th>
                <th class="pb-2 pr-3 text-center">{{ __('Translatable') }}</th>
                <th class="pb-2"></th>
            </tr>
        </thead>
        <tbody id="repeater-schema-tbody">
            @foreach ($existingRows as $rowIndex => $row)
                <tr class="repeater-schema-row border-b border-slate-100 align-top">
                    <td class="py-2 pr-3">
                        <input
                            type="text"
                            name="item_schema[{{ $rowIndex }}][key]"
                            class="form-control form-control-sm"
                            value="{{ $row['key'] }}"
                            placeholder="field_key"
                            pattern="[a-z0-9_]+"
                            title="{{ __('Lowercase letters, numbers, and underscores only.') }}"
                        >
                    </td>
                    <td class="py-2 pr-3">
                        <input
                            type="text"
                            name="item_schema[{{ $rowIndex }}][label]"
                            class="form-control form-control-sm"
                            value="{{ $row['label'] }}"
                            placeholder="{{ __('Label') }}"
                        >
                    </td>
                    <td class="py-2 pr-3">
                        <select name="item_schema[{{ $rowIndex }}][type]" class="form-control form-control-sm">
                            @foreach ($subTypeOptions as $subTypeValue => $subTypeLabel)
                                <option value="{{ $subTypeValue }}" @selected(($row['type'] ?? 'text') === $subTypeValue)>
                                    {{ $subTypeLabel }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td class="py-2 pr-3 text-center">
                        <input type="hidden" name="item_schema[{{ $rowIndex }}][required]" value="0">
                        <input
                            type="checkbox"
                            name="item_schema[{{ $rowIndex }}][required]"
                            value="1"
                            class="form-checkbox"
                            @checked($row['required'] ?? false)
                        >
                    </td>
                    <td class="py-2 pr-3 text-center">
                        <input type="hidden" name="item_schema[{{ $rowIndex }}][translatable]" value="0">
                        <input
                            type="checkbox"
                            name="item_schema[{{ $rowIndex }}][translatable]"
                            value="1"
                            class="form-checkbox"
                            @checked($row['translatable'] ?? true)
                        >
                    </td>
                    <td class="py-2">
                        <button
                            type="button"
                            class="repeater-remove-row rounded px-2 py-1 text-xs text-red-500 hover:bg-red-50"
                            title="{{ __('Remove sub-field') }}"
                        >&times;</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-3">
    <button
        type="button"
        id="repeater-add-row"
        class="btn btn-light btn-sm text-sm"
    >
        + {{ __('Add Sub-field') }}
    </button>
</div>

@php
    $hasSchemaErrors = collect($errors->keys())
        ->contains(fn (string $key) => str_starts_with($key, 'item_schema'));
@endphp
@if ($hasSchemaErrors)
    <div class="mt-2 text-sm text-red-600">
        {{ __('Some repeater sub-field entries are invalid. Please review each row (key and type are required).') }}
    </div>
@endif
