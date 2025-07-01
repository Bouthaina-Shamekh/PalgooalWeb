<ul id="sortable-children" class="flex flex-col gap-4">
    @foreach ($newItem['children'] ?? [] as $index => $child)
        <li data-index="{{ $index }}" wire:key="child-{{ $index }}"
            class="child-item bg-white dark:bg-gray-800 rounded shadow p-4 flex items-start gap-4 transition-all duration-200">

            <!-- Drag handle -->
            <span class="cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600 text-xl mt-2 select-none">
                ☰
            </span>

            <div class="flex-1 space-y-2">
                <!-- URL field -->
                <input type="text"
                       wire:model="newItem.children.{{ $index }}.url"
                       placeholder="الرابط (مثال: /about)"
                       class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary text-sm dark:bg-gray-700 dark:text-white" />

                <!-- Translation fields -->
                <div class="grid md:grid-cols-3 gap-2">
                    @foreach ($languages as $lang)
                        <input type="text"
                               wire:model="newItem.children.{{ $index }}.label.{{ $lang->code }}"
                               placeholder="الاسم ({{ $lang->native }})"
                               class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary text-sm dark:bg-gray-700 dark:text-white" />
                    @endforeach
                </div>

                <!-- Delete button -->
                <button wire:click.prevent="removeChild({{ $index }})"
                        class="inline-flex items-center gap-1 text-red-600 hover:text-red-700 hover:underline text-sm transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    حذف
                </button>
            </div>
        </li>
    @endforeach
</ul>


<!-- Add child button -->
<div class="mt-4">
    <button wire:click="addChild"
            class="bg-primary hover:bg-secondary text-white font-semibold px-5 py-2 text-sm rounded shadow transition">
        + إضافة رابط فرعي
    </button>
</div>





