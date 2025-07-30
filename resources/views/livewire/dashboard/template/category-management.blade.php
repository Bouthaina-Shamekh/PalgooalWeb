<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- ... Breadcrumb and Messages ... --}}

    <!-- القسم الرئيسي (المحتوى والخصائص معًا) -->
    <div class="col-span-1 lg:col-span-3">
        <form wire:submit.prevent="save" class="card p-6 space-y-6">
            <div class="flex justify-between items-center border-b pb-2">
                <h2 class="text-lg font-bold">{{ __('dashboard.Category_Content') }}</h2>
                <button type="submit" class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 transition-colors text-sm font-semibold">
                    {{ $mode === 'create' ? __('dashboard.Publish_Category') : __('dashboard.Update_Category') }}
                </button>
            </div>
            

            <!-- تبويبات اللغات -->
            <div>
                <ul class="flex border-b mb-4 space-x-2 rtl:space-x-reverse">
                    @foreach($languages as $lang)
                        <li>
                            <button type="button" wire:click="$set('activeLang', '{{ $lang->code }}')"
                                class="px-4 py-2 rounded-t-lg text-sm {{ $activeLang === $lang->code ? 'bg-gray-50 border-t border-l border-r font-semibold text-indigo-600' : 'bg-gray-200 hover:bg-gray-300' }}">
                                {{ $lang->name }}
                            </button>
                        </li>
                    @endforeach
                </ul>

                <!-- الحقول داخل التبويبات -->
                @foreach($languages as $lang)
                    <div wire:key="trans-{{ $lang->code }}" @if($activeLang !== $lang->code) style="display: none;" @endif>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- العمود الأول: الاسم والسلغ --}}
                            <div class="space-y-4">
                                <div>
                                    <label for="name_{{ $lang->code }}" class="block mb-1 font-semibold">{{ __('dashboard.Category_Name') }}</label>
                                    <input type="text" id="name_{{ $lang->code }}" wire:model.live.debounce.300ms="translations.{{ $lang->code }}.name"
                                        class="w-full border p-2 rounded-md" placeholder="{{ __('dashboard.Category_Name') }}">
                                    @error("translations.{$lang->code}.name") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="slug_{{ $lang->code }}" class="block mb-1 font-semibold">{{ __('dashboard.Slug') }}</label>
                                    <input type="text" id="slug_{{ $lang->code }}" wire:model="translations.{{ $lang->code }}.slug" wire:keydown="slugModified('{{ $lang->code }}')"
                                        class="w-full border p-2 rounded-md" placeholder="category-slug">
                                    @error("translations.{$lang->code}.slug") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            {{-- العمود الثاني: الوصف --}}
                            <div>
                                <label for="desc_{{ $lang->code }}" class="block mb-1 font-semibold">{{ __('dashboard.Description') }}</label>
                                <textarea id="desc_{{ $lang->code }}" wire:model="translations.{{ $lang->code }}.description"
                                    class="w-full border p-2 rounded-md h-32" placeholder="{{ __('dashboard.Description') }}"></textarea>
                                @error("translations.{$lang->code}.description") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </form>
    </div>
    
    {{-- يمكنك إضافة جدول عرض الفئات هنا --}}
</div>
