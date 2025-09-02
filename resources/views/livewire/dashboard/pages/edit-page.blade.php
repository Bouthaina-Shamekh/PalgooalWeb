<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- [ breadcrumb ] start -->
    <div class="page-header col-span-3">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.languages.index') }}">{{ t('dashboard.All_Pages', 'ALL Pages') }}</a></li>
                <li class="breadcrumb-item" aria-current="page">{{ $mode === 'edit' ? t('dashboard.Edit_Page', 'Edit Page') : t('dashboard.Add_Pages', 'Add Pages') }}</li>
            </ul>

            {{-- العنوان + زر أضف سكشن --}}
            <div class="page-header-title flex items-center justify-between gap-3">
                <h2 class="mb-0">
                    {{ $mode === 'edit' ? t('dashboard.Edit_Page', 'Edit Page') : t('dashboard.Add_Pages', 'Add Pages') }}
                </h2>

                @if ($editingPageId && $mode === 'edit')
                    <button type="button"
                            wire:click="$dispatch('open-sections-palette')"
                            class="bg-primary text-white px-4 py-2 rounded hover:bg-primary/90 transition">
                        + أضف سكشن
                    </button>
                @endif
            </div>
        </div>
    </div>
    <!-- [ breadcrumb ] end -->


    <!-- القسم الأيسر (المحتوى) -->
    <div class="col-span-2">
        <div class="card p-6 space-y-6">
            <h2 class="text-lg font-bold">{{ $mode === 'edit' ? __('Edit Page') : __('Add Page') }}</h2>

            <!-- تبويبات اللغات -->
            <div>
                <ul class="flex border-b mb-4 space-x-2 rtl:space-x-reverse">
                    @foreach($languages as $index => $lang)
                        <li>
                            <button type="button"
                                wire:click="$set('activeLang', '{{ $lang->code }}')"
                                class="px-4 py-2 rounded-t {{ $activeLang === $lang->code ? 'bg-white border-t border-l border-r font-bold' : 'bg-gray-200' }}">
                                {{ $lang->name }}
                            </button>
                        </li>
                    @endforeach
                </ul>

                <!-- الحقول -->
                @foreach($languages as $lang)
                    @if($activeLang === $lang->code)
                        <div>
                            <label class="block mb-1 font-semibold">عنوان الصفحة ({{ $lang->code }})</label>
                            <input type="text" wire:model.defer="translations.{{ $lang->code }}.title"
                                class="w-full border p-2 rounded mb-2" placeholder="Page Title">
                            @error("translations.{$lang->code}.title")
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror

                            <label class="block mb-1 font-semibold">المحتوى ({{ $lang->code }})</label>
                            <textarea wire:model.defer="translations.{{ $lang->code }}.content"
                                class="w-full border p-2 rounded h-40"
                                placeholder="Page Content"></textarea>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <!-- القسم الأيمن (الخصائص) -->
    <div class="space-y-6">
        <div class="card p-4 space-y-4">
            <h3 class="font-semibold">خصائص الصفحة</h3>

            <div>
                <label class="block font-semibold">Slug</label>
                <input type="text" wire:model.defer="slug" class="w-full border p-2 rounded" placeholder="page-slug">
                @error('slug') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block font-semibold">الحالة</label>
                <label class="flex items-center gap-2">
                    <input type="radio" wire:model="is_active" value="0" class="form-radio">
                    <span>Draft</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" wire:model="is_active" value="1" class="form-radio">
                    <span>Published</span>
                </label>
            </div>

            <div>
                <label class="block font-semibold">تاريخ النشر</label>
                <input type="datetime-local" wire:model="published_at" class="w-full border p-2 rounded">
            </div>

            <div class="flex items-center justify-between">
                <span class="font-semibold">Make with Builder:</span>
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-300 rounded-full peer peer-checked:bg-primary relative transition"></div>
                </label>
            </div>

            <button type="button" wire:click="save"
                class="w-full bg-primary text-white py-2 rounded hover:bg-primary/80 transition">
                {{ $mode === 'edit' ? __('Update') : __('Publish') }}
            </button>

            @if ($mode === 'edit')
                <button type="button" wire:click="resetForm"
                    class="w-full bg-gray-200 text-gray-800 py-2 rounded hover:bg-gray-300 transition">
                    {{ __('Cancel Edit') }}
                </button>
            @endif
        </div>

        @if (session()->has('success'))
            <div class="bg-green-100 text-green-800 p-2 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="bg-red-100 text-red-800 p-2 rounded">
                {{ session('error') }}
            </div>
        @endif
    </div>
    {{-- إدارة السكشنات للصفحة الرئيسية فقط --}}
    @if ($editingPageId && $mode === 'edit')
        <div class="mt-10">
            <livewire:dashboard.sections :pageId="$editingPageId" />
        </div>
    @endif
</div>
