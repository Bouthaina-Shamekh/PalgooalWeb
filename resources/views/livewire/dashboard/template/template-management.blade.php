<div class="space-y-6">
    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">{{ __('dashboard.Home') }}</a></li>
                <li class="breadcrumb-item" aria-current="page">{{ __('dashboard.Templates_Management') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ __('dashboard.Templates_Management') }}</h2>
            </div>
        </div>
    </div>
    <!-- [ breadcrumb ] end -->

    @if (session()->has('success'))
        <div class="px-4 py-2 text-green-800 bg-green-100 border border-green-300 rounded shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- [ Main Content ] start -->
    <div class="grid grid-cols-12 gap-6">
        <!-- [ form-element ] start -->
        <div class="col-span-12 lg:col-span-7">
            <form wire:submit.prevent="save" class="card">
                <div class="card-header">
                    <h5>{{ $mode === 'edit' ? __('dashboard.Edit_Template') : __('dashboard.Add_New_Template') }}</h5>
                </div>
                <div class="card-body">
                    {{-- القسم الأول: البيانات الأساسية --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div class="mb-3">
                                <label for="category" class="form-label">{{ __('dashboard.Category') }}</label>
                                <select id="category" wire:model="category_template_id" class="form-select">
                                    <option value="">{{ __('dashboard.Select_Category') }}</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->translation?->name ?? $category->getTranslation('en')?->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_template_id') <small class="text-red-500 form-text">{{ $message }}</small> @enderror
                            </div>
                            <div class="mb-3">
                                <label for="price" class="form-label">{{ __('dashboard.Price') }}</label>
                                <input type="number" step="0.01" id="price" wire:model="price" class="form-control" placeholder="e.g., 99.99">
                                @error('price') <small class="text-red-500 form-text">{{ $message }}</small> @enderror
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="form-label">{{ __('dashboard.Template_Image') }}</label>
                            <div class="flex items-center gap-4">
                                @if ($image)
                                    <img src="{{ $image->temporaryUrl() }}" class="object-cover w-24 h-24 rounded-lg">
                                @elseif($existing_image_url)
                                    <img src="{{ asset('storage/' . $existing_image_url) }}" class="object-cover w-24 h-24 rounded-lg">
                                @endif
                                <input type="file" wire:model="image" class="form-control">
                            </div>
                            <div wire:loading wire:target="image" class="mt-1 text-sm text-blue-500">{{ __('dashboard.Uploading') }}...</div>
                            @error('image') <small class="text-red-500 form-text">{{ $message }}</small> @enderror
                        </div>
                    </div>

                    <hr class="my-6">

                    {{-- القسم الثاني: تفاصيل العرض --}}
                    <h6 class="mb-4 text-lg font-semibold">{{ __('dashboard.Offer_Details') }}</h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="mb-3">
                            <label for="discount_price" class="form-label">{{ __('dashboard.Discount_Price') }}</label>
                            <input type="number" step="0.01" id="discount_price" wire:model="discount_price" class="form-control" placeholder="e.g., 49.99">
                            @error('discount_price') <small class="text-red-500 form-text">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="discount_ends_at" class="form-label">{{ __('dashboard.Discount_Ends_At') }}</label>
                            <input type="datetime-local" id="discount_ends_at" wire:model="discount_ends_at" class="form-control">
                            @error('discount_ends_at') <small class="text-red-500 form-text">{{ $message }}</small> @enderror
                        </div>
                    </div>
                    {{-- حقل رابط المعاينة تم حذفه من هنا --}}

                    <hr class="my-6">

                    {{-- القسم الثالث: الترجمات --}}
                    <h6 class="mb-4 text-lg font-semibold">{{ __('dashboard.Translations') }}</h6>
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
                    @foreach($languages as $lang)
                        <div wire:key="trans-{{ $lang->code }}" @if($activeLang !== $lang->code) style="display: none;" @endif>
                            <div class="space-y-4">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('dashboard.Template_Name') }}</label>
                                    <input type="text" wire:model="translations.{{ $lang->code }}.name" class="form-control">
                                    @error("translations.{$lang->code}.name") <small class="text-red-500 form-text">{{ $message }}</small> @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('dashboard.Slug_for_language') }} ({{ $lang->name }})</label>
                                    <input type="text" wire:model="translations.{{ $lang->code }}.slug" class="form-control">
                                    @error("translations.{$lang->code}.slug") <small class="text-red-500 form-text">{{ $message }}</small> @enderror
                                </div>
                                {{-- --- هذا هو الحقل الذي تم نقله --- --}}
                                <div class="mb-3">
                                    <label class="form-label">{{ __('dashboard.Preview_URL_for_language') }} ({{ $lang->name }})</label>
                                    <input type="url" wire:model="translations.{{ $lang->code }}.preview_url" class="form-control" placeholder="https://example.com/template-preview-{{$lang->code}}">
                                    @error("translations.{$lang->code}.preview_url" ) <small class="text-red-500 form-text">{{ $message }}</small> @enderror
                                </div>
                                {{-- --------------------------------- --}}
                                <div class="mb-3">
                                    <label class="form-label">{{ __('dashboard.Description') }}</label>
                                    <textarea wire:model="translations.{{ $lang->code }}.description" class="form-control" rows="4"></textarea>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="p-4 mt-4 text-left bg-gray-50 card-footer">
                    <button type="submit" wire:loading.attr="disabled" class="btn btn-primary">
                        <span wire:loading.remove wire:target="save">{{ $mode === 'edit' ? __('dashboard.Save_Changes') : __('dashboard.Add_Template') }}</span>
                        <span wire:loading wire:target="save">{{ __('dashboard.Saving') }}...</span>
                    </button>
                    @if($mode === 'edit')
                        <button type="button" wire:click="resetForm" class="btn btn-secondary">{{ __('dashboard.Cancel') }}</button>
                    @endif
                </div>
            </form>
        </div>
        <!-- [ form-element ] end -->

        <!-- [ list-element ] start -->
        <div class="col-span-12 lg:col-span-5">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('dashboard.Current_Templates') }}</h5>
                </div>
                <div class="card-body">
                    <ul class="space-y-3">
                        @forelse ($templates as $template)
                            <li wire:key="tpl-{{ $template->id }}" class="flex items-start justify-between p-3 bg-gray-100 rounded-lg">
                                <div class="flex items-center gap-4">
                                    <img src="{{ asset('storage/' . $template->image) }}" class="object-cover w-16 h-16 rounded-md">
                                    <div>
                                        <strong class="text-gray-800">{{ $template->translation?->name ?? $template->getTranslation('en')?->name }}</strong>
                                        <p class="text-sm text-gray-600">{{ $template->categoryTemplate->translation?->name ?? 'Uncategorized' }}</p>
                                        <div class="flex items-baseline gap-2 mt-1">
                                            @if($template->discount_price && ($template->discount_ends_at ? $template->discount_ends_at->isFuture() : true))
                                                <p class="text-sm font-bold text-red-600">${{ number_format($template->discount_price, 2) }}</p>
                                                <p class="text-xs text-gray-500 line-through">${{ number_format($template->price, 2) }}</p>
                                            @else
                                                <p class="text-sm font-bold text-blue-600">${{ number_format($template->price, 2) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button wire:click="edit({{ $template->id }})" class="w-8 h-8 inline-flex items-center justify-center text-yellow-600 rounded-xl hover:bg-yellow-100">
                                        <i class="ti ti-edit text-xl leading-none"></i>
                                    </button>
                                    <button onclick="confirm('هل أنت متأكد من الحذف؟') || event.stopImmediatePropagation()" wire:click="confirmDelete({{ $template->id }})" class="w-8 h-8 inline-flex items-center justify-center text-red-600 rounded-xl hover:bg-red-100">
                                        <i class="ti ti-trash text-xl"></i>
                                    </button>
                                </div>
                            </li>
                        @empty
                            <p class="text-center text-gray-500">{{ __('dashboard.No_templates_found') }}</p>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        <!-- [ list-element ] end -->
    </div>
</div>
