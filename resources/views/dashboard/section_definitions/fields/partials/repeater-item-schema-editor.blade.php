@php
    $subTypeOptions = $repeaterSubFieldTypeOptions ?? [];
    $existingRows   = $repeaterItemSchema ?? [];
@endphp

<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-slate-200 text-xs font-medium uppercase tracking-wide text-slate-500">
                <th class="pb-2 pr-3 text-start">{{ t('dashboard.Field_Key', 'المفتاح') }}</th>
                <th class="pb-2 pr-3 text-start">{{ t('dashboard.Field_Label', 'الاسم') }}</th>
                <th class="pb-2 pr-3 text-start">{{ t('dashboard.Field_Type', 'النوع') }}</th>
                <th class="pb-2 pr-3 text-start">{{ t('dashboard.Options', 'الخيارات') }}</th>
                <th class="pb-2 pr-3 text-center">{{ t('dashboard.Required', 'إلزامي') }}</th>
                <th class="pb-2 pr-3 text-center">{{ t('dashboard.Translatable', 'قابل للترجمة') }}</th>
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
                            class="form-control form-control-sm font-mono"
                            dir="ltr"
                            value="{{ $row['key'] }}"
                            placeholder="field_key"
                            pattern="[a-z0-9_]+"
                            title="{{ t('dashboard.Field_Key_Lowercase_Hint', 'حروف صغيرة وأرقام وشرطات سفلية فقط.') }}"
                        >
                    </td>
                    <td class="py-2 pr-3">
                        <input
                            type="text"
                            name="item_schema[{{ $rowIndex }}][label]"
                            class="form-control form-control-sm"
                            value="{{ $row['label'] }}"
                            placeholder="{{ t('dashboard.Field_Label', 'الاسم') }}"
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
                    <td class="py-2 pr-3">
                        <textarea
                            name="item_schema[{{ $rowIndex }}][options]"
                            class="form-control form-control-sm font-mono min-w-48"
                            dir="ltr"
                            rows="3"
                            placeholder="1x1|Normal&#10;2x1|Wide"
                        >{{ $row['options'] ?? '' }}</textarea>
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
                            class="repeater-remove-row w-7 h-7 rounded-lg inline-flex items-center justify-center text-red-400 hover:text-red-600 hover:bg-red-50 transition"
                            title="{{ t('dashboard.Remove_Sub_field', 'حذف الحقل الفرعي') }}"
                        ><i class="ti ti-trash text-sm leading-none"></i></button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-3">
    <button type="button" id="repeater-add-row" class="btn btn-light btn-sm">
        <i class="ti ti-plus me-1"></i>
        {{ t('dashboard.Add_Sub_field', 'إضافة حقل فرعي') }}
    </button>
</div>

@php
    $hasSchemaErrors = collect($errors->keys())
        ->contains(fn (string $key) => str_starts_with($key, 'item_schema'));
@endphp
@if ($hasSchemaErrors)
    <div class="mt-2 text-sm text-red-600">
        {{ t('dashboard.Repeater_Schema_Error', 'الحقول الفرعية للـ Repeater غير صالحة. يجب وجود حقل فرعي واحد على الأقل، ولكل سطر مفتاح صحيح ونوع صالح.') }}
    </div>
@endif
