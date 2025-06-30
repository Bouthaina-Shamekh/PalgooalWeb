<ul wire:sortable="reorderChildren" class="flex flex-col gap-4">
    @foreach ($newItem['children'] ?? [] as $index => $child)
        <li wire:sortable.item="{{ $index }}" wire:key="child-{{ $index }}"
            class="bg-white dark:bg-gray-800 rounded shadow p-4 flex items-start gap-4 transition-all duration-200">

            {{-- Drag handle --}}
<span class="cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600 text-xl mt-2 select-none">
    ☰
</span>


            <div class="flex-1 space-y-2">
                {{-- URL field --}}
                <input type="text"
                    wire:model="newItem.children.{{ $index }}.url"
                    placeholder="الرابط"
                    class="form-input w-full px-3 py-2 border rounded" />

                {{-- Translations --}}
                <div class="grid md:grid-cols-3 gap-2">
                    @foreach ($languages as $lang)
                        <input type="text"
                            wire:model="newItem.children.{{ $index }}.label.{{ $lang->code }}"
                            placeholder="الاسم ({{ $lang->native }})"
                            class="form-input w-full px-3 py-2 border rounded" />
                    @endforeach
                </div>

                {{-- Remove button --}}
                <button wire:click.prevent="removeChild({{ $index }})"
                        class="text-red-600 hover:underline text-sm mt-1">
                    حذف
                </button>
            </div>
        </li>
    @endforeach
</ul>

{{-- Add button --}}
<div class="mt-4">
    <button wire:click="addChild"
            class="bg-gray-200 hover:bg-gray-300 text-sm text-gray-700 font-semibold px-4 py-1.5 rounded">
        + إضافة رابط فرعي
    </button>
</div>
