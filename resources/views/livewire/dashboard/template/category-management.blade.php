
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.category') }}">{{ t('dashboard.Template_Categories', 'Template Categories') }}</a></li>
                {{-- <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.All_Pages', 'ALL Pages') }}</li> --}}
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Template_Categories', 'Template Categories') }}</h2>
            </div>
        </div>
    </div>
    <!-- [ breadcrumb ] end -->
    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-2 rounded shadow-sm">
            {{ session('success') }}
        </div>
    @endif
    
    <!-- [ Main Content ] start -->
    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12 lg:col-span-6">
            <div class="card">
                <div class="card-header">
                    <h5>{{ $mode === 'edit' ? t('dashboard.Edit_Category', 'Edit Category') : t('dashboard.Add_New_Category', 'Add New Category') }}</h5>
                </div>
                <div class="card-body">
                    <!-- تبويبات اللغات -->
                    <ul class="flex mb-4 border-b space-x-2 rtl:space-x-reverse">
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
                            <div class="space-y-4">
                                <div class="mb-3">
                                    <label class="form-label">{{ t('dashboard.Category_Name', 'Category Name') }}</label>
                                    <input type="text" wire:model="translations.{{ $lang->code }}.name" class="form-control" placeholder="{{ t('dashboard.Category_Name', 'Category Name') }}">
                                    @error("translations.{$lang->code}.name") <small class="text-red-500 form-text">{{ $message }}</small> @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ t('dashboard.Slug', 'Slug') }}</label>
                                    <input type="text" wire:model="translations.{{ $lang->code }}.slug" class="form-control" placeholder="{{ t('dashboard.Slug', 'Slug') }}">
                                    @error("translations.{$lang->code}.slug") <small class="text-red-500 form-text">{{ $message }}</small> @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ t('dashboard.Description', 'Description') }}</label>
                                    <textarea wire:model="translations.{{ $lang->code }}.description" class="form-control" rows="4" placeholder="{{ t('dashboard.Description', 'Description') }}"></textarea>
                                    @error("translations.{$lang->code}.description") <small class="text-red-500 form-text">{{ $message }}</small> @enderror
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <div class="mt-4 text-left">
                        <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn btn-primary">
                            <span wire:loading.remove wire:target="save">
                            {{ $mode === 'edit' ? t('dashboard.Save_Changes', 'Save Changes') : t('dashboard.Add_Category', 'Add Category') }}
                            </span>
                            <span wire:loading wire:target="save">
                                {{ t('dashboard.Saving', 'Saving...') }}
                            </span>
                        </button>
                        @if($mode === 'edit')
                            <button type="button" wire:click="resetForm" class="btn btn-secondary">
                                {{ t('dashboard.Cancel', 'Cancel') }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <!-- [ form-element ] end -->
        <!-- [ list-element ] start -->
        <div class="col-span-12 lg:col-span-5">
            <div class="card">
                <div class="card-header">
                    <h5>{{ t('dashboard.Current_Categories', 'Current Categories') }}</h5>
                </div>
                <div class="card-body">
                    <ul class="space-y-3">
                        @forelse ($categories as $category)
                            <li wire:key="cat-{{ $category->id }}" class="flex items-center justify-between p-3 bg-gray-100 rounded-lg">
                                <div>
                                    <strong class="text-gray-800">{{ $category->translation?->name ?? $category->getTranslation('en')?->name }}</strong>
                                    <p class="text-sm text-gray-500">{{ $category->getTranslation(app()->getLocale())?->slug ?? $category->getTranslation('en')?->slug }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button wire:click="edit({{ $category->id }})" class="w-8 h-8 inline-flex items-center justify-center text-yellow-600 rounded-xl hover:bg-yellow-100">
                                        <i class="ti ti-edit text-xl leading-none"></i>
                                    </button>
                                    <button
                                    onclick="confirm('هل أنت متأكد من رغبتك في الحذف؟') || event.stopImmediatePropagation()"
                                    wire:click="delete({{ $category->id }})"
                                    class="w-8 h-8 inline-flex items-center justify-center text-red-600 rounded-xl hover:bg-red-100">
                                        <i class="ti ti-trash text-xl"></i>
                                    </button>
                                </div>
                            </li>
                            @empty
                            <p class="text-center text-gray-500">{{ t('dashboard.No_categories_found', 'No categories found') }}</p>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        <!-- [ list-element ] end -->
    </div>
</div>
