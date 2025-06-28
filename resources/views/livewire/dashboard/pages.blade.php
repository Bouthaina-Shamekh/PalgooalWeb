<div>
    @if (session()->has('success'))
        <div class="bg-green-100 p-3 mb-4 rounded text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6 border p-6 rounded mb-10 bg-white shadow">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="text" wire:model="slug" class="p-2 border rounded" placeholder="Slug (مثال: about)">

            <div class="flex gap-4 items-center">
                <label><input type="checkbox" wire:model="is_active"> مفعل</label>
                <label><input type="checkbox" wire:model="is_home"> الصفحة الرئيسية</label>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach ($languages as $lang)
                <div class="p-4 border rounded">
                    <h3 class="font-bold text-lg mb-2">{{ $lang->name }} ({{ $lang->code }})</h3>

                    <input type="text" wire:model="translations.{{ $lang->code }}.title" class="w-full border p-2 mb-2" placeholder="العنوان">

                    <textarea wire:model="translations.{{ $lang->code }}.content" class="w-full border p-2 h-32" placeholder="المحتوى"></textarea>
                </div>
            @endforeach
        </div>

        <div>
            <button class="bg-blue-600 text-white px-4 py-2 rounded">
                {{ $mode === 'edit' ? 'تحديث الصفحة' : 'إنشاء صفحة' }}
            </button>
            @if($mode === 'edit')
                <button type="button" wire:click="resetForm" class="ml-2 text-gray-600 underline">إلغاء التعديل</button>
            @endif
        </div>
    </form>
    

    {{-- قائمة الصفحات --}}
    <h2 class="text-xl font-bold mb-4">قائمة الصفحات</h2>
    <ul class="space-y-3">
        @foreach ($pages as $p)
            <li class="p-3 border flex justify-between items-center">
                <div>
                    <strong>{{ $p->slug }}</strong>
                    @if($p->is_home)
                        <span class="text-sm text-green-600 ml-2">[الرئيسية]</span>
                    @endif
                </div>
                <button wire:click="edit({{ $p->id }})" class="text-blue-600 underline">تعديل</button>
            </li>
        @endforeach
    </ul>
    {{-- إدارة السكشنات للصفحة الرئيسية فقط --}}
    @if ($editingPageId && $is_home)
        <div class="mt-10 border-t pt-10">
            <livewire:dashboard.sections :pageId="$editingPageId" />
        </div>
    @endif

</div>
