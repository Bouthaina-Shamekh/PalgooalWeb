<x-dashboard-layout>
    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.languages.index') }}">{{ t('dashboard.Languages', 'Languages') }}</a></li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.Add_languages', 'Add languages') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Add_languages', 'Add languages') }}</h2>
            </div>
        </div>
    </div>
    <!-- [ breadcrumb ] end -->
    <!-- [ Main Content ] start -->
    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ t('dashboard.Add_languages', 'Add languages') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{Route('dashboard.languages.update', $language->id)}}" method="POST" enctype="multipart/form-data" class="grid grid-cols-12 gap-x-6">
                        @csrf
                        @method('PUT')
                        <div class="col-span-12 md:col-span-6">
                            <div class="mb-3">
                                <x-form.input
                                    label="{{ t('dashboard.Language_Name_(English):', 'Language Name (English):') }}"
                                    name="name"
                                    type="text"
                                    value="{{$language->name}}"
                                    placeholder="{{ t('dashboard.Language_Name', 'Language Name') }}"
                                />
                                {{-- @error('domain_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror --}}
                            </div>
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <div class="mb-3">
                                <x-form.input
                                    label="{{ t('dashboard.Native_Name:', 'Native Name:') }}"
                                    name="native"
                                    type="text"
                                    value="{{$language->native}}"
                                    placeholder="{{ t('dashboard.Native_Name', 'Native Name') }}"
                                />
                                {{-- @error('domain_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror --}}
                            </div>
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <div class="mb-3">
                                <x-form.input
                                    label="{{ t('dashboard.Language_Code', 'Language Code (ex: en, ar, fr):') }}"
                                    name="code"
                                    type="text"
                                    value="{{$language->code}}"
                                    placeholder="{{ t('dashboard.Language_Code', 'Language Code (ex: en, ar, fr):') }}"
                                />
                                {{-- @error('domain_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror --}}
                            </div>
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <div class="mb-3">
                                <x-form.input
                                    label="{{ t('dashboard.Flag_Image_URL:', 'Flag Image URL (optional):') }}"
                                    name="flag"
                                    type="text"
                                    value="{{$language->flag}}"
                                    placeholder="{{ t('dashboard.Flag_Image', 'Flag Image URL (https://ex.com):') }}"
                                />
                                {{-- @error('domain_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror --}}
                            </div>
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <div class="mb-3">
                                <div class="form-check form-switch switch-lg">
                                    <input type="checkbox" name="is_rtl" value="{{$language->is_rtl}}"  class="form-check-input checked:!bg-success-500 checked:!border-success-500 text-lg" >
                                    <label class="form-check-label" for="customswitch1">{{ t('dashboard.RTL:Yes_(language written from right to left):', 'RTL:Yes (language written from right to left)') }}</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <div class="mb-3">
                                <div class="form-check form-switch switch-lg">
                                    <input type="checkbox" name="is_active" value="{{$language->is_active}}" class="form-check-input checked:!bg-success-500 checked:!border-success-500 text-lg" >
                                    @if($language->is_active == 1 )
                                        <label class="form-check-label" for="customswitch1">{{ t('dashboard.Status:Active', 'Status:Active') }}</label>
                                    @else
                                       <label class="form-check-label" for="customswitch1">{{ t('dashboard.Status:Not_Active', 'Status:Not Active') }}</label>
                                    @endif              
                                </div>
                            </div>
                        </div>
                        <div class="col-span-12 text-right">
                            <button type="button" class="btn btn-secondary">{{ t('dashboard.Cancel', 'Cancel') }}</button>
                            <button type="submit" class="btn btn-primary">{{ t('dashboard.Add', 'Add') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->
</x-dashboard-layout>